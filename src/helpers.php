<?php
declare(strict_types=1);

/** HTML-sicher ausgeben. */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Feld, das bewusst Markup enthalten darf (aus dem Seed bzw. der Pflege).
 * Es wird nur eine kleine Whitelist erlaubt — alles andere wird escaped.
 */
function rich(?string $value): string
{
    $allowed = '<b><strong><em><i><br><span><small><a>';
    return strip_tags((string) $value, $allowed);
}

/** Betrag deutsch formatieren, z. B. 1.234,50 €. */
function money(?float $value): string
{
    return number_format((float) $value, 2, ',', '.') . ' €';
}

/** Zahl für ein number-Input ausgeben (Punkt als Dezimaltrenner, leer wenn null). */
function num_attr($value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') ?: '0';
}

/**
 * Abbruch mit lesbarer Meldung statt weißer Seite.
 * Ein PHP-Fehler ohne Ausgabe ist von außen nicht von einem Proxy-Fehler zu
 * unterscheiden — beides sieht im Browser gleich aus und kostet Suchzeit.
 */
function app_fail(string $message, ?Throwable $e = null, bool $debug = false): never
{
    if ($e !== null) {
        error_log('pilger: ' . $message . ' — ' . $e->getMessage());
    }

    $detail = ($debug && $e !== null) ? $e->getMessage() : null;

    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'api.php')) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(
            ['ok' => false, 'error' => $message] + ($detail ? ['detail' => $detail] : []),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Camino-Masterplan — kurz nicht erreichbar</title>'
       . '<style>body{font-family:system-ui,sans-serif;background:#f6f0e2;color:#1d2326;'
       . 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:24px}'
       . 'div{max-width:34rem;border-left:3px solid #f4b400;background:#fbf7ec;padding:22px 26px}'
       . 'h1{font-size:20px;margin:0 0 10px}p{margin:0 0 8px;line-height:1.55}'
       . 'code{font-family:ui-monospace,monospace;font-size:13px;color:#857c6c;word-break:break-all}</style>'
       . '</head><body><div><h1>Kurz nicht erreichbar</h1>'
       . '<p>' . h($message) . '</p>'
       . '<p>Die Seite kommt von selbst wieder, sobald die Datenbank antwortet — '
       . 'eingetragene Beträge und Häkchen sind davon nicht betroffen.</p>'
       . ($detail ? '<p><code>' . h($detail) . '</code></p>' : '')
       . '</div></body></html>';
    exit;
}

/**
 * Diktierter oder getippter Text als echte Absätze.
 *
 * Vorher stand hier `nl2br()` und im CSS `white-space:pre-wrap` — beides
 * zusammen. Jeder Absatzwechsel zählte dadurch doppelt, und zwischen zwei
 * Sätzen klaffte ein halber Bildschirm. Leerzeilen trennen jetzt Absätze,
 * einzelne Umbrüche bleiben Umbrüche.
 */
function absaetze(string $text): string
{
    $roh = preg_split('/\R{2,}/u', trim($text)) ?: [];
    $out = '';
    foreach ($roh as $stueck) {
        $stueck = trim($stueck);
        if ($stueck === '') {
            continue;
        }
        $out .= '<p>' . nl2br(h($stueck)) . '</p>';
    }
    return $out;
}

/**
 * Ein Foto im Tagebuch.
 *
 * Zwei Zustände, und der Unterschied ist der ganze Punkt: **beim Lesen** steht
 * unter dem Bild nur die Unterschrift, sofern es eine gibt — sonst nichts.
 * **Beim Bearbeiten** kommen Eingabefeld und Löschknopf dazu. Vorher war das
 * Löschkreuz unsichtbar, bis die Maus genau über der Kachel stand; wer ein
 * Bild loswerden wollte, fand den Knopf schlicht nicht. Und leere Felder mit
 * „Bildunterschrift" unter jedem Bild sehen für Mitlesende nach Formular aus,
 * nicht nach Reisetagebuch.
 *
 * Was sichtbar ist, entscheidet `data-edit` am Tagebuch — siehe app.css.
 *
 * @param array<string,mixed> $f Zeile aus `photos`
 * @param bool $gross Zeitleiste (größer) oder Eintragskarte (kleiner)
 */
