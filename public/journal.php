<?php
/**
 * Das Journal — der Camino als durchgehende Geschichte.
 *
 * Der Abschnitt „Tagebuch" auf der Hauptseite ist ein Werkzeug: aufnehmen,
 * hochladen, abhaken. Diese Seite ist das Gegenteil davon — sie hat keine
 * Knöpfe. Sie ist zum Lesen da, von Anfang bis Ende, auch für jemanden, der
 * nicht dabei war.
 *
 * Prototyp. Was hier steht, kommt aus denselben Tabellen wie das Tagebuch;
 * neue Felder gibt es noch keine. Was für die fertige Fassung fehlt, steht in
 * HANDOVER.md unter „Das Journal".
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require APP_ROOT . '/src/gate.php';

$s          = $repo->settings();
$stages     = $repo->stages();
$tagebuch   = new Tagebuch($db, $repo);
$eintraege  = $tagebuch->eintraege();
$alleFotos  = $tagebuch->fotos();
$healthTage = (new Gesundheit($db))->tage();
$weg        = $repo->wegProgress();

/* Wetter und Höhen sind nett, aber nicht lebenswichtig: wenn der Dienst nicht
   antwortet, fehlt eine Zeile — die Seite bleibt lesbar. */
$wetter = [];
$hoehen = [];
try {
    $aussen = new Aussen($db, $repo);
    $wetter = $aussen->wetter()['tage'] ?? [];
    $hoehen = $aussen->hoehen()['etappen'] ?? [];
} catch (Throwable $e) {
    error_log('pilger: Journal ohne Außendaten — ' . $e->getMessage());
}

$stageNachId = [];
foreach ($stages as $st) {
    $stageNachId[(int) $st['id']] = $st;
}

/* ---- Nach Tagen bündeln, älteste zuerst -------------------------------- */
/* Anders als im Tagebuch: dort steht der neueste Tag oben, weil man ihn gerade
   geschrieben hat. Ein Journal liest man von vorn. */
$kapitel = [];
foreach ($eintraege as $e) {
    $tag = (string) ($e['day_iso'] ?: ($stageNachId[(int) $e['stage_id']]['date_iso'] ?? ''));
    if ($tag === '') {
        $tag = substr((string) $e['created_at'], 0, 10);
    }
    $kapitel[$tag]['tag'] = $tag;
    $kapitel[$tag]['eintraege'][] = $e;
    if (!isset($kapitel[$tag]['stage']) && $e['stage_id']) {
        $kapitel[$tag]['stage'] = $stageNachId[(int) $e['stage_id']] ?? null;
    }
}
ksort($kapitel);

foreach ($kapitel as $tag => &$k) {
    $ids = array_map(static fn ($e) => (int) $e['id'], $k['eintraege']);
    $k['fotos'] = array_values(array_filter(
        $alleFotos,
        static fn ($f) => in_array((int) $f['entry_id'], $ids, true)
    ));
    $k['gesund'] = $healthTage[$tag] ?? null;
    $k['eintraege'] = array_reverse($k['eintraege']);   // früh zuerst
}
unset($k);

$fotosGesamt = array_sum(array_map(static fn ($k) => count($k['fotos']), $kapitel));

$monate     = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
               'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$wochentage = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];

