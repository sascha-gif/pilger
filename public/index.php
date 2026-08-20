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
$travel    = $repo->travelCards();
$ankunft   = $repo->planSteps('ankunft');
$ziel      = $repo->planSteps('ziel');
$stages    = $repo->stages();
$packCats  = $repo->packList();
$progress  = $repo->packProgress();
$tagebuch  = new Tagebuch($db, $repo);
$eintraege = $tagebuch->eintraege();
$alleFotos = $tagebuch->fotos();
$kann      = $tagebuch->faehigkeiten();
$healthTage = (new Gesundheit($db))->tage();
$weg       = $repo->wegProgress();
$stempel   = $repo->stempelProgress();
$stempelOrte = $repo->stempelOrte();

$stagesOffen    = count(array_filter($stages, static fn ($s) => !(int) $s['done']));
$stagesErledigt = count($stages) - $stagesOffen;
$costs     = $repo->costItems();
$costTotal = $repo->costTotal();
$weeks     = $repo->weightWeeks();
$notes     = $repo->notes();
$todos     = $repo->todos();
$todoProg  = $repo->todoProgress();

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
        <summary><?= $auth->istCode() ? 'Code ändern' : 'Code festlegen' ?></summary>
        <form method="post" autocomplete="off">
          <input type="password" name="neu" placeholder="neuer Code" inputmode="numeric"
                 pattern="[0-9]*" minlength="<?= Auth::CODE_LEN ?>" maxlength="<?= Auth::CODE_LEN ?>"
                 required autocomplete="new-password">
          <input type="password" name="wiederholung" placeholder="noch einmal" inputmode="numeric"
                 pattern="[0-9]*" minlength="<?= Auth::CODE_LEN ?>" maxlength="<?= Auth::CODE_LEN ?>"
                 required autocomplete="new-password">
          <button type="submit" name="passwort_aendern" value="1">Speichern</button>
          <?php if (isset($gateError) && $gateError): ?><span class="msg"><?= h($gateError) ?></span><?php endif; ?>
          <?php if (isset($_GET['geaendert'])): ?><span class="msg ok">Code gespeichert.</span><?php endif; ?>
          <?php if (!$auth->istCode()): ?><span class="msg">Noch ein Passwort — <?= Auth::CODE_LEN ?> Ziffern setzen, dann reicht der Ziffernblock.</span><?php endif; ?>
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
    <a href="#anreise">02 · Anreise</a>
    <a href="#ankunft">03 · Ankunft</a>
    <a href="#etappen">04 · Etappen</a>
    <a href="#packliste">05 · Packliste</a>
    <a href="#kosten">06 · Kosten</a>
    <a href="#countdown">07 · Countdown</a>
    <a href="#vorher">08 · Vor der Abreise</a>
    <a href="#tagebuch">09 · Tagebuch</a>
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

