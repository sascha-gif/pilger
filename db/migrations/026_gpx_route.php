<?php
declare(strict_types=1);

/**
 * Platz für einen echten Track.
 *
 * `map_routes` bekommt eine Spalte `quelle`. „plan" sind die von Hand
 * gesetzten Stützpunkte, „gpx" ist eine hochgeladene Aufzeichnung. Sobald
 * eine solche da ist, zeigt die Karte nur noch sie — eine Näherung neben dem
 * echten Weg zu zeichnen hilft niemandem.
 *
 * Getrennte Zeilen statt Überschreiben, damit `025_route_stuetzpunkte` die
 * Planlinie weiter neu einspielen darf, ohne einen hochgeladenen Track zu
 * zerstören.
 */
function migration_026(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $db->addColumn('map_routes', 'quelle', ($mysql ? 'VARCHAR(16)' : 'TEXT') . " NOT NULL DEFAULT 'plan'");
    $db->run("UPDATE map_routes SET quelle = 'plan' WHERE quelle IS NULL OR quelle = ''");
}
