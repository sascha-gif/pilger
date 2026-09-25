<?php
/**
 * Ausliefern von Fotos und Aufnahmen.
 *
 * Die Dateien liegen im Volume außerhalb des ausgelieferten Verzeichnisses —
 * erreichbar sind sie nur hier, und nur nach Anmeldung. Ein Foto vom Camino
 * soll nicht dadurch öffentlich werden, dass jemand die Adresse errät.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$id  = (int) ($_GET['id'] ?? 0);
$art = (string) ($_GET['art'] ?? 'foto');

/* Zwei Wege herein. Angemeldet: alles. Mit dem langen Journal-Link: **nur
   Bilder und Videos**. Eine Sprachaufnahme ist der Rohton, den er unterwegs
   vor sich hin gesprochen hat — der geht die Familie nichts an, auch wenn sie
   den Link hat. */
$gast = ($art !== 'audio') && journal_gast($db);

if (!may_write() && !$gast) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Nicht angemeldet.');
}

$tagebuch = new Tagebuch($db, $repo);

$istVideo = false;

if ($art === 'audio') {
    $eintrag = $tagebuch->eintrag($id);
    if ($eintrag === null || !$eintrag['audio_file']) {
        http_response_code(404);
        exit;
    }
    $pfad = data_path('audio') . '/' . basename((string) $eintrag['audio_file']);
} else {
    $foto = $tagebuch->foto($id);
    if ($foto === null) {
        http_response_code(404);
        exit;
    }
    $klein = ($art === 'klein' && $foto['thumb']);
    $name  = $klein ? $foto['thumb'] : $foto['file'];

    /* Das Standbild eines Videos ist ein Bild und kein Video — sonst käme es
       als `video/mp4` heraus und stünde im Mosaik als schwarzer Kasten. Es
       liegt deshalb auch bei den Bildern und nicht bei den Videos. */
    $istVideo = !$klein && (($foto['kind'] ?? 'foto') === 'video');
    $pfad = data_path($istVideo ? 'videos' : 'fotos') . '/' . basename((string) $name);
}

if (!is_readable($pfad)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Datei nicht gefunden.');
}

/* `mp4` und `webm` gibt es in beiden Welten. Womit eine Datei ausgeliefert
   wird, entscheidet deshalb nicht die Endung allein, sondern wofür sie
   abgelegt wurde: eine Sprachnotiz ist Audio, ein Tagebuchvideo ist Video.
   Ein Video als `audio/mp4` auszuliefern heißt, dass der Browser nur den Ton
   abspielt. */
$typen = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'heic' => 'image/heic', 'heif' => 'image/heif',
    'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'oga' => 'audio/ogg', 'm4a' => 'audio/mp4',
    'mov' => 'video/quicktime', 'm4v' => 'video/mp4',
];
$doppelt = [
    'mp4'  => ['audio/mp4',  'video/mp4'],
    'webm' => ['audio/webm', 'video/webm'],
    'ogg'  => ['audio/ogg',  'video/ogg'],
];

$endung = strtolower((string) pathinfo($pfad, PATHINFO_EXTENSION));
if (isset($doppelt[$endung])) {
    $typ = $doppelt[$endung][$istVideo ? 1 : 0];
} else {
    $typ = $typen[$endung] ?? 'application/octet-stream';
}

$groesse = (int) filesize($pfad);

header('Content-Type: ' . $typ);
header('Cache-Control: private, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($pfad) . '"');
header('Accept-Ranges: bytes');

/* ---- Bereichsanfragen ---------------------------------------------------
   Bisher stand hier `Accept-Ranges: bytes` und darunter ein `readfile()` —
   die Zusage wurde also gegeben und nie eingelöst. Bei Audio fällt das kaum
   auf: der Browser lädt die Datei eben ganz und spult im Speicher. Ein Video
   auf dem iPhone fängt so aber gar nicht erst an. Safari holt zuerst ein
   kleines Stück vom Anfang, und wer darauf mit der vollen Datei und einer 200
   antwortet, bekommt einen schwarzen Rahmen.

   Mehrteilige Bereiche („bytes=0-99,200-299") kommen von Videoplayern nicht
   vor; darauf mit der ganzen Datei zu antworten ist erlaubt. */
$von = 0;
$bis = $groesse - 1;
$teil = false;

$roh = trim((string) ($_SERVER['HTTP_RANGE'] ?? ''));
if ($roh !== '' && $groesse > 0 && preg_match('~^bytes=(\d*)-(\d*)$~', $roh, $m)) {
    if ($m[1] === '' && $m[2] === '') {
        // „bytes=-" sagt gar nichts.
        http_response_code(416);
        header('Content-Range: bytes */' . $groesse);
        exit;
    }
    if ($m[1] === '') {
        // „bytes=-500" — die letzten 500 Bytes.
        $laenge = (int) $m[2];
        $von = max(0, $groesse - $laenge);
    } else {
        $von = (int) $m[1];
        if ($m[2] !== '') {
            $bis = (int) $m[2];
        }
    }
    $bis = min($bis, $groesse - 1);

    if ($von > $bis || $von >= $groesse) {
        http_response_code(416);
        header('Content-Range: bytes */' . $groesse);
        exit;
    }
    $teil = true;
}

if ($teil) {
    http_response_code(206);
    header('Content-Range: bytes ' . $von . '-' . $bis . '/' . $groesse);
}
header('Content-Length: ' . ($bis - $von + 1));

/* Stückweise ausgeben. Ein `readfile()` zieht die ganze Datei durch den
   Speicher — bei einem Handyvideo sind das schnell dreihundert Megabyte, und
   dann stirbt PHP am `memory_limit` mitten in der Antwort. */
$zeiger = fopen($pfad, 'rb');
if ($zeiger === false) {
    http_response_code(500);
    exit;
}
if ($von > 0) {
    fseek($zeiger, $von);
}

$offen = $bis - $von + 1;
$haeppchen = 256 * 1024;
while ($offen > 0 && !feof($zeiger) && !connection_aborted()) {
    $stueck = fread($zeiger, (int) min($haeppchen, $offen));
    if ($stueck === false || $stueck === '') {
        break;
    }
    echo $stueck;
    $offen -= strlen($stueck);
    flush();
}
fclose($zeiger);
