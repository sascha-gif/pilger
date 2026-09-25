<?php
declare(strict_types=1);

/**
 * Was die beiden Gastgeber geschrieben haben.
 *
 * **Caldas de Reis** liefert nach, was bisher fehlte: die **Buchungsnummer
 * 6247972800**. Dazu die Adresse in der Form, wie sie am Haus steht —
 * *Select Real Apartments, Rúa Real 49 Bajo* — und die Bestätigung dessen, was
 * bisher nur eine Vermutung aus der Postleitzahl war: *calle peatonal del
 * centro del Pueblo*, also **Fußgängerzone im Ortskern**. „Bajo" ist das
 * Erdgeschoss.
 *
 * **Padrón** schickt eine Warnung, und die gehört auf die Seite: *„Wir
 * verlangen keine zusätzliche Zahlung über die Buchung hinaus. Die Kommunikation
 * läuft über die Plattform von booking.com."* Der Gastgeber kommt damit einer
 * bekannten Masche zuvor — gefälschte Nachrichten, die wie Booking aussehen und
 * nach Kartendaten fragen. Wer unterwegs auf dem Telefon liest, tippt so etwas
 * schnell weg. Deshalb steht es dort, wo er am Abend ohnehin hinschaut.
 */
function migration_041(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- E9: Caldas de Reis ---------------------------------------- */
        $db->run(
            'UPDATE stages SET target = ?, note = ? WHERE seq = 9',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Apartamento Loft II Select Real · '
                . '<b>65,96 €</b> (inkl. 6,00 € MwSt) · Buchungsnr. 6247972800',

                '<b>Rúa Real 49 Bajo, 36650 Caldas de Reis</b> — am Haus steht '
                . '<b>„Select Real Apartments"</b>, „Bajo" ist das Erdgeschoss. Der Gastgeber '
                . 'schreibt dazu: <i>calle peatonal del centro del Pueblo</i> — '
                . '<b>Fußgängerzone im Ortskern</b>. Kein Umweg, abends alles in Laufweite, und '
                . 'am nächsten Morgen stehst du sofort wieder auf dem Weg.<br>'
                . '<small><b>Apartment, also vermutlich kein Empfang — und damit kein Stempel.</b> '
                . 'Beide Stempel für den Tag woanders holen: Albergue, Concello, Kirche oder das '
                . 'Café auf dem Weg. Nach der Buchung gleich nachsehen, wie der Check-in läuft '
                . '(Code, Schlüsselkasten, Nachricht).</small>',
            ]
        );

        $db->run(
            'UPDATE cost_items SET detail = ? WHERE name = ?',
            [
                'Apartamento Loft II Select Real · Rúa Real 49 Bajo, Fußgängerzone im Ortskern · '
                . 'Buchungsnr. 6247972800',
                'Caldas de Reis (E9)',
            ]
        );

        /* ---- E10: Padrón, samt Warnung --------------------------------- */
        $db->run(
            'UPDATE stages SET note = ? WHERE seq = 10',
            [
                '<b>Travesía Iría 131, 15917 Padrón</b> — Postleitzahl und Straßenname deuten auf '
                . '<b>Iria Flavia</b>, rund einen Kilometer nördlich des Ortskerns und damit '
                . 'schon in Laufrichtung. Abends ist der Ort dafür nicht vor der Tür: '
                . '<b>Essen und Stempel erledigen, bevor du rausgehst.</b><br>'
                . '<b style="color:#c2410c">Der Gastgeber warnt vor Betrug:</b> Er verlangt '
                . '<b>keine zusätzliche Zahlung</b> über die Buchung hinaus, und jede '
                . 'Kommunikation läuft <b>über booking.com</b>. Wer dich per Mail oder SMS nach '
                . 'Kartendaten fragt — auch wenn es nach Booking aussieht —, ist nicht er.<br>'
                . '<small>Mit 9,4 die beste Bewertung der ganzen Reise. Apartment, also '
                . 'vermutlich kein Empfang und kein Stempel. <b>Check-in-Zeit mit dem Gastgeber '
                . 'abstimmen</b> — nach 19 km kommt niemand zur Bürozeit an. Den PIN hat die '
                . 'Booking-Mail; hier steht er nicht.</small>',
            ]
        );
    });

    /* ---- Unterkunftstabelle fuers Journal ------------------------------ */
    require_once APP_ROOT . '/db/unterkuenfte.php';
    foreach (unterkuenfte() as $u) {
        if (!in_array($u['ort'], ['Caldas de Reis', 'Padrón'], true)) {
            continue;
        }
        $db->run(
            'UPDATE lodgings SET strasse = ?, lage = ?, hinweis = ?, buchungsnr = ?
              WHERE name = ? AND date_from = ?',
            [$u['strasse'], $u['lage'], $u['hinweis'], $u['buchungsnr'], $u['name'], $u['von']]
        );
    }
}
