<?php
declare(strict_types=1);

/**
 * Gesundheitsdaten aus dem Google-Health-Konto (vormals Fitbit).
 *
 * Ein Datensatz je Tag. Fehlt ein Tag, hat das Gerät nicht synchronisiert —
 * das ist ausdrücklich nicht dasselbe wie „null Schritte" und wird deshalb
 * auch nicht als Null gespeichert, sondern gar nicht.
 *
 * Dazu bekommen die Trainingswochen feste Datumsgrenzen. Bisher stand dort nur
 * „11.–17.08." als Text; um Schritte einer Woche zuzuordnen, braucht es Daten,
 * mit denen man rechnen kann.
 */
function migration_008(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $tag   = $mysql ? 'VARCHAR(10)' : 'TEXT';
    $str   = $mysql ? 'VARCHAR(32)' : 'TEXT';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS health_days (
        day_iso $tag NOT NULL PRIMARY KEY,
        steps INT NULL,
        kcal INT NULL,
        hr_avg INT NULL,
        hr_min INT NULL,
        hr_max INT NULL,
        hr_ruhe INT NULL,
        aktiv_min INT NULL,
        distanz_m INT NULL,
        geholt_at $str NULL
    )$tail");

    $db->addColumn('weight_weeks', 'von_iso', "$tag NULL");
    $db->addColumn('weight_weeks', 'bis_iso', "$tag NULL");

    // seq => [von, bis] — aus dem Countdown-Plan. Die Zeilen beginnen bei
    // seq = 1 („Start"), nicht bei 0; die Zuordnung ist an den Feldern
    // `period` der Seed-Daten geprüft.
    $wochen = [
        1 => ['2026-08-09', '2026-08-10'],   // Start: der Wiegetag selbst
        2 => ['2026-08-11', '2026-08-17'],   // W1
        3 => ['2026-08-18', '2026-08-24'],   // W2
        4 => ['2026-08-25', '2026-08-31'],   // W3
        5 => ['2026-09-01', '2026-09-07'],   // W4
        6 => ['2026-09-08', '2026-09-14'],   // W5
        7 => ['2026-09-15', '2026-09-17'],   // W6, Taper bis zum Abflug
    ];

    $db->transaction(function (Database $db) use ($wochen) {
        foreach ($wochen as $seq => [$von, $bis]) {
            $db->run('UPDATE weight_weeks SET von_iso = ?, bis_iso = ? WHERE seq = ?', [$von, $bis, $seq]);
        }
    });
}
