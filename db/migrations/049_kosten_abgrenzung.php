<?php
declare(strict_types=1);

/**
 * Die drei offenen Posten sind entschieden — und zwar alle drei dagegen.
 *
 *  - **Klarna** (144,65 am 18.09., 21,91 am 21.09., 25,96 am 25.09. =
 *    192,52 €): privat, nichts mit dem Camino. Bleibt draußen.
 *  - **Aldi Süd 14,23 €** am 17.09. und der Einkauf vom **16.09.**
 *    (REWE 10,65 + Pizzeria Kiara 10,00): beides Alltag vor der Abreise.
 *    Die Reise fängt in Porto an, nicht am Küchentisch.
 *
 * An den Zahlen ändert das nichts — sie waren nie mitgezählt. Was sich ändert,
 * ist der Text unter der Tabelle: dort stand bisher nur der 16.09. als
 * ausgenommen. Jetzt steht da, was wirklich alles draußen ist und warum, damit
 * die Frage nicht in einem halben Jahr noch einmal aufkommt.
 */
function migration_049(Database $db): void
{
    $db->run(
        'UPDATE notes SET body = ? WHERE nkey = ?',
        [
            'Die Zeilen mit <b>grauem</b> Status sind belegt, aber nicht vollständig: '
            . 'sie stammen aus den Kartenumsätzen vom <b>17.09. bis 01.10.</b> — also der '
            . 'ganzen Reise. Was trotzdem fehlt, ist <b>alles Bargeld</b>: Fähre über den '
            . 'Minho, Bus zum Flughafen, Stempel-Kaffees, Trinkgeld.<br>'
            . 'Was sich keiner Kategorie zuordnen ließ, steht gesammelt in einer eigenen '
            . 'Zeile statt gar nicht.<br>'
            . '<b>Nicht mitgezählt</b>, weil es nicht zur Reise gehört: die N26-Buchungen '
            . '(36,35 und 3,00), Netflix (13,99), die Klarna-Raten (144,65 · 21,91 · 25,96) '
            . 'und alles, was <b>vor dem Abflug</b> in Deutschland über die Karte ging — '
            . 'Aldi Süd am 17.09. (14,23) und der Einkauf vom 16.09. (20,65). Die Reise '
            . 'fängt in Porto an.<br>'
            . 'Nicht doppelt drin: die fünf Booking-Abbuchungen und die für Vigo stehen '
            . 'schon oben bei den Unterkünften. Die <b>Reiseversicherung</b> gehört nicht in '
            . 'diese Rechnung.<br>'
            . 'Jedes Feld lässt sich hier von Hand ändern, die Summe rechnet sofort nach.',
            'cost_outro',
        ]
    );
}
