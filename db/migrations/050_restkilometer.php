<?php
declare(strict_types=1);

/**
 * Die Restkilometer auf der Etappenkarte E7 stimmen nicht.
 *
 * Auf der Karte für **Nigrán → Vigo** steht groß **88**, auf dem Kartenpunkt
 * daneben **„noch 103 km"**. Beides kann nicht gleichzeitig gelten.
 *
 * Dahinter stecken zwei Zählweisen, und die Seite mischt sie:
 *
 *  - **Entfernung bis Santiago entlang der Route** — fängt bei 266 an. So
 *    rechnen Porto bis E6 und alle Kartenpunkte.
 *  - **Was noch zu Fuß zu gehen ist** — fängt bei 251 an, weil die 15 km
 *    Arcade → Pontevedra gefahren werden. So rechnet seit Migration 039 nur
 *    E7.
 *
 * Ab E8 liefern beide dasselbe (66, 44, 25, 7, 0) — der gefahrene Abschnitt
 * liegt davor. Unterschiedlich sind sie nur bei E1 bis E7, und dort folgt
 * alles der ersten Zählweise. **Außer der einen Zahl bei E7.**
 *
 * Also wird sie korrigiert statt umgekehrt sechs andere: Vigo liegt
 * **103 km** vor Santiago. 123 nach Nigrán minus 20 gelaufene — es gibt keine
 * Stelle, an der zwischen Nigrán und Vigo 15 km verschwinden könnten; die
 * fallen erst am nächsten Tag hinter Arcade weg.
 *
 * Bei **E8** stehen weiterhin zwei verschiedene Zahlen, und das ist richtig
 * so: 81 km sind es ab **Arcade**, wo die Etappe zu Fuß endet und der
 * Kartenpunkt sitzt, und 66 km ab **Pontevedra**, wo nach der Fahrt das Bett
 * steht. Bisher stand an beiden nicht dran, worauf sie sich beziehen — das
 * steht jetzt dabei.
 */
function migration_050(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- E7: Vigo ist 103 km vor Santiago, nicht 88 --------------- */
        $db->run('UPDATE stages SET km_big = ? WHERE seq = ? AND code LIKE ?', ['103', 7, 'E7%']);

        /* ---- E8: dazuschreiben, ab wo gemessen wird ------------------- */
        $db->run(
            'UPDATE stages SET km_sub = ?, map_meta = ? WHERE seq = ? AND code LIKE ?',
            ['noch ab Pontevedra', '22 km · noch 81 km ab Arcade · Austern', 8, 'E8%']
        );
    });
}
