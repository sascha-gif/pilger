<?php
declare(strict_types=1);

/**
 * Nein, es gibt keinen Zug zum Flughafen — und das gehört dazugeschrieben.
 *
 * Die Frage kam am Vorabend des Rückflugs, und sie ist naheliegend: Santiago
 * hat einen Bahnhof, und in vielen Städten fährt von dort eine Linie zum
 * Flughafen. Hier nicht. **SCQ (Lavacolla) liegt rund 10 km östlich der Stadt
 * und hat keinen Gleisanschluss.** Der Bahnhof Santiago (Estación Intermodal,
 * Hórreo) ist ein Bahnhof für Fernzüge nach A Coruña, Ourense und Madrid, kein
 * Zubringer.
 *
 * Es bleibt bei den zwei Wegen, die ohnehin im Plan stehen: Linie 6A oder
 * Taxi. Der Hinweis steht jetzt direkt dort, damit die Frage morgen um sieben
 * nicht noch einmal aufkommt.
 */
function migration_043(Database $db): void
{
    $db->run(
        'UPDATE plan_steps SET note = ? WHERE phase = ? AND title LIKE ?',
        [
            'Ein eigener Flughafentarif von 6 € ist beschlossen, aber noch nicht in Kraft: '
            . 'ein paar Euro Bargeld einstecken, mit Karte kommst du im Bus nicht weiter. '
            . 'Rückfall Taxi: Festpreis rund 23 €, 15 bis 25 Minuten. Den Fahrplan am Vorabend '
            . 'noch einmal ansehen.<br>'
            . '<b>Einen Zug zum Flughafen gibt es nicht.</b> SCQ liegt rund 10 km östlich der '
            . 'Stadt und hat keinen Gleisanschluss; der Bahnhof am Hórreo ist für Fernzüge nach '
            . 'A Coruña, Ourense und Madrid. Es bleibt bei Bus oder Taxi.',
            'ziel',
            'Linie 6A%',
        ]
    );
}
