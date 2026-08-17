<?php
declare(strict_types=1);

/**
 * Schritte, Kalorien und Puls aus dem Google-Health-Konto.
 *
 * Wichtig zur Einordnung, weil es sich oft anders herum herumspricht:
 * Google Fit ist für uns tot. Neue Entwickler nimmt die Fit-REST-Schnittstelle
 * seit dem 1. Mai 2024 nicht mehr an, und Ende 2026 wird sie abgeschaltet.
 * Health Connect ist der Nachfolger, läuft aber ausschließlich auf dem Gerät —
 * ein Server kommt da nie heran. Der einzige Weg von hier aus ist die
 * Google Health API (health.googleapis.com/v4), die das Fitbit-Konto liest.
 *
 * Aufbau und Feldnamen unten stammen aus Googles eigenem Discovery-Dokument
 * (`health.googleapis.com/$discovery/rest?version=v4`), nicht aus zweiter Hand.
 *
 * Fehlt ein Tag in der Antwort, hat das Gerät an dem Tag nicht synchronisiert.
 * Das ist nicht dasselbe wie null Schritte und wird deshalb auch nicht als
 * Null gespeichert.
 */
final class Gesundheit
{
    private const AUTH   = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN  = 'https://oauth2.googleapis.com/token';
    private const WIDERRUF = 'https://oauth2.googleapis.com/revoke';
    private const BASIS  = 'https://health.googleapis.com/v4/users/me/dataTypes/';

    /** Ohne diese beiden Rechte gibt es weder Schritte noch Ruhepuls. */
    private const RECHTE = [
        'https://www.googleapis.com/auth/googlehealth.activity_and_fitness.readonly',
        'https://www.googleapis.com/auth/googlehealth.health_metrics_and_measurements.readonly',
    ];

    /**
     * Datentypen und ihre Fenstergrenze. Google lässt für Puls, aktive Minuten
     * und Kalorien nur 14 Tage je Anfrage zu, für den Rest 90.
     */
    private const TYPEN = [
        'steps'                    => 90,
        'distance'                 => 90,
        'total-calories'           => 14,
        'heart-rate'               => 14,
        'active-minutes'           => 14,
        'daily-resting-heart-rate' => 90,
    ];

    public function __construct(private Database $db)
    {
    }

    /* ================= Einstellungen ===================================== */

    private function wert(string $key): ?string
    {
        $v = $this->db->value('SELECT svalue FROM settings WHERE skey = ?', [$key]);
        return ($v === null || $v === '') ? null : (string) $v;
    }

