<?php
declare(strict_types=1);

/**
 * Eine Etappe kann mehr als einen Tag dauern.
 *
 * `stages.date_iso` hält genau ein Datum. Für E1 bis E12 stimmt das — ein Tag,
 * eine Etappe. Das Basislager Porto steht aber für den 17. **und** den 18.09.,
 * und weil dort der 18. eingetragen ist, zeigte die Etappenkarte am Morgen des
 * 18. nur die 146 Schritte von den ersten Stunden dieses Tages. Die 17. — der
 * Anreisetag mit Flug, Metro und erstem Gang durch Porto — kamen nirgends vor.
 *
 * `date_from` sagt jetzt, wo die Etappe anfängt. Bei allen Etappen mit genau
 * einem Tag ist das dasselbe Datum wie `date_iso`; nur Porto fängt früher an.
 */
function migration_020(Database $db): void
{
    $mysql = $db->driver() === 'mysql';

    $db->addColumn('stages', 'date_from', ($mysql ? 'VARCHAR(10)' : 'TEXT') . ' NULL');

    $db->transaction(function (Database $db): void {
        // Der Normalfall: ein Tag, Anfang gleich Ende.
        $db->run('UPDATE stages SET date_from = date_iso WHERE date_from IS NULL AND date_iso IS NOT NULL');

        // Porto: Ankunft am 17.09., Orga-Tag am 18.09.
        $db->run('UPDATE stages SET date_from = ? WHERE seq = 0', ['2026-09-17']);
    });
}
