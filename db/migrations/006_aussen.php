<?php
declare(strict_types=1);

/**
 * Ein Zwischenspeicher für alles, was von außen kommt: Wetter und Höhen.
 *
 * Beides holt der Server selbst, nicht der Browser — sonst stünde ein
 * fremder Dienst in der Adresszeile jedes Geräts, und ohne Netz auf dem
 * Camino wäre gar nichts da. So liegt der letzte Stand in der Datenbank und
 * ist auch offline noch zu sehen.
 */
function migration_006(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $key   = $mysql ? 'VARCHAR(190)' : 'TEXT';
    $blob  = $mysql ? 'LONGTEXT' : 'TEXT';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS ext_cache (
        skey $key NOT NULL PRIMARY KEY,
        payload $blob NOT NULL,
        fetched_at $str NOT NULL,
        expires_at $str NOT NULL
    )$tail");
}