<section id="anreise">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">02</div><h2>Anreise &amp; Logistik<small>17.09. – 01.10.2026 · ab/an Frankfurt Main (FRA)</small></h2></div>
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
    <div class="sec-head"><div class="sec-num">03</div><h2>Ankunft &amp; Heimreise<small>Was an den beiden Enden konkret zu tun ist — Schritt für Schritt</small></h2></div>

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

          <?php
            // Tatsächlich gelaufene Schritte an diesem Tag, sofern die Uhr
            // synchronisiert hat. Steht neben den geplanten Kilometern.
            $gTag = $st['date_iso'] ? ($healthTage[(string) $st['date_iso']] ?? null) : null;
          ?>
          <div class="tagdaten" data-stage="<?= (int) $st['id'] ?>"<?= $gTag ? '' : ' hidden' ?>>
            <span class="wetterfeld"></span>
            <span class="hoehenfeld"></span>
            <?php if ($gTag): ?>
              <?php
                // Geplant steht links im Kopf, gelaufen kommt von der Uhr. Auf
                // dem Camino ist das zweite fast immer größer — Umwege, Suche
                // nach der Unterkunft, abends noch mal los.
                $gelaufenKm = $gTag['distanz_m'] !== null ? round(((int) $gTag['distanz_m']) / 1000, 1) : null;
                $planKm     = $st['km_walk'] !== null ? (float) $st['km_walk'] : null;
                $teile = [];
                if ($gTag['steps'] !== null) { $teile[] = number_format((int) $gTag['steps'], 0, ',', '.') . ' Schritte'; }
                if ($gelaufenKm !== null)    { $teile[] = number_format($gelaufenKm, 1, ',', '.') . ' km'; }
                if ($gTag['kcal'] !== null)  { $teile[] = number_format((int) $gTag['kcal'], 0, ',', '.') . ' kcal'; }
                if ($gTag['hr_avg'] !== null){ $teile[] = 'Ø ' . (int) $gTag['hr_avg'] . ' bpm'; }
              ?>
              <span class="gehfeld da">
                <b>👣</b> <?= h(implode(' · ', $teile)) ?: '—' ?>
                <em>gemessen<?php
                  if ($gelaufenKm !== null && $planKm !== null && $planKm > 0) {
                      $delta = round($gelaufenKm - $planKm, 1);
                      echo ' · ' . ($delta >= 0 ? '+' : '−') . number_format(abs($delta), 1, ',', '.') . ' km gegenüber Plan';
                  }
                ?></em>
              </span>
            <?php endif; ?>
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

            <?php $orte = $stempelOrte[(int) $st['id']] ?? []; ?>
            <?php if ($orte): ?>
              <details class="sorte">
                <summary>Wo gibt es den Stempel?</summary>
                <ul>
                  <?php foreach ($orte as $o): ?>
                    <li class="<?= $o['art'] === 'fest' ? 'fest' : 'suche' ?>">
                      <a href="<?= h(maps_link($o['adresse'], $o['suche'])) ?>" target="_blank" rel="noopener">
                        <?= h($o['name']) ?><?= $o['art'] === 'suche' ? ' <span class="lupe">auf der Karte suchen</span>' : '' ?>
                      </a>
                      <?php if ($o['adresse']): ?><span class="adr"><?= h($o['adresse']) ?></span><?php endif; ?>
                      <?php if ($o['note']): ?><span class="hin"><?= h($o['note']) ?></span><?php endif; ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <p class="sfuss">
                  Deine Unterkunft stempelt auch — und Cafés und Bars am Weg fast immer.
                  Die Suchen führen in den Zielort; benannte Adressen sind geprüft, die Suchen
                  zeigen dir, was es dort <b>gerade</b> gibt. Albergues machen zu und ziehen um.
                </p>
              </details>
            <?php endif; ?>
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

