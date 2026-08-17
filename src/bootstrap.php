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

$db = new Database($config);

if (!empty($config['auto_migrate'])) {
    Schema::migrate($db);
}

$repo = new Repo($db);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Darf der aktuelle Besucher Änderungen speichern? */
function may_write(): bool
{
    $pass = app_config()['write_password'];
    if ($pass === null || $pass === '') {
        return true;
    }
    return !empty($_SESSION['pilger_write']);
}
