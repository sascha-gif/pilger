<?php
declare(strict_types=1);

/**
 * Viana do Castelo: die vollständige Bestätigung nachgetragen.
 *
 * In der ersten Bestätigung stand kein Name, nur die Telefonnummer. Daraus war
 * das **B&B HOTEL Viana do Castelo** erschlossen — das stimmt, die zweite
 * Bestätigung nennt es ausdrücklich, mit drei Sternen und 8,7 Punkten. Der
 * Hinweis, wie der Name zustande kam, kann damit raus.
 *
 * Zwei Dinge waren dagegen falsch oder fehlten:
 *
 *  1. **Die Postleitzahl.** Aus dem Straßenregister hatte ich 4900-470
 *     übernommen — die Bestätigung sagt **4900-462**. Eine Straße kann mehrere
 *     Postleitzahlen haben; hier gilt die der Buchung.
 *  2. **Es ist ein „Partnerangebot".** Der Buchungsvertrag besteht nicht mit
 *     dem Hotel und nicht mit Booking, sondern mit **LINKALL HONGKONG
 *     LIMITED**. Änderungen an der Buchung sind damit **nicht möglich** —
 *     nachfragen kann man beim Hotel, zugesagt ist nichts. Zuständig bleibt
 *     der Kundenservice von Booking.com, nicht der Empfang vor Ort. Das gehört
 *     auf die Seite, bevor es um 18 Uhr an der Rezeption zum Thema wird.
 *
 * Dazu die **Check-in-Nummer B1541743170**: am Empfang vorzuzeigen, zusammen
 * mit einem Lichtbildausweis, der auf denselben Namen lautet.
 */
function migration_023(Database $db): void
{
    $db->transaction(function (Database $db): void {
        $db->run(
            'UPDATE stages SET target = ? WHERE seq = 3',
            [
                '<b style="color:#2e7d32">Gebucht:</b> B&amp;B HOTEL Viana do Castelo ★★★ · '
                . 'Doppelzimmer · <b>65,00 €</b> · Check-in ab 14:00, Check-out bis 12:00',
            ]
        );
        $db->run(
            'UPDATE stages SET note = ? WHERE seq = 3',
            [
                '<b>Weg dorthin:</b> Der Küstenweg kommt von Süden und quert den <b>Rio Lima '
                . 'über die Ponte Eiffel</b> in die Altstadt. Von dort noch einmal <b>rund '
                . 'anderthalb Kilometer nach Osten</b>: <b>Estrada da Papanata 74, 4900-462</b>. '
                . 'Nach 25 km aus Esposende ist das kein Nebensatz — und am nächsten Morgen geht '
                . 'es denselben Weg zurück, weil der Camino nach Norden aus der Altstadt führt. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'B%26B%20HOTEL%20Viana%20do%20Castelo" '
                . 'target="_blank" rel="noopener">Karte</a> · '
                . '<a href="tel:+351258121906">+351 258 121 906</a><br>'
                . '<b>Am Empfang:</b> Check-in-Nummer <b>B1541743170</b> und ein Ausweis auf '
                . 'denselben Namen.<br>'
                . '<small><b>Partnerangebot — hier aufpassen:</b> Der Buchungsvertrag besteht '
                . 'nicht mit dem Hotel, sondern mit LINKALL HONGKONG LIMITED. <b>Änderungen an '
                . 'der Buchung sind nicht möglich.</b> Fragen kann man das Hotel, zugesagt ist '
                . 'nichts. Klemmt etwas, ist der Kundenservice von Booking.com zuständig und '
                . 'nicht der Empfang vor Ort — Bestätigungsnummer und PIN aus der Mail '
                . 'bereithalten.</small>',
            ]
        );
    });
}