<section id="packliste">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">05</div><h2>Packliste<small>Häkchen werden gespeichert — Stand gilt auf allen Geräten</small></h2></div>
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
    <div class="sec-head"><div class="sec-num">06</div><h2>Kosten<small>Beträge werden gespeichert · Summe rechnet live · leere Felder zählen als 0</small></h2></div>
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
    <div class="sec-head"><div class="sec-num">07</div><h2>Countdown –5 kg<small>93 → ~88 kg bis zum Abflug · ~0,7–1 kg/Woche · Ist-Gewicht wird gespeichert</small></h2></div>
    <?php if (isset($notes['weight_intro'])): ?>
      <div class="pl-note"><?= rich($notes['weight_intro']) ?></div>
    <?php endif; ?>
    <?php
      $gesundheit = new Gesundheit($db);
      $gStand     = $gesundheit->stand();
    ?>
    <div class="gsd">
      <div class="gsd-kopf">
        <span class="gsd-titel">Google Health</span>
        <?php if ($gStand['verbunden']): ?>
          <span class="gsd-lage ja">verbunden<?= $gStand['tage'] ? ' · ' . (int) $gStand['tage'] . ' Tage' : '' ?></span>
        <?php elseif ($gStand['zugang']): ?>
          <span class="gsd-lage offen">Zugang hinterlegt, noch nicht verbunden</span>
        <?php else: ?>
          <span class="gsd-lage offen">noch nicht eingerichtet</span>
        <?php endif; ?>
      </div>

      <?php if ($gStand['fehler']): ?>
        <p class="gsd-fehler"><?= h((string) $gStand['fehler']) ?></p>
      <?php endif; ?>

      <?php if ($gStand['verbunden']): ?>
        <p class="gsd-text">
          Schritte, Kalorien und Ruhepuls kommen aus deinem Fitbit-Konto.
          <?php if ($gStand['geholt']): ?>
            Zuletzt geholt: <b><?= h(date('d.m.Y, H:i', strtotime((string) $gStand['geholt']))) ?></b> Uhr.
          <?php endif; ?>
          Fehlt ein Tag, hat die Uhr nicht synchronisiert — das ist nicht dasselbe wie null Schritte,
          deshalb bleibt die Zeile dann leer statt auf 0 zu springen.
        </p>
        <div class="tb-aktion">
          <button type="button" class="tb-knopf haupt" id="gsdHolen">Jetzt aktualisieren</button>
          <button type="button" class="tb-knopf" id="gsdTrennen">Verbindung trennen</button>
          <span class="tb-hinweis" id="gsdHinweis"></span>
        </div>
      <?php else: ?>
        <details class="gsd-einrichten"<?= $gStand['zugang'] ? ' open' : '' ?>>
          <summary>Einrichten — was in der Google Cloud Console zu tun ist</summary>
          <ol class="gsd-schritte">
            <li>Projekt anlegen und die <b>Google Health API</b> aktivieren.</li>
            <li>Unter <i>Anmeldedaten</i> eine <b>OAuth-Client-ID für Webanwendung</b> erstellen.</li>
            <li>Dort als <b>autorisierte Weiterleitungs-URI</b> genau eintragen:
              <code><?= h((new Gesundheit($db))->weiterleitung()) ?></code>
              — ohne das bricht Google mit <i>redirect_uri_mismatch</i> ab.</li>
            <li>Beim Zustimmungsbildschirm die Rechte <i>activity_and_fitness.readonly</i> und
              <i>health_metrics_and_measurements.readonly</i> auswählen.</li>
            <li><b>Zielgruppe auf „Extern"</b> stellen, wenn die Health-Daten nicht unter einem
              Konto der eigenen Organisation liegen. Sonst lehnt Google mit
              <i>Fehler 403: org_internal</i> ab — dann darf nur die eigene Firma die App benutzen.</li>
            <li><b>Wichtig:</b> Veröffentlichungsstatus auf <b>„In production"</b> stellen. Bleibt die App
              auf „Testing", läuft die Verbindung nach <b>sieben Tagen</b> ab — und zwar mitten auf dem Camino.
              Veröffentlicht ist das Dauer-Token unbegrenzt gültig.</li>
            <li>Eine <b>Freigabe durch Google ist nicht nötig.</b> Die Prüfung entfernt nur den
              Warnbildschirm. Beim ersten Verbinden kommt <i>„Google hat diese App nicht überprüft"</i> —
              das ist der erwartete Weg: <b>Erweitert → Weiter zu pilger.milsh.com</b>. Die Daten gehen
              an den eigenen Server und an niemanden sonst.</li>
          </ol>
          <label>Client-ID
            <input type="text" id="gsdId" value="<?= h((string) ($gStand['client_id'] ?? '')) ?>"
                   placeholder="…apps.googleusercontent.com" autocomplete="off">
          </label>
          <label>Client-Secret
            <input type="password" id="gsdSecret"
                   placeholder="<?= $gStand['zugang'] ? '— hinterlegt, zum Ändern neu eingeben —' : 'GOCSPX-…' ?>" autocomplete="off">
          </label>
          <div class="tb-aktion">
            <button type="button" class="tb-knopf haupt" id="gsdSpeichern">Speichern</button>
            <?php if ($gStand['zugang']): ?>
              <a class="tb-knopf" id="gsdVerbinden" href="#">Mit Google verbinden</a>
            <?php endif; ?>
            <span class="tb-hinweis" id="gsdHinweis"></span>
          </div>
        </details>
      <?php endif; ?>
    </div>

    <div class="pcat">
      <table class="ctbl">
        <tr>
          <th>Woche</th><th class="det">Zeitraum</th><th class="r">Ziel</th><th class="r">Ist</th>
          <th>Schritte/Tag</th><th class="r">gemessen</th><th class="det">Lange Wanderung</th><th>Fokus</th>
        </tr>
        <?php foreach ($weeks as $w): ?>
          <?php
            // Gemessen wird über die Tage gemittelt, an denen es Daten gibt —
            // ein Tag ohne Synchronisierung soll den Schnitt nicht drücken.
            $ist = ($w['von_iso'] && $w['bis_iso'])
                ? $gesundheit->schnitt((string) $w['von_iso'], (string) $w['bis_iso'])
                : ['schritte' => null, 'tage' => 0, 'ruhepuls' => null, 'kcal' => null];

            // Läuft die Woche noch? Dann ist der Schnitt ein Zwischenstand und
            // kein Ergebnis. Ohne diesen Hinweis liest sich ein halber Tag wie
            // ein verfehltes Wochenziel.
            $laeuft = $w['von_iso'] && $w['bis_iso']
                && date('Y-m-d') >= (string) $w['von_iso'] && date('Y-m-d') <= (string) $w['bis_iso'];

            $gemessenesGewicht = ($w['von_iso'] && $w['bis_iso'])
                ? $gesundheit->gewicht((string) $w['von_iso'], (string) $w['bis_iso'])
                : null;
          ?>
          <tr>
            <td class="i-name"><?= h($w['label']) ?></td>
            <td class="det"><?= h($w['period']) ?></td>
            <td class="r"><?= h($w['target']) ?></td>
            <td class="r">
              <input class="wt" type="number" step="0.1" inputmode="decimal"
                     data-id="<?= (int) $w['id'] ?>"
                     value="<?= h(num_attr($w['actual'])) ?>" placeholder="kg"<?= $locked ? ' disabled' : '' ?>>
              <?php if ($gemessenesGewicht !== null): ?>
                <?php $abweichung = $w['actual'] !== null ? abs((float) $w['actual'] - $gemessenesGewicht['kg']) : null; ?>
                <span class="gewogen">
                  <?php if ($w['actual'] === null): ?>
                    <button type="button" class="wtnimm" data-id="<?= (int) $w['id'] ?>"
                            data-kg="<?= h(num_attr($gemessenesGewicht['kg'])) ?>"
                            title="Von der Waage übernehmen">
                      ⌂ <?= h(number_format($gemessenesGewicht['kg'], 1, ',', '.')) ?> übernehmen
                    </button>
                  <?php else: ?>
                    Waage: <?= h(number_format($gemessenesGewicht['kg'], 1, ',', '.')) ?><?php
                      if ($abweichung !== null && $abweichung >= 0.5): ?> <b>≠</b><?php endif; ?>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </td>
            <td><?= h($w['steps']) ?></td>
            <td class="r gemessen<?= $laeuft ? ' laeuft' : '' ?>">
              <?php if ($ist['schritte'] !== null): ?>
                <b><?= number_format($ist['schritte'], 0, ',', '.') ?></b>
                <span title="Durchschnitt pro Tag, gemittelt über die Tage mit Daten<?= $ist['ruhepuls'] ? ' · Ruhepuls Ø ' . $ist['ruhepuls'] : '' ?>">
                  Ø aus <?= (int) $ist['tage'] ?> <?= $ist['tage'] === 1 ? 'Tag' : 'Tagen' ?><?= $ist['ruhepuls'] ? ' · ' . (int) $ist['ruhepuls'] . ' bpm' : '' ?>
                  <?php if ($laeuft): ?><em>läuft noch</em><?php endif; ?>
                </span>
              <?php else: ?>
                <span class="leerwert">—</span>
              <?php endif; ?>
            </td>
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

