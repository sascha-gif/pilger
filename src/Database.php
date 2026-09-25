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

    /**
     * Spaltennamen einer Tabelle. Migrationen fragen damit nach, bevor sie
     * etwas anlegen — ein zweiter Durchlauf soll nicht am „gibt es schon"
     * scheitern und die Migration dauerhaft rot stehen lassen.
     *
     * @return array<int,string>
     */
    public function columns(string $table): array
    {
        if ($this->driver === 'mysql') {
            return array_map(
                static fn ($r) => (string) $r['Field'],
                $this->all('SHOW COLUMNS FROM ' . $table)
            );
        }
        return array_map(
            static fn ($r) => (string) $r['name'],
            $this->all('PRAGMA table_info(' . $table . ')')
        );
    }

    /** Spalte anlegen, falls sie fehlt. */
    public function addColumn(string $table, string $name, string $definition): void
    {
        if (in_array($name, $this->columns($table), true)) {
            return;
        }
        $this->exec("ALTER TABLE $table ADD COLUMN $name $definition");
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

    /**
     * Einen Wert in `settings` setzen — in **einem** Befehl.
     *
     * Vorher stand an drei Stellen DELETE und danach INSERT, ohne Klammer
     * darum: beim Tagebuch-Schlüssel, beim Google-Token und beim Passwort der
     * Seite. Geht das INSERT schief oder bricht der Aufruf dazwischen ab, ist
     * der Wert weg — und nichts sagt es. Beim Passwort hieße das: die Seite
     * stünde ohne Passwort da und fragte beim nächsten Aufruf nach einem neuen.
     *
     * `skey` ist Primärschlüssel, also kann beides ein Befehl sein. Ein
     * Befehl kann nicht halb misslingen.
     */
    public function setSetting(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            $this->run('DELETE FROM settings WHERE skey = ?', [$key]);
            return;
        }
        $sql = $this->driver() === 'mysql'
            ? 'INSERT INTO settings (skey, svalue) VALUES (?, ?)
               ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
            : 'INSERT INTO settings (skey, svalue) VALUES (?, ?)
               ON CONFLICT(skey) DO UPDATE SET svalue = excluded.svalue';

        try {
            $this->run($sql, [$key, $value]);
            return;
        } catch (Throwable $e) {
            /* Der MySQL-Zweig lässt sich aus der Entwicklungsumgebung heraus
               nicht ausprobieren — dort gibt es keine MariaDB. Die Schreibweise
               ist Standard und MariaDB kennt sie seit jeher, aber an diesem
               Wert hängt unter anderem das Passwort der Seite. Fällt sie wider
               Erwarten durch, tut es der alte Weg weiterhin; eingeklammert,
               damit er nicht auf halber Strecke stehen bleibt. */
            error_log('pilger: setSetting per Upsert fehlgeschlagen — ' . $e->getMessage());
        }

        $this->transaction(function (Database $db) use ($key, $value): void {
            $db->run('DELETE FROM settings WHERE skey = ?', [$key]);
            $db->run('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
        });
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
