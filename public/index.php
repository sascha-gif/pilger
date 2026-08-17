<?php
/**
 * pilger.milsh.com — Camino Portugués da Costa 2026
 * Dynamische Fassung des Masterplans: jeder Inhalt kommt aus der Datenbank,
 * Häkchen, Beträge und Gewichte werden dort auch wieder gespeichert.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

/* ---- Tür: ohne Anmeldung geht es hier nicht weiter ---------------------- */
require APP_ROOT . '/src/gate.php';

$locked = !may_write();

/* ---- Daten laden -------------------------------------------------------- */
$s         = $repo->settings();
$hero      = $repo->heroFacts();
$profile   = $repo->profileFacts();
$pills     = $repo->nutritionPills();
$slots     = $repo->nutritionSlots();
$travel    = $repo->travelCards();
$ankunft   = $repo->planSteps('ankunft');
$ziel      = $repo->planSteps('ziel');
$stages    = $repo->stages();
$equipment = $repo->equipment();
$packCats  = $repo->packList();
$progress  = $repo->packProgress();
$tagebuch  = new Tagebuch($db, $repo);
$eintraege = $tagebuch->eintraege();
$alleFotos = $tagebuch->fotos();
$kann      = $tagebuch->faehigkeiten();
$weg       = $repo->wegProgress();
$stempel   = $repo->stempelProgress();
$eqProg    = $repo->equipmentProgress();

$stagesOffen    = count(array_filter($stages, static fn ($s) => !(int) $s['done']));
$stagesErledigt = count($stages) - $stagesOffen;
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
<meta name="robots" content="noindex, nofollow">
<title><?= h($s['title'] ?? 'Camino Portugués 2026 — Masterplan') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,900&family=Public+Sans:wght@400;500;600;700&family=Spline+Sans+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/app.css?v=<?= h((string) @filemtime(__DIR__ . '/assets/app.css')) ?>">
</head>
<body<?= $locked ? ' data-readonly' : '' ?>>

<div class="lockbar">
  <div class="wrap">
    <span class="who">Angemeldet — die Seite ist für alle anderen zu.</span>
    <span class="netz" id="netz" hidden>offline — Eingaben werden gemerkt</span>
    <?php if (!$auth->fromEnv()): ?>
      <details class="pwchg">
        <summary>Passwort ändern</summary>
        <form method="post" autocomplete="off">
          <input type="password" name="neu" placeholder="neues Passwort" minlength="8" required autocomplete="new-password">
          <input type="password" name="wiederholung" placeholder="noch einmal" minlength="8" required autocomplete="new-password">
          <button type="submit" name="passwort_aendern" value="1">Speichern</button>
          <?php if (isset($gateError) && $gateError): ?><span class="msg"><?= h($gateError) ?></span><?php endif; ?>
          <?php if (isset($_GET['geaendert'])): ?><span class="msg ok">Passwort geändert.</span><?php endif; ?>
        </form>
      </details>
    <?php endif; ?>
    <form method="post" class="ab"><button type="submit" name="abmelden" value="1">Abmelden</button></form>
  </div>
</div>

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
    <a href="#ankunft">04 · Ankunft</a>
    <a href="#etappen">05 · Etappen</a>
    <a href="#equipment">06 · Equipment</a>
    <a href="#packliste">07 · Packliste</a>
    <a href="#kosten">08 · Kosten</a>
    <a href="#countdown">09 · Countdown</a>
    <a href="#tagebuch">10 · Tagebuch</a>
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

<section id="ankunft">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">04</div><h2>Ankunft &amp; Heimreise<small>Was an den beiden Enden konkret zu tun ist — Schritt für Schritt</small></h2></div>

    <h3 class="phase-head">Porto — ankommen und startklar werden</h3>
    <div class="day">
      <?php foreach ($ankunft as $s): ?>
        <div class="slot<?= $s['accent'] ? ' acc' : '' ?>">
          <time><?= h($s['time_label']) ?></time>
          <div class="txt">
            <span class="step-title"><?= h($s['title']) ?></span>
            <?php if ($s['body']): ?><span class="step-body"><?= rich($s['body']) ?></span><?php endif; ?>
            <?php if ($s['note']): ?><span class="step-note"><?= rich($s['note']) ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <h3 class="phase-head">Santiago — ankommen und heimreisen</h3>
    <div class="day">
      <?php foreach ($ziel as $s): ?>
        <div class="slot<?= $s['accent'] ? ' acc' : '' ?>">
          <time><?= h($s['time_label']) ?></time>
          <div class="txt">
            <span class="step-title"><?= h($s['title']) ?></span>
            <?php if ($s['body']): ?><span class="step-body"><?= rich($s['body']) ?></span><?php endif; ?>
            <?php if ($s['note']): ?><span class="step-note"><?= rich($s['note']) ?></span><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="pl-note" style="margin-top:26px;margin-bottom:0">
      <b>Stand August 2026.</b> Fahrpreise und Fahrpläne ändern sich — Metro-Ticket und Flughafenbus
      kurz vor dem Abflug noch einmal prüfen. Die Wege und Adressen bleiben.
    </div>
  </div>
