<?php
declare(strict_types=1);

/**
 * Vila do Conde gebucht — und der Weg vom Ortskern zur Pension.
 *
 * Residencial Princesa do Ave, Einzelzimmer, 19.–20.09.2026, 78,00 € gesamt,
 * Bestätigungsnummer 5656009787. Damit ist die erste der zwölf offenen
 * Etappenunterkünfte weg; elf bleiben.
 *
 * Zwei Dinge sind dabei nachgeschlagen und nicht sicher gleich sauber:
 *
 *  1. **Die Hausnummer.** Die Verzeichnisse nennen für dieselbe Pension mal
 *     261, mal 395 — Straße (Rua Dr. António José Sousa Pereira) und
 *     Postleitzahl (4480-807) sind überall gleich. Erfunden wird hier nichts:
 *     261 steht dran, der Widerspruch steht dabei, und der Kartenlink sucht
 *     nach dem Namen statt nach der Nummer. Verbindlich ist die
 *     Buchungsbestätigung.
 *  2. **Der Kartenpunkt der Etappe war falsch.** In `stages` stand für Vila
 *     do Conde 41.333336 / -8.682063 — das liegt rund 5,5 km landeinwärts.
 *     Die Route in `db/kuestenroute.php` hat an derselben Stelle seit jeher
 *     41.3533 / -8.7425, der Pin widersprach also der eigenen Linie. Jetzt
 *     stimmen beide überein.
 */
function migration_019(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Kosten -------------------------------------------------- */
        // Beschriftung und Stand immer, den Betrag nur, solange nichts drin
        // steht — eine auf der Seite eingetippte Zahl gehoert Sascha.
        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
            [
                'Residencial Princesa do Ave · Einzelzimmer · Buchungsnr. 5656009787',
                'ok', 'gebucht', date('c'), 'Vila do Conde (E1)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [78.00, 'Vila do Conde (E1)']
        );

        /* ---- Etappe 1: gebucht, mit Weg zur Tür ---------------------- */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL,
                    lat = ?, lng = ? WHERE seq = 1',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Residencial Princesa do Ave · '
                . 'Einzelzimmer · <b>78,00 €</b> gesamt · Buchungsnr. 5656009787',

                '<b>Weg dorthin:</b> Der Küstenweg kommt von Süden, quert den <b>Rio Ave</b> '
                . 'und endet im Ortskern. Von dort rund <b>400 m</b> zur Pension: '
                . '<b>Rua Dr. António José Sousa Pereira 261, 4480-807</b> — keine 5 Minuten. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'Residencial%20Princesa%20do%20Ave%2C%20Vila%20do%20Conde" '
                . 'target="_blank" rel="noopener">Karte</a> · '
                . '<a href="tel:+351252642065">+351 252 642 065</a><br>'
                . '<small>Die Hausnummer steht in den Verzeichnissen mal als 261, mal als 395 — '
                . 'verbindlich ist die Buchungsbestätigung, der Kartenlink sucht nach dem Namen. '
                . 'Die Check-in-Zeit vorher kurz durchgeben: kleine Häuser sind nicht rund um die '
                . 'Uhr besetzt. PIN-Code steht in der Booking-Mail.</small>',

                41.3533,
                -8.7425,
            ]
        );
    });
}
