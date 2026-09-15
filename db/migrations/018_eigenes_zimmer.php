<?php
declare(strict_types=1);

/**
 * Nur Unterkünfte mit eigenem Zimmer — keine Schlafsaal-Betten.
 *
 * Die Budget-Ziele nannten schon die richtigen Haustypen (Pension, Hostal,
 * Stadthotel), aber nur zwischen den Zeilen. Jetzt steht die Anforderung
 * ausdrücklich in jeder Zeile.
 *
 * Wichtig für jemanden, der zum ersten Mal in Spanien bucht — und der
 * Hauptgrund, warum der Hinweis hier nötig ist:
 *
 *   **Albergue**  = Pilgerherberge, Bett im Schlafsaal.
 *   **Hostal**    = kleines, günstiges Hotel mit *eigenem* Zimmer.
 *
 * Die beiden Wörter sehen sich ähnlich und meinen das Gegenteil. Ein
 * „Hostal" ist genau das, was gesucht ist; ein „Albergue" genau das nicht.
 *
 * Im Booking-Link lässt sich das nicht erzwingen: der Filter dafür ist auf
 * booking.com nur in der Oberfläche vorhanden, der zugehörige URL-Parameter
 * ist nicht öffentlich dokumentiert. Einen erratenen Parameter einzubauen
 * wäre schlimmer als keinen — er könnte still auf null Ergebnisse filtern,
 * und zwar genau abends um sieben in Vigo. Deshalb steht der Hinweis auf der
 * Seite statt in der Adresse.
 */
function migration_018(Database $db): void
{
    // Etappencode => neues Budget-Ziel.
    $ziele = [
        'E1'  => '<b>Budget-Ziel:</b> Pension/3★ am Ortskern · <b>eigenes Zimmer</b> · ca. 55–75 €',
        'E2'  => '<b>Budget-Ziel:</b> Guesthouse/3★ am Fluss · <b>eigenes Zimmer</b> · ca. 55–75 €',
        'E3'  => '<b>Budget-Ziel:</b> Pension Altstadt nahe Eiffel-Brücke · <b>eigenes Zimmer</b> · ca. 60–80 €',
        'E4'  => '<b>Budget-Ziel:</b> Pension am Hauptplatz · <b>eigenes Zimmer</b> · ca. 60–85 €',
        'E5'  => '<b>Budget-Ziel:</b> Pension/Hostal · <b>eigenes Zimmer</b> · ca. 65–90 € '
               . '<span style="color:var(--stone)">(dünnes Angebot — hier sind die Privatzimmer zuerst weg, früh buchen)</span>',
        'E6'  => '<b>Budget-Ziel:</b> Hostal Altstadt (statt Parador) · <b>eigenes Zimmer</b> · ca. 65–95 €',
        'E7'  => '<b>Budget-Ziel:</b> Stadthotel/Hostal Zentrum · <b>eigenes Zimmer</b> · ca. 55–80 €',
        'E8'  => '<b>Budget-Ziel:</b> Pension vor der Brücke · <b>eigenes Zimmer</b> · ca. 55–75 €',
        'E9'  => '<b>Budget-Ziel:</b> Hostal Altstadt (casco vello) · <b>eigenes Zimmer</b> · ca. 60–85 €',
        'E10' => '<b>Budget-Ziel:</b> Pension/kleines Balneario · <b>eigenes Zimmer</b> · ca. 55–80 €',
        'E11' => '<b>Budget-Ziel:</b> Pension am Ort · <b>eigenes Zimmer</b> · ca. 50–70 €',
        'E12' => '<b>Budget-Ziel:</b> Pension/Hostal nahe Altstadt · <b>eigenes Zimmer</b> · ca. 70–110 € '
               . '<span style="color:var(--stone)">(hohe Nachfrage — früh buchen)</span>',
    ];

    $db->transaction(function (Database $db) use ($ziele): void {
        foreach ($ziele as $code => $ziel) {
            // Ueber den Code suchen, nicht ueber die Zeilennummer: der Code
            // steht als "E7 · 25.09." in der Spalte.
            $db->run(
                "UPDATE stages SET target = ? WHERE code LIKE ? AND target LIKE '%Budget-Ziel%'",
                [$ziel, $code . ' %']
            );
        }

        $db->run('DELETE FROM notes WHERE nkey = ?', ['stage_zimmer']);
        $db->run(
            'INSERT INTO notes (nkey, body) VALUES (?, ?)',
            ['stage_zimmer',
             '<b>Nur mit eigenem Zimmer.</b> Kein Bett im Schlafsaal — die Suchlinks zeigen '
             . 'aber alles, was es am Ort gibt. Auf booking.com deshalb einmal den Filter '
             . '<i>Bettenart → Privatzimmer</i> setzen (bzw. bei der Unterkunftsart die '
             . 'Herbergen abwählen). Und aufgepasst bei zwei Wörtern, die sich ähneln und '
             . 'das Gegenteil meinen: ein <b>Albergue</b> ist die Pilgerherberge mit '
             . 'Schlafsaal, ein <b>Hostal</b> ein kleines günstiges Hotel mit eigenem Zimmer.']
        );
    });
}