</section>

<section id="etappen">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">05</div><h2>Etappen &amp; Unterkünfte<small>Budget-Ausrichtung · ohne Gepäcktransport · Booking-Links nach Preis sortiert (live prüfen, Sept. schwankt)</small></h2></div>

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

    <div class="weg reveal">
      <div class="wegkopf">
        <span class="wegtitel">Auf dem Weg</span>
        <span class="wegzahl" id="wegZahl">
          <b><?= h(num_attr($weg['gelaufen'])) ?></b> von <?= h(num_attr($weg['gesamt'])) ?> km
          · noch <span id="wegRest"><?= h(num_attr($weg['rest'])) ?></span> km
        </span>
      </div>
      <span class="wegbalken"><i id="wegBalken" style="width:<?= (int) $weg['prozent'] ?>%"></i></span>
      <div class="wegfuss">
        <span id="wegEtappen"><?= (int) $weg['etappen'] ?> von <?= (int) $weg['etappen_gesamt'] ?> Etappen gelaufen</span>
        <span class="stempelzahl<?= $stempel['fehlt'] > 0 ? ' fehlt' : '' ?>" id="stempelZahl">
          <?= (int) $stempel['da'] ?> / <?= (int) $stempel['noetig'] ?> Stempel<?= $stempel['fehlt'] > 0 ? ' · ' . (int) $stempel['fehlt'] . ' fehlen' : '' ?>
        </span>
      </div>
    </div>

    <div class="tabs" data-tabs="etappen">
      <button type="button" class="tab is-on" data-tab="offen">Offen <i id="tabEtappenOffen"><?= $stagesOffen ?></i></button>
      <button type="button" class="tab" data-tab="erledigt">Erledigt <i id="tabEtappenErledigt"><?= $stagesErledigt ?></i></button>
    </div>

    <div class="stages" data-tabgruppe="etappen" data-show="offen">
    <?php foreach ($stages as $st): ?>
      <?php
        $cls = 'stage';
        if ($st['variant'] === 'special') { $cls .= ' special'; }
        if ($st['variant'] === 'anchor')  { $cls .= ' anchor-day'; }
        $done   = (int) $st['done'] === 1;
        $noetig = (int) $st['stamps_needed'];
        $da     = (int) $st['stamps_done'];
      ?>
      <div class="<?= $cls ?>" data-done="<?= $done ? 1 : 0 ?>" data-stage="<?= (int) $st['id'] ?>">
        <div class="mojon">
          <div class="et"><?= h($st['code']) ?></div>
          <div class="km"><?= h($st['km_big']) ?><span><?= h($st['km_sub']) ?></span></div>
          <label class="tagab" title="Tag abhaken">
            <input type="checkbox" class="stagedone" data-id="<?= (int) $st['id'] ?>"<?= $done ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>>
            <span>erledigt</span>
          </label>
        </div>
        <div>
          <h3><?= h($st['title']) ?><?php if ($st['title_suffix']): ?> <span style="font-size:14px;color:var(--stone)"><?= h($st['title_suffix']) ?></span><?php endif; ?></h3>
          <?php if ($st['dist']): ?><div class="dist"><?= h($st['dist']) ?></div><?php endif; ?>

          <div class="tagdaten" data-stage="<?= (int) $st['id'] ?>" hidden>
            <span class="wetterfeld"></span>
            <span class="hoehenfeld"></span>
          </div>

          <?php if ($st['target']): ?><div class="target"><?= rich($st['target']) ?></div><?php endif; ?>
          <?php if ($st['note']): ?><div class="note"><?= rich($st['note']) ?></div><?php endif; ?>
          <?php if ($st['alt_note']): ?><div class="alt"><?= rich($st['alt_note']) ?></div><?php endif; ?>

          <?php if ($noetig > 0): ?>
            <div class="stempel" data-noetig="<?= $noetig ?>">
              <span class="slabel">Stempel<?= $noetig > 1 ? ' (' . $noetig . ' nötig)' : '' ?></span>
              <?php for ($i = 1; $i <= $noetig; $i++): ?>
                <label class="sbox" title="Stempel <?= $i ?>">
                  <input type="checkbox" class="stampbox" data-id="<?= (int) $st['id'] ?>" data-nr="<?= $i ?>"<?= $i <= $da ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>>
                  <span></span>
                </label>
              <?php endfor; ?>
              <span class="swarn"<?= ($done && $da < $noetig) ? '' : ' hidden' ?>>Tag ist abgehakt, aber es fehlt ein Stempel.</span>
            </div>
          <?php endif; ?>

          <?php if ($st['booking_url']): ?>
            <a class="book" href="<?= h($st['booking_url']) ?>" target="_blank" rel="noopener"><?= h($st['booking_label']) ?></a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
      <p class="leer" data-leer="offen" hidden>Alles abgehakt. 266 km. Das war’s.</p>
      <p class="leer" data-leer="erledigt" hidden>Noch nichts abgehakt — der erste Tag steht im Reiter nebenan.</p>
    </div>

    <p class="quelle" id="datenQuelle" hidden></p>

  </div>
