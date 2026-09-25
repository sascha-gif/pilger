<?php
declare(strict_types=1);

/**
 * Pontevedra gebucht — aber für die **Nacht nach E8**, nicht nach E9.
 *
 * Das Bett steht am 26. auf den 27. in Pontevedra. Das Etappenziel des 26. ist
 * aber Arcade, rund 15 km davor. Wer beides zusammenbringen will, hat zwei
 * Wege, und sie sind nicht gleichwertig:
 *
 *  1. **Alles an einem Tag laufen: 37 km.** Das ist das Doppelte des heutigen
 *     Tages und zehn Kilometer mehr als die bisher längste Etappe der ganzen
 *     Reise. Machbar, aber nicht nach acht Tagen am Stück.
 *
 *  2. **Laufen wie geplant, zum Bett fahren, morgens zurückfahren.** 22 km
 *     nach Arcade, die 15 km nach Pontevedra mit Zug, Bus oder Taxi, dort
 *     schlafen; am Sonntagmorgen zurück nach Arcade und die 15 km zu Fuß.
 *     **Gelaufen wird dadurch kein Meter weniger.**
 *
 * Der zweite Weg ist der, der die Compostela nicht gefährdet, und das ist hier
 * keine Kleinigkeit: **ab Vigo sind es nach den Zahlen dieses Plans 103 km.**
 * Verlangt sind die letzten 100 zu Fuß. Drei Kilometer Luft, mehr nicht. Ein
 * Taxi *über die Strecke* frisst die sofort auf; eine Fahrt zum Quartier und
 * morgens zurück an dieselbe Stelle kostet nichts davon.
 *
 * Dafür braucht es eine zweite Nacht in Pontevedra — die vom 27. auf den 28.
 * ist ohnehin offen und steht auf der Liste. Am einfachsten dieselbe
 * Unterkunft um eine Nacht verlängern: dann entfällt das Auschecken um 12:00
 * und der Rucksack bleibt liegen, während er die 15 km läuft.
 *
 * **Der Name des Hauses stand nicht auf dem Screenshot** — nur Adresse und
 * Telefonnummer. Deshalb steht hier keiner; ausgedacht wird er nicht.
 * Der Preis stammt aus der Zeile der Stornogebühr („ab 25.09. 19:44:
 * 103,50 €"), weil die Buchung bei Stornierung den Gesamtpreis kostet.
 */
function migration_038(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- E8: gelaufen wird nach Arcade, geschlafen in Pontevedra --- */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, alt_note = ?, booking_url = NULL, booking_label = NULL
             WHERE seq = 8',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Unterkunft in Pontevedra · Doppel-/Zweibettzimmer · '
                . '<b>103,50 €</b> — <b style="color:#b26a00">aber 15 km hinter dem Etappenziel.</b>',

                '<b>Virgen del Camino 53–55, 36001 Pontevedra</b> · '
                . '<a href="tel:+34986855904">+34 986 85 59 04</a><br>'
                . '<b>Check-in ab 14:00, Check-out 07:00–12:00.</b> Eine Nacht, 26. auf 27.09., '
                . 'für zwei Erwachsene gebucht. '
                . '<b>Nicht stornierbar</b> — seit dem 25.09. um 19:44 kostet jede Stornierung, '
                . 'Änderung oder Nichtanreise den vollen Preis.<br>'
                . '<small>Der Name des Hauses stand nicht auf der Bestätigung, nur Adresse und '
                . 'Telefonnummer. Der Preis stammt aus der Zeile der Stornogebühr.</small>',

                '<b>Wie der Samstag aufgeht — zwei Wege, und sie sind nicht gleichwertig:</b><br>'
                . '<b>Empfohlen:</b> Laufen wie geplant, <b>22 km bis Arcade</b>. Dann die 15 km '
                . 'nach Pontevedra mit <b>Zug, Bus oder Taxi</b> — Vigo, Arcade und Pontevedra '
                . 'liegen an derselben Bahnlinie. Dort schlafen. Am Sonntagmorgen zurück nach '
                . 'Arcade und die 15 km zu Fuß. <b>Gelaufen wird dadurch kein Meter weniger.</b> '
                . 'Dafür die Unterkunft um <b>eine zweite Nacht</b> verlängern (27. auf 28. ist '
                . 'ohnehin offen) — dann entfällt auch das Auschecken um 12:00 und der Rucksack '
                . 'bleibt liegen, während du läufst.<br>'
                . '<b>Oder alles an einem Tag: 37 km.</b> Das ist das Doppelte von heute und zehn '
                . 'Kilometer mehr als die längste Etappe der ganzen Reise. Nach acht Tagen am '
                . 'Stück ist das viel.<br>'
                . '<b style="color:#c2410c">Was du nicht tun solltest:</b> ein Taxi <i>über die '
                . 'Strecke</i> nehmen. Ab Vigo sind es nach den Zahlen hier <b>103 km</b>, und für '
                . 'die Compostela müssen die <b>letzten 100 gelaufen</b> sein. Das sind drei '
                . 'Kilometer Luft. Eine Fahrt zum Quartier und morgens zurück an dieselbe Stelle '
                . 'kostet davon nichts — eine Fahrt nach vorn kostet alles.',
            ]
        );

        /* ---- E9: die Nacht danach ist noch offen ---------------------- */
        $db->run(
            'UPDATE stages SET note = ? WHERE seq = 9',
            [
                '<b>Die Nacht vom 27. auf den 28. ist noch offen.</b> Wenn du am Samstag bis '
                . 'Arcade läufst und zum Bett nach Pontevedra fährst, brauchst du dort eine '
                . 'zweite Nacht — am einfachsten dieselbe Unterkunft verlängern, dann bleibt der '
                . 'Rucksack liegen, während du die 15 km läufst.',
            ]
        );

        /* ---- Kosten --------------------------------------------------- */
        $db->run(
            'UPDATE cost_items SET name = ?, detail = ?, status = ?, status_label = ?, updated_at = ?
             WHERE name = ?',
            [
                'Pontevedra (Nacht nach E8)',
                'Virgen del Camino 53–55 · Doppel-/Zweibettzimmer · nicht stornierbar · Name des Hauses unbekannt',
                'ok', 'gebucht', date('c'),
                'Arcade (E8)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [103.50, 'Pontevedra (Nacht nach E8)']
        );

        /* ---- Unterkunft fuers Journal --------------------------------- */
        require_once APP_ROOT . '/db/unterkuenfte.php';
        require_once APP_ROOT . '/db/migrations/031_unterkuenfte.php';
    });

    unterkuenfte_einspielen($db, unterkuenfte());
}
