<?php
declare(strict_types=1);

/**
 * Erste echte Ausrüstungskäufe in der Kostenliste.
 *
 * Bis hierher stand unter „Ausrüstung / Apotheke" nur eine Schätzung ohne
 * Betrag. Jetzt gibt es Belege, und Belege gehören einzeln in die Liste —
 * eine Sammelposition sagt später nicht mehr, wofür das Geld weg war.
 *
 * Die Beträge sind die bezahlten Endsummen der Belege, nicht die
 * Artikelpreise: bei den Socken sind 5,50 € Versand dabei, und die zwei
 * Müller-Bons vom selben Nachmittag stehen zusammen, weil sie derselbe
 * Einkauf waren.
 *
 * Die Schätzzeile bleibt stehen, aber ihr Text wird ehrlich gemacht: Seife
 * und Pflaster sind gekauft, sie stehen dort nicht mehr als Beispiel für das,
 * was noch kommt.
 */
function migration_015(Database $db): void
{
    $neu = [
        [
            'Wandersocken',
            'FALKE TK2 · 1 Paar · Amazon 19.08. · inkl. 5,50 € Versand',
            28.63,
        ],
        [
            'Wandershorts',
            'maamgic 2-in-1 · 1 Stück · Amazon 16.08.',
            27.99,
        ],
        [
            'Reiseapotheke & Hygiene',
            'Compeed Blasenpflaster, Dr. Bronner\'s Seife, Odol-med3 mini, Deo-Roller · Müller 19.08.',
            20.54,
        ],
    ];

    $db->transaction(function (Database $db) use ($neu): void {
        // Vor die Schätzzeile einsortieren, damit Gekauftes und Geschätztes
        // nicht durcheinandergeraten. Die Position wird gesucht, nicht geraten
        // — auf der laufenden Datenbank kann die Reihenfolge abweichen.
        // Was schon dasteht, wird nicht angefasst — auf einer frischen
        // Datenbank hat der Seed die Zeilen bereits angelegt, und auf der
        // laufenden kann von Hand nachgetragen worden sein.
        $fehlend = array_values(array_filter(
            $neu,
            static fn (array $z) => $db->one('SELECT id FROM cost_items WHERE name = ?', [$z[0]]) === null
        ));
        if (!$fehlend) {
            return;
        }

        // Vor die Schätzzeile einsortieren, damit Gekauftes und Geschätztes
        // nicht durcheinandergeraten. Die Position wird gesucht, nicht geraten
        // — auf der laufenden Datenbank kann die Reihenfolge abweichen.
        $ziel = $db->value(
            'SELECT seq FROM cost_items WHERE name = ?',
            ['Ausrüstung / Apotheke']
        );
        if ($ziel === null) {
            $ziel = (int) ($db->value('SELECT COALESCE(MAX(seq), 0) FROM cost_items') ?? 0) + 1;
        } else {
            $ziel = (int) $ziel;
            $db->run('UPDATE cost_items SET seq = seq + ? WHERE seq >= ?', [count($fehlend), $ziel]);
        }

        foreach ($fehlend as $n => [$name, $detail, $betrag]) {
            $db->run(
                'INSERT INTO cost_items (seq, name, detail, amount, status, status_label, updated_at)
                 VALUES (?,?,?,?,?,?,?)',
                [$ziel + $n, $name, $detail, $betrag, 'ok', 'gekauft', date('c')]
            );
        }

        $db->run(
            'UPDATE cost_items SET detail = ? WHERE name = ?',
            ['Was noch fehlt — Powerbank, Trailrunner, Rucksack', 'Ausrüstung / Apotheke']
        );
    });
}
