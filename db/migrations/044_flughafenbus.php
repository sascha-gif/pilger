<?php
declare(strict_types=1);

/**
 * Der Flughafenbus fährt **nicht** ab Praza de Galicia. Das stand falsch im Plan.
 *
 * Der ausgehängte Fahrplan (gültig ab 28.05.2026) macht es eindeutig: in der
 * Richtung **Santiago → Aeropuerto** ist Praza de Galicia gar keine Haltestelle.
 * Sie taucht nur in der Gegenrichtung auf, als Nummer 410 — und dort sogar nur
 * als *parada exclusivamente de BAJADA*, also reine Ausstiegshaltestelle.
 *
 * Wer morgens dort steht, wartet auf einen Bus, der nicht kommt.
 *
 * **Eingestiegen wird am Hórreo (Estación Intermodal), Haltestelle 691.** Dort
 * beginnt die Linie, dort ist ein Platz sicher, und dort fährt sie pünktlich
 * ab. Andere Einstiege in dieser Richtung wären Virxe da Cerca nº 10 (662) oder
 * San Roque/La Salle (572).
 *
 * **Zwei Bauarten desselben Busses:**
 *  - *Lanzadera directa* — hält nur am Hórreo und am Flughafen, rund 20–25 Min.
 *    Morgens: 7:35, 8:35, **9:30**, 10:25, 11:20, 12:10
 *  - *Servicio con paradas* — die lange Runde über San Lázaro und San Marcos,
 *    rund 40 Min. Morgens: 7:45, 8:15, 8:45, 9:15, **9:45**, 10:15, 10:45
 *
 * Für den Flug um 12:30 heißt das: **9:30 direkt, um 9:55 am Flughafen.**
 * Das sind zweieinhalb Stunden Vorlauf, und dahinter liegen noch zwei Busse,
 * falls etwas schiefgeht.
 */
function migration_044(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Aufbruch: die Haltestelle stimmt nicht -------------------- */
        $db->run(
            'UPDATE plan_steps SET time_label = ?, title = ?, body = ?, note = ?
              WHERE phase = ? AND title LIKE ?',
            [
                '01.10. · 09:00',
                'Aufbruch zum Hórreo (Estación Intermodal)',
                'Vom Lemonade Stays (Rúa das Galeras 44) quer durch die Altstadt zur '
                . '<b>Estación Intermodal am Hórreo</b>, Haltestelle <b>691</b>. Dort beginnt die '
                . 'Linie 6A — Platz sicher, Abfahrt pünktlich. Rechne für den Weg eine gute '
                . 'halbe Stunde mit Rucksack.',
                '<b style="color:#c2410c">Nicht an der Praza de Galicia warten.</b> In Richtung '
                . 'Flughafen hält die 6A dort <b>nicht</b> — die Haltestelle gibt es nur auf dem '
                . 'Rückweg, und selbst dort nur zum Aussteigen. Wer morgens dort steht, wartet '
                . 'auf einen Bus, der nicht kommt.<br>'
                . 'Näher als der Hórreo liegen <b>Virxe da Cerca nº 10</b> (Haltestelle 662, am '
                . 'Markt) und <b>San Roque/La Salle</b> (572) — aber dort kommt der Bus schon '
                . 'besetzt an, und der Direkte hält gar nicht.',
                'ziel',
                'Aufbruch%',
            ]
        );

        /* ---- Der Bus selbst -------------------------------------------- */
        $db->run(
            'UPDATE plan_steps SET time_label = ?, title = ?, body = ?, note = ?
              WHERE phase = ? AND title LIKE ?',
            [
                '01.10. · 09:30',
                'Linie 6A — nimm den direkten',
                'Es gibt die Linie in zwei Bauarten. Die <b>Lanzadera directa</b> hält nur am '
                . 'Hórreo und am Flughafen und braucht <b>rund 20 bis 25 Minuten</b>; morgens '
                . 'fährt sie um 7:35, 8:35, <b>9:30</b>, 10:25, 11:20, 12:10. Der '
                . '<b>Servicio con paradas</b> nimmt die lange Runde über San Lázaro und San '
                . 'Marcos und braucht <b>rund 40 Minuten</b>: 7:45, 8:15, 8:45, 9:15, '
                . '<b>9:45</b>, 10:15, 10:45.<br>'
                . '<b>Nimm den 9:30 direkt — am Flughafen um 9:55.</b> Das sind zweieinhalb '
                . 'Stunden vor dem Abflug, und dahinter liegen noch der 9:45 (an 10:30) und der '
                . '10:25 direkt (an 10:50), falls etwas dazwischenkommt.',
                'Fahrschein beim Fahrer, <b>bar</b> — mit Karte kommst du im Bus nicht weiter, '
                . 'also ein paar Euro einstecken. Rückfall Taxi: Festpreis rund 23 €, 15 bis 25 '
                . 'Minuten.<br>'
                . '<b>Einen Zug zum Flughafen gibt es nicht.</b> SCQ liegt rund 10 km östlich der '
                . 'Stadt und hat keinen Gleisanschluss; der Bahnhof am Hórreo ist für Fernzüge '
                . 'nach A Coruña, Ourense und Madrid. Es bleibt bei Bus oder Taxi.<br>'
                . '<small>Zeiten vom ausgehängten Fahrplan, gültig ab 28.05.2026.</small>',
                'ziel',
                'Linie 6A%',
            ]
        );

        /* ---- Ankunft am Flughafen -------------------------------------- */
        $db->run(
            'UPDATE plan_steps SET time_label = ?, title = ?, body = ? WHERE phase = ? AND title LIKE ?',
            [
                '01.10. · 09:55',
                'Am Flughafen SCQ',
                '<b>Zweieinhalb Stunden vor Abflug</b> — reichlich, und genau so gewollt. Es sind '
                . '<b>zwei getrennte Tickets</b>, also auch zwei Check-ins: hier bei <b>Vueling</b>, '
                . 'in Palma später noch einmal bei <b>TUI fly</b>.',
                'ziel',
                'Am Flughafen%',
            ]
        );

        /* ---- Die 9:20 standen noch im Urkunden-Schritt ------------------ */
        $db->run(
            'UPDATE plan_steps SET note = ? WHERE phase = ? AND title LIKE ?',
            [
                'Am 1.10. bleibt dafür keine Zeit: wenn das Büro um 9:00 öffnet, bist du schon '
                . 'auf dem Weg zum Bus. <b>Alles, was in Santiago noch zu erledigen ist, gehört '
                . 'auf den 30.09.</b>',
                'ziel',
                'Compostela-Urkunde%',
            ]
        );

        /* ---- Der letzte Tag sind längst keine 25 km mehr ---------------- */
        $db->run(
            'UPDATE plan_steps SET body = ? WHERE phase = ? AND title LIKE ?',
            [
                'Die letzten <b>7 km von O Milladoiro</b> enden auf dem Platz vor der Kathedrale. '
                . 'Kein Stempel nötig — hier wird erst mal angekommen.',
                'ziel',
                'Einzug Praza do Obradoiro%',
            ]
        );
    });
}
