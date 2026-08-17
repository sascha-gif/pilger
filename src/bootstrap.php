<?php
/**
 * Gemeinsamer Einstiegspunkt für index.php und api.php.
 * Lädt Konfiguration, öffnet die Datenbank und spielt Schema + Seed ein,
 * falls die Datenbank noch leer ist.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/src/helpers.php';
require APP_ROOT . '/src/Database.php';
require APP_ROOT . '/src/Schema.php';
require APP_ROOT . '/src/Repo.php';
require APP_ROOT . '/src/Auth.php';
require APP_ROOT . '/src/Aussen.php';

/** @return array<string,mixed> */
function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $defaults = [
        'driver'         => 'sqlite',
        'mysql'          => ['host' => 'localhost', 'port' => 3306, 'name' => 'pilger', 'user' => 'pilger', 'pass' => '', 'charset' => 'utf8mb4'],
        'sqlite'         => ['path' => 'var/pilger.sqlite'],
        // Fotos und Sprachaufnahmen liegen bewusst außerhalb des Images: der
        // Container wird bei jedem Deploy neu gebaut, ein eigenes Volume nicht.
        'data_dir'       => APP_ROOT . '/var/data',
        'write_password' => null,
        'auto_migrate'   => true,
        'debug'          => false,
    ];

    $file = APP_ROOT . '/config/config.php';
    $local = is_readable($file) ? require $file : [];
    if (!is_array($local)) {
        $local = [];
    }

    $config = array_replace_recursive($defaults, $local);

    // Umgebungsvariablen haben Vorrang — so kommt der Docker-Container ganz
    // ohne config.php aus und bekommt alles über docker-compose.
    $env = static fn (string $key) => ($v = getenv($key)) === false || $v === '' ? null : $v;

    if ($v = $env('PILGER_DB_DRIVER')) {
        $config['driver'] = $v === 'sqlite' ? 'sqlite' : 'mysql';
    }
    foreach (['HOST' => 'host', 'PORT' => 'port', 'NAME' => 'name', 'USER' => 'user', 'PASS' => 'pass'] as $suffix => $key) {
        if (($v = $env('PILGER_DB_' . $suffix)) !== null) {
            $config['mysql'][$key] = $key === 'port' ? (int) $v : $v;
            $config['driver'] = $config['driver'] === 'sqlite' && $env('PILGER_DB_DRIVER') === null ? 'mysql' : $config['driver'];
        }
    }
    if (($v = $env('PILGER_SQLITE_PATH')) !== null) {
        $config['sqlite']['path'] = $v;
    }
    if (($v = $env('PILGER_DATA_DIR')) !== null) {
        $config['data_dir'] = rtrim($v, '/');
    }
    if (($v = $env('PILGER_WRITE_PASSWORD')) !== null) {
        $config['write_password'] = $v;
    }
    if (($v = $env('PILGER_DEBUG')) !== null) {
        $config['debug'] = filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    return $config;
}

$config = app_config();

if (!empty($config['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

try {
    $db = new Database($config);

    if (!empty($config['auto_migrate'])) {
        Schema::migrate($db);
    }
} catch (Throwable $e) {
    app_fail('Die Datenbank ist gerade nicht erreichbar.', $e, !empty($config['debug']));
}

$repo = new Repo($db);

if (session_status() === PHP_SESSION_NONE) {
    // Die Sitzung soll eine Reise überdauern, nicht einen Nachmittag.
    session_set_cookie_params([
        'lifetime' => 30 * 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$auth = new Auth($db, $config['write_password'] === null ? null : (string) $config['write_password']);
auth_instance($auth);
$auth->restore();

/**
 * Zugriff auf den Zutritt aus einfachen Funktionen heraus.
 * (Ein Objekt herumreichen wäre sauberer, lohnt bei einer Datei aber nicht.)
 */
function auth_instance(?Auth $set = null): Auth
{
    static $instance = null;
    if ($set !== null) {
        $instance = $set;
    }
    return $instance;
}

/**
 * Darf der aktuelle Besucher etwas speichern?
 * Ohne gesetztes Passwort ist die Antwort nein — eine frische Datenbank steht
 * damit zu und nicht offen.
 */
function may_write(): bool
{
    $auth = auth_instance();
    return $auth->isConfigured() && $auth->isLoggedIn();
}

/** Verzeichnis für Fotos und Aufnahmen, bei Bedarf angelegt. */
function data_path(string $sub = ''): string
{
    $base = rtrim((string) app_config()['data_dir'], '/');
    $path = $sub === '' ? $base : $base . '/' . ltrim($sub, '/');
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    return $path;
}
