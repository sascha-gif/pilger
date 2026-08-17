<?php
declare(strict_types=1);

/**
 * Tabellen für den Zutritt: gemerkte Geräte und Fehlversuche.
 *
 * Im Cookie steht ein Zufallswert, hier nur dessen SHA-256. Wer die Tabelle
 * liest, kann sich damit nicht anmelden.
 */
function migration_004(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $hash  = $mysql ? 'VARCHAR(64)'  : 'TEXT';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
        id $pk,
        token_hash $hash NOT NULL,
        created_at $str NOT NULL,
        seen_at $str NULL,
        expires_at $str NOT NULL
    )$tail");

    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id $pk,
        ip $str NOT NULL,
        at $str NOT NULL
    )$tail");

    // Nachschlagen passiert bei jedem Aufruf mit Cookie — dafür lohnt der Index.
    try {
        $db->exec('CREATE INDEX idx_auth_tokens_hash ON auth_tokens (token_hash' . ($mysql ? '(64)' : '') . ')');
    } catch (Throwable $e) {
        // Index existiert schon — kein Grund, die Migration scheitern zu lassen.
    }
    try {
        $db->exec('CREATE INDEX idx_login_attempts_ip ON login_attempts (ip' . ($mysql ? '(64)' : '') . ')');
    } catch (Throwable $e) {
    }
}
