<?php
declare(strict_types=1);

/**
 * Die Tür vor der Seite.
 *
 * Wird ganz oben von index.php eingebunden und bricht ab, solange niemand
 * angemeldet ist. Alles dahinter — Etappen, Kosten, Gewicht, Tagebuch und
 * Fotos — bekommt nur zu sehen, wer den Code kennt.
 *
 * Kein Benutzername, keine Mailadresse: nur ein sechsstelliger Zahlencode wie
 * die Gerätesperre am iPad. Unterwegs mit klammen Fingern und Handschuhen ist
 * ein Ziffernblock das einzig Brauchbare — ein Passwortfeld ist es nicht.
 *
 * Drei Zustände:
 *   1. kein Code gesetzt → Einrichtung, sonst nichts
 *   2. Code gesetzt, nicht angemeldet → Ziffernblock
 *   3. angemeldet → diese Datei tut nichts und die Seite läuft weiter
 *
 * Wer noch ein altes Passwort hat, kommt weiter damit hinein, bis er einen
 * Code festlegt — sonst spärrte diese Umstellung ihn aus.
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

/* ---- Code ändern (nur angemeldet) -------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passwort_aendern']) && $auth->isLoggedIn()) {
    $neu  = trim((string) ($_POST['neu'] ?? ''));
    $noch = trim((string) ($_POST['wiederholung'] ?? ''));
    if ($auth->fromEnv()) {
        $gateError = 'Der Zugang steht in der .env auf dem Server und lässt sich hier nicht ändern.';
    } elseif (!Auth::istGueltigerCode($neu)) {
        $gateError = 'Der Code besteht aus genau ' . Auth::CODE_LEN . ' Ziffern.';
    } elseif ($neu !== $noch) {
        $gateError = 'Die beiden Eingaben sind nicht gleich.';
    } else {
        $auth->setCode($neu);
        $auth->login(true);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?geaendert=1');
        exit;
    }
}

/* ---- Einrichtung: das allererste Passwort ------------------------------ */
if (!$auth->isConfigured()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['einrichten'])) {
        $neu  = trim((string) ($_POST['neu'] ?? ''));
        $noch = trim((string) ($_POST['wiederholung'] ?? ''));
        if (!Auth::istGueltigerCode($neu)) {
            $gateError = 'Bitte genau ' . Auth::CODE_LEN . ' Ziffern.';
        } elseif ($neu !== $noch) {
            $gateError = 'Die beiden Eingaben stimmen nicht überein.';
        } else {
            $auth->setCode($neu);
            $auth->login(true);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }
    }
    gate_page('einrichten', $gateError, null, $auth);
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
            $gateError = $auth->istCode() ? 'Code stimmt nicht.' : 'Passwort stimmt nicht.';
        }
    }
    gate_page('anmelden', $gateError, $gateNotice, $auth);
}

/**
 * Tür-Seite ausgeben und beenden. Bewusst eigenständig: kein Leaflet, keine
 * Schriften von außen, kein app.js — was vor der Anmeldung ausgeliefert wird,
 * soll so wenig sein wie möglich.
 */