    private function setze(string $key, ?string $wert): void
    {
        $this->db->run('DELETE FROM settings WHERE skey = ?', [$key]);
        if ($wert !== null && $wert !== '') {
            $this->db->run('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $wert]);
        }
    }

    public function zugangSetzen(?string $clientId, ?string $secret): void
    {
        if ($clientId !== null) { $this->setze('google_client_id', trim($clientId)); }
        if ($secret !== null)   { $this->setze('google_client_secret', trim($secret)); }
    }

    public function stand(): array
    {
        $letzter = $this->db->value('SELECT MAX(day_iso) FROM health_days');
        return [
            'zugang'    => $this->wert('google_client_id') !== null && $this->wert('google_client_secret') !== null,
            'client_id' => $this->wert('google_client_id'),
            'verbunden' => $this->wert('google_refresh_token') !== null,
            'letzterTag' => $letzter === null ? null : (string) $letzter,
            'geholt'    => $this->wert('google_health_stand'),
            'tage'      => (int) $this->db->value('SELECT COUNT(*) FROM health_days'),
            'fehler'    => $this->wert('google_health_fehler'),
        ];
    }

    /* ================= Anmeldung bei Google ============================== */

    public function weiterleitung(): string
    {
        $schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https' ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'pilger.milsh.com');
        return $schema . '://' . $host . '/gesundheit.php';
    }

    public function anmeldeUrl(): ?string
    {
        $id = $this->wert('google_client_id');
        if ($id === null) {
            return null;
        }
        $staat = bin2hex(random_bytes(16));
        $_SESSION['google_state'] = $staat;

        return self::AUTH . '?' . http_build_query([
            'client_id'     => $id,
            'redirect_uri'  => $this->weiterleitung(),
            'response_type' => 'code',
            'scope'         => implode(' ', self::RECHTE),
            // Ohne beides gibt es kein Aktualisierungs-Token, und dann wäre
            // nach einer Stunde Schluss.
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $staat,
        ]);
    }

    /** @return array{ok:bool,meldung:string} */
    public function codeEinloesen(string $code, string $staat): array
    {
        if (!hash_equals((string) ($_SESSION['google_state'] ?? ''), $staat)) {
            return ['ok' => false, 'meldung' => 'Die Rückmeldung passt nicht zur Anfrage. Bitte noch einmal verbinden.'];
        }
        unset($_SESSION['google_state']);

        $antwort = $this->postForm(self::TOKEN, [
            'code'          => $code,
            'client_id'     => (string) $this->wert('google_client_id'),
            'client_secret' => (string) $this->wert('google_client_secret'),
            'redirect_uri'  => $this->weiterleitung(),
            'grant_type'    => 'authorization_code',
        ]);

        if (!isset($antwort['refresh_token'])) {
            $grund = $antwort['error_description'] ?? $antwort['error'] ?? 'keine Antwort';
            return ['ok' => false, 'meldung' => 'Google hat kein Dauer-Token geschickt: ' . $grund];
        }

        $this->setze('google_refresh_token', (string) $antwort['refresh_token']);
        $this->setze('google_access_token', (string) ($antwort['access_token'] ?? ''));
        $this->setze('google_token_ablauf', (string) (time() + (int) ($antwort['expires_in'] ?? 3600) - 60));
        $this->setze('google_health_fehler', null);

        // Google verrät hier, ob die App noch im Testbetrieb steht: dann läuft
        // das Dauer-Token nach einer Woche ab. Das gehört gesagt, bevor es
        // mitten auf dem Camino auffällt.
        $ablauf = isset($antwort['refresh_token_expires_in'])
            ? (int) $antwort['refresh_token_expires_in'] : 0;
        $hinweis = $ablauf > 0 && $ablauf < 40 * 86400
            ? ' Achtung: die Verbindung läuft in ' . (int) round($ablauf / 86400) . ' Tagen ab, weil die App '
              . 'in der Google Cloud noch auf „Testing" steht. Auf „In production" umstellen, dann bleibt sie.'
            : '';

        return ['ok' => true, 'meldung' => 'Verbunden.' . $hinweis];
    }

    public function trennen(): void
    {
        $token = $this->wert('google_refresh_token');
        if ($token !== null) {
            $this->postForm(self::WIDERRUF, ['token' => $token]);
        }
        foreach (['google_refresh_token', 'google_access_token', 'google_token_ablauf', 'google_health_fehler'] as $k) {
            $this->setze($k, null);
        }
    }

    private function zugriffsToken(): ?string
    {
        $ablauf = (int) ($this->wert('google_token_ablauf') ?? 0);
        $token  = $this->wert('google_access_token');
        if ($token !== null && $ablauf > time()) {
            return $token;
        }

        $refresh = $this->wert('google_refresh_token');
        if ($refresh === null) {
            return null;
        }

        $antwort = $this->postForm(self::TOKEN, [
            'client_id'     => (string) $this->wert('google_client_id'),
            'client_secret' => (string) $this->wert('google_client_secret'),
            'refresh_token' => $refresh,
            'grant_type'    => 'refresh_token',
        ]);

        if (!isset($antwort['access_token'])) {
            // invalid_grant heißt fast immer: Token abgelaufen oder Zugriff
            // entzogen. Dann ist die Verbindung weg und muss neu gemacht werden.
            $grund = $antwort['error'] ?? 'unbekannt';
            $this->setze('google_health_fehler', $grund === 'invalid_grant'
                ? 'Die Verbindung zu Google ist abgelaufen — bitte neu verbinden.'
                : 'Google antwortet nicht wie erwartet: ' . $grund);
            if ($grund === 'invalid_grant') {
                $this->setze('google_refresh_token', null);
            }
            return null;
        }

        $this->setze('google_access_token', (string) $antwort['access_token']);
        $this->setze('google_token_ablauf', (string) (time() + (int) ($antwort['expires_in'] ?? 3600) - 60));
        return (string) $antwort['access_token'];
    }

    /* ================= Daten holen ======================================= */

    /**
     * Tage von `$von` bis `$bis` (jeweils einschließlich) holen und ablegen.
     *
     * @return array{ok:bool,meldung:string,tage:int}
     */
    public function holen(string $von, string $bis): array
    {
        $token = $this->zugriffsToken();
        if ($token === null) {
            return ['ok' => false, 'meldung' => $this->wert('google_health_fehler') ?? 'Nicht mit Google verbunden.', 'tage' => 0];
        }

        $gesammelt = [];
        foreach (self::TYPEN as $typ => $maxTage) {
            foreach ($this->abschnitte($von, $bis, $maxTage) as [$a, $b]) {
                $punkte = $this->rollUp($typ, $a, $b, $token);
                if ($punkte === null) {
                    // Ein Typ kann fehlen — etwa weil das Konto ihn nicht führt.
                    // Das darf die anderen nicht mitreißen.
                    continue;
                }
                foreach ($punkte as $p) {
                    $tag = $this->tagAus($p);
                    if ($tag === null) {
                        continue;
                    }
                    $gesammelt[$tag] = ($gesammelt[$tag] ?? []) + $this->werteAus($p);
                }
            }
        }

        foreach ($gesammelt as $tag => $werte) {
            $this->merke($tag, $werte);
        }

        $this->setze('google_health_stand', date('c'));
        if ($gesammelt) {
            $this->setze('google_health_fehler', null);
        }

        return [
            'ok'      => true,
            'meldung' => $gesammelt
                ? count($gesammelt) . ' Tage geholt.'
                : 'Google hat für den Zeitraum nichts geliefert — meist heißt das, die Uhr hat nicht synchronisiert.',
            'tage'    => count($gesammelt),
        ];
    }

    /** Zeitraum in Stücke schneiden, die Google auch annimmt. */
    private function abschnitte(string $von, string $bis, int $maxTage): array
    {
        $out = [];
        $a = new DateTimeImmutable($von);
        $ende = new DateTimeImmutable($bis);
        while ($a <= $ende) {
            $b = $a->modify('+' . ($maxTage - 1) . ' days');
            if ($b > $ende) {
                $b = $ende;
            }
            $out[] = [$a->format('Y-m-d'), $b->format('Y-m-d')];
            $a = $b->modify('+1 day');
        }
        return $out;
    }

    /**
     * Ein Datentyp, ein Zeitraum. Das Ende ist bei Google ausschließend —
     * wer den letzten Tag mit haben will, muss einen Tag weiter fragen.
     */
    private function rollUp(string $typ, string $von, string $bis, string $token): ?array
    {
        $ende = (new DateTimeImmutable($bis))->modify('+1 day');

        $antwort = $this->postJson(
            self::BASIS . $typ . '/dataPoints:dailyRollUp',
            [
                'range' => [
                    'start' => ['date' => self::datum(new DateTimeImmutable($von))],
                    'end'   => ['date' => self::datum($ende)],
                ],
                'windowSizeDays' => 1,
                'pageSize'       => 1000,
            ],
            $token
        );

        return $antwort['rollupDataPoints'] ?? null;
    }

    /** @return array{year:int,month:int,day:int} */
    private static function datum(DateTimeImmutable $d): array
    {
        return ['year' => (int) $d->format('Y'), 'month' => (int) $d->format('n'), 'day' => (int) $d->format('j')];
    }

    private function tagAus(array $punkt): ?string
    {
        $d = $punkt['civilStartTime']['date'] ?? null;
        if (!is_array($d) || !isset($d['year'], $d['month'], $d['day'])) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $d['year'], $d['month'], $d['day']);
    }

