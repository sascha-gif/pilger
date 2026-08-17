<?php
/**
 * Vorlage für config/config.php
 *
 * Auf dem Server wird config/config.php vom Deploy-Workflow aus den
 * GitHub-Secrets erzeugt (siehe docs/DEPLOYMENT.md). Diese Datei hier ist
 * nur die Vorlage und enthält keine echten Zugangsdaten.
 *
 * Fehlt config/config.php komplett, startet die App automatisch mit einer
 * SQLite-Datei unter var/pilger.sqlite — sie läuft also auch ohne DB-Server.
 */

return [
    // 'mysql' (Hetzner/MariaDB) oder 'sqlite' (Fallback ohne DB-Server)
    'driver'   => 'mysql',

    'mysql'    => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'pilger',
        'user'    => 'pilger',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // Pfad der SQLite-Datei, relativ zum Projektroot
    'sqlite'   => [
        'path' => 'var/pilger.sqlite',
    ],

    // Schreibschutz: Solange hier ein Passwort steht, sind Änderungen
    // (Häkchen, Beträge, Gewicht) erst nach Eingabe möglich.
    // null = jeder Besucher darf bearbeiten.
    'write_password' => null,

    // Schema/Seed automatisch beim ersten Aufruf einspielen
    'auto_migrate' => true,

    // Fehlerdetails im Browser anzeigen (nur zur Fehlersuche)
    'debug' => false,
];