function bild_kachel(array $f, bool $gross): string
{
    $id    = (int) $f['id'];
    $text  = (string) ($f['caption'] ?? '');
    $video = (($f['kind'] ?? 'foto') === 'video');

    /* Ein Video spielt hier an Ort und Stelle. `preload="none"` ist wichtig:
       sonst holt das Handy beim Aufklappen eines Tages gleich alle Videos
       darin an — auf dem Camino ist das Netz knapp und die Daten sind es
       auch. Geladen wird erst, wenn jemand auf Abspielen drückt. */
    if ($video) {
        $poster = $f['thumb'] ? ' poster="media.php?art=klein&amp;id=' . $id . '"' : '';
        $dauer  = $f['dauer'] !== null
            ? '<span class="bk-dauer">' . h(sekunden_kurz((int) $f['dauer'])) . '</span>' : '';
        $inhalt = '<video controls playsinline preload="none"' . $poster
                . ' src="media.php?art=foto&amp;id=' . $id . '"></video>' . $dauer;
    } else {
        $inhalt = '<a href="media.php?art=foto&amp;id=' . $id . '" target="_blank" rel="noopener">'
                . '<img src="media.php?art=klein&amp;id=' . $id . '" alt="' . h($text) . '" loading="lazy">'
                . '</a>';
    }

    return '<figure class="bk' . ($gross ? ' gross' : '') . ($video ? ' istvideo' : '')
        . '" data-foto="' . $id . '">'
        . $inhalt
        . '<button type="button" class="bk-weg" title="' . ($video ? 'Video löschen' : 'Bild löschen')
        . '" aria-label="' . ($video ? 'Video löschen' : 'Bild löschen') . '">×</button>'
        . '<figcaption>'
        . ($text !== '' ? '<span class="bk-schau">' . h($text) . '</span>' : '')
        . '<input type="text" class="bk-text" value="' . h($text) . '"'
        . ' placeholder="Bildunterschrift" maxlength="500">'
        . '</figcaption>'
        . '</figure>';
}

/** 95 Sekunden sind „1:35". */
function sekunden_kurz(int $s): string
{
    $s = max(0, $s);
    return intdiv($s, 60) . ':' . str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT);
}

/**
 * Link auf Google Maps. Bei einer Adresse zeigt er genau dorthin, bei einer
 * Suche öffnet er die Suche im richtigen Ort — das ist bei Albergues das
 * Ehrlichere, weil die zumachen und umziehen.
 */
function maps_link(?string $adresse, ?string $suche): string
{
    $frage = $adresse !== null && $adresse !== '' ? $adresse : (string) $suche;
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($frage);
}

