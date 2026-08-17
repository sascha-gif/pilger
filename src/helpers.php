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

/** JSON-Antwort senden und beenden. */
function json_out(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
