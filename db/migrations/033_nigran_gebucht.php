<?php
declare(strict_types=1);

/**
 * Nigrán ist gebucht — das Datum wurde telefonisch geradegezogen.
 *
 * Auf der Buchungsübersicht stand der 25.09. als Anreise, gebraucht wurde der
 * 24. Das ist per Telefon geändert worden, und er hat dort übernachtet. Damit
 * ist es bestätigt, so gut es geht: besser als jede Mail.
 *
 * Eine **Buchungsnummer gibt es trotzdem nicht** — die Vorlage war die
 * Übersicht, nicht die Bestätigung. Das bleibt so vermerkt, sonst wundert sich
 * später jemand, warum bei dieser einen Nacht keine dasteht.
 *
 * Damit sind noch vier Etappenorte offen: Arcade, Pontevedra, Caldas de Reis
 * und Padrón.
 */
function migration_033(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- E6 ------------------------------------------------------ */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL
             WHERE seq = 6',
            [
                '<b style="color:#2e7d32">Gebucht:</b> HOTEL HOLIDAY camino de Santiago por la '
                . 'costa en playa América ★★★ · Einzelbelegung · <b>50,00 €</b> '
                . '(inkl. 4,55 € MwSt)',

                '<b>Carretera Vigo–Baiona (por la costa) 17, 36350 Nigrán</b> — direkt an der '
                . 'Küstenstraße, an der <b>Praia América</b>, rund 7 km hinter Baiona und damit '
                . 'schon ein gutes Stück in Laufrichtung. Genau dort läuft die Küstenvariante '
                . 'entlang: hinter der Ponte da Ramallosa am Wasser bleiben statt über den Monte '
                . 'San Román zu steigen — den Anstieg sparst du dir damit. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'HOTEL%20HOLIDAY%2C%20Carretera%20Vigo-Baiona%2017%2C%20Nigr%C3%A1n" '
                . 'target="_blank" rel="noopener">Karte</a><br>'
                . '<b>Dadurch werden aus 14 und 27 km rund 21 und 20</b> — der halbe Tag und der '
                . 'längste Tag werden zu zwei normalen. Die Gesamtstrecke bleibt gleich. Die '
                . 'längste Etappe des ganzen Camino liegt damit hinter dir: die 27 km von '
                . 'gestern. Das Längste, was noch kommt, ist der letzte Tag nach Santiago.<br>'
                . '<small>Das Datum stand auf der Übersicht als 25.09. und wurde <b>telefonisch '
                . 'auf die Nacht vom 24. auf den 25.</b> geändert. Eine Buchungsnummer gibt es '
                . 'nicht — die Vorlage war die Übersicht, nicht die Bestätigung.</small>',
            ]
        );

        /* ---- Kosten --------------------------------------------------
           Beschriftung und Stand immer, den Betrag nur, solange nichts drin
           steht — eine auf der Seite eingetippte Zahl gehoert Sascha. */
        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ?
             WHERE name = ?',
            [
                'HOTEL HOLIDAY playa América ★★★ · Einzelbelegung · inkl. 4,55 € MwSt',
                'ok', 'gebucht', date('c'),
                'Nigrán (E6)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [50.00, 'Nigrán (E6)']
        );

        /* ---- Unterkunft fuer das Journal ------------------------------ */
        require_once APP_ROOT . '/db/unterkuenfte.php';
        require_once APP_ROOT . '/db/migrations/031_unterkuenfte.php';
    });

    /* Laeuft ausserhalb der Transaktion oben, weil es eine eigene aufmacht. */
    unterkuenfte_einspielen($db, unterkuenfte());
}
