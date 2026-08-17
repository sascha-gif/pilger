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

/** JSON-Antwort senden und beenden. */
function json_out(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
