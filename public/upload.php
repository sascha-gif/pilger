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
    json_out(['ok' => false, 'error' => $grund], 400);
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

    json_out(['ok' => false, 'error' => 'Unbekannte Art: ' . $art], 400);
} catch (Throwable $e) {
    error_log('pilger: Upload fehlgeschlagen — ' . $e->getMessage());
    json_out([
        'ok'    => false,
        'error' => !empty($config['debug']) ? $e->getMessage() : 'Speichern fehlgeschlagen.',
    ], 500);
}