<section id="vorher">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">08</div><h2>Vor der Abreise<small>Termine und Erledigungen — abhaken, was steht</small></h2></div>

    <div class="progress">
      <span>Erledigt</span>
      <span class="bar"><i id="todoBalken" style="width:<?= $todoProg['total'] ? round($todoProg['done'] / $todoProg['total'] * 100) : 0 ?>%"></i></span>
      <span class="cnt" id="todoZahl"><?= (int) $todoProg['done'] ?> / <?= (int) $todoProg['total'] ?> erledigt</span>
    </div>

    <div class="todos" id="todoListe">
      <?php foreach ($todos as $td): ?>
        <div class="todo<?= (int) $td['done'] ? ' fertig' : '' ?>" data-id="<?= (int) $td['id'] ?>">
          <label class="tdhaken">
            <input type="checkbox" class="todobox" data-id="<?= (int) $td['id'] ?>"<?= (int) $td['done'] ? ' checked' : '' ?><?= $locked ? ' disabled' : '' ?>>
          </label>
          <div class="tdtext">
            <span class="tdtitel"><?= h($td['titel']) ?></span>
            <?php if ($td['zweck']): ?><span class="tdzweck"><?= h($td['zweck']) ?></span><?php endif; ?>
          </div>
          <input type="text" class="tdnotiz" data-id="<?= (int) $td['id'] ?>"
                 value="<?= h((string) $td['notiz']) ?>" placeholder="Termin / Notiz" maxlength="200"<?= $locked ? ' disabled' : '' ?>>
          <button type="button" class="tdweg" data-id="<?= (int) $td['id'] ?>" title="Punkt löschen" aria-label="Punkt löschen">×</button>
        </div>
      <?php endforeach; ?>
    </div>

    <form class="todoneu" id="todoNeu" autocomplete="off">
      <input type="text" id="todoTitel" placeholder="Was noch? — z. B. Apotheke, Impfpass suchen" maxlength="200">
      <button type="submit" class="tb-knopf">Hinzufügen</button>
    </form>
  </div>