    /** Nur die Felder herausziehen, die auf der Seite auch auftauchen. */
    private function werteAus(array $p): array
    {
        $w = [];
        if (isset($p['steps']['countSum']))            { $w['steps']     = (int) $p['steps']['countSum']; }
        if (isset($p['totalCalories']['kcalSum']))     { $w['kcal']      = (int) round((float) $p['totalCalories']['kcalSum']); }
        if (isset($p['distance']['millimetersSum']))   { $w['distanz_m'] = (int) round(((int) $p['distance']['millimetersSum']) / 1000); }
        if (isset($p['heartRate']['beatsPerMinuteAvg'])) { $w['hr_avg'] = (int) round((float) $p['heartRate']['beatsPerMinuteAvg']); }
        if (isset($p['heartRate']['beatsPerMinuteMin'])) { $w['hr_min'] = (int) round((float) $p['heartRate']['beatsPerMinuteMin']); }
        if (isset($p['heartRate']['beatsPerMinuteMax'])) { $w['hr_max'] = (int) round((float) $p['heartRate']['beatsPerMinuteMax']); }
        if (isset($p['restingHeartRatePersonalRange']['beatsPerMinuteMin'])) {
            $w['hr_ruhe'] = (int) round((float) $p['restingHeartRatePersonalRange']['beatsPerMinuteMin']);
        }
        if (isset($p['activeMinutes']['activeMinutesRollupByActivityLevel'])) {
            $summe = 0;
            foreach ($p['activeMinutes']['activeMinutesRollupByActivityLevel'] as $stufe) {
                $summe += (int) ($stufe['minutesSum'] ?? 0);
            }
            if ($summe > 0) { $w['aktiv_min'] = $summe; }
        }
        return $w;
    }

