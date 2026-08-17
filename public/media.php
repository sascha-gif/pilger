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

if (!may_write()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Nicht angemeldet.');
}

$id  = (int) ($_GET['id'] ?? 0);
$art = (string) ($_GET['art'] ?? 'foto');

$tagebuch = new Tagebuch($db, $repo);

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
    $name = ($art === 'klein' && $foto['thumb']) ? $foto['thumb'] : $foto['file'];
    $pfad = data_path('fotos') . '/' . basename((string) $name);
}

if (!is_readable($pfad)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Datei nicht gefunden.');
}

$typen = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'heic' => 'image/heic', 'heif' => 'image/heif',
    'webm' => 'audio/webm', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg',
    'mp4' => 'audio/mp4', 'm4a' => 'audio/mp4', 'mp3' => 'audio/mpeg', 'wav' => 'audio/wav',
];
$endung = strtolower((string) pathinfo($pfad, PATHINFO_EXTENSION));

header('Content-Type: ' . ($typen[$endung] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($pfad));
header('Cache-Control: private, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . basename($pfad) . '"');

// Für Audio zurückspulen und springen zu können.
header('Accept-Ranges: bytes');

readfile($pfad);
