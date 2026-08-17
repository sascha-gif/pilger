<?php
declare(strict_types=1);

/**
 * Neuer Abschnitt „Ankunft & Heimreise": was an den beiden Enden der Reise
 * konkret zu tun ist. Landen in Porto, zum Hotel kommen, Credencial holen —
 * und in Santiago Urkunde, Pilgermesse, Bus zum Flughafen.
 *
 * Angaben im August 2026 nachgeschlagen; Preise und Fahrpläne ändern sich,
 * deshalb steht bei allem der Stand dabei.
 */
function migration_003(Database $db): void
{
    $driver = $db->driver();
    $mysql  = $driver === 'mysql';
    $pk     = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $str    = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $tail   = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS plan_steps (
        id $pk,
        phase $str NOT NULL,
        seq INT NOT NULL DEFAULT 0,
        time_label $str NULL,
        title $str NOT NULL,
        body TEXT NULL,
        note TEXT NULL,
        accent INT NOT NULL DEFAULT 0
    )$tail");

    $db->transaction(function (Database $db) {
        $db->exec('DELETE FROM plan_steps');

        $steps = [
            /* ---- Ankunft in Porto ---------------------------------------- */
            ['ankunft', 1, '15:40', 'Landung in Porto (OPO)',
                'Gepäckband — dein <b>Aufgabegepäck</b> hat Messer, Nagelschere und alles über 100 ml. Danach durch die Ankunftshalle auf <b>Ebene 0</b>.',
                null, 0],

            ['ankunft', 2, '~16:00', 'Zur Metro',
                'Die Station liegt <b>direkt unter dem Terminal</b>. In der Ankunftshalle den Schildern „Metro" folgen, Rolltreppe oder Aufzug nach unten. Zu Fuß etwa 5 Minuten.',
                'Es gibt nur eine Metro-Station am Flughafen — verlaufen kann man sich hier nicht.', 0],

            ['ankunft', 3, '~16:10', 'Fahrkarte am Automaten',
                'Du brauchst zweierlei: die blaue Plastikkarte <b>Andante Azul</b> (0,60 €, einmalig) und darauf eine Fahrt der <b>Zone Z4</b> (rund 2,30 €). Zusammen also etwa <b>2,90 €</b>. Die Automaten können Deutsch und nehmen Karte.',
                'Vor dem Einsteigen an der Säule <b>validieren</b> — die Fahrt gilt danach 1 Stunde 15 Minuten, Umstiege eingeschlossen.', 1],

            ['ankunft', 4, '~16:20', 'Linie E (violett) bis Trindade',
                'Richtung <b>Estádio do Dragão</b> einsteigen und bis <b>Trindade</b> fahren — rund <b>30 Minuten</b>. Es gibt nur eine Fahrtrichtung ab dem Flughafen, die Linie ist Endstation.',
                'Alle 20 Minuten, außerhalb der Stoßzeit alle 30. Betrieb von 6:00 bis 1:00.', 0],

            ['ankunft', 5, '~16:55', 'Von Trindade zum Hotel',
                'Trindade ist der zentrale Umsteigebahnhof. Zum <b>Carpe Diem Porto by Dualgroup</b> bei São Bento entweder <b>eine Station mit Linie D (gelb)</b> bis São Bento — im selben Ticket enthalten — oder rund <b>10 Minuten zu Fuß bergab</b>.',
                'Buchungsnr. <b>5888925188</b>, den PIN hast du in der Booking-Mail. Zwei Nächte, 186,83 € sind bezahlt.', 1],

            ['ankunft', 6, '18.09.', 'Orga-Tag: Credencial holen',
                'Der Pilgerausweis kommt an der <b>Sé do Porto</b>, Terreiro da Sé, 4050-573 Porto. Ausgabe im <b>Sekretariat rechts neben der Kathedrale</b> bzw. im Shop. <b>Reisepass mitnehmen</b>, Kosten rund 2 €. Nur während der Öffnungszeiten der Kathedrale, also nicht auf den späten Nachmittag verschieben.',
                'Von São Bento etwa 5 Minuten zu Fuß bergauf. Direkt den <b>ersten Stempel</b> mitnehmen.', 1],

            ['ankunft', 7, '18.09.', 'Falls die Kathedrale zu ist',
                'Ausweichstelle: <b>Albergue de Peregrinos do Porto</b>, rund 2 km von der Kathedrale an der Zentralroute. Dort gibt es die Credencial ebenfalls.',
                null, 0],

            ['ankunft', 8, '19.09.', 'Losgehen',
                'Mit Linie A bis <b>Matosinhos Sul</b> — von dort beginnt Etappe 1 an der Küste. Ab jetzt <b>ein Stempel pro Tag</b>, ab der spanischen Grenze <b>zwei</b>.',
                null, 0],

            /* ---- Ankunft in Santiago -------------------------------------- */
            ['ziel', 1, '30.09.', 'Einzug Praza do Obradoiro',
                'Die letzten 25 km von Padrón enden auf dem Platz vor der Kathedrale. Kein Stempel nötig — hier wird erst mal angekommen.',
                null, 1],

            ['ziel', 2, '30.09.', 'Compostela-Urkunde abholen',
                'Im <b>Pilgerbüro (Oficina del Peregrino)</b>, Rúa das Carretas 33. Geöffnet <b>täglich 9:00 bis 19:00</b>, ohne Mittagspause, das ganze Jahr gleich. Du legst die <b>Credencial mit den Stempeln</b> vor — ab Spanien zwei pro Tag, sonst wird die Urkunde verweigert.',
                'Bei langer Schlange am späten Nachmittag lohnt sich der nächste Morgen mehr als zwei Stunden Anstehen.', 1],

            ['ziel', 3, '30.09. o. 01.10.', 'Pilgermesse',
                'Täglich <b>12:00</b> in der Kathedrale. Früh dasein, wenn du sitzen willst.',
                'Das Schwenken des Botafumeiro ist nicht garantiert — es hängt an Spenden und Anlässen.', 0],

            ['ziel', 4, '01.10.', 'Bus zum Flughafen SCQ',
                'Vom Zentrum fährt der Flughafenbus (<b>Empresa Freire</b>) <b>alle 30 Minuten</b>, von 7:00 bis 1:00. Einfache Fahrt <b>3,00 €</b>, hin und zurück 5,10 €. Fahrzeit rund 35 Minuten.',
                'Rechne großzügig: 35 Minuten Fahrt, dazu Puffer — <b>zwei Stunden vor Abflug</b> am Flughafen sein.', 1],

            ['ziel', 5, '01.10.', 'Rückflug SCQ → FRA',
                'Gebucht. Die genauen Zeiten stehen in deiner Buchungsbestätigung und gehören noch in den Kostenblock nachgetragen.',
                'Messer und alles über 100 ml wieder ins Aufgabegepäck, Powerbank ins Handgepäck.', 0],
        ];

        foreach ($steps as [$phase, $seq, $time, $title, $body, $note, $accent]) {
            $db->run(
                'INSERT INTO plan_steps (phase, seq, time_label, title, body, note, accent) VALUES (?,?,?,?,?,?,?)',
                [$phase, $seq, $time, $title, $body, $note, $accent]
            );
        }
    });
}
