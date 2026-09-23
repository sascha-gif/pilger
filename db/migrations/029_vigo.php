<?php
declare(strict_types=1);

/**
 * Vigo (E7) — Alda Estación Vigo, 25.–26.09.2026, 57,00 € (inkl. 5,18 € MwSt).
 * Calle Alfonso XIII 19, 36201. Damit sind noch fünf Etappenorte offen.
 *
 * **Die Vorlage war die Buchungsübersicht, nicht die Bestätigung.** Unten stand
 * „Letzter Schritt", und eine Buchungsnummer gab es nicht. Preis, Haus und
 * Daten stehen deshalb hier, der Stand aber mit dem Vorbehalt: kommt eine
 * Bestätigung mit Nummer, gehört die nachgetragen.
 *
 * Zur Lage: 200 m vom Bahnhof Vigo-Urzáiz, mitten im Geschäftsviertel, wenige
 * Schritte von der Fußgängerstraße Príncipe, die in die Altstadt hinunterführt.
 * Der Küstenweg kommt von Südwesten am Hafen an — von dort rund 20 Minuten
 * bergauf. Dafür liegt das Haus schon auf der Seite, auf der es am nächsten
 * Morgen nach Redondela weitergeht.
 */
function migration_029(Database $db): void
{
    $db->transaction(function (Database $db): void {

        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
            [
                'Alda Estación Vigo · Calle Alfonso XIII 19 · inkl. 5,18 € MwSt',
                'ok', 'gebucht', date('c'), 'Vigo (E7)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [57.00, 'Vigo (E7)']
        );

        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL WHERE seq = 7',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Alda Estación Vigo · <b>57,00 €</b> '
                . '(inkl. 5,18 € MwSt)',

                '<b>Calle Alfonso XIII 19, 36201</b> — 200 m vom Bahnhof Vigo-Urzáiz, mitten im '
                . 'Geschäftsviertel, wenige Schritte von der Fußgängerstraße <b>Príncipe</b>, die in '
                . 'die Altstadt hinunterführt. Der Küstenweg kommt von Südwesten am Hafen an: von '
                . 'dort rund <b>20 Minuten bergauf</b>. Dafür liegt das Haus schon auf der Seite, auf '
                . 'der es am nächsten Morgen nach Redondela weitergeht. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'Alda%20Estaci%C3%B3n%20Vigo%2C%20Calle%20Alfonso%20XIII%2019%2C%20Vigo" '
                . 'target="_blank" rel="noopener">Karte</a><br>'
                . '<small>Vorlage war die <b>Buchungsübersicht</b>, nicht die Bestätigung — unten '
                . 'stand noch „Letzter Schritt", eine Buchungsnummer gab es nicht. Wenn die '
                . 'Bestätigungsmail da ist: nachsehen, ob sie wirklich kam.</small>',
            ]
        );
    });
}
