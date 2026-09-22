<?php
/**
 * Annahme von Fotos und Sprachaufnahmen.
 *
 * Eigener Endpunkt statt api.php, weil hier multipart/form-data ankommt und
 * kein JSON. Die Antwort ist trotzdem JSON — der Browser räumt danach seine
 * Warteschlange auf.
 *
 * Alles hinter der Anmeldung. Ohne sie kommt hier gar nichts durch.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Nur POST erlaubt.'], 405);
}
if (!may_write()) {
    json_out(['ok' => false, 'error' => 'Nicht angemeldet.'], 403);
}

$art      = (string) ($_POST['art'] ?? '');
$stageId  = isset($_POST['stage']) && $_POST['stage'] !== '' ? (int) $_POST['stage'] : null;
$entryId  = isset($_POST['entry']) && $_POST['entry'] !== '' ? (int) $_POST['entry'] : null;
$tag      = isset($_POST['tag']) && $_POST['tag'] !== '' ? substr((string) $_POST['tag'], 0, 10) : null;
$clientId = substr((string) ($_POST['client_id'] ?? ''), 0, 64);
$sekunden = isset($_POST['sekunden']) ? (int) $_POST['sekunden'] : null;
$wann     = isset($_POST['aufgenommen']) && $_POST['aufgenommen'] !== ''
    ? substr((string) $_POST['aufgenommen'], 0, 32) : null;

$datei = $_FILES['datei'] ?? null;
if (!is_array($datei) || ($datei['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $grund = match ((int) ($datei['error'] ?? UPLOAD_ERR_NO_FILE)) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß.',
        UPLOAD_ERR_PARTIAL                        => 'Die Übertragung ist abgebrochen.',
        UPLOAD_ERR_NO_FILE                        => 'Es kam keine Datei an.',
        default                                   => 'Die Datei kam nicht heil an.',
    };

    /* Liegt die Sendung über `post_max_size`, wirft PHP den kompletten Rumpf
       weg: $_POST und $_FILES sind dann beide leer, und der Endpunkt meldet
       „keine Datei" — was stimmt, aber nicht weiterhilft. Die Zahlen stehen
       hier noch zur Verfügung, also gehören sie in die Antwort. */
    $laenge = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $grenze = ini_bytes((string) ini_get('post_max_size'));
    if (!$_POST && $laenge > 0 && $grenze > 0 && $laenge > $grenze) {
        $grund = sprintf(
            'Die Sendung war %.1f MB und damit über der Grenze von %d MB — der Server hat sie verworfen.',
            $laenge / 1048576,
            (int) round($grenze / 1048576)
        );
    }

    /* Wenn es trotzdem nicht klar ist, hilft nur noch, was der Server selbst
       sieht. Fotos scheitern hier seit Tagen mit „keine Datei", waehrend
       Sprachnotizen durchgehen — beide auf demselben Weg. Diese Zahlen sagen,
       ob der Rumpf ueberhaupt ankam, ob PHP ihn zerlegt hat und was von den
       Uploads uebrig blieb. Hinter der Anmeldung, also unbedenklich. */
    $diagnose = [
        'laenge'       => (int) ($_SERVER['CONTENT_LENGTH'] ?? 0),
        'typ'          => substr((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 0, 90),
        'post_felder'  => implode(',', array_keys($_POST)),
        'files_felder' => implode(',', array_keys($_FILES)),
        'fehlercode'   => (int) ($datei['error'] ?? -1),
        'post_max'     => (string) ini_get('post_max_size'),
        'upload_max'   => (string) ini_get('upload_max_filesize'),
        'uploads_an'   => ini_get('file_uploads') ? 'ja' : 'NEIN',
        'max_dateien'  => (string) ini_get('max_file_uploads'),
        'tmp'          => ((string) ini_get('upload_tmp_dir') ?: sys_get_temp_dir()),
    ];
    $diagnose['tmp_beschreibbar'] = is_writable($diagnose['tmp']) ? 'ja' : 'NEIN';

    json_out(['ok' => false, 'error' => $grund, 'diagnose' => $diagnose], 400);
}

try {
    $tagebuch = new Tagebuch($db, $repo);

    if ($art === 'audio') {
        $eintrag = $tagebuch->nimmAudio($datei, $stageId, $tag, $clientId ?: null, $sekunden ?: null);
        json_out(['ok' => true, 'art' => 'audio', 'eintrag' => $eintrag]);
    }

    if ($art === 'foto') {
        $foto = $tagebuch->nimmFoto($datei, $stageId, $entryId, $clientId ?: null, $wann);
        json_out(['ok' => true, 'art' => 'foto', 'foto' => $foto]);
    }

    if ($art === 'gpx') {
        /* Eine echte Aufzeichnung statt der Stuetzpunkte von Hand. Sie wird
           beim Annehmen ausgeduennt — eine GPX-Datei ueber 266 km hat schnell
           hunderttausend Punkte, und die will niemand durchs Mobilnetz laden. */
        $xml = @file_get_contents($datei['tmp_name']);
        if ($xml === false) {
            throw new RuntimeException('Die Datei konnte nicht gelesen werden.');
        }

        $roh = Route::ausGpx($xml);
        [$linie, $toleranz] = Route::eindampfen($roh);

        $db->transaction(static function (Database $db) use ($linie): void {
            $db->run("DELETE FROM map_routes WHERE quelle = 'gpx'");
            $db->run(
                'INSERT INTO map_routes (seq, name, color, weight, dashed, points, quelle)
                 VALUES (?,?,?,?,?,?,?)',
                [1, 'Der Weg (GPX)', '#f4b400', 4, 0, json_encode($linie), 'gpx']
            );
        });

        json_out([
            'ok'       => true,
            'art'      => 'gpx',
            'roh'      => count($roh),
            'punkte'   => count($linie),
            'km'       => Route::laengeKm($linie),
            'toleranz' => $toleranz,
        ]);
    }

    json_out(['ok' => false, 'error' => 'Unbekannte Art: ' . $art], 400);
} catch (Throwable $e) {
    error_log('pilger: Upload fehlgeschlagen — ' . $e->getMessage());
    json_out([
        'ok'    => false,
        'error' => !empty($config['debug']) ? $e->getMessage() : 'Speichern fehlgeschlagen.',
    ], 500);
}
