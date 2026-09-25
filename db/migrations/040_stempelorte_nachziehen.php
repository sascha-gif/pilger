<?php
declare(strict_types=1);

/**
 * Die Stempelsuchen zeigten noch auf Baiona.
 *
 * Gefunden hat das der Kaltstart-Vergleich, nicht ein Blick auf die Seite: in
 * der laufenden Datenbank suchte E6 nach „Albergue de Peregrinos **Baiona**",
 * in einer frischen nach Nigrán. Am 24. hat er also, während er in Nigrán
 * stand, Suchknöpfe angeboten bekommen, die auf einen Ort sieben Kilometer
 * zurück zeigten.
 *
 * **Die Ursache ist eine Kopie.** Migration 009 hat die Suchtexte einmal aus
 * `stages.map_name` zusammengesetzt und als eigene Zeilen abgelegt. Wird eine
 * Etappe später umbenannt — Baiona → Nigrán in 032, und heute gleich vier auf
 * einmal in 039 —, ändert sich der Name, die Kopie aber nicht. Im Seed stand
 * der neue Name längst, deshalb war eine frische Datenbank richtig und die
 * laufende falsch.
 *
 * Hier werden die vier Standardsuchen aller Etappen aus dem aktuellen
 * `map_name` neu geschrieben. Die beiden festen Adressen — die Sé in Porto und
 * das Pilgerbüro in Santiago — bleiben unangetastet; das sind Gebäude, keine
 * Suchen.
 *
 * **Sauber wäre es, die Suchen gar nicht zu speichern**, sondern beim Anzeigen
 * aus `map_name` zu bilden. Dann kann so etwas nicht mehr auseinanderlaufen.
 * Das ist ein Umbau an `stamp_spots` und gehört nicht in eine Nacht, in der er
 * morgen früh losläuft — es steht in HANDOVER.md.
 */
function migration_040(Database $db): void
{
    $db->transaction(function (Database $db): void {
        foreach ($db->all('SELECT id, seq, map_name FROM stages ORDER BY seq') as $st) {
            $ort = trim((string) $st['map_name']);
            if ($ort === '') {
                continue;
            }
            // Porto steht auf der Karte als „Porto — Sé Kathedrale".
            if ((int) $st['seq'] === 0) {
                $ort = 'Porto';
            }
            // Bis einschließlich Caminha ist es Portugal, danach Spanien.
            $spanisch = (int) $st['seq'] >= 5;

            $suchen = $spanisch
                ? [
                    'Albergue de Peregrinos' => 'Albergue de Peregrinos ' . $ort,
                    'Oficina de Turismo'     => 'Oficina de Turismo ' . $ort,
                    'Kirche'                 => 'Iglesia ' . $ort,
                    'Concello'               => 'Concello de ' . $ort,
                ]
                : [
                    'Albergue de Peregrinos' => 'Albergue de Peregrinos ' . $ort,
                    'Posto de Turismo'       => 'Posto de Turismo ' . $ort,
                    'Kirche'                 => 'Igreja Matriz ' . $ort,
                ];

            foreach ($suchen as $name => $suche) {
                $db->run(
                    "UPDATE stamp_spots SET suche = ?
                      WHERE stage_id = ? AND name = ? AND art = 'suche'",
                    [$suche, (int) $st['id'], $name]
                );
            }
        }
    });
}
