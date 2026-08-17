<?php
declare(strict_types=1);

/**
 * Zutritt zur Seite.
 *
 * Ein Passwort für alles: ohne Anmeldung ist nichts zu sehen und nichts zu
 * ändern. Sobald Tagebuch und Fotos drin sind, ist das keine Bequemlichkeit
 * mehr, sondern die Grundlage — die Adresse allein darf nicht reichen.
 *
 * Das Passwort wird in der Oberfläche gesetzt, nicht im Terminal. Es liegt als
 * Hash in `settings`, nie im Klartext und nie im Repo. Wer lieber die .env
 * pflegt, setzt PILGER_WRITE_PASSWORD — dieser Wert gewinnt dann.
 *
 * Solange kein Passwort gesetzt ist, zeigt die Seite nichts als die
 * Einrichtung. Das ist die sichere Richtung: eine Datenbank, die gerade neu
 * angelegt wurde, steht damit zu und nicht offen.
 */
final class Auth
{
    private const COOKIE    = 'pilger_zutritt';
    private const SETTING   = 'auth_hash';
    private const REMEMBER  = 180 * 86400;   // ein halbes Jahr — die Reise dauert zwei Wochen
    private const MAX_TRIES = 10;
    private const WINDOW    = 900;           // 15 Minuten

    public function __construct(private Database $db, private ?string $envPassword)
    {
    }

    /* ---- Zustand -------------------------------------------------------- */

    public function isConfigured(): bool
    {
        return $this->envPassword() !== null || $this->storedHash() !== null;
    }

    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['pilger_write']);
    }

    /** Kommt das Passwort aus der .env, kann es die Oberfläche nicht ändern. */
    public function fromEnv(): bool
    {
        return $this->envPassword() !== null;
    }

    /* ---- Anmelden ------------------------------------------------------- */

    public function verify(string $plain): bool
    {
        $env = $this->envPassword();
        if ($env !== null) {
            return hash_equals($env, $plain);
        }
        $hash = $this->storedHash();
        return $hash !== null && password_verify($plain, $hash);
    }

    public function login(bool $remember): void
    {
        session_regenerate_id(true);
        $_SESSION['pilger_write'] = true;
        if ($remember) {
            $this->issueToken();
        }
    }

    public function logout(): void
    {
        unset($_SESSION['pilger_write']);
        $raw = (string) ($_COOKIE[self::COOKIE] ?? '');
        if ($raw !== '') {
            $this->db->run('DELETE FROM auth_tokens WHERE token_hash = ?', [hash('sha256', $raw)]);
        }
        $this->clearCookie();
    }

    /**
     * Passwort setzen oder ändern. Alle gemerkten Geräte fliegen dabei raus —
     * ein Passwortwechsel, der alte Sitzungen stehen lässt, ist keiner.
     */
    public function setPassword(string $plain): void
    {
        $this->putSetting(self::SETTING, password_hash($plain, PASSWORD_DEFAULT));
        $this->db->exec('DELETE FROM auth_tokens');
    }

    /**
     * Anmeldung von einem gemerkten Gerät wiederherstellen.
     * Im Cookie steht ein Zufallswert, in der Datenbank nur dessen Hash — wer
     * die Tabelle liest, kann sich damit trotzdem nicht anmelden.
     */
    public function restore(): void
    {
        if ($this->isLoggedIn()) {
            return;
        }
        $raw = (string) ($_COOKIE[self::COOKIE] ?? '');
        if ($raw === '' || !$this->isConfigured()) {
            return;
        }

        $row = $this->db->one(
            'SELECT id, expires_at FROM auth_tokens WHERE token_hash = ?',
            [hash('sha256', $raw)]
        );

        if ($row === null || strtotime((string) $row['expires_at']) < time()) {
            if ($row !== null) {
                $this->db->run('DELETE FROM auth_tokens WHERE id = ?', [(int) $row['id']]);
            }
            $this->clearCookie();
            return;
        }

        $_SESSION['pilger_write'] = true;
        $this->db->run('UPDATE auth_tokens SET seen_at = ? WHERE id = ?', [date('c'), (int) $row['id']]);
    }

    /* ---- Bremse gegen Durchprobieren ------------------------------------ */

    public function blockedFor(): int
    {
        $since = date('c', time() - self::WINDOW);
        $n = (int) $this->db->value(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND at > ?',
            [$this->ip(), $since]
        );
        if ($n < self::MAX_TRIES) {
            return 0;
        }
        $oldest = (string) $this->db->value(
            'SELECT MIN(at) FROM login_attempts WHERE ip = ? AND at > ?',
            [$this->ip(), $since]
        );
        return max(1, (int) ceil((strtotime($oldest) + self::WINDOW - time()) / 60));
    }

    public function noteFailure(): void
    {
        $this->db->run('INSERT INTO login_attempts (ip, at) VALUES (?, ?)', [$this->ip(), date('c')]);
        $this->db->run('DELETE FROM login_attempts WHERE at < ?', [date('c', time() - 3 * self::WINDOW)]);
        usleep(400000);   // ein bisschen Sand im Getriebe
    }

    public function clearFailures(): void
    {
        $this->db->run('DELETE FROM login_attempts WHERE ip = ?', [$this->ip()]);
    }

    /* ---- Innereien ------------------------------------------------------ */

    private function envPassword(): ?string
    {
        return ($this->envPassword === null || $this->envPassword === '') ? null : $this->envPassword;
    }

    private function storedHash(): ?string
    {
        try {
            $v = $this->db->value('SELECT svalue FROM settings WHERE skey = ?', [self::SETTING]);
        } catch (Throwable $e) {
            return null;
        }
        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function putSetting(string $key, string $value): void
    {
        $this->db->run('DELETE FROM settings WHERE skey = ?', [$key]);
        $this->db->run('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
    }

    private function issueToken(): void
    {
        $token = bin2hex(random_bytes(32));
        $this->db->run(
            'INSERT INTO auth_tokens (token_hash, created_at, seen_at, expires_at) VALUES (?,?,?,?)',
            [hash('sha256', $token), date('c'), date('c'), date('c', time() + self::REMEMBER)]
        );
        $this->db->run('DELETE FROM auth_tokens WHERE expires_at < ?', [date('c')]);
        setcookie(self::COOKIE, $token, $this->cookieOptions(time() + self::REMEMBER));
    }

    private function clearCookie(): void
    {
        setcookie(self::COOKIE, '', $this->cookieOptions(time() - 3600));
    }

    /** @return array<string,mixed> */
    private function cookieOptions(int $expires): array
    {
        return [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => $this->https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    /**
     * Hinter Caddy spricht Apache nur HTTP — ob der Besucher über TLS kam,
     * steht deshalb im X-Forwarded-Proto und nicht in $_SERVER['HTTPS'].
     */
    private function https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    private function ip(): string
    {
        $fwd = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($fwd !== '') {
            $first = trim(explode(',', $fwd)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unbekannt');
    }
}
