<?php
declare(strict_types=1);

/**
 * Die Küstenlinie neu einspielen — mit mehr Stützpunkten.
 *
 * Zwischen zwei Stützpunkten zeichnet die Karte eine Gerade. Bei 31 Punkten
 * auf 266 km waren das im Schnitt 8 km am Stück, im galicischen Binnenland
 * deutlich mehr: Pontevedra → Caldas de Reis 19,3 km, Padrón → Santiago
 * 18,5 km, Caldas → Padrón 14,7 km. Genau dort sah die Linie aus wie mit dem
 * Lineal gezogen.
 *
 * Dazwischen stehen jetzt **Barro/A Portela, Valga, Pontecesures und
 * O Milladoiro** — alles Orte am Weg, deren Koordinaten nachgeschlagen sind,
 * keine geratenen Zwischenpunkte. Die größte Lücke sinkt damit von 19,3 auf
 * 13,5 km.
 *
 * Mehr war von hier aus nicht zu holen: die Quellen mit dem echten Track
 * (Overpass/OpenStreetMap, waymarkedtrails, gronze, caminodesantiago.gal) sind
 * aus dieser Umgebung gesperrt, und für die restlichen Zwischenorte — Saiáns,
 * Bouzas, Chapela, Cesantes, Viladesuso — war keine belegte Koordinate zu
 * bekommen. Geraten wird hier nichts. Wer es genau will, lädt eine GPX-Datei
 * hoch; die ersetzt diese Liste vollständig.
 *
 * Wiederholbar: liest `kuesten_route_punkte()` und schreibt die Zeile neu.
 * Eine hochgeladene Route wird dabei **nicht** angefasst.
 */
function migration_025(Database $db): void
{
    require_once APP_ROOT . '/db/kuestenroute.php';

    $db->run(
        "UPDATE map_routes SET points = ? WHERE name = 'Route an der Küste'",
        [json_encode(kuesten_route_punkte())]
    );
}
