<?php
declare(strict_types=1);

/**
 * Oia und Santiago gebucht — und die Etappen dazu verschoben.
 *
 * **Oia (E5):** Hotel-Restaurante Glasgow ***, Estrada Xeral 103, 36309
 * Viladesuso, 23.–24.09.2026, Einzelzimmer, 100,00 € (90,91 + 9,09 MwSt).
 * Der Name stand nicht in der Bestätigung; die Telefonnummer +34 986 361 552
 * führt eindeutig dorthin, und die Gemeinde Oia listet das Haus selbst.
 *
 * **Santiago (E12):** Lemonade Stays, Rúa das Galeras 44, 15705,
 * 30.09.–01.10.2026, Standard-Einzelzimmer, 76,95 € bezahlt,
 * Buchungsnr. 6412320933. Rund 385 m von der Praza do Obradoiro.
 *
 * Damit sind noch sechs Etappenorte offen — und die beiden kritischen,
 * Oia und Santiago, sind es nicht mehr.
 *
 * ## Warum sich E5 und E6 ändern
 *
 * Viladesuso ist eine Pfarrei der Gemeinde Oia, liegt aber **nicht** in Oia:
 * die Pfarrkirche San Miguel steht bei 42,0367 / −8,8722, das Kloster von Oia
 * bei 42,0006 / −8,8756. Das sind **4 km weiter nördlich**, in Laufrichtung.
 * Die Zahlen des Hauses passen dazu (12 km bis Baiona, 15 km bis A Guarda).
 *
 * Also wandern 4 km von E6 nach E5:
 *
 *   E5 Caminha → Oia     23 km → 27 km, Rest bis Santiago 148 → 144
 *   E6 Oia → Baiona      18 km → 14 km, Rest bleibt 130
 *
 * Die Summe bleibt 266 km. Der Tag nach Oia wird damit der zweitlängste der
 * Reise, der danach der kürzeste — das gehört auf die Karte, bevor er am
 * Morgen des 23. losgeht und sich wundert.
 *
 * Die 4 km sind aus der Koordinate der Pfarrkirche gerechnet, nicht aus der
 * Hausnummer. Auf ein paar hundert Meter genau ist das nicht, auf die
 * Richtung schon.
 */
function migration_028(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Kosten -------------------------------------------------- */
        $kosten = [
            ['Oia (E5)', 'Hotel-Restaurante Glasgow ★★★, Viladesuso · Einzelzimmer · inkl. 9,09 € MwSt', 100.00],
            ['Santiago (E12, 1 N)', 'Lemonade Stays · Standard-Einzelzimmer · Buchungsnr. 6412320933', 76.95],
        ];
        foreach ($kosten as [$name, $detail, $betrag]) {
            // Beschriftung und Stand immer, den Betrag nur, solange nichts
            // drin steht — eine auf der Seite eingetippte Zahl gehoert Sascha.
            $db->run(
                'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
                [$detail, 'ok', 'gebucht', date('c'), $name]
            );
            $db->run('UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL', [$betrag, $name]);
        }

        /* ---- Etappe 5: Oia ------------------------------------------- */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, dist = ?, km_walk = ?, km_big = ?, map_meta = ?,
                    booking_url = NULL, booking_label = NULL WHERE seq = 5',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Hotel-Restaurante Glasgow ★★★, Viladesuso · '
                . 'Einzelzimmer · <b>100,00 €</b> (90,91 + 9,09 MwSt)',

                '<b>Achtung, die Etappe ist länger als geplant.</b> Viladesuso gehört zur Gemeinde '
                . 'Oia, liegt aber <b>rund 4 km nördlich des Klosters</b> — in Laufrichtung. Aus '
                . '23 km werden damit <b>27 km</b>, und der Tag danach nach Baiona wird um dieselben '
                . '4 km kürzer. Nach der Fähre über den Minho und dem Grenzübertritt ist das der '
                . 'zweitlängste Tag der Reise.<br>'
                . '<b>Estrada Xeral 103, 36309 Viladesuso</b> — direkt an der Küstenstraße, '
                . 'Restaurant im Haus. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'Hotel%20Restaurante%20Glasgow%2C%20Viladesuso%2C%20Oia" '
                . 'target="_blank" rel="noopener">Karte</a> · '
                . '<a href="tel:+34986361552">+34 986 361 552</a><br>'
                . '<small><b>Nicht stornierbar, Daten nicht änderbar.</b> Ab hier gilt Spanien: '
                . '<b>zwei Stempel pro Tag</b>. Die 4 km sind aus der Koordinate der Pfarrkirche '
                . 'gerechnet, nicht aus der Hausnummer — auf die Richtung ist Verlass, auf ein paar '
                . 'hundert Meter nicht.</small>',

                '27 km · Fähre/Taxiboot über den Minho · ab hier 2 Stempel/Tag',
                27.0,
                '144',
                '27 km · noch 144 km',
            ]
        );

        /* ---- Etappe 6: die 4 km fehlen hier jetzt -------------------- */
        $db->run(
            'UPDATE stages SET dist = ?, km_walk = ?, map_meta = ?, note = ? WHERE seq = 6',
            [
                '14 km · kürzeste Etappe',
                14.0,
                '14 km · noch 130 km',
                'Kurz, weil das Bett schon 4 km hinter Oia stand. Nach dem langen Tag davor '
                . 'ein Geschenk — Baiona ist früh erreicht. Parador wäre das teure Highlight, '
                . 'als bewusste Ausnahme behaltbar.',
            ]
        );

        /* ---- Etappe 9 ist nicht mehr die kuerzeste -------------------- */
        // E6 hat sie mit 14 km ueberholt. Zwei „kuerzeste Etappen" auf einer
        // Seite sind eine zu viel.
        $db->run(
            "UPDATE stages SET dist = '15 km · kurze Etappe',
                    map_meta = '15 km · noch 66 km · kurze Etappe' WHERE seq = 9"
        );

        /* ---- Etappe 12: Santiago ------------------------------------- */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL WHERE seq = 12',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Lemonade Stays · Standard-Einzelzimmer · '
                . '<b>76,95 €</b> bezahlt · Buchungsnr. 6412320933',

                '<b>Rúa das Galeras 44, 15705</b> — rund <b>385 m von der Praza do Obradoiro</b>, '
                . 'am Rand der Altstadt. Check-in ab 15:00, Check-out am 01.10. bis 11:00: der Bus '
                . 'zum Flughafen geht um 9:45, das passt. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'Lemonade%20Stays%2C%20R%C3%BAa%20das%20Galeras%2044%2C%20Santiago%20de%20Compostela" '
                . 'target="_blank" rel="noopener">Karte</a> · '
                . '<a href="tel:+34981072903">+34 981 072 903</a><br>'
                . '<b>Alles Wichtige passiert am 30.09.:</b> Compostela-Urkunde im Pilgerbüro '
                . '(Rúa das Carretas 33, 9:00–19:00) und Pilgermesse um 12:00. Am 01.10. sitzt du '
                . 'zu der Zeit im Flieger.<br>'
                . '<small><b>Nicht stornierbar, Daten nicht änderbar.</b></small>',
            ]
        );
    });
}
