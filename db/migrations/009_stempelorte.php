<?php
declare(strict_types=1);

/**
 * Wo bekommt man den Stempel?
 *
 * Bewusst überwiegend als **Kartensuche** und nicht als feste Adresse.
 * Albergues machen zu, ziehen um, wechseln den Betreiber — eine Adresse, die
 * heute stimmt, kann im September falsch sein, und dann steht man um 17 Uhr
 * vor der falschen Tür. Ein Suchlink zeigt immer, was es jetzt gerade gibt,
 * und zwar im richtigen Ort.
 *
 * Feste Adressen stehen nur dort, wo sie belegt und ortsfest sind: die
 * Kathedrale in Porto und das Pilgerbüro in Santiago. Beides sind Gebäude,
 * die nicht umziehen.
 *
 * `art`:
 *   fest  — benannter Ort mit Adresse
 *   suche — Kartensuche im Zielort
 */
function migration_009(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $kurz  = $mysql ? 'VARCHAR(16)'  : 'TEXT';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS stamp_spots (
        id $pk,
        stage_id INT NOT NULL,
        seq INT NOT NULL DEFAULT 0,
        art $kurz NOT NULL DEFAULT 'suche',
        name $str NOT NULL,
        adresse $str NULL,
        suche $str NULL,
        note $str NULL
    )$tail");

    $db->transaction(function (Database $db) {
        $db->exec('DELETE FROM stamp_spots');

        // Ab Oia liegt der Weg in Spanien — dort heißen die Dinge anders,
        // und danach muss auch gesucht werden.
        $spanisch = static fn (int $seq): bool => $seq >= 5;

        foreach ($db->all('SELECT id, seq, map_name FROM stages ORDER BY seq') as $st) {
            $id  = (int) $st['id'];
            $seq = (int) $st['seq'];
            $ort = (string) $st['map_name'];

            // Porto steht auf der Karte als „Porto — Sé Kathedrale".
            if ($seq === 0) {
                $ort = 'Porto';
            }

            $n = 0;
            $lege = static function (array $zeile) use ($db, $id, &$n): void {
                $n++;
                $db->run(
                    'INSERT INTO stamp_spots (stage_id, seq, art, name, adresse, suche, note) VALUES (?,?,?,?,?,?,?)',
                    [$id, $n, $zeile['art'], $zeile['name'], $zeile['adresse'] ?? null,
                     $zeile['suche'] ?? null, $zeile['note'] ?? null]
                );
            };

            /* ---- Die beiden festen Punkte ------------------------------- */
            if ($seq === 0) {
                $lege([
                    'art'     => 'fest',
                    'name'    => 'Sé do Porto — Credencial und erster Stempel',
                    'adresse' => 'Terreiro da Sé, 4050-573 Porto',
                    'note'    => 'Ausgabe im Sekretariat bzw. Shop rechts neben der Kathedrale. Reisepass mitnehmen, rund 2 €. Nur während der Öffnungszeiten.',
                ]);
            }
            if ($seq === 12) {
                $lege([
                    'art'     => 'fest',
                    'name'    => 'Oficina del Peregrino — Compostela abholen',
                    'adresse' => 'Rúa das Carretas 33, 15703 Santiago de Compostela',
                    'note'    => 'Täglich 9:00–19:00, ohne Mittagspause. Hier wird die Credencial vorgelegt.',
                ]);
            }

            /* ---- Und für jeden Ort die drei verlässlichen Anlaufstellen -- */
            if ($spanisch($seq)) {
                $lege(['art' => 'suche', 'name' => 'Albergue de Peregrinos',
                       'suche' => 'Albergue de Peregrinos ' . $ort,
                       'note' => 'Stempelt immer — auch wenn du dort nicht schläfst.']);
                $lege(['art' => 'suche', 'name' => 'Oficina de Turismo',
                       'suche' => 'Oficina de Turismo ' . $ort]);
                $lege(['art' => 'suche', 'name' => 'Kirche',
                       'suche' => 'Iglesia ' . $ort]);
            } else {
                $lege(['art' => 'suche', 'name' => 'Albergue de Peregrinos',
                       'suche' => 'Albergue de Peregrinos ' . $ort,
                       'note' => 'Stempelt immer — auch wenn du dort nicht schläfst.']);
                $lege(['art' => 'suche', 'name' => 'Posto de Turismo',
                       'suche' => 'Posto de Turismo ' . $ort]);
                $lege(['art' => 'suche', 'name' => 'Kirche',
                       'suche' => 'Igreja Matriz ' . $ort]);
            }
        }
    });
}
