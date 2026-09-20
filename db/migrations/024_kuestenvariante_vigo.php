<?php
declare(strict_types=1);

/**
 * Küstenvariante für die längste Etappe, Baiona → Vigo.
 *
 * Bisher stand dort nur „27 km · längste Etappe". Der offizielle Weg biegt
 * hinter der Ponte da Ramallosa ins Land ab und steigt über den Monte San
 * Román. Es gibt aber eine Variante, die am Wasser bleibt — über Nigrán,
 * Praia América und Saiáns nach Vigo, auf Holzstegen, Radwegen und flachen
 * Stadtabschnitten. Mehrere Quellen beschreiben sie übereinstimmend als
 * flacher als das Original und fast durchgehend parallel zum Atlantik.
 *
 * Ein Kilometerwert steht bewusst nicht dabei: die Angaben gehen je nach
 * Quelle von 23 bis 27 km auseinander, und es ist nicht zu klären, wo genau
 * in Vigo jeweils gemessen wird. Die 27 km der Etappe bleiben deshalb stehen.
 */
function migration_024(Database $db): void
{
    $db->run(
        'UPDATE stages SET alt_note = ? WHERE seq = 7',
        [
            '<b>Küstenvariante:</b> Hinter der <b>Ponte da Ramallosa</b> biegt der offizielle Weg '
            . 'ins Land ab und steigt über den Monte San Román. Wer am Wasser bleibt, geht über '
            . '<b>Nigrán, Praia América und Saiáns</b> nach Vigo — Holzstege, Radwege, flache '
            . 'Stadtabschnitte, fast durchgehend am Atlantik und flacher als das Original. '
            . 'Danach dreht der Weg in die Ría und später ins Landesinnere: das hier ist der '
            . 'letzte Tag am offenen Meer.',
        ]
    );
}
