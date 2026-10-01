<?php
declare(strict_types=1);

/**
 * Was der Camino wirklich gekostet hat — soweit die Karte es weiß.
 *
 * Vorlage sind die Umsätze des N26-Kontos vom **21.09. bis 01.10.**. Das ist
 * weniger, als es klingt, und das gehört dazugeschrieben:
 *
 *  - **Der 17. bis 20.09. fehlt** — Anreise, Porto, Vila do Conde, Esposende.
 *    Die Auszüge fangen erst am 21. an.
 *  - **Bargeld fehlt vollständig.** Die Fähre über den Minho, der Bus zum
 *    Flughafen, Stempel-Kaffees, Trinkgeld: alles bar, alles unsichtbar.
 *  - Was nicht zur Reise gehört, ist **nicht** mitgezählt: Klarna (21,91 und
 *    25,96), Netflix (13,99), N26-Kontogebühr (3,00) und die fehlgeschlagene
 *    Amazon-Abbuchung.
 *  - Was schon in der Liste stand, ist **nicht doppelt** gezählt: die beiden
 *    Booking-Abbuchungen (100,00 für Oia, 76,95 für Santiago) und
 *    „ALDA ESTACION VIGO" (57,00) sind die Kartenbuchungen zu den
 *    Unterkunftszeilen weiter oben.
 *
 * Zwei Posten kommen neu dazu, weil sie eindeutig sind und bisher fehlten:
 * die **ERGO-Reiseversicherung** (186,00 € am 21.09.) und das
 * **Pilgerbüro** (7,00 € am 30.09., Compostela und Urkunde).
 *
 * Die beiden Schätzzeilen bekommen echte Zahlen, bleiben aber grau: sie sind
 * belegt, aber nicht vollständig.
 *
 *  - **Verpflegung 236,53 €.** Davon sind 216,05 € eindeutig — Froiz, SPAR,
 *    Eroski, Mercadona, Aldi, Alcampo, Alimentación, die Restaurants. Die
 *    restlichen 20,48 € sind nach Name und Betrag zugeordnet und damit ein
 *    Urteil, kein Beleg: zwei Tankstellen (5,20 und 2,60), ein Kaffee in
 *    Portugal (1,50), das Frühstück am Hórreo (6,68) und „Sabor a España"
 *    (4,50) — Letzteres kann auch ein Mitbringsel gewesen sein.
 *  - **Souvenirs 68,25 €:** Bordón (41,00), eine Boutique in Santiago (15,45)
 *    und Quintana Souvenirs (11,80).
 *
 * Nicht zugeordnet, weil nicht zu erkennen: „LUSIGALIA TOURS 2" (25,00),
 * „NH Hotels" (1,50), „Hotel San Luis Co" (9,50), „HOTEL LEMONADE" (1,50)
 * und eine N26-Buchung über 36,35. Zusammen 73,85 € — die Felder lassen sich
 * in der Seite von Hand nachtragen, sobald klar ist, was es war.
 */
