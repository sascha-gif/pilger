<?php
declare(strict_types=1);

/**
 * E6 endet in Nigrán, nicht in Baiona — und E7 fängt dort an.
 *
 * Gebucht ist die Nacht in **Playa América**, das gehört zur Gemeinde Nigrán
 * und liegt an der Carretera Vigo–Baiona, also **zwischen Baiona und Vigo**,
 * in Laufrichtung. Der Weg dorthin steht schon in der Küstenvariante von E7:
 * „Wer am Wasser bleibt, geht über Nigrán, Praia América und Saiáns nach Vigo."
 * Genau dort ist jetzt das Bett.
 *
 * **Das macht die beiden Tage erst vernünftig.** Bisher standen 14 km und
 * 27 km nebeneinander — ein halber Tag und der längste der ganzen Reise.
 * Jetzt sind es rund 21 und 20 km. Die Summe bleibt dieselbe, an der
 * Gesamtstrecke ändert sich nichts.
 *
 * Und damit liegt die längste Etappe des ganzen Camino hinter ihm: E5 mit
 * 27 km, gestern. Das Längste, was noch kommt, ist der letzte Tag nach
 * Santiago mit 25 km.
 *
 * **Die Kilometer sind geschätzt, nicht gemessen.** Gerechnet aus der
 * Küstenlinie in `db/kuestenroute.php`: Baiona → Panxón/Nigrán sind gut ein
 * Viertel des Stücks Baiona → Vigo, also rund 7 der 27 km. Dieselbe Art
 * Schätzung wie bei allen anderen Etappen auch.
 *
 * **Zwei Dinge an der Buchung stimmen nicht und stehen deshalb so auf der
 * Seite:**
 *  1. Die Vorlage war die **Buchungsübersicht** mit dem Knopf „Letzter
 *     Schritt" — nicht die Bestätigung. Es gibt keine Buchungsnummer. Ob die
 *     Buchung überhaupt zustande kam, ist damit offen. (Beim Hotel in Vigo war
 *     es genau dasselbe.)
 *  2. Als Anreise steht dort **Fr, 25.09.** — das ist die Nacht nach E7, und
 *     die ist in Vigo gebucht. Gebraucht wird die Nacht von **Do, 24.09. auf
 *     Fr, 25.09.** Deshalb wird hier **kein Betrag** als bezahlt eingetragen
 *     und nichts als „gebucht" ausgezeichnet.
 *
 * Der Kartenpunkt ist der Nigrán-Stützpunkt der Küstenlinie, keine
 * nachgeschlagene Adresse — auf der Übersichtskarte reicht das, und einen
 * genaueren habe ich nicht.
 */
function migration_032(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- E6: Ziel ist jetzt Nigrán ------------------------------- */
        $db->run(
            'UPDATE stages SET title = ?, dist = ?, km_walk = ?, km_big = ?,
                    lat = ?, lng = ?, map_name = ?, map_meta = ?,
                    target = ?, note = ?, booking_url = ?, booking_label = ?
             WHERE seq = 6',
            [
                'Oia → Nigrán',
                '21 km · Praia América',
                21,
                123,                       // 144 − 21; nach E7 bleiben wie bisher 103
                42.1500, -8.8100,
                'Nigrán',
                '21 km · noch 123 km',

                '<b style="color:#b26a00">Zimmer gefunden, Buchung noch nicht bestätigt:</b> '
                . 'HOTEL HOLIDAY camino de Santiago por la costa en playa América ★★★ · '
                . 'Einzelbelegung · <b>50,00 €</b> (inkl. 4,55 € MwSt)',

                '<b>Carretera Vigo–Baiona (por la costa) 17, 36350 Nigrán</b> — direkt an der '
                . 'Küstenstraße, an der <b>Praia América</b>, rund 7 km hinter Baiona und damit '
                . 'schon ein gutes Stück in Laufrichtung. Genau dort läuft die Küstenvariante '
                . 'entlang: hinter der Ponte da Ramallosa am Wasser bleiben statt über den Monte '
                . 'San Román zu steigen.<br>'
                . '<b>Dadurch werden aus 14 und 27 km jetzt rund 21 und 20</b> — der halbe Tag '
                . 'und der längste Tag werden zu zwei normalen. Die Gesamtstrecke bleibt gleich. '
                . 'Die längste Etappe des ganzen Camino liegt damit hinter dir: die 27 km von '
                . 'gestern. Das Längste, was noch kommt, ist der letzte Tag nach Santiago.<br>'
                . '<small><b>Zwei Dinge prüfen:</b> Der Screenshot war die Buchungs<i>übersicht</i> '
                . 'mit „Letzter Schritt", nicht die Bestätigung — es gibt keine Buchungsnummer, '
                . 'also ist offen, ob die Buchung durchging. Und als Anreise stand dort '
                . '<b>Fr, 25.09.</b>; gebraucht wird die Nacht <b>Do, 24.09. → Fr, 25.09.</b> '
                . 'Der 25. ist die Nacht danach, und die ist in Vigo gebucht.</small>',

                'https://www.booking.com/searchresults.html?ss=Nigr%C3%A1n%2C%20Pontevedra%2C%20Spain'
                . '&checkin=2026-09-24&checkout=2026-09-25&group_adults=1&no_rooms=1&order=price',
                'Booking Nigrán',
            ]
        );

        /* ---- E7: fängt jetzt in Nigrán an ---------------------------- */
        $db->run(
            'UPDATE stages SET title = ?, dist = ?, km_walk = ?, map_meta = ? WHERE seq = 7',
            [
                'Nigrán → Vigo',
                '20 km · am Wasser entlang',
                20,
                '20 km · noch 103 km',
            ]
        );

        /* Die Küstenvariante stand bisher da, weil sie über Nigrán führt. Wenn
           das Bett dort steht, ist sie keine Variante mehr, sondern der Weg. */
        $db->run(
            'UPDATE stages SET alt_note = ? WHERE seq = 7',
            [
                '<b>Küstenweg ab Praia América:</b> Von Nigrán aus bleibt der Weg am Wasser — '
                . 'über <b>Saiáns</b> nach Vigo, Holzstege, Radwege, flache Stadtabschnitte, fast '
                . 'durchgehend am Atlantik. Der offizielle Weg würde hinter der Ponte da Ramallosa '
                . 'ins Land abbiegen und über den Monte San Román steigen — den Anstieg hast du '
                . 'mit dem Quartier in Nigrán ohnehin umgangen.',
            ]
        );

        /* ---- E5 war der längste Tag, nicht mehr E7 -------------------- */
        $db->run(
            'UPDATE stages SET dist = ?, map_meta = ? WHERE seq = 5',
            [
                '27 km · längste Etappe · Fähre/Taxiboot über den Minho · ab hier 2 Stempel/Tag',
                '27 km · noch 144 km · längste Etappe',
            ]
        );

        /* ---- Kosten --------------------------------------------------
           Beschriftung ja, Betrag nein: solange die Bestätigung fehlt, ist
           nichts gebucht, und eine Zahl, die nach „erledigt" aussieht, wäre
           hier das Gegenteil von hilfreich. */
        $db->run(
            'UPDATE cost_items SET name = ?, detail = ?, status = ?, status_label = ?, updated_at = ?
             WHERE name = ?',
            [
                'Nigrán (E6)',
                'HOTEL HOLIDAY playa América ★★★ · 50,00 € · Bestätigung fehlt, Datum prüfen',
                'warn', 'zu prüfen', date('c'),
                'Baiona (E6)',
            ]
        );
    });
}