</section>

<section id="equipment">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">06</div><h2>Equipment &amp; Gelenkschutz<small>Du trägst alles selbst — Packgewicht ist jetzt der kritische Faktor</small></h2></div>

    <div class="tabs" data-tabs="equip">
      <button type="button" class="tab is-on" data-tab="offen">Offen <i id="tabEquipOffen"><?= (int) ($eqProg['total'] - $eqProg['done']) ?></i></button>
      <button type="button" class="tab" data-tab="erledigt">Erledigt <i id="tabEquipErledigt"><?= (int) $eqProg['done'] ?></i></button>
      <button type="button" class="tab" data-tab="alle">Alle <i><?= (int) $eqProg['total'] ?></i></button>
    </div>

    <div class="eq" data-tabgruppe="equip" data-show="offen">
      <?php foreach ($equipment as $card): ?>
        <?php $eqDone = count(array_filter($card['items'], static fn ($i) => (int) $i['checked'] === 1)); ?>
        <div class="eqcard" data-karte="<?= (int) $card['id'] ?>">
          <h4><?= rich($card['title']) ?> <span class="eqcount"><?= $eqDone ?>/<?= count($card['items']) ?></span></h4>
          <ul>
            <?php foreach ($card['items'] as $item): ?>
              <li data-done="<?= (int) $item['checked'] === 1 ? 1 : 0 ?>">
                <label>
                  <input type="checkbox" class="equipbox" data-id="<?= (int) $item['id'] ?>"<?= (int) $item['checked'] === 1 ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>>
                  <span><?= rich($item['body']) ?></span>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="leer" hidden>alles erledigt</p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section id="packliste">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">07</div><h2>Packliste<small>Häkchen werden gespeichert — Stand gilt auf allen Geräten</small></h2></div>
    <?php if (isset($notes['pack_intro'])): ?>
      <div class="pl-note"><?= rich($notes['pack_intro']) ?></div>
    <?php endif; ?>

    <div class="progress">
      <span>Fortschritt</span>
      <span class="bar"><i style="width:<?= $progress['total'] ? round($progress['done'] / $progress['total'] * 100) : 0 ?>%"></i></span>
      <span class="cnt"><?= (int) $progress['done'] ?> / <?= (int) $progress['total'] ?> gepackt</span>
      <button type="button" class="toggle-all" id="toggleAll" aria-expanded="false">alle aufklappen</button>
    </div>

    <div class="tabs" data-tabs="pack">
      <button type="button" class="tab is-on" data-tab="alle">Alle <i><?= (int) $progress['total'] ?></i></button>
      <button type="button" class="tab" data-tab="offen">Offen <i id="tabPackOffen"><?= (int) ($progress['total'] - $progress['done']) ?></i></button>
      <button type="button" class="tab" data-tab="erledigt">Gepackt <i id="tabPackErledigt"><?= (int) $progress['done'] ?></i></button>
    </div>

    <div class="pack" data-tabgruppe="pack" data-show="alle">
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
                <tr data-done="<?= $item['checked'] ? 1 : 0 ?>"<?= $item['checked'] ? ' class="done"' : '' ?>>
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
    <div class="sec-head"><div class="sec-num">08</div><h2>Kosten<small>Beträge werden gespeichert · Summe rechnet live · leere Felder zählen als 0</small></h2></div>
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
    <div class="sec-head"><div class="sec-num">09</div><h2>Countdown –5 kg<small>93 → ~88 kg bis zum Abflug · ~0,7–1 kg/Woche · Ist-Gewicht wird gespeichert</small></h2></div>
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

