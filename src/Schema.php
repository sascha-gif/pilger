<?php
declare(strict_types=1);

/**
 * Legt das Schema an und spielt beim ersten Start die Seed-Daten ein.
 * Läuft automatisch beim ersten Seitenaufruf — es ist kein Terminal-Zugriff
 * auf dem Server nötig, um die Datenbank zu installieren.
 */
final class Schema
{
    public static function migrate(Database $db): void
    {
        $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(64) NOT NULL PRIMARY KEY, applied_at VARCHAR(32) NOT NULL)');

        $done = array_column($db->all('SELECT version FROM schema_migrations'), 'version');

        $apply = static function (string $version, callable $fn) use ($db, $done): void {
            if (in_array($version, $done, true)) {
                return;
            }
            $fn($db);
            $db->run('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)', [$version, date('c')]);
        };

        // Tabellen und der komplette Masterplan-Inhalt.
        $apply('001_init', static function (Database $db): void {
            foreach (self::tables($db->driver()) as $sql) {
                $db->exec($sql);
            }
            require_once APP_ROOT . '/db/seed.php';
            seed_database($db);
        });

        // Küstenroute statt Geraden zwischen den Etappenorten.
        $apply('002_kuestenroute', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/002_kuestenroute.php';
            migration_002($db);
        });

        // Abschnitt „Ankunft & Heimreise" — Schritt für Schritt an beiden Enden.
        $apply('003_ankunft', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/003_ankunft.php';
            migration_003($db);
        });

        // Zutritt: gemerkte Geräte und Fehlversuche.
        $apply('004_zutritt', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/004_zutritt.php';
            migration_004($db);
        });

        // Erledigt-Häkchen für Etappen und Ausrüstung, echte Kilometer, Stempel.
        $apply('005_erledigt', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/005_erledigt.php';
            migration_005($db);
        });
    }

    /** @return array<int,string> */
    private static function tables(string $driver): array
    {
        $mysql = $driver === 'mysql';
        $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $txt   = $mysql ? 'TEXT' : 'TEXT';
        $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
        $dec   = $mysql ? 'DECIMAL(10,2)' : 'REAL';
        $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

        return [
            "CREATE TABLE IF NOT EXISTS settings (
                skey $str NOT NULL PRIMARY KEY,
                svalue $txt NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS hero_facts (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                number $str NOT NULL,
                label $str NOT NULL,
                mono INT NOT NULL DEFAULT 0
            )$tail",

            "CREATE TABLE IF NOT EXISTS profile_facts (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                label $str NOT NULL,
                value $str NOT NULL,
                sub $str NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS nutrition_pills (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                strong $str NOT NULL,
                rest $str NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS nutrition_slots (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                time_label $str NOT NULL,
                body $txt NOT NULL,
                accent INT NOT NULL DEFAULT 0
            )$tail",

            "CREATE TABLE IF NOT EXISTS travel_cards (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                tag $str NOT NULL,
                tag_ok INT NOT NULL DEFAULT 0,
                route $str NOT NULL,
                route_small INT NOT NULL DEFAULT 0,
                meta $txt NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS stages (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                code $str NULL,
                date_label $str NULL,
                title $str NOT NULL,
                title_suffix $str NULL,
                dist $str NULL,
                target $txt NULL,
                note $txt NULL,
                alt_note $txt NULL,
                booking_url $txt NULL,
                booking_label $str NULL,
                km_big $str NULL,
                km_sub $str NULL,
                variant $str NOT NULL DEFAULT 'normal',
                lat REAL NULL,
                lng REAL NULL,
                map_name $str NULL,
                map_eyebrow $str NULL,
                map_meta $str NULL,
                map_hub INT NOT NULL DEFAULT 0,
                on_map INT NOT NULL DEFAULT 1
            )$tail",

            "CREATE TABLE IF NOT EXISTS map_routes (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                name $str NOT NULL,
                color $str NOT NULL,
                weight INT NOT NULL DEFAULT 3,
                dashed INT NOT NULL DEFAULT 0,
                points $txt NOT NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS equipment_cards (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                title $str NOT NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS equipment_items (
                id $pk,
                card_id INT NOT NULL,
                seq INT NOT NULL DEFAULT 0,
                body $txt NOT NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS pack_categories (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                title $str NOT NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS pack_items (
                id $pk,
                category_id INT NOT NULL,
                seq INT NOT NULL DEFAULT 0,
                name $str NOT NULL,
                size $str NULL,
                qty $str NULL,
                purpose $txt NULL,
                checked INT NOT NULL DEFAULT 0,
                updated_at $str NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS cost_items (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                name $str NOT NULL,
                detail $str NULL,
                amount $dec NULL,
                status $str NOT NULL DEFAULT 'open',
                status_label $str NULL,
                updated_at $str NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS weight_weeks (
                id $pk,
                seq INT NOT NULL DEFAULT 0,
                label $str NOT NULL,
                period $str NULL,
                target $str NULL,
                actual $dec NULL,
                steps $str NULL,
                long_walk $str NULL,
                focus $txt NULL,
                updated_at $str NULL
            )$tail",

            "CREATE TABLE IF NOT EXISTS notes (
                id $pk,
                nkey $str NOT NULL,
                body $txt NOT NULL
            )$tail",
        ];
    }
}
