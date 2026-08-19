<?php
declare(strict_types=1);

/**
 * „Vor der Abreise" — Erledigungen, die nichts mit Packen zu tun haben.
 *
 * Bewusst eine eigene Tabelle und nicht eine dreizehnte Packlisten-Kategorie:
 * ein Zahnarzttermin ist kein Gegenstand im Rucksack, und er würde den
 * Packfortschritt verfälschen.
 *
 * `notiz` ist frei beschreibbar — dort steht der Termin, sobald er steht.
 * Ausgedacht wird hier keiner: die Zeilen kommen leer, Datum trägt Sascha ein.
 */
function migration_012(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS todos (
        id $pk,
        seq INT NOT NULL DEFAULT 0,
        titel $str NOT NULL,
        zweck $str NULL,
        notiz $str NULL,
        done INT NOT NULL DEFAULT 0,
        done_at $str NULL,
        created_at $str NULL
    )$tail");

    if ((int) $db->value('SELECT COUNT(*) FROM todos') > 0) {
        return;
    }

    $punkte = [
        ['Zahnarzttermin',  'Kontrolle vorher — Zahnschmerzen unterwegs sind kaum zu lösen'],
        ['Frisörtermin',    'Kurz vor dem Abflug'],
        ['Infusion',        null],
        ['Osteopathe',      'Rücken und Hüfte vor 266 km durchsehen lassen'],
        ['Decathlon',       'Was auf der Packliste noch fehlt'],
    ];

    $seq = 0;
    foreach ($punkte as [$titel, $zweck]) {
        $seq++;
        $db->run(
            'INSERT INTO todos (seq, titel, zweck, created_at) VALUES (?,?,?,?)',
            [$seq, $titel, $zweck, date('c')]
        );
    }
}