</section>

<section id="tagebuch">
  <div class="wrap reveal">
    <div class="sec-head"><div class="sec-num">09</div><h2>Tagebuch &amp; Fotos<small>Sprachnotiz oder getippt · Aufnahmen und Bilder werden gemerkt, bis wieder Netz da ist</small></h2></div>

    <?php
      // Nachschlagewerk für die Anzeige: Etappen-Id → Beschriftung.
      $stageLabel = [];
      foreach ($stages as $st) {
          $stageLabel[(int) $st['id']] = trim(($st['code'] ?? '') . ' · ' . $st['title']);
      }
    ?>

    <div class="tb-neu">
      <?php
        // Erledigte Tage rutschen nach unten, damit die Auswahl mit der Reise
        // kürzer wird. Ganz verschwinden dürfen sie nicht: der Eintrag zu einem
        // Tag entsteht abends, wenn der Tag längst abgehakt ist.
        $offeneTage    = array_values(array_filter($stages, static fn ($st) => !(int) $st['done']));
        $erledigteTage = array_values(array_filter($stages, static fn ($st) => (int) $st['done']));
        $vorauswahl    = $offeneTage ? (int) $offeneTage[0]['id'] : 0;
      ?>
      <div class="tb-kopf">
        <label for="tbTag">Zu welchem Tag?</label>
        <select id="tbTag">
          <?php if ($offeneTage): ?>
            <optgroup label="Offen" data-gruppe="offen">
              <?php foreach ($offeneTage as $st): ?>
                <option value="<?= (int) $st['id'] ?>" data-tag="<?= h((string) $st['date_iso']) ?>"<?= (int) $st['id'] === $vorauswahl ? ' selected' : '' ?>>
                  <?= h($stageLabel[(int) $st['id']]) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
          <?php if ($erledigteTage): ?>
            <optgroup label="Erledigt" data-gruppe="erledigt">
              <?php foreach (array_reverse($erledigteTage) as $st): ?>
                <option value="<?= (int) $st['id'] ?>" data-tag="<?= h((string) $st['date_iso']) ?>"<?= !$offeneTage && (int) $st['id'] === (int) $erledigteTage[count($erledigteTage) - 1]['id'] ? ' selected' : '' ?>>
                  <?= h($stageLabel[(int) $st['id']]) ?>
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
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

      <?php /* Was schon ausgewaehlt ist, bleibt sichtbar und bleibt liegen:
               am Handy holt man die Bilder meist einzeln aus der Galerie. */ ?>
      <div class="tb-wahl" id="tbWahl" hidden></div>

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
        <article class="tbe" data-id="<?= (int) $e['id'] ?>" data-stage="<?= (int) $e['stage_id'] ?>">
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
            <details class="tbroh"><summary>Original ansehen — genau so gesagt oder getippt</summary><p><?= nl2br(h($roh)) ?></p></details>
          <?php endif; ?>

          <?php if ($fotos): ?>
            <div class="tbfotos">
              <?php foreach ($fotos as $f): ?>
                <?= bild_kachel($f, false) ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($e['status_note']): ?><p class="tbnotiz"><?= h((string) $e['status_note']) ?></p><?php endif; ?>

          <footer>
            <?php if ($e['audio_file'] && $e['status'] !== 'fertig'): ?>
              <button type="button" class="tb-mini veredeln">Text daraus machen</button>
            <?php elseif ($text !== '' && !$e['text_clean']): ?>
              <?php /* Diktiert oder getippt — der Ausbau fehlt noch. */ ?>
              <button type="button" class="tb-mini veredeln">Text ausbauen</button>
            <?php elseif ($e['text_clean']): ?>
              <?php /* Nochmal: nachgereichte Bilder und die Zahlen der Uhr, die
                       erst am nächsten Morgen synchronisiert wurden, kommen so
                       noch in den Text. Gebaut wird immer aus dem Original. */ ?>
              <button type="button" class="tb-mini veredeln erneut">Neu ausbauen</button>
            <?php endif; ?>
            <button type="button" class="tb-mini bearbeiten">Bearbeiten</button>
            <?php /* Bilder gehoeren zum Eintrag, nicht nur zum Anlegen: was
                     abends dazukommt, muss auch spaeter noch dazu koennen. */ ?>
            <label class="tb-mini datei">
              Fotos hinzufügen
              <input type="file" class="tbe-fotos" accept="image/*" multiple hidden>
            </label>
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
                <?= bild_kachel($f, true) ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <details class="tb-einstellungen">
      <summary>Sprachnotizen in Text verwandeln — Schlüssel hinterlegen</summary>
      <div class="tb-eform">
        <p class="kannstand">
          <span class="<?= $kann['glaettung'] ? 'ja' : 'nein' ?>">
            <?= $kann['glaettung'] ? '✓' : '○' ?> Ausbau (Claude)
          </span>
          <span class="<?= $kann['transkription'] ? 'ja' : 'nein' ?>">
            <?= $kann['transkription'] ? '✓' : '○' ?> Transkription (Whisper)
          </span>
        </p>
        <p>
          Aufnehmen, tippen und speichern geht ohne alles. Die beiden Zugänge machen
          zweierlei, und sie hängen nicht aneinander:
        </p>
        <p>
          <b>Claude</b> macht aus der Notiz einen fertigen Eintrag: lesbares Deutsch, und dazu
          bekommt es den Tag mitgeliefert, wie er wirklich war — Etappe und Zielort, Schritte,
          Kilometer, Kalorien und Puls von der Uhr, Wetter, Höhenmeter und die Fotos des
          Eintrags. Aus drei hingeworfenen Sätzen werden so zwei, drei Absätze. Ergänzt wird
          nur, was in diesen Daten steht oder auf den Bildern zu sehen ist; Gefühle und
          Begegnungen kommen ausschließlich aus dem, was du selbst gesagt hast. Das Original
          bleibt immer erhalten und steht am Eintrag unter <i>„Original ansehen"</i>.
          Diktier oben ins Tippfeld über den Mikrofon-Knopf deiner Handytastatur, speichere,
          und am Eintrag steht dann <i>„Text ausbauen"</i>. Rund 2 Cent je Eintrag,
          mit vielen Bildern etwas mehr.
        </p>
        <p>
          <b>Whisper</b> braucht es nur für <i>aufgenommene</i> Sprachnotizen — Claude nimmt
          keine Audiodateien entgegen, es kann lesen und schreiben, aber nicht zuhören. Rund
          0,6 Cent je Minute. Ohne Whisper bleibt eine Aufnahme eine Aufnahme: gespeichert
          und abspielbar, aber ohne Text.
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
        <div class="tb-aktion">
          <button type="button" id="keySpeichern" class="tb-knopf haupt">Speichern und ausprobieren</button>
          <button type="button" id="keyPruefen" class="tb-knopf">Nur ausprobieren</button>
        </div>
        <p class="tb-hinweis" id="keyHinweis"></p>
      </div>
    </details>
  </div>
