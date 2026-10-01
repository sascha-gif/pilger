<?php
declare(strict_types=1);

/**
 * Zwei Entscheidungen von Sascha: **Reiseversicherung raus**, und der Rest,
 * der bisher nur unter der Tabelle stand, **kommt kumuliert in die Kosten**.
 *
 * Die ERGO-Police (186,00 € am 21.09.) fliegt als Zeile wieder heraus. Der
 * Beleg bleibt hier stehen, falls sie je wieder gebraucht wird.
 *
 * Die **N26-Buchungen gehen nicht mit** — so war die Ansage. Das sind die
 * 36,35 € am 30.09. und die Kontogebühr von 3,00 € am 29.09.; Bankkram, keine
 * Reise.
 *
 * Was übrig bleibt, steht jetzt als eine Zeile in der Liste statt als Fußnote
 * darunter — **37,50 €**:
 *
 *  - Lusigalia Tours 2 · 25,00 € · 21.09.
 *  - Hotel San Luis Co · 9,50 € · 30.09.
 *  - NH Hotels · 1,50 € · 21.09.
 *  - Hotel Lemonade · 1,50 € · 30.09.
 *
 * Was es genau war, steht auf keinem Auszug. Es waren aber Zahlungen
 * unterwegs, an Tagen der Reise, an Orten der Reise — also gehören sie in die
 * Summe, auch ohne Kategorie.
 *
 * **Noch offen und deshalb noch nicht drin:** Aldi Süd 14,23 € am Abreisetag,
 * der Einkauf vom 16.09. (20,65 €) und die Klarna-Raten (192,52 €). Dazu läuft
 * eine Rückfrage — die Klarna-Beträge könnten die Raten für die Ausrüstung
 * sein, die als Einzelposten längst in der Liste steht.
 */
function migration_048(Database $db): void
{
    $db->transaction(function (Database $db): void {

        $db->run('DELETE FROM cost_items WHERE name = ?', ['Reiseversicherung']);

        if ((int) $db->value('SELECT COUNT(*) FROM cost_items WHERE name = ?', ['Vor Ort, nicht zugeordnet']) === 0) {
            $db->run(
                'INSERT INTO cost_items (seq, name, detail, amount, status, status_label) VALUES (?,?,?,?,?,?)',
                [
                    28,
                    'Vor Ort, nicht zugeordnet',
                    'Lusigalia Tours 25,00 · Hotel San Luis 9,50 · NH Hotels 1,50 · '
                    . 'Hotel Lemonade 1,50 — Zahlungen unterwegs, Zweck aus dem Namen nicht erkennbar',
                    37.50,
                    'est',
                    'Karte, gesammelt',
                ]
            );
        }

        $db->run(
            'UPDATE notes SET body = ? WHERE nkey = ?',
            [
                'Die Zeilen mit <b>grauem</b> Status sind belegt, aber nicht vollständig: '
                . 'sie stammen aus den Kartenumsätzen vom <b>17.09. bis 01.10.</b> — also der '
                . 'ganzen Reise. Was trotzdem fehlt, ist <b>alles Bargeld</b>: Fähre über den '
                . 'Minho, Bus zum Flughafen, Stempel-Kaffees, Trinkgeld.<br>'
                . 'Was sich keiner Kategorie zuordnen ließ, steht jetzt gesammelt in einer '
                . 'eigenen Zeile statt gar nicht. <b>Nicht</b> mitgezählt sind die '
                . 'N26-Buchungen, Netflix und der Einkauf vom 16.09. — da ging der Flug noch '
                . 'nicht. Die fünf Booking-Abbuchungen und die für Vigo stehen schon oben bei '
                . 'den Unterkünften und sind deshalb nicht doppelt drin.<br>'
                . 'Jedes Feld lässt sich hier von Hand ändern, die Summe rechnet sofort nach.',
                'cost_outro',
            ]
        );
    });
}
