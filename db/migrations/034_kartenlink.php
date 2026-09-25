<?php
declare(strict_types=1);

/**
 * Ein Kartenlink je Etappe — als Knopf, nicht mitten im Fließtext.
 *
 * Es gab die Links schon: in jedem Etappenabsatz stand hinter der Adresse ein
 * „Karte" und oft noch eine Telefonnummer. Gefunden hat er sie trotzdem nicht
 * und stattdessen den Hotelnamen jedes Mal von Hand in Google Maps getippt —
 * was verständlich ist. Es waren zwei Wörter in kleiner grauer Schrift, am
 * Ende eines Absatzes, nach einem Wandertag, auf einem Telefon.
 *
 * Jetzt steht der Link direkt unter der Buchung, mit Namen und Adresse als
 * eigene Fläche zum Antippen, daneben die Telefonnummer. Gebaut wird er aus
 * `lodgings` — also aus Feldern, nicht aus HTML —, und er erscheint genau
 * dann, wenn dort eine Unterkunft hinterlegt ist.
 *
 * Hier werden deshalb die alten Links **aus dem Text entfernt**. Zwei
 * Kartenlinks nebeneinander sind schlechter als einer: dann fragt man sich,
 * ob sie auf dasselbe zeigen.
 *
 * Was im Text stehen bleibt, ist die **Adresse als Wort** — sie gehört zum
 * Satz („76 m vom Ortszentrum, Rua da Corredoura 15") und sagt auch ohne Netz
 * noch, wo man hinmuss.
 */
function migration_034(Database $db): void
{
    /* Der Kartenlink, und gleich dahinter die Telefonnummer, wenn eine da ist.
       Beides steckt jetzt im Knopf. Der Trenner „ · " davor oder dazwischen
       geht mit weg, sonst bleibt ein Mittelpunkt ohne alles davor stehen. */
    $muster = '~\s*<a href="https://www\.google\.com/maps[^"]*"[^>]*>\s*Karte\s*</a>'
            . '(?:\s*·\s*<a href="tel:[^"]*"[^>]*>[^<]*</a>)?~u';

    $db->transaction(function (Database $db) use ($muster): void {
        foreach ($db->all('SELECT id, note FROM stages WHERE note IS NOT NULL') as $st) {
            $alt = (string) $st['note'];
            if (!str_contains($alt, 'google.com/maps')) {
                continue;
            }
            $neu = preg_replace($muster, '', $alt);
            if ($neu === null || $neu === $alt) {
                continue;
            }
            // Ein „<br>" am Anfang eines Absatzes sieht aus wie ein Versehen.
            $neu = preg_replace('~(?:\s|<br\s*/?>)+$~u', '', $neu) ?? $neu;
            $db->run('UPDATE stages SET note = ? WHERE id = ?', [$neu, (int) $st['id']]);
        }
    });
}
