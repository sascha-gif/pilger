<?php
/**
 * Rückkehr von Google nach der Anmeldung.
 *
 * Diese Adresse muss in der Google Cloud Console als autorisierte
 * Weiterleitungs-URI eingetragen sein, sonst lehnt Google schon vor dem
 * Zustimmungsbildschirm ab:
 *
 *   https://pilger.milsh.com/gesundheit.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

if (!may_write()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Nicht angemeldet.');
}

$gesundheit = new Gesundheit($db);

$ergebnis = ['ok' => false, 'meldung' => 'Unerwartete Rückmeldung von Google.'];

if (isset($_GET['error'])) {
    $ergebnis['meldung'] = match ((string) $_GET['error']) {
        'access_denied' => 'Du hast den Zugriff abgelehnt — es wurde nichts verbunden.',
        'redirect_uri_mismatch' => 'Google kennt diese Rückkehr-Adresse nicht. In der Cloud Console '
            . 'muss unter „Autorisierte Weiterleitungs-URIs" genau ' . $gesundheit->weiterleitung() . ' stehen.',
        default => 'Google meldet: ' . (string) $_GET['error'],
    };
} elseif (isset($_GET['code'])) {
    $ergebnis = $gesundheit->codeEinloesen((string) $_GET['code'], (string) ($_GET['state'] ?? ''));

    // Direkt die letzten Wochen mitnehmen, damit die Seite nicht leer bleibt.
    if ($ergebnis['ok']) {
        $holen = $gesundheit->holen(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        $ergebnis['meldung'] .= ' ' . $holen['meldung'];
    }
}

http_response_code($ergebnis['ok'] ? 200 : 400);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $ergebnis['ok'] ? 'Verbunden' : 'Nicht verbunden' ?> — Camino 2026</title>
<style>
  body{font-family:system-ui,-apple-system,sans-serif;background:#232a2e;color:#f6f0e2;
       min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;padding:24px;line-height:1.55}
  div{max-width:32rem;background:#fbf7ec;color:#1d2326;padding:24px 26px;
      border-left:3px solid <?= $ergebnis['ok'] ? '#2e7d32' : '#a4341f' ?>}
  h1{font-size:21px;margin:0 0 10px}
  p{margin:0 0 14px}
  a{display:inline-block;background:#232a2e;color:#f6f0e2;text-decoration:none;padding:11px 16px;font-weight:600}
</style>
</head>
<body>
<div>
  <h1><?= $ergebnis['ok'] ? 'Google Health ist verbunden' : 'Es hat nicht geklappt' ?></h1>
  <p><?= h($ergebnis['meldung']) ?></p>
  <a href="/#countdown">Zurück zum Plan</a>
</div>
</body>
</html>
