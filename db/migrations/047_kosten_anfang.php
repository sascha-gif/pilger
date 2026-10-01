<?php
declare(strict_types=1);

/**
 * Die Lücke am Anfang ist zu: die Auszüge vom **16. bis 21.09.** sind da.
 *
 * Migration 046 konnte nur ab dem 21.09. rechnen. Jetzt liegen auch Abreisetag,
 * Porto und die ersten beiden Etappen vor. Was dazukommt:
 *
 * **Verpflegung: +65,12 €** — damit 301,65 €.
 * Eindeutig neu (63,17 €): S Bento S Market 3,50 · Continente 3,22 ·
 * Primaprix 1,40 (alle 17.09.) · SM Supermarhet 2,80 · SPAR 4,47 ·
 * Potato Project 6,00 · Recheio 3,89 (18.09.) · Lidl 11,38 · Auchan 3,96 ·
 * Aldi Matosinhos 7,94 · Star Confeitaria 3,00 (19.09.) · Aldi Esposende
 * 11,61 (20.09.). Dazu 1,95 € von „ANA RITA FELIX RIBEIRO" am 21.09. — ein
 * Händler, der auf den Namen der Inhaberin läuft; bei dem Betrag ein Kaffee,
 * aber geraten.
 *
 * **Nahverkehr: 6,33 €** — zum ersten Mal überhaupt etwas Belegtes. Eine
 * Bolt-Fahrt am 19.09., dem Tag, an dem es mit der Metro nach Matosinhos ging.
 * Alles andere auf der Strecke war bar und bleibt unsichtbar.
 *
 * **Nicht gezählt:** REWE 10,65 und Pizzeria Kiara 10,00 am **16.09.** — da
 * war er noch zu Hause, der Flug ging erst am 17. Klarna 144,65 am 18.09.
 * ist wie die anderen Klarna-Raten keine Reiseausgabe.
 *
 * **Nicht zugeordnet:** Aldi Süd 14,23 am 17.09. Deutscher Aldi am Abreisetag
 * — Proviant für unterwegs oder der normale Einkauf, das steht da nicht.
 * Zusammen mit den fünf Posten aus 046 sind das jetzt 88,08 €.
 *
 * **Die drei Booking-Abbuchungen** (77,50 am 18.09., 74,00 am 19.09.,
 * 64,89 am 20.09.) gehören zu Unterkünften, die längst in der Liste stehen,
 * und werden deshalb nicht noch einmal gezählt. 74,00 ist Esposende auf den
 * Cent. Die beiden anderen liegen dicht an Vila do Conde (78,00) und Viana do
 * Castelo (65,00), treffen sie aber nicht genau — bei Viana lief der Vertrag
 * über LINKALL HONGKONG, da ist eine Umrechnung die naheliegende Erklärung.
 * Die Zeilen behalten den gebuchten Betrag; wer es genau will, trägt den
 * abgebuchten von Hand ein.
 */
function migration_047(Database $db): void
{
    $db->transaction(function (Database $db): void {

        $db->run(
            'UPDATE cost_items SET detail = ?, amount = ?, status_label = ? WHERE name = ?',
            [
                'Supermarkt und Restaurants 17.09.–01.10. · 279,22 € belegt, '
                . '22,43 € nach Name zugeordnet (Tankstellen, Kaffee, Hórreo) · ohne Bargeld',
                301.65,
                'Karte, 17.09.–01.10.',
                'Verpflegung',
            ]
        );

        $db->run(
            'UPDATE cost_items SET detail = ?, amount = ?, status_label = ? WHERE name = ?',
            [
                'Bolt-Fahrt am 19.09. · Metro Porto, Fähre über den Minho und Bus zum Flughafen '
                . 'waren bar und stehen auf keinem Auszug',
                6.33,
                'nur Bolt belegt',
                'Nahverkehr',
            ]
        );

        $db->run(
            'UPDATE notes SET body = ? WHERE nkey = ?',
            [
                'Die Zeilen mit <b>grauem</b> Status sind belegt, aber nicht vollständig: '
                . 'sie stammen aus den Kartenumsätzen vom <b>17.09. bis 01.10.</b> — also der '
                . 'ganzen Reise. Was trotzdem fehlt, ist <b>alles Bargeld</b>: Fähre über den '
                . 'Minho, Bus zum Flughafen, Stempel-Kaffees, Trinkgeld.<br>'
                . 'Nicht mitgezählt sind Klarna, Netflix, Kontogebühren und der Einkauf vom '
                . '16.09. — da ging der Flug noch nicht. Die fünf Booking-Abbuchungen und die '
                . 'für Vigo stehen schon oben bei den Unterkünften und sind deshalb nicht '
                . 'doppelt drin.<br>'
                . 'Nicht zugeordnet, weil aus dem Namen nicht zu erkennen: Lusigalia Tours '
                . '(25,00), N26 (36,35), Aldi Süd am Abreisetag (14,23), Hotel San Luis (9,50), '
                . 'NH Hotels (1,50), Hotel Lemonade (1,50) — zusammen <b>88,08 €</b>. '
                . 'Jedes Feld lässt sich hier von Hand ändern, die Summe rechnet sofort nach.',
                'cost_outro',
            ]
        );
    });
}