/* Die Punkte für die Kartenkarten: je Kapitel ein Ziel, dazu die Küstenlinie. */
require_once APP_ROOT . '/db/kuestenroute.php';
$journalKarte = [
    'linie'   => kuesten_route_punkte(),
    'kapitel' => array_values(array_filter(array_map(static function ($k) {
        $st = $k['stage'] ?? null;
        if (!$st || $st['lat'] === null) {
            return null;
        }
        return [
            'tag'  => $k['tag'],
            'name' => (string) ($st['map_name'] ?? $st['title']),
            'lat'  => (float) $st['lat'],
            'lng'  => (float) $st['lng'],
        ];
    }, $kapitel))),
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
<title>Journal — Camino Portugués da Costa 2026</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,900;1,9..144,400&family=Public+Sans:wght@400;500;600&family=Spline+Sans+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="assets/journal.css?v=<?= h((string) @filemtime(__DIR__ . '/assets/journal.css')) ?>">
</head>
<body>

<!-- ============ Titelseite ============ -->
<header class="jcover">
  <svg class="jshell" viewBox="0 0 100 100" aria-hidden="true"><path fill="#f4b400" d="<?= $shellPath ?>"/></svg>
  <div class="jcover-in">
    <p class="jkicker">Camino Portugués da Costa</p>
    <h1>Porto<br><em>nach</em><br>Santiago</h1>
    <p class="jdaten">17. September – 1. Oktober 2026 · zu Fuß, alles selbst getragen</p>
    <div class="jzahlen">
      <div><b><?= h(num_attr($weg['gesamt'])) ?></b><span>Kilometer</span></div>
      <div><b><?= count($stages) - 1 ?></b><span>Etappen</span></div>
      <div><b><?= count($kapitel) ?></b><span><?= count($kapitel) === 1 ? 'Tag' : 'Tage' ?> notiert</span></div>
      <div><b><?= (int) $fotosGesamt ?></b><span><?= $fotosGesamt === 1 ? 'Bild' : 'Bilder' ?></span></div>
    </div>
    <p class="jprototyp">Prototyp — die Seite entsteht gerade</p>
  </div>
  <a class="jrunter" href="#k1" aria-label="Zum ersten Tag">↓</a>
</header>

<?php if (!$kapitel): ?>
  <section class="jleer">
    <p>Noch kein Eintrag. Sobald im Tagebuch etwas liegt, steht es hier.</p>
    <p><a href="index.php#tagebuch">Zum Tagebuch</a></p>
  </section>
<?php else: ?>

<!-- ============ Tagesleiste ============ -->
<nav class="jleiste" id="jleiste">
  <?php $n = 0; foreach ($kapitel as $k): $n++; $z = strtotime($k['tag']); ?>
    <a href="#k<?= $n ?>" data-k="k<?= $n ?>">
      <b><?= $z ? (int) date('j', $z) : '?' ?>.</b>
      <span><?= $k['stage'] ? h(trim(explode('·', (string) $k['stage']['code'])[0])) : '—' ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<!-- ============ Kapitel ============ -->
<main>
<?php $n = 0; foreach ($kapitel as $k):
  $n++;
  $st    = $k['stage'] ?? null;
  $z     = strtotime($k['tag']);
  $g     = $k['gesund'];
  $w     = $st ? ($wetter[(int) $st['id']] ?? null) : null;
  $h     = $st ? ($hoehen[(int) $st['id']] ?? null) : null;
  $fotos = $k['fotos'];
  // Das erste Bild trägt das Kapitel, der Rest steht im Mosaik darunter.
  $held  = $fotos ? array_shift($fotos) : null;
?>
  <article class="jkap" id="k<?= $n ?>">

    <?php if ($held): ?>
      <figure class="jheld">
        <img src="media.php?art=foto&amp;id=<?= (int) $held['id'] ?>" alt=""
             loading="<?= $n === 1 ? 'eager' : 'lazy' ?>" decoding="async">
        <?php if ($held['caption']): ?>
          <figcaption><?= h((string) $held['caption']) ?></figcaption>
        <?php endif; ?>
      </figure>
    <?php endif; ?>

    <header class="jkopf">
      <p class="jtagzahl"><?= $z ? h($wochentage[(int) date('w', $z)]) : '' ?>,
        <b><?= $z ? (int) date('j', $z) : '' ?>. <?= $z ? h($monate[(int) date('n', $z)]) : '' ?></b></p>
      <?php if ($st): ?>
        <h2><?= h((string) $st['title']) ?></h2>
        <p class="jetappe"><?= h(trim((string) $st['code'])) ?><?php
          if ($st['km_walk'] > 0): ?> · <?= h(num_attr($st['km_walk'])) ?> km geplant<?php endif; ?></p>
      <?php endif; ?>
    </header>

    <?php
      $zahlen = [];
      if ($g && $g['steps'] !== null)     { $zahlen[] = ['Schritte', number_format((int) $g['steps'], 0, ',', '.')]; }
      if ($g && $g['distanz_m'] !== null) { $zahlen[] = ['gelaufen', number_format(((int) $g['distanz_m']) / 1000, 1, ',', '.') . ' km']; }
      if ($g && $g['kcal'] !== null)      { $zahlen[] = ['verbraucht', number_format((int) $g['kcal'], 0, ',', '.') . ' kcal']; }
      if ($h && isset($h['auf']))         { $zahlen[] = ['bergauf', (int) $h['auf'] . ' hm']; }
      if ($w && $w['max'] !== null) {
          $zahlen[] = ['Wetter', round((float) $w['max']) . ' °C'
              . (Aussen::wetterText(isset($w['code']) ? (int) $w['code'] : null)
                  ? ', ' . Aussen::wetterText((int) $w['code']) : '')];
      }
    ?>
    <?php if ($zahlen): ?>
      <div class="jzeile">
        <?php foreach ($zahlen as [$was, $wert]): ?>
          <div><b><?= h($wert) ?></b><span><?= h($was) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="jtext">
      <?php foreach ($k['eintraege'] as $e):
        $text = (string) ($e['text_clean'] ?: $e['text_raw']);
        if (trim($text) === '') { continue; }
      ?>
        <?= absaetze($text) ?>
      <?php endforeach; ?>
    </div>

    <?php
      /* Wo die Bilder des Tages entstanden sind. Die Koordinaten stehen im
         Bild und werden auf dem Handy gelesen, bevor es verkleinert wird —
         hat ein Bild keine, fehlt hier eben ein Punkt. Erfunden wird keiner. */
      $fotoPunkte = [];
      foreach ($k['fotos'] as $f) {
          if ($f['lat'] !== null && $f['lng'] !== null) {
              $fotoPunkte[] = [(float) $f['lat'], (float) $f['lng']];
          }
      }
    ?>
    <?php if (($st && $st['lat'] !== null) || $fotoPunkte): ?>
      <?php /* Der Satz darin ist der Rueckfall: laedt Leaflet nicht (kein Netz),
               steht hier etwas statt eines leeren Kastens. journal.js raeumt
               ihn weg, bevor die Karte hineinkommt. */ ?>
      <div class="jkarte"
           <?php if ($st && $st['lat'] !== null): ?>
             data-lat="<?= h((string) $st['lat']) ?>" data-lng="<?= h((string) $st['lng']) ?>"
             data-name="<?= h((string) ($st['map_name'] ?? $st['title'])) ?>"
           <?php endif; ?>
           <?php if ($fotoPunkte): ?>
             data-fotos="<?= h(json_encode($fotoPunkte)) ?>"
           <?php endif; ?>>
        <p class="jkartenot"><?= h((string) ($st['map_name'] ?? $st['title'] ?? 'Unterwegs')) ?><br>
          <span style="opacity:.7">Karte braucht Internet</span></p>
      </div>
      <?php if ($fotoPunkte): ?>
        <p class="jkartennote"><?= count($fotoPunkte) === 1
          ? 'Ein Bild dieses Tages weiß, wo es entstanden ist.'
          : count($fotoPunkte) . ' Bilder dieses Tages wissen, wo sie entstanden sind.' ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($fotos): ?>
      <div class="jmosaik">
        <?php foreach ($fotos as $f):
          $hoch = ((int) $f['height']) > ((int) $f['width']);
        ?>
          <figure class="<?= $hoch ? 'hoch' : 'quer' ?>">
            <img src="media.php?art=foto&amp;id=<?= (int) $f['id'] ?>" alt="" loading="lazy" decoding="async">
            <?php if ($f['caption']): ?><figcaption><?= h((string) $f['caption']) ?></figcaption><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </article>
<?php endforeach; ?>
</main>
<?php endif; ?>

<footer class="jfuss">
  <p>Camino Portugués da Costa · 2026</p>
  <p><a href="index.php">Zurück zum Plan</a></p>
</footer>

<script type="application/json" id="journal-karte"><?= json_encode($journalKarte, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/journal.js?v=<?= h((string) @filemtime(__DIR__ . '/assets/journal.js')) ?>"></script>
</body>
</html>
