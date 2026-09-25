<?php
declare(strict_types=1);

/**
 * Redondela als Ausweichquartier für E8.
 *
 * Arcade ist ein Dorf. Wenn dort nichts frei ist — und Ende September auf
 * diesem Abschnitt ist das keine Überraschung —, liegt die Antwort auf dem
 * Weg: **Redondela**, rund 13 km hinter Vigo und damit rund 9 km vor Arcade.
 * Dort läuft der Küstenweg mit dem Caminho Central zusammen, und entsprechend
 * mehr Betten gibt es.
 *
 * Der Plan wird hier **nicht** umgestellt. Solange nichts gebucht ist, wäre
 * das eine Änderung auf Verdacht; steht erst ein Zimmer fest, kommen Titel,
 * Kilometer und Kartenpunkt wie bei Nigrán hinterher. Was hier reinkommt, ist
 * der Hinweis samt Suchlink für die richtige Nacht — damit er am Abend nicht
 * erst rechnen muss.
 *
 * Die Kilometer sind wie überall geschätzt: aus der Küstenlinie gerechnet und
 * auf die 22 km des Plans hochskaliert, weil der gelaufene Weg rund ein
 * Viertel länger ist als die Geraden dazwischen.
 *
 * Der zweite Hinweis ist die Bahn. Vigo, Redondela, Arcade und Pontevedra
 * liegen alle an derselben Linie. Wer am Ort nichts findet, schläft eine
 * Station weiter und fährt morgens an genau die Stelle zurück, an der er
 * aufgehört hat — gelaufen wird dadurch keinen Meter weniger. Fahrpläne stehen
 * hier bewusst nicht; die wären erfunden.
 */
function migration_037(Database $db): void
{
    $db->transaction(function (Database $db): void {
        $db->run(
            'UPDATE stages SET alt_note = ? WHERE seq = 8',
            [
                '<b>Wenn in Arcade nichts frei ist:</b> In <b>Redondela</b> aufhören — rund '
                . '<b>13 km</b> hinter Vigo statt 22, und damit rund 9 km vor Arcade. Dort trifft '
                . 'der Küstenweg auf den Caminho Central, deshalb gibt es dort deutlich mehr '
                . 'Pilgerbetten als im Dorf Arcade. Der nächste Tag wird dann rund <b>24 km</b> '
                . 'statt 15. '
                . '<a href="https://www.booking.com/searchresults.html?ss=Redondela%2C%20Pontevedra%2C%20Spain'
                . '&amp;checkin=2026-09-26&amp;checkout=2026-09-27&amp;group_adults=1&amp;no_rooms=1&amp;order=price" '
                . 'target="_blank" rel="noopener">Booking Redondela</a><br>'
                . '<b>Und wenn gar nichts geht:</b> Vigo, Redondela, Arcade und Pontevedra liegen '
                . 'an <b>derselben Bahnlinie</b>. Schlaf dort, wo ein Bett frei ist, und fahr '
                . 'morgens an genau die Stelle zurück, an der du aufgehört hast — gelaufen wird '
                . 'dadurch kein Meter weniger. <small>Abfahrtszeiten am Bahnhof oder am Empfang '
                . 'erfragen; hier stehen keine, die wären erfunden.</small><br>'
                . '<small><b>Nicht alles steht auf den Portalen.</b> Viele galicische '
                . '<i>pensións</i> und <i>hostales</i> nehmen nur telefonisch an. Der Empfang '
                . 'deiner Unterkunft ruft für Pilger vor — danach zu fragen ist hier völlig '
                . 'üblich. Und ein <i>Albergue</i> ist nicht automatisch Schlafsaal: viele '
                . 'private haben eine <i>habitación individual</i>.</small>',
            ]
        );
    });
}
