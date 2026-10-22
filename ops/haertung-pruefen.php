<?php
declare(strict_types=1);

/**
 * Rechnet nach, ob die Härtung aus dem BSI-Advisory wirklich greift.
 *
 * BITS-H 2026-297981-1132 — CVE-2026-109618 und CVE-2026-109619 („PHP2Shell"),
 * CVSS 9.2, Codeausführung aus der Ferne ohne Anmeldung.
 *
 * Läuft **im Container**, nicht daneben:
 *
 *     docker compose exec -T app php < ops/haertung-pruefen.php
 *
 * Das ist Absicht. Eine Einstellung, die in der Datei steht, ist nicht
 * dasselbe wie eine, die greift — und was über die Kommandozeile gilt, muss
 * über Apache noch lange nicht gelten. Deshalb fragt dieses Skript den
 * laufenden Webserver über 127.0.0.1, wo es darauf ankommt.
 *
 * Gibt 0 zurück, wenn alles sitzt, sonst 1 und eine Liste dessen, was fehlt.
 */

$fehler = [];
$ok     = static fn (string $t) => print('  ok   ' . $t . PHP_EOL);
$weg    = static function (string $t) use (&$fehler) {
    $fehler[] = $t;
    print('  FEHLT ' . $t . PHP_EOL);
};

/* ================= 1. Die Fassung ==================================== */

print(PHP_EOL . 'PHP-Fassung' . PHP_EOL);
if (version_compare(PHP_VERSION, '8.3.36', '<')) {
    $weg(sprintf('PHP %s liegt unter 8.3.36 — anfällig für CVE-2026-109618/109619', PHP_VERSION));
} else {
    $ok(sprintf('PHP %s — gepatcht (>= 8.3.36)', PHP_VERSION));
}

/* ================= 2. Die Einstellungen ============================== */

print(PHP_EOL . 'Einstellungen, wie PHP sie wirklich sieht' . PHP_EOL);
/* Werte, die genau stimmen muessen. */
foreach (['memory_limit' => '-1', 'post_max_size' => '32M', 'upload_max_filesize' => '24M'] as $name => $wert) {
    $ist = (string) ini_get($name);
    if ($ist === $wert) {
        $ok(sprintf('%s = %s', $name, $ist));
    } else {
        $weg(sprintf('%s ist "%s", erwartet "%s"', $name, $ist, $wert));
    }
}

/* Schalter, die aus sein muessen. `ini_get()` gibt hier "0" oder "1" zurueck,
   nicht den leeren String — deshalb die Wahrheitspruefung und kein Vergleich. */
foreach (['session.upload_progress.enabled', 'expose_php'] as $name) {
    if (ini_get($name)) {
        $weg(sprintf('%s ist an', $name));
    } else {
        $ok(sprintf('%s = aus', $name));
    }
}

/* Eine ungültige Einstellung löst pro Anfrage eine Diagnosemeldung vor der
   Initialisierung aus — das Advisory verlangt im dritten Punkt ausdrücklich,
   solche zu entfernen. Ein stummer PHP-Start ist der Nachweis, dass keine
   da ist. */
$laut = [];
exec('php -r "exit(0);" 2>&1', $laut, $code);
if ($laut === [] && $code === 0) {
    $ok('PHP startet stumm — keine ungültige Einstellung in den ini-Dateien');
} else {
    $weg('PHP meldet beim Start: ' . implode(' ', $laut));
}

/* ================= 3. Greift php_admin_value wirklich? =============== */

/* Der Punkt, den man sonst glaubt statt prüft. In der ini kann die
   Anwendung jeden Wert per ini_set() zurückdrehen; erst php_admin_value in
   der Apache-Konfiguration nagelt ihn fest. Über die Kommandozeile greift
   die aber gar nicht — also muss die Frage über den Webserver gehen. */

print(PHP_EOL . 'Kann die Anwendung die Werte zurückdrehen?' . PHP_EOL);

/* Den Document-Root suchen statt raten. `__DIR__` hilft hier nicht: das
   Skript kommt ueber stdin in den Container (`php < ops/...`), und dann ist
   `__FILE__` "Standard input code" und `__DIR__` leer. Die Umgebungsvariable
   setzt das Dockerfile, die beiden anderen sind der Rueckfall. */
$docroot = '';
$versucht = array_values(array_filter([
    (string) (getenv('APACHE_DOCUMENT_ROOT') ?: ''),
    '/var/www/html/public',
    (getcwd() ?: '.') . '/public',
]));
foreach ($versucht as $k) {
    if (is_dir($k)) { $docroot = $k; break; }
}
$name    = 'haertungspruefung-' . bin2hex(random_bytes(8)) . '.php';
$pfad    = $docroot . '/' . $name;

$probe = <<<'PROBE'
<?php
@ini_set('memory_limit', '512M');
@ini_set('session.upload_progress.enabled', '1');
header('Content-Type: text/plain');
echo 'memory_limit=', ini_get('memory_limit'), "\n";
echo 'upload_progress=', ini_get('session.upload_progress.enabled') ? 'an' : 'aus', "\n";
PROBE;