function gate_page(string $mode, ?string $error, ?string $notice, Auth $auth): never
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
       padding:20px;line-height:1.5}
  .box{width:100%;max-width:20rem;text-align:center}
  svg{width:46px;height:46px;display:block;margin:0 auto 16px}
  .eyebrow{font-size:11px;letter-spacing:.28em;text-transform:uppercase;color:var(--flecha);margin-bottom:10px}
  h1{font-size:21px;font-weight:600;line-height:1.25;margin-bottom:6px}
  .sub{color:#b3ad9c;font-size:14px;margin-bottom:22px;min-height:2.6em}
  .sub b{color:var(--paper);font-weight:600}

  /* Sechs Punkte, wie die Gerätesperre am iPad. */
  .punkte{display:flex;justify-content:center;gap:14px;margin-bottom:26px}
  .punkte i{width:13px;height:13px;border-radius:50%;border:1.5px solid #6f6a60;
            transition:background .12s ease,border-color .12s ease,transform .12s ease}
  .punkte i.voll{background:var(--flecha);border-color:var(--flecha);transform:scale(1.12)}
  .box.falsch .punkte{animation:wackeln .4s}
  @keyframes wackeln{0%,100%{transform:translateX(0)}20%{transform:translateX(-9px)}
                     40%{transform:translateX(9px)}60%{transform:translateX(-5px)}80%{transform:translateX(5px)}}

  .tasten{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;justify-items:center}
  .tasten button{width:70px;height:70px;border-radius:50%;border:1px solid #3a444a;
                 background:#2b3338;color:var(--paper);font-family:inherit;font-size:26px;
                 font-weight:400;cursor:pointer;-webkit-tap-highlight-color:transparent;
                 transition:background .1s ease}
  .tasten button:active{background:var(--flecha);color:var(--granite)}
  .tasten button.leer{border:none;background:none;cursor:default}
  .tasten button.weg{font-size:19px;border:none;background:none;color:#b3ad9c}
  .tasten button.weg:active{background:none;color:var(--flecha)}

  .err{background:#3a2420;border-left:3px solid var(--warn);color:#f0a08c;padding:9px 12px;
       font-size:13.5px;margin-bottom:16px;text-align:left}
  .merken{display:flex;align-items:center;justify-content:center;gap:9px;margin-top:24px;
          font-size:13.5px;color:#b3ad9c}
  .merken input{width:17px;height:17px;accent-color:var(--flecha)}
  .hint{font-size:12.5px;color:#6f6a60;margin-top:18px}
  .altpw{margin-top:20px;border-top:1px solid #3a444a;padding-top:16px}
  .altpw summary{font-size:12.5px;color:#8a938f;cursor:pointer}
  .altpw input{width:100%;font-size:16px;font-family:inherit;padding:10px 12px;margin-top:10px;
               border:1px solid #3a444a;background:#1c2226;color:var(--paper)}
  .altpw button{margin-top:10px;width:100%;font-family:inherit;font-size:14px;font-weight:600;
                padding:11px;background:var(--flecha);color:var(--granite);border:0;cursor:pointer}
  footer{margin-top:24px;font-size:11px;color:#5d5952;letter-spacing:.05em}
  .keinjs{display:none}
  .keinjs input{width:100%;font-size:17px;padding:11px;border:1px solid #3a444a;
                background:#1c2226;color:var(--paper);margin-bottom:10px}
  .keinjs button{width:100%;padding:11px;background:var(--flecha);color:var(--granite);
                 border:0;font-weight:600;font-size:15px;cursor:pointer}
</style>
</head>
<body>
<div class="box" id="box">
  <svg viewBox="0 0 100 100" aria-hidden="true"><path fill="#f4b400" d="<?= $shell ?>"/></svg>
  <div class="eyebrow">pilger.milsh.com</div>

<?php if ($mode === 'einrichten'): ?>
  <h1>Code festlegen</h1>
  <p class="sub" id="sub">Sechs Ziffern, wie die Sperre am Handy. Danach kommt
     <b>niemand mehr ohne</b> an Plan, Kosten, Gewicht, Tagebuch und Fotos.</p>
<?php else: ?>
  <h1>Camino 2026</h1>
  <p class="sub" id="sub">Porto → Santiago. Code eingeben.</p>
<?php endif; ?>

  <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>

  <form method="post" id="form" autocomplete="off">
    <div class="punkte" id="punkte" aria-hidden="true">
      <?php for ($i = 0; $i < Auth::CODE_LEN; $i++): ?><i></i><?php endfor; ?>
    </div>

    <div class="tasten" id="tasten">
      <?php foreach ([1,2,3,4,5,6,7,8,9] as $z): ?>
        <button type="button" data-z="<?= $z ?>"><?= $z ?></button>
      <?php endforeach; ?>
      <button type="button" class="leer" tabindex="-1" aria-hidden="true"></button>
      <button type="button" data-z="0">0</button>
      <button type="button" class="weg" data-weg="1" aria-label="Löschen">&#9003;</button>
    </div>

<?php if ($mode === 'einrichten'): ?>
    <input type="hidden" name="neu" id="neu">
    <input type="hidden" name="wiederholung" id="wdh">
    <input type="hidden" name="einrichten" value="1">
<?php else: ?>
    <input type="hidden" name="anmelden" id="anmelden">
    <label class="merken"><input type="checkbox" name="merken" value="1" checked>
      Auf diesem Gerät angemeldet bleiben</label>
<?php endif; ?>
  </form>

<?php if ($mode === 'einrichten'): ?>
  <p class="hint">Merk ihn dir gut: Zurücksetzen geht nur an der Datenbank.</p>
<?php elseif (!$auth->istCode()): ?>
  <?php /* Wer von früher noch ein Passwort hat, muss hineinkommen, um sich
           einen Code setzen zu können. Sobald einer steht, fällt das hier weg. */ ?>
  <details class="altpw">
    <summary>Ich habe noch ein Passwort statt eines Codes</summary>
    <form method="post" autocomplete="on">
      <input type="password" name="anmelden" placeholder="Passwort" autocomplete="current-password" required>
      <input type="hidden" name="merken" value="1">
      <button type="submit">Anmelden</button>
    </form>
  </details>
<?php endif; ?>

  <noscript>
    <div class="keinjs" style="display:block;margin-top:20px">
      <form method="post">
        <input type="password" name="<?= $mode === 'einrichten' ? 'neu' : 'anmelden' ?>"
               inputmode="numeric" pattern="[0-9]*" placeholder="Code" required>
        <?php if ($mode === 'einrichten'): ?>
          <input type="password" name="wiederholung" inputmode="numeric" pattern="[0-9]*" placeholder="Noch einmal" required>
          <input type="hidden" name="einrichten" value="1">
        <?php endif; ?>
        <button type="submit">Weiter</button>
      </form>
    </div>
  </noscript>

  <footer>266 km · 12 Etappen · 17.09.–01.10.2026</footer>
</div>

<script>
(function () {
  var LEN = <?= (int) Auth::CODE_LEN ?>;
  var EINRICHTEN = <?= $mode === 'einrichten' ? 'true' : 'false' ?>;
  var box = document.getElementById('box'), form = document.getElementById('form');
  var punkte = document.getElementById('punkte').children, sub = document.getElementById('sub');
  var puffer = '', ersterCode = null;

  function male() {
    for (var i = 0; i < punkte.length; i++) {
      punkte[i].classList.toggle('voll', i < puffer.length);
    }
  }

  function ruettele(text) {
    box.classList.add('falsch');
    if (text) sub.textContent = text;
    setTimeout(function () { box.classList.remove('falsch'); }, 420);
    puffer = '';
    male();
  }

  function fertig() {
    if (!EINRICHTEN) {
      document.getElementById('anmelden').value = puffer;
      form.submit();
      return;
    }
    if (ersterCode === null) {
      // Erste Eingabe gemerkt, jetzt zur Bestätigung.
      ersterCode = puffer;
      puffer = '';
      male();
      sub.textContent = 'Und noch einmal zur Sicherheit.';
      return;
    }
    if (ersterCode !== puffer) {
      ersterCode = null;
      ruettele('Die beiden stimmten nicht überein. Noch einmal von vorn.');
      return;
    }
    document.getElementById('neu').value = ersterCode;
    document.getElementById('wdh').value = puffer;
    form.submit();
  }

  function tippe(ziffer) {
    if (puffer.length >= LEN) return;
    puffer += ziffer;
    male();
    if (puffer.length === LEN) setTimeout(fertig, 120);
  }

  document.getElementById('tasten').addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b) return;
    if (b.dataset.weg) { puffer = puffer.slice(0, -1); male(); return; }
    if (b.dataset.z) tippe(b.dataset.z);
  });

  // Auf dem Rechner tippt man lieber auf der Tastatur.
  document.addEventListener('keydown', function (e) {
    if (e.key >= '0' && e.key <= '9') { tippe(e.key); e.preventDefault(); }
    else if (e.key === 'Backspace') { puffer = puffer.slice(0, -1); male(); e.preventDefault(); }
  });

  male();
})();
</script>
</body>
</html>
<?php
    exit;
}
