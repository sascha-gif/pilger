<?php
declare(strict_types=1);

/**
 * Esposende gebucht — die zweite Etappenunterkunft.
 *
 * Hello Esposende, 20.–21.09.2026, 74,00 € bezahlt, Bestätigungsnummer
 * 5557270875. Rua Dom Dinis 8, 4740-267 Esposende. Damit sind noch zehn
 * Etappenorte offen.
 *
 * Zum Weg dorthin, und was daran belegt ist und was nicht:
 *
 *  - **Die Ankunft ist sicher.** Der Küstenweg läuft von Vila do Conde über
 *    Póvoa de Varzim, Aguçadoura und Apúlia nach Fão und quert dort den
 *    **Rio Cávado** — Fão liegt am Südufer, Esposende am Nordufer.
 *  - **Die Lage steht im Postleitzahlenregister**, nicht in der Buchung: die
 *    Rua Dom Dinis liegt bei 41,5427 / −8,7877 und damit gut einen Kilometer
 *    nördlich des Ortskerns (41,5324 / −8,7831), in der Freguesia Marinhas.
 *    Das ist die Richtung, in die es am nächsten Morgen ohnehin weitergeht.
 *  - **Die Entfernungsangaben der Buchungsportale widersprechen sich** — mal
 *    „500 m zum Praia de Ofir", mal „700 m zum Stadtstrand". Ofir liegt aber
 *    südlich des Cávado. Deshalb steht davon nichts auf der Seite, und der
 *    Kartenlink sucht nach dem Namen statt nach einer Koordinate.
 */
function migration_021(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Kosten -------------------------------------------------- */
        // Beschriftung und Stand immer, den Betrag nur, solange nichts drin
        // steht — eine auf der Seite eingetippte Zahl gehoert Sascha.
        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
            [
                'Hello Esposende · Buchungsnr. 5557270875',
                'ok', 'gebucht', date('c'), 'Esposende (E2)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [74.00, 'Esposende (E2)']
        );

        /* ---- Etappe 2 ------------------------------------------------ */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL WHERE seq = 2',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Hello Esposende · <b>74,00 €</b> · '
                . 'Buchungsnr. 5557270875',

                '<b>Weg dorthin:</b> Der Küstenweg quert bei Fão den <b>Rio Cávado</b> — '
                . 'Fão am Südufer, Esposende am Nordufer. Die Unterkunft liegt <b>gut einen '
                . 'Kilometer nördlich des Ortskerns</b> (Freguesia Marinhas), also schon in der '
                . 'Richtung, in die es am nächsten Morgen weitergeht: '
                . '<b>Rua Dom Dinis 8, 4740-267</b>. '
                . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                . 'Hello%20Esposende%2C%20Rua%20Dom%20Dinis%208%2C%20Esposende" '
                . 'target="_blank" rel="noopener">Karte</a><br>'
                . '<small>Die Lage stammt aus dem Postleitzahlenregister, nicht aus der Buchung — '
                . 'für den letzten Kilometer gilt der Kartenlink. Die Entfernungsangaben der '
                . 'Portale widersprechen sich (Praia de Ofir liegt südlich des Cávado), deshalb '
                . 'steht davon hier nichts. Check-in-Zeit vorher durchgeben.</small>',
            ]
        );
    });
}