function migration_046(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Platz schaffen für zwei neue Zeilen ----------------------- */
        /* Reiseversicherung und Pilgerbüro gehören zu den Gebühren der
           Reise, also vor den Pilgerpass und nicht ans Ende der Liste. */
        $db->run('UPDATE cost_items SET seq = seq + 2 WHERE seq >= ?', [16]);

        $neu = [
            [16, 'Reiseversicherung', 'ERGO · abgebucht 21.09.', 186.00, 'ok', 'bezahlt'],
            [17, 'Pilgerbüro Santiago', 'Compostela und Urkunde · 30.09.', 7.00, 'ok', 'bezahlt'],
        ];
        foreach ($neu as [$seq, $name, $detail, $betrag, $status, $label]) {
            if ((int) $db->value('SELECT COUNT(*) FROM cost_items WHERE name = ?', [$name]) > 0) {
                continue;   // schon da — nichts doppelt anlegen
            }
            $db->run(
                'INSERT INTO cost_items (seq, name, detail, amount, status, status_label) VALUES (?,?,?,?,?,?)',
                [$seq, $name, $detail, $betrag, $status, $label]
            );
        }

        /* ---- Die Schätzzeilen bekommen echte Zahlen -------------------- */
        $db->run(
            'UPDATE cost_items SET detail = ?, amount = ?, status = ?, status_label = ? WHERE name = ?',
            [
                'Supermarkt und Restaurants 21.09.–01.10. · 216,05 € belegt, '
                . '20,48 € nach Name zugeordnet (Tankstellen, Kaffee, Hórreo) · ohne Bargeld',
                236.53,
                'est',
                'Karte, ab 21.09.',
                'Verpflegung',
            ]
        );

        $db->run(
            'UPDATE cost_items SET name = ?, detail = ?, amount = ?, status = ?, status_label = ? WHERE name = ?',
            [
                'Souvenirs / Sonstiges',
                'Bordón 41,00 · Boutique Santiago 15,45 · Quintana Souvenirs 11,80',
                68.25,
                'est',
                'Karte, 30.09.–01.10.',
                'Puffer / Sonstiges',
            ]
        );

        /* ---- Nahverkehr bleibt leer, und zwar mit Grund ---------------- */
        $db->run(
            'UPDATE cost_items SET detail = ?, status_label = ? WHERE name = ?',
            [
                'Metro Porto · Fähre über den Minho · Bus zum Flughafen — alles bar bezahlt, '
                . 'steht auf keinem Kontoauszug',
                'bar, nicht belegt',
                'Nahverkehr',
            ]
        );

        /* ---- „Was noch fehlt" fehlt nicht mehr ------------------------ */
        /* Die Reise ist gelaufen; die Zeile fragte noch nach Ausrüstung, die
           längst gekauft und getragen wurde. Was sie gekostet hat, steht auf
           keinem der Auszüge hier — die sind von September, gekauft wurde im
           August. */
        $db->run(
            'UPDATE cost_items SET name = ?, detail = ?, status_label = ? WHERE name = ?',
            [
                'Ausrüstung (Rucksack, Schuhe)',
                'Vor der Reise gekauft · Belege liegen nicht in den Auszügen vom September',
                'nachzutragen',
                'Ausrüstung / Apotheke',
            ]
        );

        /* ---- Ein Rest vom alten Busfahrplan --------------------------- */
        /* Migration 044 hat die Abfahrt auf 9:30 korrigiert, aber am Quartier
           in Santiago stand weiter die 9:45. */
        $db->run(
            'UPDATE lodgings SET hinweis = ? WHERE name = ?',
            [
                'Bezahlt, nicht stornierbar, keine Änderungen. Der Check-out um 11:00 passt zum '
                . 'Bus 6A um 9:30 ab Hórreo.',
                'Lemonade Stays',
            ]
        );

        /* ---- Der Text unter der Tabelle stimmt nicht mehr -------------- */
        $db->run(
            'UPDATE notes SET body = ? WHERE nkey = ?',
            [
                'Die Zeilen mit <b>grauem</b> Status sind belegt, aber nicht vollständig: '
                . 'sie stammen aus den Kartenumsätzen vom <b>21.09. bis 01.10.</b> '
                . '<b>Der 17. bis 20.09. fehlt</b> — die Auszüge fangen später an —, und '
                . '<b>Bargeld taucht nirgends auf</b>: Fähre, Bus, Stempel-Kaffees, Trinkgeld. '
                . 'Nicht mitgezählt sind Klarna, Netflix und Kontogebühren; die Booking-Abbuchungen '
                . 'für Oia und Santiago und die für Vigo stehen schon oben bei den Unterkünften und '
                . 'sind deshalb nicht doppelt drin.<br>'
                . 'Nicht zugeordnet, weil aus dem Namen nicht zu erkennen: Lusigalia Tours (25,00), '
                . 'NH Hotels (1,50), Hotel San Luis (9,50), Hotel Lemonade (1,50) und eine '
                . 'N26-Buchung über 36,35 — zusammen <b>73,85 €</b>. '
                . 'Jedes Feld lässt sich hier von Hand ändern, die Summe rechnet sofort nach.',
                'cost_outro',
            ]
        );
    });
}
