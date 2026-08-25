<?php
declare(strict_types=1);

/**
 * Nachtrag zu den Käufen: Tüten, Ladegerät, und Socken/Shorts doppelt.
 *
 * Drei Dinge auf einmal, weil sie zusammen aufgefallen sind:
 *
 *  1. **Papiertüten.** Kein Haushaltskram, sondern die Tagesportionen für
 *     Medikamente und Supplements. In der Packliste stehen sie schon
 *     („Papiertütchen (Tagesportionen)") — dort war als Zweck aber nur von
 *     Supplements die Rede. Das wird ergänzt, eine zweite Zeile braucht es
 *     nicht.
 *  2. **Ladegerät.** Braucht keine neue Zeile in der Packliste: „Ladegerät
 *     USB-C + Kabel" steht dort seit dem ersten Tag. Es war nur keins da.
 *  3. **Socken und Shorts gab es zweimal** — je eine frühere Bestellung, für
 *     die kein Beleg vorliegt. Der Betrag wird deshalb verdoppelt und im
 *     Detail steht, welche Hälfte belegt ist und welche angesetzt.
 *
 * Die Verdopplung greift nur, solange der Betrag noch der aus Migration 015
 * ist. Wer die Zahl auf der Seite schon von Hand korrigiert hat, behält sie.
 */
function migration_016(Database $db): void
{
    $db->transaction(function (Database $db): void {
        /* ---- 1 + 2: neue Positionen ---------------------------------- */
        $neu = [
            ['Papiertüten', 'PAKNOR 100 Stück · Tagesportionen für Medikamente · Amazon 16.08.', 5.49],
            ['USB-C-Ladegerät', 'Tupneuf 60 W, 4 Ports · Amazon 19.08.', 9.99],
        ];

        $fehlend = array_values(array_filter(
            $neu,
            static fn (array $z) => $db->one('SELECT id FROM cost_items WHERE name = ?', [$z[0]]) === null
        ));

        if ($fehlend) {
            // Vor die Schätzzeile, zu den anderen Käufen. Die Stelle wird
            // gesucht, nicht geraten.
            $ziel = $db->value('SELECT seq FROM cost_items WHERE name = ?', ['Ausrüstung / Apotheke']);
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
        }

        /* ---- 3: je zweimal bestellt ---------------------------------- */
        $doppelt = [
            ['Wandersocken', 28.63, 57.26,
             'FALKE TK2 · 2× bestellt · belegt 28,63 € inkl. Versand, 1. Bestellung gleich angesetzt'],
            ['Wandershorts', 27.99, 55.98,
             'maamgic 2-in-1 · 2× bestellt · belegt 27,99 €, 1. Bestellung gleich angesetzt'],
        ];
        foreach ($doppelt as [$name, $einfach, $zweifach, $detail]) {
            $db->run(
                'UPDATE cost_items SET amount = ?, detail = ?, updated_at = ?
                  WHERE name = ? AND amount = ?',
                [$zweifach, $detail, date('c'), $name, $einfach]
            );
        }

        /* ---- Packliste: Zweck der Tütchen ---------------------------- */
        // Die Zeile gibt es schon. Nur der Zweck stimmte nur halb.
        $db->run(
            "UPDATE pack_items SET size = ?, purpose = ?, updated_at = ?
              WHERE name = ? AND purpose LIKE 'Supplements pro Tag%'",
            ['klein, braun', 'Supplements und Medikamente pro Tag vorportionieren — spart Platz',
             date('c'), 'Papiertütchen (Tagesportionen)']
        );
    });
}
