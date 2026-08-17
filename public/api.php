<?php
/**
 * JSON-Schnittstelle für alle Änderungen aus der Oberfläche.
 * Erwartet POST mit JSON-Body: {"action":"...", ...}
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['ok' => false, 'error' => 'Nur POST erlaubt.'], 405);
}

$raw  = file_get_contents('php://input') ?: '';
$body = json_decode($raw, true);
if (!is_array($body)) {
    json_out(['ok' => false, 'error' => 'Ungültiger JSON-Body.'], 400);
}

if (!may_write()) {
    json_out(['ok' => false, 'error' => 'Nicht angemeldet.'], 403);
}

$action = (string) ($body['action'] ?? '');
$id     = isset($body['id']) ? (int) $body['id'] : 0;

/** Wert aus dem Body als Zahl oder null lesen. */
$asFloat = static function ($value): ?float {
    if ($value === null || $value === '') {
        return null;
    }
    if (is_string($value)) {
        $value = str_replace(',', '.', trim($value));
    }
    if (!is_numeric($value)) {
        return null;
    }
    return round((float) $value, 2);
};

try {
    switch ($action) {

        case 'pack.toggle':
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'Ungültige ID.'], 400);
            }
            $checked = !empty($body['checked']);
            if (!$repo->togglePackItem($id, $checked)) {
                json_out(['ok' => false, 'error' => 'Eintrag nicht gefunden.'], 404);
            }
            $p = $repo->packProgress();
            json_out(['ok' => true, 'checked' => $checked, 'done' => $p['done'], 'total' => $p['total']]);
            // no break — json_out beendet

        case 'cost.set':
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'Ungültige ID.'], 400);
            }
            $amount = $asFloat($body['amount'] ?? null);
            if ($amount !== null && ($amount < 0 || $amount > 1000000)) {
                json_out(['ok' => false, 'error' => 'Betrag außerhalb des gültigen Bereichs.'], 422);
            }
            if (!$repo->setCostAmount($id, $amount)) {
                json_out(['ok' => false, 'error' => 'Position nicht gefunden.'], 404);
            }
            $total = $repo->costTotal();
            json_out([
                'ok'              => true,
                'amount'          => $amount,
                'total'           => $total,
                'total_formatted' => money($total),
            ]);

        case 'weight.set':
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'Ungültige ID.'], 400);
            }
            $actual = $asFloat($body['actual'] ?? null);
            if ($actual !== null && ($actual < 30 || $actual > 300)) {
                json_out(['ok' => false, 'error' => 'Gewicht außerhalb des gültigen Bereichs (30–300 kg).'], 422);
            }
            if (!$repo->setWeightActual($id, $actual)) {
                json_out(['ok' => false, 'error' => 'Woche nicht gefunden.'], 404);
            }
            json_out([
                'ok'     => true,
                'actual' => $actual,
                'latest' => $repo->latestWeight(),
            ]);

        case 'stage.update':
            if ($id <= 0) {
                json_out(['ok' => false, 'error' => 'Ungültige ID.'], 400);
            }
            $fields = array_intersect_key($body, array_flip(['target', 'note', 'booking_url', 'booking_label']));
            if (!$repo->updateStage($id, $fields)) {
                json_out(['ok' => false, 'error' => 'Etappe nicht gefunden oder nichts zu ändern.'], 404);
            }
            json_out(['ok' => true]);

        case 'state':
            $p = $repo->packProgress();
            $total = $repo->costTotal();
            json_out([
                'ok'    => true,
                'pack'  => $p,
                'costs' => ['total' => $total, 'total_formatted' => money($total)],
                'weight' => ['latest' => $repo->latestWeight()],
            ]);

        default:
            json_out(['ok' => false, 'error' => 'Unbekannte Aktion: ' . $action], 400);
    }
} catch (Throwable $e) {
    $debug = !empty($config['debug']);
    json_out([
        'ok'    => false,
        'error' => $debug ? $e->getMessage() : 'Serverfehler beim Speichern.',
    ], 500);
}
