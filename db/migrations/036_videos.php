<?php
declare(strict_types=1);

/**
 * Videos im Tagebuch.
 *
 * Sie liegen in derselben Tabelle wie die Fotos, nicht in einer eigenen. Alles,
 * was an einem Foto hängt, hängt auch an einem Video: der Eintrag, die Etappe,
 * die Bildunterschrift, die Aufnahmezeit, die Koordinaten. Eine zweite Tabelle
 * hieße, jede Abfrage und jede Anzeige zweimal zu schreiben — und die
 * Zeitleiste, das Mosaik und die Tageskarte müssten beides zusammenführen.
 *
 * `kind` unterscheidet die beiden. Bestehende Zeilen sind Fotos.
 *
 * `thumb` trägt bei einem Video das **Standbild**. Erzeugt wird es auf dem
 * Handy, nicht hier: im Container steckt kein ffmpeg, und eins dazuzunehmen
 * hieße, ein Programm mit eigener Angriffsfläche an fremde Dateien zu lassen.
 * Der Browser kann ohnehin abspielen — dann kann er auch ein Bild abgreifen.
 *
 * `dauer` in Sekunden, ebenfalls vom Gerät. Nur zum Anzeigen.
 */
function migration_036(Database $db): void
{
    $mysql = $db->driver() === 'mysql';

    $db->addColumn('photos', 'kind', ($mysql ? 'VARCHAR(16)' : 'TEXT') . " NOT NULL DEFAULT 'foto'");
    $db->addColumn('photos', 'dauer', 'INT NULL');

    // Was vor dieser Migration da war, sind Fotos.
    $db->run("UPDATE photos SET kind = 'foto' WHERE kind IS NULL OR kind = ''");
}
