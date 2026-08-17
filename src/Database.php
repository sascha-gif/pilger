<?php
declare(strict_types=1);

/**
 * Dünne PDO-Hülle. Spricht MariaDB/MySQL (Hetzner) und SQLite (Fallback,
 * damit die App auch ohne DB-Server sofort läuft).
 */
final class Database
{
    private PDO $pdo;
    private string $driver;

    public function __construct(array $config)
    {
        $this->driver = $config['driver'] === 'mysql' ? 'mysql' : 'sqlite';

        if ($this->driver === 'mysql') {
            $m = $config['mysql'];
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $m['host'],
                (int) $m['port'],
                $m['name'],
                $m['charset'] ?? 'utf8mb4'
            );
            $this->pdo = new PDO($dsn, $m['user'], $m['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } else {
            $path = $config['sqlite']['path'] ?? 'var/pilger.sqlite';
            if ($path[0] !== '/') {
                $path = APP_ROOT . '/' . $path;
            }
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $this->pdo = new PDO('sqlite:' . $path, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA journal_mode = WAL');
        }
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    /** @param array<string|int,mixed> $params */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** @return array<int,array<string,mixed>> */
    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function value(string $sql, array $params = [])
    {
        $val = $this->run($sql, $params)->fetchColumn();
        return $val === false ? null : $val;
    }

    public function tableExists(string $table): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM ' . $table . ' LIMIT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function transaction(callable $fn): void
    {
        $this->pdo->beginTransaction();
        try {
            $fn($this);
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
