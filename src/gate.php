<?php
declare(strict_types=1);

/**
 * Die Tür vor der Seite.
 *
 * Wird ganz oben von index.php eingebunden und bricht ab, solange niemand
 * angemeldet ist. Alles dahinter — Etappen, Kosten, Gewicht, später Tagebuch
 * und Fotos — bekommt nur zu sehen, wer das Passwort hat.
 *
 * Drei Zustände:
 *   1. kein Passwort gesetzt  → Einrichtung, sonst nichts
 *   2. Passwort gesetzt, nicht angemeldet → Anmeldung
 *   3. angemeldet → diese Datei tut nichts und die Seite läuft weiter
 */

/** @var Auth $auth */
/** @var array<string,mixed> $config */

$gateError  = null;
$gateNotice = null;

// Zuerst die Anmeldung von einem gemerkten Gerät wiederherstellen — sonst
// stünde jemand, der lange nicht da war, trotz gültigem Cookie vor der Tür.
$auth->restore();

/* ---- Abmelden ---------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abmelden'])) {
    $auth->logout();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

/* ---- Passwort ändern (nur angemeldet) ---------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passwort_aendern']) && $auth->isLoggedIn()) {
    $neu   = (string) ($_POST['neu'] ?? '');
    $noch  = (string) ($_POST['wiederholung'] ?? '');
    if ($auth->fromEnv()) {
        $gateError = 'Das Passwort steht in der .env auf dem Server und lässt sich hier nicht ändern.';
    } elseif (mb_strlen($neu) < 8) {
        $gateError = 'Das neue Passwort braucht mindestens 8 Zeichen.';
    } elseif ($neu !== $noch) {
        $gateError = 'Die beiden Eingaben sind nicht gleich.';
    } else {
        $auth->setPassword($neu);
        $auth->login(true);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?geaendert=1');
        exit;
    }
}

/* ---- Einrichtung: das allererste Passwort ------------------------------ */
if (!$auth->isConfigured()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['einrichten'])) {
        $neu  = (string) ($_POST['neu'] ?? '');
        $noch = (string) ($_POST['wiederholung'] ?? '');
        if (mb_strlen($neu) < 8) {
            $gateError = 'Bitte mindestens 8 Zeichen.';
        } elseif ($neu !== $noch) {
            $gateError = 'Die beiden Eingaben sind nicht gleich.';
        } else {
            $auth->setPassword($neu);
            $auth->login(true);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    }
    gate_page('einrichten', $gateError, null);
}

/* ---- Anmeldung --------------------------------------------------------- */
if (!$auth->isLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['anmelden'])) {
        $wait = $auth->blockedFor();
        if ($wait > 0) {
            $gateError = 'Zu viele Versuche. Bitte ' . $wait . ' Minuten warten.';
        } elseif ($auth->verify((string) $_POST['anmelden'])) {
            $auth->clearFailures();
            $auth->login(!empty($_POST['merken']));
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        } else {
            $auth->noteFailure();
            $gateError = 'Passwort stimmt nicht.';
        }
    }
    gate_page('anmelden', $gateError, $gateNotice);
}

/**
 * Tür-Seite ausgeben und beenden. Bewusst eigenständig: kein Leaflet, keine
 * Schriften von außen, kein app.js — was vor der Anmeldung ausgeliefert wird,
 * soll so wenig sein wie möglich.
 */