</section>

<footer>
  <div class="wrap">
    <span><?= h($s['footer_left'] ?? '') ?></span>
    <span><?= h($s['footer_right'] ?? '') ?></span>
    <?php
      // Welcher Stand läuft gerade? Ohne diese Zeile ist „ist es schon
      // deployt?" eine Frage, die niemand von außen beantworten kann.
      $commit = getenv('PILGER_COMMIT') ?: null;
      $gebaut = getenv('PILGER_BUILD_TIME') ?: null;
    ?>
    <?php if ($commit): ?>
      <span class="stand">
        Stand <a href="https://github.com/sascha-gif/pilger/commit/<?= h($commit) ?>"
                 target="_blank" rel="noopener"><?= h($commit) ?></a><?php
        if ($gebaut): ?> · gebaut <?= h(date('d.m. H:i', strtotime($gebaut))) ?><?php endif; ?>
      </span>
    <?php endif; ?>
  </div>
</footer>

<div class="saver" id="saver" role="status" aria-live="polite"></div>

<script type="application/json" id="map-data"><?= json_encode($mapPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/app.js?v=<?= h((string) @filemtime(__DIR__ . '/assets/app.js')) ?>"></script>
<script src="assets/tagebuch.js?v=<?= h((string) @filemtime(__DIR__ . '/assets/tagebuch.js')) ?>"></script>
</body>
</html>
