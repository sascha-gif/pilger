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

        // Zwischenspeicher für Wetter und Höhen.
        $apply('006_aussen', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/006_aussen.php';
            migration_006($db);
        });

        // Tagebuch und Fotos.
        $apply('007_tagebuch', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/007_tagebuch.php';
            migration_007($db);
        });

        // Schritte, Kalorien und Puls aus dem Google-Health-Konto.
        $apply('008_gesundheit', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/008_gesundheit.php';
            migration_008($db);
        });

        // Wo es den Stempel gibt — Kartensuche je Ort, feste Adressen wo belegt.
        $apply('009_stempelorte', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/009_stempelorte.php';
            migration_009($db);
        });

        // Gewicht aus dem Google-Health-Konto.
        $apply('010_gewicht', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/010_gewicht.php';
            migration_010($db);
        });

        // Fitbit und ihr Ladekabel in die Packliste.
        $apply('011_fitbit', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/011_fitbit.php';
            migration_011($db);
        });

        // „Vor der Abreise" — Termine und Erledigungen zum Abhaken.
        $apply('012_vorbereitung', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/012_vorbereitung.php';
            migration_012($db);
        });

        // Schlafsack in die Packliste, Rezept und Apotheke in die Erledigungen.
        $apply('013_schlafsack_rezept', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/013_schlafsack_rezept.php';
            migration_013($db);
        });

        // Praezisierung: Seidenschlafsack aus Hygienegruenden.
        $apply('014_seidenschlafsack', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/014_seidenschlafsack.php';
            migration_014($db);
        });

        // Erste echte Ausruestungskaeufe in der Kostenliste.
        $apply('015_gekauft', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/015_gekauft.php';
            migration_015($db);
        });

        // Nachtrag: Papiertueten, Ladegeraet, Socken und Shorts doppelt.
        $apply('016_nachtrag_kaeufe', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/016_nachtrag_kaeufe.php';
            migration_016($db);
        });

        // Rueckflug gebucht: Zeiten, Kosten, Weg zum Flughafen SCQ.
        $apply('017_rueckflug', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/017_rueckflug.php';
            migration_017($db);
        });

        // Nur Unterkuenfte mit eigenem Zimmer, kein Schlafsaal.
        $apply('018_eigenes_zimmer', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/018_eigenes_zimmer.php';
            migration_018($db);
        });

        // Vila do Conde gebucht, Weg zur Tuer, Kartenpunkt der Etappe berichtigt.
        $apply('019_vila_do_conde', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/019_vila_do_conde.php';
            migration_019($db);
        });

        // Eine Etappe kann mehr als einen Tag dauern — Porto sind zwei.
        $apply('020_tagespanne', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/020_tagespanne.php';
            migration_020($db);
        });

        // Esposende gebucht.
        $apply('021_esposende', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/021_esposende.php';
            migration_021($db);
        });

        // Viana do Castelo gebucht.
        $apply('022_viana', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/022_viana.php';
            migration_022($db);
        });

        // Viana: richtige Postleitzahl, Check-in-Nummer, Partnerangebot.
        $apply('023_viana_nachtrag', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/023_viana_nachtrag.php';
            migration_023($db);
        });

        // Baiona -> Vigo: die Variante am Wasser statt ueber den Berg.
        $apply('024_kuestenvariante_vigo', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/024_kuestenvariante_vigo.php';
            migration_024($db);
        });

        // Mehr Stuetzpunkte, damit die Linie weniger abschneidet.
        $apply('025_route_stuetzpunkte', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/025_route_stuetzpunkte.php';
            migration_025($db);
        });

        // Platz fuer einen echten Track aus einer GPX-Datei.
        $apply('026_gpx_route', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/026_gpx_route.php';
            migration_026($db);
        });

        // Caminha gebucht.
        $apply('027_caminha', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/027_caminha.php';
            migration_027($db);
        });

        // Oia und Santiago gebucht — und E5/E6 verschieben sich dadurch.
        $apply('028_oia_santiago', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/028_oia_santiago.php';
            migration_028($db);
        });

        // Vigo gebucht.
        $apply('029_vigo', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/029_vigo.php';
            migration_029($db);
        });

        // Fotos wissen, wo sie entstanden sind.
        $apply('030_foto_koordinaten', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/030_foto_koordinaten.php';
            migration_030($db);
        });

        // Unterkuenfte als Tabelle, nicht als Absatz.
        $apply('031_unterkuenfte', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/031_unterkuenfte.php';
            migration_031($db);
        });

        // E6 endet in Nigran, nicht in Baiona — und E7 faengt dort an.
        $apply('032_nigran', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/032_nigran.php';
            migration_032($db);
        });

        // Nigran ist gebucht, das Datum telefonisch geradegezogen.
        $apply('033_nigran_gebucht', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/033_nigran_gebucht.php';
            migration_033($db);
        });

        // Ein Kartenlink je Etappe — als Knopf, nicht mitten im Fliesstext.
        $apply('034_kartenlink', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/034_kartenlink.php';
            migration_034($db);
        });

        // Das Concello stempelt auch — in Galicien die verlaesslichste Adresse.
        $apply('035_stempel_concello', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/035_stempel_concello.php';
            migration_035($db);
        });

        // Videos im Tagebuch — dieselbe Tabelle wie die Fotos.
        $apply('036_videos', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/036_videos.php';
            migration_036($db);
        });

        // Redondela als Ausweichquartier, falls Arcade voll ist.
        $apply('037_redondela', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/037_redondela.php';
            migration_037($db);
        });

        // Pontevedra gebucht — fuer die Nacht nach E8, nicht nach E9.
        $apply('038_pontevedra', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/038_pontevedra.php';
            migration_038($db);
        });

        // Die letzten fuenf Tage, wie sie wirklich laufen.
        $apply('039_letzte_etappen', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/039_letzte_etappen.php';
            migration_039($db);
        });

        // Die Stempelsuchen zeigten noch auf Baiona.
        $apply('040_stempelorte_nachziehen', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/040_stempelorte_nachziehen.php';
            migration_040($db);
        });

        // Was die Gastgeber in Caldas und Padron geschrieben haben.
        $apply('041_gastgebernachrichten', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/041_gastgebernachrichten.php';
            migration_041($db);
        });

        // Caldas will Name, Geburtsdatum und Passnummer vorab.
        $apply('042_caldas_checkin', static function (Database $db): void {
            require_once APP_ROOT . '/db/migrations/042_caldas_checkin.php';
            migration_042($db);
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
                points $txt NOT NULL,
                quelle $str NOT NULL DEFAULT 'plan'
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