function gate_page(string $mode, ?string $error, ?string $notice): never
{
    $shell = 'M50 6c2 0 3 2 4 6 1-3 3-4 5-3 1 1 1 4 0 8 2-2 4-2 5 0 1 2 0 5-2 8 3-1 5 0 5 2 1 3-2 6-5 8 11 4 20 14 23 27 1 4-2 8-6 8H16c-4 0-7-4-6-8 3-13 12-23 23-27-3-2-6-5-5-8 0-2 2-3 5-2-2-3-3-6-2-8 1-2 3-2 5 0-1-4-1-7 0-8 2-1 4 0 5 3 1-4 2-6 4-6z';

    http_response_code($mode === 'einrichten' ? 200 : 401);
    header('Content-Type: text/html; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: same-origin');
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<meta name="color-scheme" content="light only">
<title><?= $mode === 'einrichten' ? 'Einrichten' : 'Anmelden' ?> — Camino 2026</title>
<style>
  :root{--granite:#232a2e;--flecha:#f4b400;--paper:#f6f0e2;--paper-2:#fbf7ec;
        --ink:#1d2326;--stone:#857c6c;--line:#ddd3bd;--warn:#a4341f}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:var(--granite);
       color:var(--paper);min-height:100vh;display:flex;align-items:center;justify-content:center;
       padding:24px;line-height:1.55}
  .box{width:100%;max-width:26rem}
  svg{width:56px;height:56px;display:block;margin-bottom:22px}
  .eyebrow{font-size:12px;letter-spacing:.3em;text-transform:uppercase;color:var(--flecha);margin-bottom:12px}
  h1{font-size:28px;font-weight:700;letter-spacing:-.01em;line-height:1.15;margin-bottom:10px}
  p{color:#b3ad9c;font-size:15px;margin-bottom:22px}
  p b{color:var(--paper);font-weight:600}
  form{background:var(--paper-2);color:var(--ink);padding:22px;border-left:3px solid var(--flecha)}
  label{display:block;font-size:12px;letter-spacing:.12em;text-transform:uppercase;
        color:var(--stone);margin-bottom:6px}
  input[type=password]{width:100%;font-size:17px;font-family:inherit;padding:11px 12px;
        border:1px solid var(--line);background:#fff;color:var(--ink);border-radius:0}
  input[type=password]:focus{outline:2px solid var(--flecha);outline-offset:-2px;border-color:var(--flecha)}
  .field + .field{margin-top:14px}
  .merken{display:flex;align-items:center;gap:9px;margin-top:16px;font-size:14px;color:var(--stone)}
  .merken input{width:17px;height:17px;accent-color:var(--flecha)}
  button{margin-top:18px;width:100%;font-family:inherit;font-size:15px;font-weight:600;
         padding:12px;background:var(--granite);color:var(--paper);border:0;cursor:pointer}
  button:hover{background:#000}
  .err{background:#fbeae6;border-left:3px solid var(--warn);color:#7e2717;padding:10px 12px;
       font-size:14px;margin-bottom:16px}
  .hint{font-size:13px;color:var(--stone);margin-top:16px}
  footer{margin-top:22px;font-size:12px;color:#6f6a60;letter-spacing:.06em}
</style>
</head>
<body>
<div class="box">
  <svg viewBox="0 0 100 100" aria-hidden="true"><path fill="#f4b400" d="<?= $shell ?>"/></svg>
  <div class="eyebrow">pilger.milsh.com</div>

<?php if ($mode === 'einrichten'): ?>
  <h1>Erst zumachen,<br>dann losgehen.</h1>
  <p>Die Seite ist noch für jeden offen, der die Adresse kennt. Vergib jetzt ein Passwort —
     danach kommt <b>niemand mehr ohne</b> an Plan, Kosten, Gewicht, Tagebuch und Fotos.</p>
  <form method="post" autocomplete="off">
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="field">
      <label for="neu">Passwort</label>
      <input type="password" id="neu" name="neu" required minlength="8" autocomplete="new-password" autofocus>
    </div>
    <div class="field">
      <label for="wdh">Noch einmal</label>
      <input type="password" id="wdh" name="wiederholung" required minlength="8" autocomplete="new-password">
    </div>
    <button type="submit" name="einrichten" value="1">Passwort setzen und Seite schließen</button>
    <div class="hint">Mindestens 8 Zeichen. Es wird als Hash gespeichert, nie im Klartext —
      auch nicht im Repo. Merk es dir gut: Zurücksetzen geht nur an der Datenbank.</div>
  </form>
<?php else: ?>
  <h1>Camino 2026</h1>
  <p>Porto → Santiago de Compostela. Bitte anmelden.</p>
  <form method="post" autocomplete="on">
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <?php if ($notice): ?><div class="hint"><?= h($notice) ?></div><?php endif; ?>
    <div class="field">
      <label for="pw">Passwort</label>
      <input type="password" id="pw" name="anmelden" required autocomplete="current-password" autofocus>
    </div>
    <label class="merken"><input type="checkbox" name="merken" value="1" checked> Auf diesem Gerät angemeldet bleiben</label>
    <button type="submit">Anmelden</button>
  </form>
<?php endif; ?>

  <footer>266 km · 12 Etappen · 17.09.–01.10.2026</footer>
</div>
</body>
</html>
<?php
    exit;
}
