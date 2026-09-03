<?php
declare(strict_types=1);

/**
 * Rückflug gebucht — Zeiten, Kosten und der Weg zum Flughafen.
 *
 * Gebucht über Kiwi.com (Booking-ID 838426721, Rechnung 2026-55669288),
 * 235,00 € bezahlt, am 01.10.2026 in zwei Etappen:
 *
 *   SCQ 12:30 → PMI 14:25   Vueling VY3981          80,90 €
 *   PMI 16:20 → FRA 18:50   TUI fly X32433         154,10 €
 *
 * Drei Dinge daran sind wichtiger als die Zeiten selbst:
 *
 *  1. **Es sind zwei getrennte Tickets**, von Kiwi nur zusammengelegt. In
 *     Palma wird selbst umgestiegen: raus, neu einchecken, noch einmal durch
 *     die Kontrolle. Bei Verspätung greift die mitgebuchte „Connection
 *     protection" — zuständig ist dann Kiwi, nicht die Airline.
 *  2. **Das Gepäck ist ein persönliches Gepäckstück, 40 × 30 × 20 cm.** Da
 *     passt kein 30–40-Liter-Rucksack hinein. Das muss vor der Reise geklärt
 *     werden, nicht am Gate.
 *  3. **In Santiago bleibt nur der 30.09.** Das Pilgerbüro öffnet um 9:00,
 *     der Bus geht um 9:45, und die Pilgermesse um 12:00 findet statt,
 *     während der Flieger schon in der Luft ist.
 *
 * Der Flughafenbus stimmte bisher nicht: Empresa Freire fährt die Strecke
 * seit Dezember 2020 nicht mehr, und 3,00 € kostet sie auch nicht. Es ist die
 * Stadtlinie 6A. Nachgeschlagen im September 2026.
 */
