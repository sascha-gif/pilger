<?php
declare(strict_types=1);

/**
 * Tagebuch und Fotos.
 *
 * `client_id` ist der Schlüssel für die Offline-Fähigkeit: der Browser vergibt
 * ihn schon beim Aufnehmen, lange bevor Netz da ist. Wird ein Upload später
 * doppelt versucht — weil das Handy zwischendurch das Netz verloren hat —,
 * erkennt der Server daran, dass es derselbe Eintrag ist, und legt ihn nicht
 * ein zweites Mal an.
 *
 * Die Dateien selbst liegen nicht in der Datenbank, sondern im Volume unter
 * PILGER_DATA_DIR. Hier steht nur, wie sie heißen.
 */
function migration_007(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $kurz  = $mysql ? 'VARCHAR(64)'  : 'TEXT';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS diary_entries (
        id $pk,
        stage_id INT NULL,
        client_id $kurz NULL,
        day_iso " . ($mysql ? 'VARCHAR(10)' : 'TEXT') . " NULL,
        kind $kurz NOT NULL DEFAULT 'text',
        text_raw TEXT NULL,
        text_clean TEXT NULL,
        audio_file $str NULL,
        audio_seconds INT NULL,
        status $kurz NOT NULL DEFAULT 'neu',
        status_note TEXT NULL,
        created_at $str NOT NULL,
        updated_at $str NULL
    )$tail");

    $db->exec("CREATE TABLE IF NOT EXISTS photos (
        id $pk,
        stage_id INT NULL,
        entry_id INT NULL,
        client_id $kurz NULL,
        file $str NOT NULL,
        thumb $str NULL,
        caption TEXT NULL,
        width INT NULL,
        height INT NULL,
        bytes INT NULL,
        taken_at $str NULL,
        created_at $str NOT NULL
    )$tail");

    foreach ([
        ['diary_entries', 'idx_diary_client', 'client_id'],
        ['diary_entries', 'idx_diary_stage',  'stage_id'],
        ['photos',        'idx_photos_client', 'client_id'],
        ['photos',        'idx_photos_stage',  'stage_id'],
    ] as [$tabelle, $name, $spalte]) {
        try {
            $laenge = ($mysql && $spalte === 'client_id') ? '(64)' : '';
            $db->exec("CREATE INDEX $name ON $tabelle ($spalte$laenge)");
        } catch (Throwable $e) {
            // Gibt es schon — kein Grund, die Migration scheitern zu lassen.
        }
    }
}