<section id="tagebuch">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">10</div><h2>Tagebuch &amp; Fotos<small>Sprachnotiz oder getippt · Aufnahmen und Bilder werden gemerkt, bis wieder Netz da ist</small></h2></div>

    <?php
      // Nachschlagewerk für die Anzeige: Etappen-Id → Beschriftung.
      $stageLabel = [];
      foreach ($stages as $st) {
          $stageLabel[(int) $st['id']] = trim(($st['code'] ?? '') . ' · ' . $st['title']);
      }
    ?>

    <div class="tb-neu">
      <div class="tb-kopf">
        <label for="tbTag">Zu welchem Tag?</label>
        <select id="tbTag">
          <?php foreach ($stages as $st): ?>
            <option value="<?= (int) $st['id'] ?>" data-tag="<?= h((string) $st['date_iso']) ?>">
              <?= h($stageLabel[(int) $st['id']]) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="tb-werkzeug">
        <button type="button" id="tbAufnahme" class="tb-knopf rec">
          <span class="punkt"></span><span class="beschriftung">Sprachnotiz aufnehmen</span>
          <span class="uhr" id="tbUhr" hidden>0:00</span>
        </button>
        <label class="tb-knopf datei">
          Fotos hinzufügen
          <input type="file" id="tbFotos" accept="image/*" multiple hidden>
        </label>
      </div>

      <textarea id="tbText" rows="3" placeholder="… oder einfach tippen. Speichern geht auch ohne Netz — der Eintrag geht raus, sobald wieder Empfang ist."></textarea>
      <div class="tb-aktion">
        <button type="button" id="tbSpeichern" class="tb-knopf haupt">Eintrag speichern</button>
        <span class="tb-hinweis" id="tbHinweis"></span>
      </div>

      <div class="tb-queue" id="tbQueue" hidden></div>
    </div>

    <div class="tb-liste" id="tbListe">
      <?php foreach ($eintraege as $e): ?>
        <?php
          $etappe = $e['stage_id'] ? ($stageLabel[(int) $e['stage_id']] ?? '') : '';
          $text   = (string) ($e['text_clean'] ?: $e['text_raw']);
          $roh    = $e['text_clean'] && $e['text_raw'] ? (string) $e['text_raw'] : null;
          $fotos  = array_values(array_filter($alleFotos, static fn ($f) => (int) $f['entry_id'] === (int) $e['id']));
        ?>
        <article class="tbe" data-id="<?= (int) $e['id'] ?>">
          <header>
            <span class="tbtag"><?= h($etappe ?: (string) $e['day_iso']) ?></span>
            <?php if ($e['kind'] === 'audio'): ?>
              <span class="tbart">Sprachnotiz<?= $e['audio_seconds'] ? ' · ' . floor((int) $e['audio_seconds'] / 60) . ':' . str_pad((string) ((int) $e['audio_seconds'] % 60), 2, '0', STR_PAD_LEFT) : '' ?></span>
            <?php endif; ?>
            <span class="tbstatus st-<?= h($e['status']) ?>">
              <?= h(match ($e['status']) {
                  'neu'           => 'noch nicht verschriftlicht',
                  'transkribiert' => 'Rohtext',
                  'fehler'        => 'Fehler',
                  default         => '',
              }) ?>
            </span>
          </header>

          <?php if ($e['audio_file']): ?>
            <audio controls preload="none" src="media.php?art=audio&amp;id=<?= (int) $e['id'] ?>"></audio>
          <?php endif; ?>

          <div class="tbtext"<?= $text === '' ? ' hidden' : '' ?>><?= nl2br(h($text)) ?></div>

          <?php if ($roh !== null): ?>
            <details class="tbroh"><summary>Rohtext ansehen</summary><p><?= nl2br(h($roh)) ?></p></details>
          <?php endif; ?>

          <?php if ($fotos): ?>
            <div class="tbfotos">
              <?php foreach ($fotos as $f): ?>
                <a href="media.php?art=foto&amp;id=<?= (int) $f['id'] ?>" target="_blank" rel="noopener">
                  <img src="media.php?art=klein&amp;id=<?= (int) $f['id'] ?>" alt="<?= h((string) $f['caption']) ?>" loading="lazy">
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($e['status_note']): ?><p class="tbnotiz"><?= h((string) $e['status_note']) ?></p><?php endif; ?>

          <footer>
            <?php if ($e['audio_file'] && $e['status'] !== 'fertig'): ?>
              <button type="button" class="tb-mini veredeln">Text daraus machen</button>
            <?php endif; ?>
            <button type="button" class="tb-mini bearbeiten">Bearbeiten</button>
            <button type="button" class="tb-mini loeschen">Löschen</button>
          </footer>
        </article>
      <?php endforeach; ?>

      <?php if (!$eintraege): ?>
        <p class="leer" style="border:none">Noch kein Eintrag. Der erste kommt am 17. September.</p>
      <?php endif; ?>
    </div>

    <?php
      // Zeitleiste: alle Fotos in der Reihenfolge der Etappen.
      $nachEtappe = [];
      foreach ($alleFotos as $f) {
          $nachEtappe[(int) $f['stage_id']][] = $f;
      }
    ?>
    <?php if ($alleFotos): ?>
      <h3 class="phase-head">Zeitleiste — <?= count($alleFotos) ?> <?= count($alleFotos) === 1 ? 'Bild' : 'Bilder' ?>
        an <?= count($nachEtappe) ?> <?= count($nachEtappe) === 1 ? 'Tag' : 'Tagen' ?></h3>
      <div class="zeitleiste">
        <?php foreach ($stages as $st): ?>
          <?php $bilder = $nachEtappe[(int) $st['id']] ?? []; ?>
          <?php if (!$bilder) { continue; } ?>
          <div class="zl-tag">
            <div class="zl-kopf">
              <span class="zl-code"><?= h($st['code']) ?></span>
              <span class="zl-titel"><?= h($st['title']) ?></span>
              <span class="zl-zahl"><?= count($bilder) ?></span>
            </div>
            <div class="zl-bilder">
              <?php foreach ($bilder as $f): ?>
                <a href="media.php?art=foto&amp;id=<?= (int) $f['id'] ?>" target="_blank" rel="noopener"
                   title="<?= h((string) $f['caption']) ?>">
                  <img src="media.php?art=klein&amp;id=<?= (int) $f['id'] ?>" alt="<?= h((string) $f['caption']) ?>" loading="lazy">
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <details class="tb-einstellungen">
      <summary>Sprachnotizen in Text verwandeln — Schlüssel hinterlegen</summary>
      <div class="tb-eform">
        <p>
          Aufnehmen und speichern geht ohne alles. Damit aus einer Sprachnotiz von selbst
          ein sauberer Text wird, braucht der Server zwei Zugänge:
          <b>Whisper</b> hört die Aufnahme ab (rund 0,6 Cent je Minute), <b>Claude</b> macht
          daraus lesbares Deutsch (rund 1–2 Cent je Eintrag). Für zwei Wochen Camino sind das
          zusammen deutlich unter einem Euro.
        </p>
        <p class="warn">
          Die Schlüssel liegen danach im Klartext in deiner Datenbank — auf deinem Server,
          hinter deinem Passwort. Ein Schlüssel, den der Server benutzen soll, muss für ihn
          lesbar sein; anders geht es nicht. Ohne Eintrag bleibt einfach alles beim Rohton.
        </p>
        <label>OpenAI-Schlüssel (Whisper)
          <input type="password" id="keyOpenAi" placeholder="<?= $kann['transkription'] ? '— hinterlegt, zum Ändern neu eingeben —' : 'sk-…' ?>" autocomplete="off">
        </label>
        <label>Anthropic-Schlüssel (Claude)
          <input type="password" id="keyAnthropic" placeholder="<?= $kann['glaettung'] ? '— hinterlegt, zum Ändern neu eingeben —' : 'sk-ant-…' ?>" autocomplete="off">
        </label>
        <label>Modell
          <input type="text" id="keyModell" value="<?= h($kann['modell']) ?>" autocomplete="off">
        </label>
        <button type="button" id="keySpeichern" class="tb-knopf haupt">Schlüssel speichern</button>
        <span class="tb-hinweis" id="keyHinweis"></span>
      </div>
    </details>
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
<script src="assets/tagebuch.js?v=<?= h((string) @filemtime(__DIR__ . '/assets/tagebuch.js')) ?>"></script>
</body>
</html>