function migration_017(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Kosten -------------------------------------------------- */
        // Beschriftung und Stand immer, den Betrag nur, solange das Feld leer
        // ist — eine auf der Seite eingetippte Zahl gehoert Sascha, nicht mir.
        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
            [
                'SCQ → Palma → FRA · Vueling + TUI fly · Kiwi.com 838426721',
                'ok', 'gebucht', date('c'), 'Flug Rückflug',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [235.00, 'Flug Rückflug']
        );

        /* ---- Packliste-Hinweis: die Gepäckfalle ---------------------- */
        // Bisher stand dort nur die Regel für den Hinflug. Für den Rückflug
        // gilt etwas anderes, und zwar etwas Teures.
        $db->run(
            'UPDATE notes SET body = ? WHERE nkey = ?',
            [
                '<b>Zielgewicht: unter 8 kg</b> (max. 10 % Körpergewicht) — du trägst alles selbst '
                . 'über Ø 22 km. <b>Seidenschlafsack</b> statt Schlafsack: Bettwäsche stellen die '
                . 'Häuser, das Inlett ist nur für die Hygiene. Waschen alle 2–3 Tage → wenig Kleidung. '
                . '<b>Hinflug:</b> Messer &amp; Flüssiges &gt;100 ml ins Aufgabegepäck, Powerbank ins '
                . 'Handgepäck. <b>Rückflug — hier aufpassen:</b> im gebuchten Tarif steckt nur '
                . '<b>ein persönliches Gepäckstück, 40 × 30 × 20 cm</b>. Da passt kein 30–40-Liter-'
                . 'Rucksack hinein. Kabinengepäck (55 × 40 × 20 cm, 10 kg) oder Aufgabegepäck muss '
                . '<b>bei Vueling und bei TUI fly getrennt</b> dazugebucht werden — es sind zwei '
                . 'Tickets. Am Gate kostet ein zu großes Stück 60 bis 140 €. '
                . 'Steckdosen PT/ES = EU Typ F → kein Adapter nötig.',
                'pack_intro',
            ]
        );

        /* ---- Heimreise Schritt für Schritt --------------------------- */
        $db->run("DELETE FROM plan_steps WHERE phase = 'ziel'");

        $steps = [
            [1, '30.09.', 'Einzug Praza do Obradoiro',
                'Die letzten 25 km von Padrón enden auf dem Platz vor der Kathedrale. Kein Stempel nötig — hier wird erst mal angekommen.',
                null, 1],

            [2, '30.09.', 'Compostela-Urkunde abholen — heute, nicht morgen',
                'Im <b>Pilgerbüro (Oficina del Peregrino)</b>, Rúa das Carretas 33, täglich <b>9:00 bis 19:00</b> ohne Mittagspause. Du legst die <b>Credencial mit den Stempeln</b> vor — ab Spanien zwei pro Tag, sonst wird die Urkunde verweigert.',
                'Am 1.10. bleibt dafür keine Zeit: das Büro öffnet um 9:00, und um 9:20 musst du los zum Bus. <b>Alles, was in Santiago noch zu erledigen ist, gehört auf den 30.09.</b>', 1],

            [3, '30.09.', 'Pilgermesse — 12:00',
                'Täglich <b>12:00</b> in der Kathedrale. Früh dasein, wenn du sitzen willst.',
                'Auch die geht nur am 30.09.: am 1.10. um 12:00 stehst du schon am Gate. Das Schwenken des Botafumeiro ist nicht garantiert, es hängt an Spenden und Anlässen.', 0],

            [4, '01.10. · 09:20', 'Aufbruch zur Haltestelle',
                'Aus der Altstadt rund <b>10 Minuten zu Fuß</b> bis <b>Praza de Galicia</b> — dort hält die Flughafenlinie. Wer an der <b>Estación Intermodal</b> (Hórreo) einsteigt, braucht 5 Minuten länger im Bus.',
                'Frühstück vorher oder am Flughafen. Für beides in Ruhe reicht die Zeit nicht.', 0],

            [5, '01.10. · 09:45', 'Linie 6A zum Flughafen',
                'Die Flughafenlinie heißt <b>6A</b>, fährt <b>alle 20 bis 30 Minuten</b> zwischen etwa <b>7:00 und 23:00</b> und braucht von Praza de Galicia rund <b>35 Minuten</b>. Fahrschein beim Fahrer, <b>bar</b> — zurzeit rund 1 €.',
                'Ein eigener Flughafentarif von 6 € ist beschlossen, aber noch nicht in Kraft: ein paar Euro <b>Bargeld</b> einstecken, mit Karte kommst du im Bus nicht weiter. <b>Rückfall Taxi:</b> Festpreis rund 23 €, 15 bis 25 Minuten. Den Fahrplan am Vorabend noch einmal ansehen.', 1],

            [6, '01.10. · 10:30', 'Am Flughafen SCQ',
                'Zwei Stunden vor Abflug. Es sind <b>zwei getrennte Tickets</b>, also auch zwei Check-ins: hier bei <b>Vueling</b>, in Palma später noch einmal bei <b>TUI fly</b>.',
                '<b>Am Vorabend prüfen, ob beide Bordkarten da sind</b> — Kiwi schickt für jeden Flug eigene Anweisungen. Und: im Tarif steckt nur ein persönliches Gepäckstück <b>40 × 30 × 20 cm</b>. Passt der Rucksack da nicht hinein, muss vorher Gepäck dazugebucht sein — bei beiden Airlines einzeln.', 1],

            [7, '01.10. · 12:30', 'Santiago → Palma de Mallorca',
                '<b>Vueling VY3981</b> · ab 12:30, an <b>14:25</b> · 1 h 55 Flugzeit.',
                null, 0],

            [8, '01.10. · 14:25', 'Umsteigen in Palma — auf eigene Faust',
                'Knapp <b>zwei Stunden</b> Zeit. Raus aus dem Ankunftsbereich, bei TUI fly neu einchecken, noch einmal durch die Sicherheitskontrolle. Reicht, wenn du zügig gehst — Palma ist ein großer Flughafen mit weiten Wegen.',
                'Wird der erste Flug spät: <b>Kiwi anrufen, nicht die Airline.</b> Die mitgebuchte „Connection protection" deckt genau diesen Fall — die beiden Fluggesellschaften wissen nichts voneinander.', 1],

            [9, '01.10. · 16:20', 'Palma → Frankfurt',
                '<b>TUI fly Deutschland X32433</b> · ab 16:20, an <b>18:50</b> · 2 h 30 Flugzeit.',
                null, 0],

            [10, '01.10. · 18:50', 'Landung Frankfurt',
                'Ende der Reise. Gebucht über <b>Kiwi.com</b>, Booking-ID <b>838426721</b>, Rechnung 2026-55669288 — <b>235,00 € bezahlt</b> (80,90 € Santiago–Palma, 154,10 € Palma–Frankfurt).',
                null, 1],
        ];

        foreach ($steps as [$seq, $time, $title, $body, $note, $accent]) {
            $db->run(
                'INSERT INTO plan_steps (phase, seq, time_label, title, body, note, accent) VALUES (?,?,?,?,?,?,?)',
                ['ziel', $seq, $time, $title, $body, $note, $accent]
            );
        }
    });
}
