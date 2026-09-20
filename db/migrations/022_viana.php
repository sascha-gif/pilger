<?php
declare(strict_types=1);

/**
 * Viana do Castelo gebucht — die dritte Etappenunterkunft.
 *
 * 21.–22.09.2026, Doppelzimmer, 65,00 €, Check-in ab 14:00, Check-out bis
 * 12:00. Damit sind noch neun Etappenorte offen.
 *
 * **Der Name stand nicht in der Bestätigung.** Dort war eine Telefonnummer,
 * +351 258 121 906, und die gehört zum **B&B HOTEL Viana do Castelo**,
 * Estrada da Papanata 74, 4900-470. Dazu passen die Karte in der Bestätigung
 * (an der EN202, östlich der Kathedrale) und das Bild einer Hotellobby mit
 * Parkplatz davor. Sicher ist das nicht — deshalb steht der Weg, auf dem der
 * Name gefunden wurde, mit auf der Seite.
 *
 * Zur Lage: Die Estrada da Papanata liegt laut Straßenregister bei etwa
 * 41,698 / −8,821, der Etappenpunkt der Altstadt bei 41,694 / −8,837. Das sind
 * rund anderthalb Kilometer nach Osten — nach 25 Kilometern aus Esposende ist
 * das kein Nebensatz, und am nächsten Morgen geht es denselben Weg zurück,
 * weil der Camino nach Norden aus der Altstadt herausführt.
 */
function migration_022(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Kosten -------------------------------------------------- */
        // Beschriftung und Stand immer, den Betrag nur, solange nichts drin
        // steht — eine auf der Seite eingetippte Zahl gehoert Sascha.
        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
            [
                'B&B HOTEL Viana do Castelo · Doppelzimmer',
                'ok', 'gebucht', date('c'), 'Viana do Castelo (E3)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [65.00, 'Viana do Castelo (E3)']
        );

        /* ---- Etappe 3 ------------------------------------------------ */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL WHERE seq = 3',
            [
                '<b style="color:#2e7d32">Gebucht:</b> B&amp;B HOTEL Viana do Castelo · '
                . 'Doppelzimmer · <b>65,00 €</b> · Check-in ab 14:00, Check-out bis 12:00',

                '<b>Weg dorthin:</b> Der Küstenweg kommt von Süden und quert den <b>Rio Lima '
                . 'über die Ponte Eiffel</b> in die Altstadt. Von dort noch einmal <b>rund '
                . 'anderthalb Kilometer nach Osten</b>: <b>Estrada da Papanata 74, 4900-470</b>. '
                . 'Nach 25 km aus Esposende ist das kein Nebensatz — und am nächsten Morgen geht '
                . 'es denselben Weg zurück, weil der Camino nach Norden aus der Altstadt führt. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'B%26B%20HOTEL%20Viana%20do%20Castelo" '
                . 'target="_blank" rel="noopener">Karte</a> · '
                . '<a href="tel:+351258121906">+351 258 121 906</a><br>'
                . '<small>Der Name stand nicht in der Bestätigung, nur die Telefonnummer — die '
                . 'gehört zum B&amp;B HOTEL Viana do Castelo, und Karte und Foto der Bestätigung '
                . 'passen dazu. Die Entfernung stammt aus dem Straßenregister, nicht aus der '
                . 'Buchung; für den letzten Kilometer gilt der Kartenlink.</small>',
            ]
        );
    });
}
