<?php
declare(strict_types=1);

/**
 * Was von außen kommt: Wetter und Höhen.
 *
 * Beides von Open-Meteo — kein Schlüssel nötig, freie Nutzung, und die Daten
 * sind echt. Geholt wird serverseitig und in `ext_cache` abgelegt: der Browser
 * spricht nie mit einem fremden Dienst, und der letzte Stand steht auch dann
 * noch da, wenn unterwegs kein Netz ist.
 *
 * Wichtig zur Einordnung: die Höhen liegen entlang der in der Datenbank
 * hinterlegten Küstenlinie, nicht entlang einer aufgezeichneten GPX-Spur. Für
 * „wo geht es hoch" reicht das; auf den letzten Meter genau ist es nicht, und
 * so steht es auch auf der Seite.
 */
final class Aussen
{
    private const FORECAST  = 'https://api.open-meteo.com/v1/forecast';
    private const ARCHIVE   = 'https://archive-api.open-meteo.com/v1/archive';
    private const ELEVATION = 'https://api.open-meteo.com/v1/elevation';

    public function __construct(private Database $db, private Repo $repo)
    {
    }

    /* ================= Zwischenspeicher ================================== */

    private function cached(string $key): ?array
    {
        $row = $this->db->one('SELECT payload, expires_at, fetched_at FROM ext_cache WHERE skey = ?', [$key]);
        if ($row === null) {
            return null;
        }
        $data = json_decode((string) $row['payload'], true);
        if (!is_array($data)) {
            return null;
        }
        return [
            'data'    => $data,
            'frisch'  => strtotime((string) $row['expires_at']) > time(),
            'geholt'  => (string) $row['fetched_at'],
        ];
    }

    private function store(string $key, array $data, int $ttl): void
    {
        $this->db->run('DELETE FROM ext_cache WHERE skey = ?', [$key]);
        $this->db->run(
            'INSERT INTO ext_cache (skey, payload, fetched_at, expires_at) VALUES (?,?,?,?)',
            [$key, json_encode($data, JSON_UNESCAPED_UNICODE), date('c'), date('c', time() + $ttl)]
        );
    }

    /* ================= HTTP ============================================== */

