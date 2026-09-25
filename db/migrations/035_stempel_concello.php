<?php
declare(strict_types=1);

/**
 * Das Concello stempelt auch.
 *
 * In Galicien ist das Rathaus eine der verlässlicheren Adressen: es hat
 * Öffnungszeiten, es liegt im Ortskern, und es stempelt ohne Gegenleistung.
 * Bisher standen je Ort nur Albergue, Turismo und Kirche da — die Kirche ist
 * tagsüber oft zu, das Turismo hat Saison.
 *
 * Nur für die spanischen Etappen. In Portugal heißt es *Câmara Municipal* und
 * ist dort nicht die übliche Anlaufstelle; ausgedacht wird das hier nicht.
 */
function migration_035(Database $db): void
{
    $db->transaction(function (Database $db): void {
        foreach ($db->all('SELECT id, seq, map_name FROM stages WHERE seq >= 5 ORDER BY seq') as $st) {
            $id  = (int) $st['id'];
            $ort = (string) $st['map_name'];
            if ($ort === '') {
                continue;
            }
            $da = $db->one(
                'SELECT id FROM stamp_spots WHERE stage_id = ? AND name = ?',
                [$id, 'Concello']
            );
            if ($da !== null) {
                continue;
            }
            $max = (int) $db->value('SELECT COALESCE(MAX(seq), 0) FROM stamp_spots WHERE stage_id = ?', [$id]);
            $db->run(
                'INSERT INTO stamp_spots (stage_id, seq, art, name, adresse, suche, note) VALUES (?,?,?,?,?,?,?)',
                [$id, $max + 1, 'suche', 'Concello', null, 'Concello de ' . $ort,
                 'Das Rathaus stempelt, kostenlos — zu Bürozeiten.'],
            );
        }
    });
}