    private function merke(string $tag, array $werte): void
    {
        if (!$werte) {
            return;
        }
        $vorhanden = $this->db->one('SELECT day_iso FROM health_days WHERE day_iso = ?', [$tag]);
        $werte['geholt_at'] = date('c');

        if ($vorhanden === null) {
            $spalten = array_keys($werte);
            $this->db->run(
                'INSERT INTO health_days (day_iso, ' . implode(', ', $spalten) . ') VALUES (?' . str_repeat(', ?', count($spalten)) . ')',
                array_merge([$tag], array_values($werte))
            );
            return;
        }

        $setz = [];
        foreach (array_keys($werte) as $s) {
            $setz[] = $s . ' = ?';
        }
        $this->db->run(
            'UPDATE health_days SET ' . implode(', ', $setz) . ' WHERE day_iso = ?',
            array_merge(array_values($werte), [$tag])
        );
    }

    /* ================= Lesen ============================================= */

    /** @return array<string,array<string,mixed>> Tag => Werte */
    public function tage(?string $von = null, ?string $bis = null): array
    {
        $sql = 'SELECT * FROM health_days';
        $p = [];
        if ($von !== null && $bis !== null) {
            $sql .= ' WHERE day_iso BETWEEN ? AND ?';
            $p = [$von, $bis];
        }
        $out = [];
        foreach ($this->db->all($sql . ' ORDER BY day_iso', $p) as $r) {
            $out[(string) $r['day_iso']] = $r;
        }
        return $out;
    }

    /**
     * Mittel je Tag über einen Zeitraum. Gemittelt wird über die Tage, an denen
     * es Daten gibt — sonst zöge ein Tag ohne Synchronisierung den Schnitt
     * grundlos nach unten.
     */
    public function schnitt(string $von, string $bis): array
    {
        $r = $this->db->one(
            'SELECT AVG(steps) s, AVG(kcal) k, AVG(hr_ruhe) hr, COUNT(steps) n
               FROM health_days WHERE day_iso BETWEEN ? AND ?',
            [$von, $bis]
        );
        return [
            'schritte' => $r && $r['s'] !== null ? (int) round((float) $r['s']) : null,
            'kcal'     => $r && $r['k'] !== null ? (int) round((float) $r['k']) : null,
            'ruhepuls' => $r && $r['hr'] !== null ? (int) round((float) $r['hr']) : null,
            'tage'     => $r ? (int) $r['n'] : 0,
        ];
    }

    /* ================= HTTP ============================================== */

    private function postForm(string $url, array $felder): array
    {
        return $this->post($url, http_build_query($felder), ['Content-Type: application/x-www-form-urlencoded']);
    }

    private function postJson(string $url, array $daten, string $token): array
    {
        return $this->post($url, json_encode($daten), [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
    }

    private function post(string $url, string $koerper, array $kopf): array
    {
        if (!function_exists('curl_init')) {
            return ['error' => 'curl fehlt auf dem Server'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_HTTPHEADER     => $kopf,
            CURLOPT_POSTFIELDS     => $koerper,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $netz = curl_error($ch);
        curl_close($ch);

        if (!is_string($body)) {
            return ['error' => $netz ?: 'keine Antwort'];
        }
        $daten = json_decode($body, true);
        if (!is_array($daten)) {
            return ['error' => 'HTTP ' . $code . ': ' . substr($body, 0, 200)];
        }
        if ($code >= 400) {
            error_log('pilger: Google Health HTTP ' . $code . ' — ' . substr($body, 0, 400));
            $daten['error'] = $daten['error']['message'] ?? $daten['error'] ?? ('HTTP ' . $code);
        }
        return $daten;
    }
}
