<?php
declare(strict_types=1);

/**
 * Caldas de Reis will die Daten vorab — und das ist eine Aufgabe, keine Notiz.
 *
 * Der Gastgeber schreibt: Check-in ab 14:00, und vorher zu schicken sind
 * **vollständiger Name, Geburtsdatum und Passnummer**. (Die DNI-Nummer und die
 * „Soporte"-Nummer betreffen nur Spanier.) Infotelefon **+34 630 98 24 88**.
 *
 * Das ist nichts Ungewöhnliches: in Spanien muss jede Unterkunft ihre Gäste
 * melden, der *parte de viajeros*. Bei einem Apartment ohne Empfang passiert
 * das eben vorab statt an der Rezeption.
 *
 * **Wichtig ist der Weg.** Einen Tag nachdem der Gastgeber in Padrón vor
 * gefälschten Zahlungsaufforderungen gewarnt hat, fragt hier jemand nach einer
 * Passnummer — das sieht sich ähnlich genug, um zu stutzen. Der Unterschied:
 * diese Nachricht kam über die Booking-Plattform, und die Antwort gehört in
 * denselben Nachrichtenverlauf. **Nicht** auf eine Mail antworten, nicht per
 * SMS, und niemals Kartendaten — die verlangt kein Gastgeber.
 */
function migration_042(Database $db): void
{
    $db->transaction(function (Database $db): void {
        $db->run(
            'UPDATE stages SET note = ? WHERE seq = 9',
            [
                '<b style="color:#c2410c">Vor der Ankunft zu erledigen:</b> Der Gastgeber braucht '
                . '<b>vollständigen Namen, Geburtsdatum und Passnummer</b> — in Spanien muss jede '
                . 'Unterkunft ihre Gäste melden, und hier gibt es keinen Empfang, der das beim '
                . 'Einchecken macht. Die DNI-Angaben betreffen nur Spanier.<br>'
                . '<b>Antworte im Booking-Nachrichtenverlauf</b>, in dem die Frage kam — nicht per '
                . 'Mail, nicht per SMS. <b>Kartendaten verlangt kein Gastgeber</b>, weder hier noch '
                . 'sonstwo.<br>'
                . '<b>Check-in ab 14:00.</b> Infotelefon '
                . '<a href="tel:+34630982488">+34 630 98 24 88</a>.<br>'
                . '<b>Rúa Real 49 Bajo, 36650 Caldas de Reis</b> — am Haus steht '
                . '<b>„Select Real Apartments"</b>, „Bajo" ist das Erdgeschoss. Der Gastgeber '
                . 'schreibt dazu: <i>calle peatonal del centro del Pueblo</i> — '
                . '<b>Fußgängerzone im Ortskern</b>. Kein Umweg, abends alles in Laufweite, und '
                . 'am nächsten Morgen stehst du sofort wieder auf dem Weg.<br>'
                . '<small><b>Kein Empfang heißt auch kein Stempel.</b> Beide Stempel für den Tag '
                . 'woanders holen: Albergue, Concello, Kirche oder das Café auf dem Weg.</small>',
            ]
        );
    });

    require_once APP_ROOT . '/db/unterkuenfte.php';
    foreach (unterkuenfte() as $u) {
        if ($u['ort'] !== 'Caldas de Reis') {
            continue;
        }
        $db->run(
            'UPDATE lodgings SET telefon = ?, checkin = ?, hinweis = ?
              WHERE name = ? AND date_from = ?',
            [$u['telefon'], $u['checkin'], $u['hinweis'], $u['name'], $u['von']]
        );
    }
}