/** JSON-Antwort senden und beenden. */
function json_out(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Wie das Haus zu nennen ist.
 *
 * **Nicht jede Schlafstelle war ein Hotel.** Ein *Residencial* ist ein
 * portugiesisches Gästehaus, ein *Hostal* das spanische Gegenstück, und ein
 * *Albergue* wäre die Pilgerherberge mit Schlafsaal gewesen — die kam nie
 * infrage. Steht die Art nicht fest, wird sie auch nicht behauptet: dann heißt
 * es schlicht „Übernachtung", und die Zimmerart dahinter sagt ohnehin mehr.
 */
function bett_art(array $u): string
{
    $teile = array_filter([
        $u['art'] ?: 'Übernachtung',
        $u['zimmer'] ?: null,
    ]);
    return implode(' · ', $teile);
}

/**
 * Der Kartenlink zur Unterkunft.
 *
 * Gesucht wird mit **Name und Adresse zusammen**. Nur der Name reicht nicht —
 * „Hello Esposende" und „Lemonade Stays" finden ohne Ort halb Europa —, und
 * nur die Adresse setzt den Stift zwar richtig, sagt aber nicht, ob man vor
 * dem richtigen Haus steht. Ist keine Adresse bekannt, tut es der Ort.
 */
function bett_karte(array $u): string
{
    $adresse = bett_adresse($u);
    if ($adresse === '') {
        $adresse = trim((string) $u['ort']);
    }
    $frage = trim(((string) $u['name']) . ($adresse !== '' ? ', ' . $adresse : ''));
    return maps_link($frage, $frage);
}

/**
 * Straße, Postleitzahl, Ort — so viel davon, wie bekannt ist.
 */
function bett_adresse(array $u): string
{
    $zeile2 = trim(((string) $u['plz']) . ' ' . ((string) $u['ort']));
    $teile  = array_filter([trim((string) $u['strasse']), $zeile2]);
    return implode(', ', $teile);
}

/**
 * Die Zeilen unter der Unterkunft: Nächte, Preis, Ankommen, Gehen, Telefon.
 * Was nicht belegt ist, fehlt — es wird nichts ausgedacht, um die Liste
 * vollzukriegen.
 *
 * @return array<int,array{0:string,1:string}>
 */
function bett_zahlen(array $u): array
{
    $zeilen = [];

    if ($u['naechte'] !== null && (int) $u['naechte'] > 0) {
        $n = (int) $u['naechte'];
        $zeilen[] = ['Bleibe', $n === 1 ? 'eine Nacht' : $n . ' Nächte'];
    }
    if ($u['preis'] !== null) {
        $zeilen[] = ['Kosten', number_format((float) $u['preis'], 2, ',', '.') . ' €'];
    }
    if ($u['checkin']) {
        $zeilen[] = ['Ankommen', (string) $u['checkin']];
    }
    if ($u['checkout']) {
        $zeilen[] = ['Gehen', (string) $u['checkout']];
    }
    if ($u['telefon']) {
        $zeilen[] = ['Telefon', (string) $u['telefon']];
    }
    return $zeilen;
}

/**
 * Eine Koordinate, wie sie vom Gerät kommt — oder nichts.
 *
 * Gelesen wird sie im Browser aus dem Bild selbst, und was von dort kommt,
 * wird nicht geglaubt, sondern geprüft: Breite bis 90, Länge bis 180, und
 * genau 0/0 ist keine Position, sondern ein Gerät ohne Empfang. Sechs
 * Nachkommastellen sind rund zehn Zentimeter — mehr zu speichern täuscht eine
 * Genauigkeit vor, die ein Handy-GPS nie hat.
 */
function koordinate(mixed $wert, float $grenze): ?float
{
    if ($wert === null || $wert === '' || !is_scalar($wert)) {
        return null;
    }
    if (!is_numeric($wert)) {
        return null;
    }
    $zahl = (float) $wert;
    if (!is_finite($zahl) || abs($zahl) > $grenze) {
        return null;
    }
    return round($zahl, 6);
}

/**
 * `post_max_size = 72M` als Zahl. Die INI-Kurzschreibweise kennt K, M und G,
 * und `ini_get()` gibt sie unverändert zurück — vergleichen lässt sich damit
 * nichts.
 */
function ini_bytes(string $wert): int
{
    $wert = trim($wert);
    if ($wert === '') {
        return 0;
    }
    $zahl   = (int) $wert;
    $einheit = strtolower(substr($wert, -1));
    return match ($einheit) {
        'g'     => $zahl * 1024 * 1024 * 1024,
        'm'     => $zahl * 1024 * 1024,
        'k'     => $zahl * 1024,
        default => $zahl,
    };
}

/**
 * Die Zeitzone, in der Sascha gerade steht.
 *
 * Die Uhrzeiten auf der Seite sollen die sein, die er am Handgelenk sieht.
 * Portugal geht der deutschen Zeit im Sommer eine Stunde nach — ein Eintrag
 * von 9:04 stand deshalb als „10:04" da. Ab der Grenze am Minho (E5,
 * 23.09.2026) ist es Spanien, und das hat dieselbe Uhr wie Deutschland.
 * Vor und nach der Reise gilt die heimische Zeit.
 *
 * Welcher Tag gerade ist, wird in UTC bestimmt — sonst müsste man die Zone
 * schon kennen, um die Zone zu wählen. Auf den Wechseltag genau geht es
 * damit um höchstens eine Stunde daneben, und beide Zonen liegen ohnehin
 * nur eine Stunde auseinander.
 */
function reise_zeitzone(?string $heute = null): string
{
    $heute ??= gmdate('Y-m-d');

    if ($heute >= '2026-09-17' && $heute <= '2026-09-22') {
        return 'Europe/Lisbon';   // Porto bis Caminha
    }
    if ($heute >= '2026-09-23' && $heute <= '2026-10-01') {
        return 'Europe/Madrid';   // ab dem Minho
    }
    return 'Europe/Berlin';
}

/**
 * Alle Tage, die zu einer Etappe gehören — von `date_from` bis `date_iso`.
 *
 * Fast immer ist das genau ein Tag. Porto sind zwei: Ankunft und Orga-Tag.
 * Ohne diese Liste zeigt die Etappenkarte nur die Zahlen des letzten Tages,
 * und der Anreisetag fällt unter den Tisch.
 *
 * @return array<int,string> aufsteigend, JJJJ-MM-TT
 */
function etappen_tage(array $stage): array
{
    $bis = (string) ($stage['date_iso'] ?? '');
    if ($bis === '') {
        return [];
    }
    $von = (string) ($stage['date_from'] ?? '');
    if ($von === '' || $von > $bis) {
        $von = $bis;
    }

    $tage = [];
    for ($t = $von; $t <= $bis; $t = date('Y-m-d', strtotime($t . ' +1 day'))) {
        $tage[] = $t;
        if (count($tage) >= 14) {
            break;   // Sicherung gegen ein verrutschtes Datum
        }
    }
    return $tage;
}