if ($docroot === '') {
    $weg('Document-Root nicht gefunden (versucht: ' . implode(', ', $versucht) . ')');
} elseif (@file_put_contents($pfad, $probe) === false) {
    $weg('Probe-Datei liess sich nicht anlegen: ' . $pfad);
} else {
    $antwort = (string) @file_get_contents('http://127.0.0.1/' . $name);
    @unlink($pfad);

    if ($antwort === '') {
        $weg('Der Webserver hat auf die Probe nicht geantwortet');
    } else {
        if (str_contains($antwort, 'memory_limit=-1')) {
            $ok('ini_set("memory_limit") kommt nicht durch — php_admin_value greift');
        } else {
            $weg('ini_set() konnte memory_limit überschreiben: ' . trim($antwort));
        }
        if (str_contains($antwort, 'upload_progress=aus')) {
            $ok('ini_set("session.upload_progress") kommt nicht durch');
        } else {
            $weg('ini_set() konnte session.upload_progress einschalten');
        }
    }
}

/* ================= 4. Die Grenzen am Webserver ======================= */

print(PHP_EOL . 'Apache' . PHP_EOL);

/** Eine Anfrage an den eigenen Webserver, mit dem Statuscode zurück. */
$hole = static function (string $pfad, ?string $rumpf = null): array {
    $opt = ['http' => [
        'method'        => $rumpf === null ? 'GET' : 'POST',
        'ignore_errors' => true,
        'timeout'       => 30,
    ]];
    if ($rumpf !== null) {
        $opt['http']['header']  = "Content-Type: application/json\r\n";
        $opt['http']['content'] = $rumpf;
    }
    $inhalt = @file_get_contents('http://127.0.0.1' . $pfad, false, stream_context_create($opt));
    $kopf   = $http_response_header ?? [];
    $code   = 0;
    if (isset($kopf[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $kopf[0], $t)) {
        $code = (int) $t[1];
    }
    return ['code' => $code, 'kopf' => $kopf, 'inhalt' => (string) $inhalt];
};

/* LimitRequestBody: ein Rumpf über der Grenze muss abprallen, bevor PHP ihn
   anfasst. post_max_size allein reicht dafür nicht — es greift nicht bei
   Anfragen ohne verwertbaren Content-Length. */
$gross = str_repeat('x', 40 * 1024 * 1024);
$a = $hole('/api.php', $gross);
unset($gross);
if ($a['code'] === 413) {
    $ok('40-MB-Rumpf wird mit 413 abgewiesen — LimitRequestBody greift');
} elseif ($a['code'] === 0) {
    $weg('40-MB-Rumpf: keine Antwort vom Webserver — läuft dieses Skript im Container?');
} else {
    $weg(sprintf('40-MB-Rumpf kam mit HTTP %d durch — LimitRequestBody greift nicht', $a['code']));
}

/* Kein X-Powered-By: die genaue PHP-Fassung gehört nicht in jede Antwort. */
$a = $hole('/');
$verraet = array_filter($a['kopf'], static fn ($z) => stripos($z, 'x-powered-by') === 0);
if ($verraet === []) {
    $ok('kein X-Powered-By in den Kopfzeilen');
} else {
    $weg('X-Powered-By verrät die Fassung: ' . implode(' ', $verraet));
}

/* Die Tür. 401 ist hier das erwartete Lebenszeichen. */
if ($a['code'] === 401) {
    $ok('die Seite ist zu (HTTP 401 ohne Anmeldung)');
} elseif ($a['code'] === 200 && !str_contains($a['inhalt'], 'costTotal')) {
    $ok('die Seite zeigt ohne Anmeldung keinen Inhalt');
} elseif ($a['code'] === 0) {
    $weg('keine Antwort vom Webserver — läuft dieses Skript im Container?');
} else {
    $weg(sprintf('die Seite antwortet ohne Anmeldung mit HTTP %d und zeigt Inhalt', $a['code']));
}

/* ================= 5. Was nicht ausgeliefert werden darf ============= */

print(PHP_EOL . 'Dateien ausserhalb von public/' . PHP_EOL);
foreach (['/src/Database.php', '/db/seed.php', '/config/', '/CLAUDE.md', '/docker-compose.yml', '/.git/config'] as $pfad) {
    $a = $hole($pfad);
    if ($a['code'] === 0) {
        $weg(sprintf('%s — keine Antwort vom Webserver, nicht geprüft', $pfad));
    } elseif ($a['code'] >= 300 && $a['code'] < 500) {
        $ok(sprintf('%s nicht ausgeliefert (HTTP %d)', $pfad, $a['code']));
    } else {
        $weg(sprintf('%s ist über den Webserver erreichbar (HTTP %d)', $pfad, $a['code']));
    }
}

/* ================= Ergebnis ========================================== */

print(PHP_EOL);
if ($fehler === []) {
    print('Haertung sitzt: PHP ' . PHP_VERSION . ', Apache und Dateibaum.' . PHP_EOL);
    exit(0);
}
fwrite(STDERR, '::error::' . count($fehler) . ' Punkt(e) nicht in Ordnung: ' . implode('; ', $fehler) . PHP_EOL);
exit(1);
