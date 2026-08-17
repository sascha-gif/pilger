<?php
declare(strict_types=1);

/**
 * Abhaken, was hinter einem liegt.
 *
 * Etappen und Ausrüstungspunkte bekommen ein Häkchen. Dazu je Etappe die
 * echten Kilometer (bisher stand nur „24 km" als Text im Feld `dist` — daraus
 * lässt sich nichts rechnen) und die Zahl der Stempel, die der Tag braucht:
 * in Portugal einer, ab der spanischen Grenze zwei. Wer da einen vergisst,
 * bekommt in Santiago keine Compostela.
 */
function migration_005(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $str   = $mysql ? 'VARCHAR(32)' : 'TEXT';
    $dec   = $mysql ? 'DECIMAL(6,1)' : 'REAL';

    $db->addColumn('stages', 'done',          'INT NOT NULL DEFAULT 0');
    $db->addColumn('stages', 'done_at',       "$str NULL");
    $db->addColumn('stages', 'km_walk',       "$dec NULL");
    $db->addColumn('stages', 'date_iso',      ($mysql ? 'VARCHAR(10)' : 'TEXT') . ' NULL');
    $db->addColumn('stages', 'stamps_needed', 'INT NOT NULL DEFAULT 0');
    $db->addColumn('stages', 'stamps_done',   'INT NOT NULL DEFAULT 0');

    $db->addColumn('equipment_items', 'checked',    'INT NOT NULL DEFAULT 0');
    $db->addColumn('equipment_items', 'checked_at', "$str NULL");

    // seq => [km, Stempel an diesem Tag, Datum]
    // Die Summe der Kilometer ist 266 — dieselbe Zahl, die oben im Kopf steht.
    $plan = [
        0  => [0.0,  1, '2026-09-18'],   // Porto: Credencial holen, erster Stempel
        1  => [20.0, 1, '2026-09-19'],
        2  => [24.0, 1, '2026-09-20'],
        3  => [25.0, 1, '2026-09-21'],
        4  => [26.0, 1, '2026-09-22'],
        5  => [23.0, 2, '2026-09-23'],   // über den Minho nach Spanien
        6  => [18.0, 2, '2026-09-24'],
        7  => [27.0, 2, '2026-09-25'],
        8  => [22.0, 2, '2026-09-26'],
        9  => [15.0, 2, '2026-09-27'],
        10 => [22.0, 2, '2026-09-28'],
        11 => [19.0, 2, '2026-09-29'],
        12 => [25.0, 2, '2026-09-30'],
    ];

    $db->transaction(function (Database $db) use ($plan) {
        foreach ($plan as $seq => [$km, $stamps, $datum]) {
            $db->run(
                'UPDATE stages SET km_walk = ?, stamps_needed = ?, date_iso = ? WHERE seq = ?',
                [$km, $stamps, $datum, $seq]
            );
        }
    });
}