    /** @return array<mixed>|null */
    private function hole(string $url, array $query): ?array
    {
        $voll = $url . '?' . http_build_query($query);

        if (function_exists('curl_init')) {
            $ch = curl_init($voll);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_USERAGENT      => 'pilger.milsh.com',
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => ['timeout' => 20, 'user_agent' => 'pilger.milsh.com']]);
            $body = @file_get_contents($voll, false, $ctx);
            $code = $body === false ? 0 : 200;
        }

        if ($code !== 200 || !is_string($body)) {
            return null;
        }
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Open-Meteo antwortet auf eine Liste von Orten mit einer Liste von
     * Ergebnissen — bei genau einem Ort dagegen mit dem Ergebnis selbst.
     * Das hier bügelt den Unterschied glatt.
     *
     * @return array<int,array<string,mixed>>
     */
    private function alsListe(?array $antwort): array
    {
        if ($antwort === null) {
            return [];
        }
        if (isset($antwort[0]) && is_array($antwort[0])) {
            return $antwort;
        }
        return [$antwort];
    }

    /* ================= Wetter ============================================ */

    /**
     * Wetter je Etappentag.
     *
     * Bis 16 Tage im Voraus gibt es eine echte Vorhersage. Alles davor ist
     * keine — deshalb steht dort das Mittel derselben Kalendertage der letzten
     * fünf Jahre, und es steht auch dran, dass es ein Mittel ist. Eine
     * ausgedachte „Vorhersage" für September wäre schlicht gelogen.
     *
     * @return array<string,mixed>
     */
    public function wetter(bool $erneuern = false): array
    {
        $stages = array_values(array_filter(
            $this->repo->stages(),
            static fn ($s) => $s['date_iso'] && $s['lat'] !== null && $s['lng'] !== null
        ));
        if (!$stages) {
            return ['tage' => [], 'stand' => null];
        }

        $lats = implode(',', array_map(static fn ($s) => round((float) $s['lat'], 4), $stages));
        $lngs = implode(',', array_map(static fn ($s) => round((float) $s['lng'], 4), $stages));

        $tage   = [];
        $stand  = null;
        $ersteZ = $stages[0]['date_iso'];
        $letzte = $stages[count($stages) - 1]['date_iso'];

        // 1. Vorhersage, soweit sie reicht.
        $vorhersageReicht = strtotime($ersteZ) <= strtotime('+15 days');
        if ($vorhersageReicht) {
            $key = 'wetter.vorhersage';
            $c   = $erneuern ? null : $this->cached($key);
            if ($c === null || !$c['frisch']) {
                $roh = $this->hole(self::FORECAST, [
                    'latitude'   => $lats,
                    'longitude'  => $lngs,
                    'daily'      => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max',
                    'timezone'   => 'Europe/Lisbon',
                    'start_date' => $ersteZ,
                    'end_date'   => $letzte,
                ]);
                if ($roh !== null) {
                    $this->store($key, $this->alsListe($roh), 3 * 3600);
                    $c = $this->cached($key);
                }
            }
            if ($c !== null) {
                $stand = $c['geholt'];
                foreach ($stages as $i => $s) {
                    $ort = $c['data'][$i] ?? null;
                    $pos = $ort ? array_search($s['date_iso'], $ort['daily']['time'] ?? [], true) : false;
                    if ($pos === false) {
                        continue;
                    }
                    $tage[(int) $s['id']] = [
                        'quelle' => 'vorhersage',
                        'datum'  => $s['date_iso'],
                        'code'   => (int) ($ort['daily']['weather_code'][$pos] ?? 0),
                        'max'    => self::z($ort['daily']['temperature_2m_max'][$pos] ?? null),
                        'min'    => self::z($ort['daily']['temperature_2m_min'][$pos] ?? null),
                        'regen'  => self::z($ort['daily']['precipitation_sum'][$pos] ?? null),
                        'regenp' => self::z($ort['daily']['precipitation_probability_max'][$pos] ?? null),
                        'wind'   => self::z($ort['daily']['wind_speed_10m_max'][$pos] ?? null),
                    ];
                }
            }
        }

        // 2. Für alles, was noch zu weit weg ist: die Vorjahre.
        $fehlend = array_filter($stages, static fn ($s) => !isset($tage[(int) $s['id']]));
        if ($fehlend) {
            $mittel = $this->septemberMittel($stages, $lats, $lngs, $erneuern);
            foreach ($fehlend as $s) {
                $m = $mittel[(int) $s['id']] ?? null;
                if ($m !== null) {
                    $tage[(int) $s['id']] = $m;
                }
            }
            $stand ??= $mittel['_stand'] ?? null;
        }

        return ['tage' => $tage, 'stand' => $stand];
    }

    /**
     * Mittel der letzten fünf Jahre für denselben Kalendertag (± 2 Tage, damit
     * ein einzelner Ausreißer nicht das Bild bestimmt).
     *
     * @return array<int|string,mixed>
     */
    private function septemberMittel(array $stages, string $lats, string $lngs, bool $erneuern): array
    {
        $jahre = range((int) date('Y') - 5, (int) date('Y') - 1);
        $roh   = [];
        $stand = null;

        foreach ($jahre as $jahr) {
            $key = 'wetter.mittel.' . $jahr;
            $c   = $erneuern ? null : $this->cached($key);
            if ($c === null || !$c['frisch']) {
                $antwort = $this->hole(self::ARCHIVE, [
                    'latitude'   => $lats,
                    'longitude'  => $lngs,
                    'daily'      => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
                    'timezone'   => 'Europe/Lisbon',
                    'start_date' => $jahr . '-09-14',
                    'end_date'   => $jahr . '-10-04',
                ]);
                if ($antwort !== null) {
                    $this->store($key, $this->alsListe($antwort), 30 * 86400);
                    $c = $this->cached($key);
                }
            }
            if ($c !== null) {
                $roh[$jahr] = $c['data'];
                $stand ??= $c['geholt'];
            }
        }

        if (!$roh) {
            return [];
        }

        $out = ['_stand' => $stand];
        foreach ($stages as $i => $s) {
            $tmax = $tmin = $regen = [];
            $ziel = (int) substr((string) $s['date_iso'], 8, 2);
            $monat = (int) substr((string) $s['date_iso'], 5, 2);

            foreach ($roh as $jahr => $orte) {
                $ort = $orte[$i] ?? null;
                if (!$ort || empty($ort['daily']['time'])) {
                    continue;
                }
                foreach ($ort['daily']['time'] as $p => $tag) {
                    $abstand = abs(
                        (int) ((strtotime($tag) - strtotime(sprintf('%d-%02d-%02d', $jahr, $monat, $ziel))) / 86400)
                    );
                    if ($abstand > 2) {
                        continue;
                    }
                    if (isset($ort['daily']['temperature_2m_max'][$p])) { $tmax[]  = (float) $ort['daily']['temperature_2m_max'][$p]; }
                    if (isset($ort['daily']['temperature_2m_min'][$p])) { $tmin[]  = (float) $ort['daily']['temperature_2m_min'][$p]; }
                    if (isset($ort['daily']['precipitation_sum'][$p]))  { $regen[] = (float) $ort['daily']['precipitation_sum'][$p]; }
                }
            }

            if (!$tmax) {
                continue;
            }
            $nass = $regen ? count(array_filter($regen, static fn ($r) => $r >= 1.0)) / count($regen) : null;

            $out[(int) $s['id']] = [
                'quelle' => 'mittel',
                'datum'  => $s['date_iso'],
                'jahre'  => count($roh),
                'max'    => round(array_sum($tmax) / count($tmax), 1),
                'min'    => $tmin ? round(array_sum($tmin) / count($tmin), 1) : null,
                'regen'  => $regen ? round(array_sum($regen) / count($regen), 1) : null,
                'regenp' => $nass === null ? null : (int) round($nass * 100),
                'code'   => null,
                'wind'   => null,
            ];
        }

        return $out;
    }

    /* ================= Höhen ============================================= */

    /**
     * Höhenprofil je Etappe.
     *
     * Die Punkte werden aus der hinterlegten Küstenroute genommen und auf
     * gleiche Abstände gebracht; die Höhe dazu kommt aus dem Geländemodell.
     * Einmal geholt, ändert sich daran nichts mehr — Gelände bleibt Gelände.
     *
     * @return array<string,mixed>
     */
    public function hoehen(bool $erneuern = false): array
    {
        $key = 'hoehen.etappen';
        $c   = $erneuern ? null : $this->cached($key);
        if ($c !== null && $c['frisch']) {
            return ['etappen' => $c['data'], 'stand' => $c['geholt']];
        }

        $route = $this->hauptroute();
        $stages = array_values(array_filter(
            $this->repo->stages(),
            static fn ($s) => $s['lat'] !== null && $s['lng'] !== null
        ));
        if (count($route) < 2 || count($stages) < 2) {
            return ['etappen' => [], 'stand' => null];
        }

        // Für jede Etappe das Stück Route zwischen Vorgänger- und Zielort.
        $strecken = [];
        for ($i = 1; $i < count($stages); $i++) {
            if ((float) ($stages[$i]['km_walk'] ?? 0) <= 0) {
                continue;
            }
            $von = self::naechsterIndex($route, (float) $stages[$i - 1]['lat'], (float) $stages[$i - 1]['lng']);
            $bis = self::naechsterIndex($route, (float) $stages[$i]['lat'], (float) $stages[$i]['lng']);
            if ($bis <= $von) {
                $bis = min($von + 1, count($route) - 1);
            }
            $teil = array_slice($route, $von, $bis - $von + 1);
            $strecken[(int) $stages[$i]['id']] = self::gleichmaessig($teil, 24);
        }

        // Alle Punkte in wenigen Anfragen holen — 100 je Anfrage sind erlaubt.
        $alle = [];
        foreach ($strecken as $id => $punkte) {
            foreach ($punkte as $p) {
                $alle[] = [$id, $p[0], $p[1]];
            }
        }

        $hoehen = [];
        foreach (array_chunk($alle, 90) as $block) {
            $antwort = $this->hole(self::ELEVATION, [
                'latitude'  => implode(',', array_map(static fn ($p) => round($p[1], 5), $block)),
                'longitude' => implode(',', array_map(static fn ($p) => round($p[2], 5), $block)),
            ]);
            if ($antwort === null || !isset($antwort['elevation'])) {
                return ['etappen' => $c['data'] ?? [], 'stand' => $c['geholt'] ?? null, 'fehler' => true];
            }
            foreach ($antwort['elevation'] as $n => $meter) {
                $hoehen[] = [$block[$n][0], (float) $meter];
            }
        }

        // Nach Etappe sortieren und Auf-/Abstieg summieren.
        $out = [];
        foreach ($hoehen as [$id, $meter]) {
            $out[$id]['punkte'][] = round($meter);
        }
        foreach ($out as $id => &$e) {
            $auf = $ab = 0.0;
            for ($i = 1; $i < count($e['punkte']); $i++) {
                $d = $e['punkte'][$i] - $e['punkte'][$i - 1];
                // Kleinkram unter 3 m ist Rauschen im Geländemodell, nicht Anstieg.
                if ($d >= 3)  { $auf += $d; }
                if ($d <= -3) { $ab  += -$d; }
            }
            $e['auf']  = (int) round($auf);
            $e['ab']   = (int) round($ab);
            $e['min']  = (int) min($e['punkte']);
            $e['max']  = (int) max($e['punkte']);
        }
        unset($e);

        $this->store($key, $out, 365 * 86400);
        return ['etappen' => $out, 'stand' => date('c')];
    }

    /** Die längste hinterlegte Linie ist der eigentliche Weg. */
    private function hauptroute(): array
    {
        $beste = [];
        foreach ($this->repo->mapRoutes() as $r) {
            if (count($r['points']) > count($beste)) {
                $beste = $r['points'];
            }
        }
        return array_map(static fn ($p) => [(float) $p[0], (float) $p[1]], $beste);
    }

    private static function naechsterIndex(array $route, float $lat, float $lng): int
    {
        $best = 0;
        $dist = PHP_FLOAT_MAX;
        foreach ($route as $i => [$a, $b]) {
            $d = ($a - $lat) ** 2 + ($b - $lng) ** 2;
            if ($d < $dist) {
                $dist = $d;
                $best = $i;
            }
        }
        return $best;
    }

    /** Ein Linienstück auf n gleich weit auseinanderliegende Punkte bringen. */
    private static function gleichmaessig(array $punkte, int $n): array
    {
        if (count($punkte) < 2) {
            return array_pad($punkte, $n, $punkte[0] ?? [0.0, 0.0]);
        }

        $laengen = [0.0];
        for ($i = 1; $i < count($punkte); $i++) {
            $laengen[$i] = $laengen[$i - 1] + sqrt(
                ($punkte[$i][0] - $punkte[$i - 1][0]) ** 2 +
                (($punkte[$i][1] - $punkte[$i - 1][1]) * cos(deg2rad($punkte[$i][0]))) ** 2
            );
        }
        $gesamt = end($laengen);
        if ($gesamt <= 0) {
            return array_fill(0, $n, $punkte[0]);
        }

        $out = [];
        for ($k = 0; $k < $n; $k++) {
            $ziel = $gesamt * $k / ($n - 1);
            $i = 0;
            while ($i < count($laengen) - 2 && $laengen[$i + 1] < $ziel) {
                $i++;
            }
            $spanne = $laengen[$i + 1] - $laengen[$i];
            $t = $spanne > 0 ? ($ziel - $laengen[$i]) / $spanne : 0.0;
            $out[] = [
                $punkte[$i][0] + ($punkte[$i + 1][0] - $punkte[$i][0]) * $t,
                $punkte[$i][1] + ($punkte[$i + 1][1] - $punkte[$i][1]) * $t,
            ];
        }
        return $out;
    }

    private static function z($v): ?float
    {
        return $v === null ? null : round((float) $v, 1);
    }

    /** WMO-Wettercode in einen deutschen Satz. */
    public static function wetterText(?int $code): string
    {
        if ($code === null) {
            return '';
        }
        return match (true) {
            $code === 0            => 'klar',
            $code <= 2             => 'meist sonnig',
            $code === 3            => 'bedeckt',
            $code <= 48            => 'Nebel',
            $code <= 55            => 'Nieselregen',
            $code <= 65            => 'Regen',
            $code <= 67            => 'gefrierender Regen',
            $code <= 77            => 'Schnee',
            $code <= 82            => 'Schauer',
            $code <= 86            => 'Schneeschauer',
            default                => 'Gewitter',
        };
    }
}
