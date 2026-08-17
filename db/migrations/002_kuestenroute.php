<?php
declare(strict_types=1);

/**
 * Die Karte zeigte eine Gerade von Etappenort zu Etappenort und schnitt damit
 * ins Landesinnere — gelaufen wird aber die Küste. Diese Migration ersetzt die
 * abgeleitete Linie durch eine gespeicherte Küstenroute und rückt vier
 * Etappenorte auf ihre richtige Position, allen voran Vila do Conde.
 *
 * Wiederholbar: sie räumt vorher auf und legt dann neu an.
 */
function migration_002(Database $db): void
{
    require_once APP_ROOT . '/db/kuestenroute.php';

    $db->transaction(function (Database $db) {
        foreach (stage_koordinaten_korrekturen() as $title => [$lat, $lng]) {
            $db->run('UPDATE stages SET lat = ?, lng = ? WHERE title = ?', [$lat, $lng, $title]);
        }

        $db->exec('DELETE FROM map_routes');
        $db->run(
            'INSERT INTO map_routes (seq, name, color, weight, dashed, points) VALUES (?,?,?,?,?,?)',
            [1, 'Route an der Küste', '#f4b400', 4, 0, json_encode(kuesten_route_punkte())]
        );
    });
}
