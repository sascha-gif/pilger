<?php
/**
 * pilger.milsh.com — Camino Portugués da Costa 2026
 * Dynamische Fassung des Masterplans: jeder Inhalt kommt aus der Datenbank,
 * Häkchen, Beträge und Gewichte werden dort auch wieder gespeichert.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

/* ---- Schreibschutz: Login / Logout ------------------------------------- */
$loginError = null;
$pass = $config['write_password'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock'])) {
    if ($pass !== null && $pass !== '' && hash_equals((string) $pass, (string) $_POST['unlock'])) {
        $_SESSION['pilger_write'] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    $loginError = 'Passwort stimmt nicht.';
}

if (isset($_GET['lock'])) {
    unset($_SESSION['pilger_write']);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$locked   = !may_write();
$hasLock  = $pass !== null && $pass !== '';

/* ---- Daten laden -------------------------------------------------------- */
$s         = $repo->settings();
$hero      = $repo->heroFacts();
$profile   = $repo->profileFacts();
$pills     = $repo->nutritionPills();
$slots     = $repo->nutritionSlots();
$travel    = $repo->travelCards();
$stages    = $repo->stages();
$equipment = $repo->equipment();
$packCats  = $repo->packList();
$progress  = $repo->packProgress();
$costs     = $repo->costItems();
$costTotal = $repo->costTotal();
$weeks     = $repo->weightWeeks();
$notes     = $repo->notes();

$mapPayload = [
    'center' => array_map('floatval', explode(',', $s['map_center'] ?? '42.0,-8.72')),
    'zoom'   => (int) ($s['map_zoom'] ?? 8),
    'stops'  => array_map(static fn ($r) => [
        'n'   => $r['n'],
        'e'   => $r['e'],
        'm'   => $r['m'],
        'lat' => (float) $r['lat'],
        'lng' => (float) $r['lng'],
        'hub' => (int) $r['hub'],
    ], $repo->mapStops()),
    'routes' => array_map(static fn ($r) => [
        'name'   => $r['name'],
        'color'  => $r['color'],
        'weight' => (int) $r['weight'],
        'dashed' => (int) $r['dashed'],
        'points' => $r['points'],
    ], $repo->mapRoutes()),
];

$shellPath = 'M50 6c2 0 3 2 4 6 1-3 3-4 5-3 1 1 1 4 0 8 2-2 4-2 5 0 1 2 0 5-2 8 3-1 5 0 5 2 1 3-2 6-5 8 11 4 20 14 23 27 1 4-2 8-6 8H16c-4 0-7-4-6-8 3-13 12-23 23-27-3-2-6-5-5-8 0-2 2-3 5-2-2-3-3-6-2-8 1-2 3-2 5 0-1-4-1-7 0-8 2-1 4 0 5 3 1-4 2-6 4-6z';
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light only">
<title><?= h($s['title'] ?? 'Camino Portugués 2026 — Masterplan') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,900&family=Public+Sans:wght@400;500;600;700&family=Spline+Sans+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/app.css?v=<?= h((string) @filemtime(__DIR__ . '/assets/app.css')) ?>">
</head>
<body<?= $locked ? ' data-readonly' : '' ?>>

<?php if ($hasLock): ?>
<div class="lockbar">
  <div class="wrap">
    <?php if ($locked): ?>
      <span>Nur-Lese-Modus — Häkchen, Beträge und Gewicht sind gesperrt.</span>
      <form method="post">
        <input type="password" name="unlock" placeholder="Passwort" autocomplete="current-password" required>
        <button type="submit">Entsperren</button>
        <?php if ($loginError): ?><span class="msg"><?= h($loginError) ?></span><?php endif; ?>
      </form>
    <?php else: ?>
      <span>Bearbeiten aktiv — Änderungen werden sofort gespeichert.</span>
      <a class="btn" href="?lock=1">Sperren</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<header class="hero">
  <svg class="bigshell" viewBox="0 0 100 100" aria-hidden="true"><path fill="#f4b400" d="<?= $shellPath ?>"/></svg>
  <div class="wrap">
    <svg class="shell" viewBox="0 0 100 100" aria-hidden="true"><path fill="#f4b400" d="<?= $shellPath ?>"/><g stroke="#232a2e" stroke-width="1.4" fill="none" opacity=".5"><path d="M50 30v50M38 33 30 78M62 33l8 45M28 42 18 76M72 42l10 34"/></g></svg>
    <div class="eyebrow"><?= h($s['eyebrow'] ?? '') ?></div>
    <h1><?= h($s['h1_top'] ?? '') ?><br><em><?= h($s['h1_em'] ?? '') ?></em></h1>
    <div class="route"><?= str_replace('→', '<span class="arrow">&rarr;</span>', h($s['route'] ?? '')) ?></div>
    <div class="facts">
      <?php foreach ($hero as $f): ?>
        <div class="fact">
          <div class="n<?= $f['mono'] ? ' mono' : '' ?>"><?= h($f['number']) ?></div>
          <div class="l"><?= h($f['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</header>

<nav class="toc">
  <div class="wrap">
    <a href="#profil">01 · Profil</a>
    <a href="#ernaehrung">02 · Ernährung</a>
    <a href="#anreise">03 · Anreise</a>
    <a href="#etappen">04 · Etappen</a>
    <a href="#equipment">05 · Equipment</a>
    <a href="#packliste">06 · Packliste</a>
    <a href="#kosten">07 · Kosten</a>
    <a href="#countdown">08 · Countdown</a>
  </div>
</nav>

<section id="profil">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">01</div><h2>Profil &amp; Basis<small>Ausgangslage und Trainingsrahmen</small></h2></div>
    <div class="grid">
      <?php foreach ($profile as $p): ?>
        <div class="cell">
          <div class="k"><?= h($p['label']) ?></div>
          <div class="v"><?= h($p['value']) ?><?php if ($p['sub']): ?> <span><?= h($p['sub']) ?></span><?php endif; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="ernaehrung">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">02</div><h2>Ernährung &amp; Supplements<small>16:8 Intervallfasten · Tagesprotokoll</small></h2></div>
    <div class="pill-row">
      <?php foreach ($pills as $p): ?>
        <span class="pill"><b><?= h($p['strong']) ?></b> <?= h($p['rest']) ?></span>
      <?php endforeach; ?>
    </div>
    <div class="day">
      <?php foreach ($slots as $slot): ?>
        <div class="slot<?= $slot['accent'] ? ' acc' : '' ?>">
          <time><?= h($slot['time_label']) ?></time>
          <div class="txt"><?= rich($slot['body']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="anreise">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">03</div><h2>Anreise &amp; Logistik<small>17.09. – 01.10.2026 · ab/an Frankfurt Main (FRA)</small></h2></div>
    <div class="flight">
      <?php foreach ($travel as $t): ?>
        <div class="fcard">
          <div class="ftag<?= $t['tag_ok'] ? ' ok' : '' ?>"><?= h($t['tag']) ?></div>
          <div class="froute<?= $t['route_small'] ? ' small' : '' ?>">
            <?= str_replace('→', '<span class="ar">&rarr;</span>', h($t['route'])) ?>
          </div>
          <div class="fmeta"><?= rich($t['meta']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="etappen">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">04</div><h2>Etappen &amp; Unterkünfte<small>Budget-Ausrichtung · ohne Gepäcktransport · Booking-Links nach Preis sortiert (live prüfen, Sept. schwankt)</small></h2></div>

    <div class="mapwrap reveal">
      <div id="map"></div>
      <div class="maplegend">
        <span><i class="dot" style="background:#f4b400"></i> Start / Ziel</span>
        <span><i class="dot" style="background:#1f5d6c"></i> Etappenort</span>
        <?php foreach ($mapPayload['routes'] as $r): ?>
          <span><i style="background:<?= h($r['color']) ?><?= $r['dashed'] ? ';height:0;border-top:3px dashed ' . h($r['color']) : '' ?>"></i> <?= h($r['name']) ?></span>
        <?php endforeach; ?>
      </div>
      <p style="font-family:'Spline Sans Mono',monospace;font-size:11px;color:var(--stone);margin-top:8px">
        Von Porto bis Caminha die Senda Litoral am Atlantik, über den Minho nach Spanien, dann die
        galicische Küste bis Vigo. Ab Pontevedra geht es landeinwärts nach Santiago.
      </p>
    </div>

    <?php foreach ($stages as $st): ?>
      <?php
        $cls = 'stage';
        if ($st['variant'] === 'special') { $cls .= ' special'; }
        if ($st['variant'] === 'anchor')  { $cls .= ' anchor-day'; }
      ?>
      <div class="<?= $cls ?>">
        <div class="mojon">
          <div class="et"><?= h($st['code']) ?></div>
          <div class="km"><?= h($st['km_big']) ?><span><?= h($st['km_sub']) ?></span></div>
        </div>
        <div>
          <h3><?= h($st['title']) ?><?php if ($st['title_suffix']): ?> <span style="font-size:14px;color:var(--stone)"><?= h($st['title_suffix']) ?></span><?php endif; ?></h3>
          <?php if ($st['dist']): ?><div class="dist"><?= h($st['dist']) ?></div><?php endif; ?>
          <?php if ($st['target']): ?><div class="target"><?= rich($st['target']) ?></div><?php endif; ?>
          <?php if ($st['note']): ?><div class="note"><?= rich($st['note']) ?></div><?php endif; ?>
          <?php if ($st['alt_note']): ?><div class="alt"><?= rich($st['alt_note']) ?></div><?php endif; ?>
          <?php if ($st['booking_url']): ?>
            <a class="book" href="<?= h($st['booking_url']) ?>" target="_blank" rel="noopener"><?= h($st['booking_label']) ?></a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</section>

<section id="equipment">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">05</div><h2>Equipment &amp; Gelenkschutz<small>Du trägst alles selbst — Packgewicht ist jetzt der kritische Faktor</small></h2></div>
    <div class="eq">
      <?php foreach ($equipment as $card): ?>
        <div class="eqcard">
          <h4><?= rich($card['title']) ?></h4>
          <ul>
            <?php foreach ($card['items'] as $item): ?>
              <li><?= rich($item['body']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="packliste">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">06</div><h2>Packliste<small>Häkchen werden gespeichert — Stand gilt auf allen Geräten</small></h2></div>
    <?php if (isset($notes['pack_intro'])): ?>
      <div class="pl-note"><?= rich($notes['pack_intro']) ?></div>
    <?php endif; ?>

    <div class="progress">
      <span>Fortschritt</span>
      <span class="bar"><i style="width:<?= $progress['total'] ? round($progress['done'] / $progress['total'] * 100) : 0 ?>%"></i></span>
      <span class="cnt"><?= (int) $progress['done'] ?> / <?= (int) $progress['total'] ?> gepackt</span>
      <button type="button" class="toggle-all" id="toggleAll" aria-expanded="false">alle aufklappen</button>
    </div>

    <div class="pack">
      <?php foreach ($packCats as $cat): ?>
        <?php
          $total = count($cat['items']);
          $done  = count(array_filter($cat['items'], static fn ($i) => (int) $i['checked'] === 1));
        ?>
        <details class="pcat" data-cat="<?= (int) $cat['id'] ?>">
          <summary>
            <h4><?= rich($cat['title']) ?></h4>
            <span class="catcount<?= $total && $done === $total ? ' full' : '' ?>"><?= $done ?> / <?= $total ?></span>
          </summary>
          <div class="ptbl-wrap">
            <table class="ptbl">
              <tr><th class="c-chk"></th><th>Item</th><th>Größe/Detail</th><th>Anz.</th><th>Zweck</th></tr>
              <?php foreach ($cat['items'] as $item): ?>
                <tr<?= $item['checked'] ? ' class="done"' : '' ?>>
                  <td class="c-chk">
                    <input type="checkbox" data-id="<?= (int) $item['id'] ?>"<?= $item['checked'] ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>>
                  </td>
                  <td class="i-name"><?= h($item['name']) ?></td>
                  <td class="c-size"><?= h($item['size']) ?></td>
                  <td class="c-qty"><?= h($item['qty']) ?></td>
                  <td class="c-use"><?= rich($item['purpose']) ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="kosten">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">07</div><h2>Kosten<small>Beträge werden gespeichert · Summe rechnet live · leere Felder zählen als 0</small></h2></div>
    <div class="pcat">
      <table class="ctbl">
        <tr><th>Position</th><th class="det">Detail</th><th class="r">Betrag €</th><th>Status</th></tr>
        <?php foreach ($costs as $c): ?>
          <tr>
            <td class="i-name"><?= h($c['name']) ?></td>
            <td class="det"><?= h($c['detail']) ?></td>
            <td class="r">
              <input class="cost" type="number" step="0.01" inputmode="decimal"
                     data-id="<?= (int) $c['id'] ?>"
                     value="<?= h(num_attr($c['amount'])) ?>" placeholder="0"<?= $locked ? ' disabled' : '' ?>>
            </td>
            <td><span class="st <?= h($c['status']) ?>"><?= h($c['status_label']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <div class="ctot">
        <span class="lbl">Summe (aktuell erfasst)</span>
        <span class="val" id="costTotal"><?= h(money($costTotal)) ?></span>
      </div>
    </div>
    <?php if (isset($notes['cost_outro'])): ?>
      <div class="pl-note" style="margin-top:22px;margin-bottom:0"><?= rich($notes['cost_outro']) ?></div>
    <?php endif; ?>
  </div>
</section>

<section id="countdown">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">08</div><h2>Countdown –5 kg<small>93 → ~88 kg bis zum Abflug · ~0,7–1 kg/Woche · Ist-Gewicht wird gespeichert</small></h2></div>
    <?php if (isset($notes['weight_intro'])): ?>
      <div class="pl-note"><?= rich($notes['weight_intro']) ?></div>
    <?php endif; ?>
    <div class="pcat">
      <table class="ctbl">
        <tr><th>Woche</th><th class="det">Zeitraum</th><th class="r">Ziel</th><th class="r">Ist</th><th>Schritte/Tag</th><th class="det">Lange Wanderung</th><th>Fokus</th></tr>
        <?php foreach ($weeks as $w): ?>
          <tr>
            <td class="i-name"><?= h($w['label']) ?></td>
            <td class="det"><?= h($w['period']) ?></td>
            <td class="r"><?= h($w['target']) ?></td>
            <td class="r">
              <input class="wt" type="number" step="0.1" inputmode="decimal"
                     data-id="<?= (int) $w['id'] ?>"
                     value="<?= h(num_attr($w['actual'])) ?>" placeholder="kg"<?= $locked ? ' disabled' : '' ?>>
            </td>
            <td><?= h($w['steps']) ?></td>
            <td class="det"><?= h($w['long_walk']) ?></td>
            <td><?= rich($w['focus']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php if (isset($notes['weight_outro'])): ?>
      <div class="pl-note" style="margin-top:22px;margin-bottom:0"><?= rich($notes['weight_outro']) ?></div>
    <?php endif; ?>
  </div>
</section>

<footer>
  <div class="wrap">
    <span><?= h($s['footer_left'] ?? '') ?></span>
    <span><?= h($s['footer_right'] ?? '') ?></span>
  </div>
</footer>

<div class="saver" id="saver" role="status" aria-live="polite"></div>

<script type="application/json" id="map-data"><?= json_encode($mapPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/app.js?v=<?= h((string) @filemtime(__DIR__ . '/assets/app.js')) ?>"></script>
</body>
</html>
