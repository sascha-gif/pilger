<?php
declare(strict_types=1);

/**
 * Tagebuch: Aufnahmen annehmen, Bilder verkleinern, Text sauber machen.
 *
 * Der Ablauf ist bewusst zweistufig und jede Stufe darf ausfallen:
 *
 *   Aufnahme  →  Transkription (Whisper)  →  Glättung (Claude)  →  Text
 *
 * Ist kein Schlüssel hinterlegt, bleibt die Aufnahme trotzdem erhalten und
 * abspielbar; getippter Text geht ohnehin immer. Nichts an dieser Funktion
 * darf dazu führen, dass ein Eintrag vom Camino verloren geht.
 *
 * Die Schlüssel stehen in `settings` und werden in der Oberfläche gepflegt —
 * auf dem Server ist dafür kein Terminal nötig. Sie liegen dort im Klartext:
 * es ist die eigene Datenbank auf dem eigenen Server hinter dem eigenen
 * Passwort, und ein Schlüssel, den der Server benutzen soll, muss für den
 * Server lesbar sein. Wer das nicht will, lässt die Felder leer und tippt.
 */
final class Tagebuch
{
    private const WHISPER  = 'https://api.openai.com/v1/audio/transcriptions';
    private const CLAUDE   = 'https://api.anthropic.com/v1/messages';
    private const MAX_KANTE = 1600;
    private const MAX_THUMB = 480;

    public function __construct(private Database $db, private Repo $repo)
    {
    }

    /* ================= Einstellungen ===================================== */

    public function einstellung(string $key): ?string
    {
        $v = $this->db->value('SELECT svalue FROM settings WHERE skey = ?', [$key]);
        return ($v === null || $v === '') ? null : (string) $v;
    }

    public function setzeEinstellung(string $key, ?string $wert): void
    {
        $this->db->run('DELETE FROM settings WHERE skey = ?', [$key]);
        if ($wert !== null && $wert !== '') {
            $this->db->run('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $wert]);
        }
    }

    /** Was kann die Anlage gerade? Für die Anzeige in der Oberfläche. */
    public function faehigkeiten(): array
    {
        return [
            'transkription' => $this->einstellung('openai_key') !== null,
            'glaettung'     => $this->einstellung('anthropic_key') !== null,
            'modell'        => $this->einstellung('claude_model') ?? 'claude-opus-5',
        ];
    }

    /**
     * Einen hinterlegten Schlüssel wirklich ausprobieren.
     *
     * Ein Schlüssel, der erst auf dem Camino zum ersten Mal benutzt wird und
     * dann nicht geht, ist schlimmer als keiner. Deshalb macht der Server hier
     * einen echten, winzigen Aufruf und sagt, was zurückkam.
     */
    /** Beide Zugänge nacheinander ausprobieren. */
    public function pruefeAlles(): array
    {
        return [
            'claude'  => $this->pruefeClaude(),
            'whisper' => $this->pruefeWhisper(),
        ];
    }

    /**
     * Whisper mit einer echten, einsekündigen Aufnahme ausprobieren.
     *
     * Ein Abruf der Modellliste würde nur zeigen, dass der Schlüssel
     * angenommen wird — nicht, ob Guthaben da ist. Das fällt sonst erst am
     * ersten Abend in Porto auf. Der Testton kostet einen Bruchteil eines
     * Cents und beantwortet beide Fragen auf einmal.
     */
    public function pruefeWhisper(): array
    {
        $key = $this->einstellung('openai_key');
        if ($key === null) {
            return ['ok' => false, 'meldung' => 'Kein Whisper-Schlüssel hinterlegt.'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'meldung' => 'Der Server kann keine Anfragen nach außen stellen (curl fehlt).'];
        }

        $pfad = tempnam(sys_get_temp_dir(), 'pilger-ton') . '.wav';
        file_put_contents($pfad, self::testTon());

        $ch = curl_init(self::WHISPER);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key],
            CURLOPT_POSTFIELDS     => [
                'file'     => new CURLFile($pfad, 'audio/wav', 'probe.wav'),
                'model'    => 'whisper-1',
                'language' => 'de',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $netz = curl_error($ch);
        curl_close($ch);
        @unlink($pfad);

        if ($code === 200) {
            return ['ok' => true, 'meldung' => 'Whisper antwortet und nimmt Aufnahmen an.'];
        }

        $daten = json_decode((string) $body, true);
        $grund = $daten['error']['message'] ?? ($netz ?: 'keine Antwort');
        $typ   = $daten['error']['code'] ?? '';

        return ['ok' => false, 'meldung' => match (true) {
            $code === 0                   => 'Der Server kommt nicht ins Internet: ' . $grund,
            $code === 401 || $code === 403 => 'Der Schlüssel wird nicht angenommen. Stimmt er noch?',
            $typ === 'insufficient_quota' => 'Schlüssel gültig, aber kein Guthaben auf dem OpenAI-Konto.',
            $code === 429                 => 'Zu viele Anfragen oder kein Guthaben auf dem Konto.',
            default                       => 'HTTP ' . $code . ' — ' . $grund,
        }];
    }

    /**
     * Eine Sekunde 440 Hz als WAV, 16 kHz mono. Reine Stille wäre riskant —
     * manche Dienste weisen sie ab, und dann sähe ein guter Schlüssel schlecht aus.
     */
    private static function testTon(): string
    {
        $rate   = 16000;
        $proben = $rate;                 // eine Sekunde
        $daten  = '';
        for ($i = 0; $i < $proben; $i++) {
            $daten .= pack('v', (int) (6000 * sin(2 * M_PI * 440 * $i / $rate)) & 0xFFFF);
        }
        $groesse = strlen($daten);

        return 'RIFF' . pack('V', 36 + $groesse) . 'WAVE'
             . 'fmt ' . pack('VvvVVvv', 16, 1, 1, $rate, $rate * 2, 2, 16)
             . 'data' . pack('V', $groesse) . $daten;
    }

    public function pruefeClaude(): array
    {
        $key = $this->einstellung('anthropic_key');
        if ($key === null) {
            return ['ok' => false, 'meldung' => 'Kein Claude-Schlüssel hinterlegt.'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'meldung' => 'Der Server kann keine Anfragen nach außen stellen (curl fehlt).'];
        }

        $modell = $this->einstellung('claude_model') ?? 'claude-opus-5';

        $ch = curl_init(self::CLAUDE);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => $modell,
                'max_tokens' => 8,
                'messages'   => [['role' => 'user', 'content' => 'Antworte nur mit: bereit']],
            ]),
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $netz = curl_error($ch);
        curl_close($ch);

        if ($code === 200) {
            return ['ok' => true, 'meldung' => 'Claude antwortet. Modell: ' . $modell];
        }

        $daten = json_decode((string) $body, true);
        $grund = $daten['error']['message'] ?? ($netz ?: 'keine Antwort');

        return ['ok' => false, 'meldung' => match ($code) {
            0        => 'Der Server kommt nicht ins Internet: ' . $grund,
            401, 403 => 'Der Schlüssel wird nicht angenommen. Stimmt er noch?',
            400      => 'Anfrage abgelehnt — meist ein falsch geschriebener Modellname. Antwort: ' . $grund,
            404      => 'Modell „' . $modell . '" gibt es unter diesem Namen nicht.',
            429      => 'Zu viele Anfragen oder kein Guthaben auf dem Konto.',
            default  => 'HTTP ' . $code . ' — ' . $grund,
        }];
    }

    /* ================= Einträge ========================================== */

    public function eintraege(): array
    {
        $rows = $this->db->all(
            'SELECT * FROM diary_entries ORDER BY COALESCE(day_iso, created_at) DESC, id DESC'
        );
        foreach ($rows as &$r) {
            $r['text'] = ($r['text_clean'] !== null && $r['text_clean'] !== '')
                ? $r['text_clean']
                : (string) $r['text_raw'];
        }
        return $rows;
    }

    public function eintrag(int $id): ?array
    {
        return $this->db->one('SELECT * FROM diary_entries WHERE id = ?', [$id]);
    }

    /** Vorhandenen Eintrag zur client_id finden — dagegen ist ein doppelter Upload machtlos. */
    public function nachClientId(string $tabelle, string $clientId): ?array
    {
        if ($clientId === '') {
            return null;
        }
        return $this->db->one("SELECT * FROM $tabelle WHERE client_id = ?", [$clientId]);
    }

    public function schreibeText(?int $stageId, ?string $tag, string $text, ?string $clientId = null): array
    {
        $vorhanden = $clientId ? $this->nachClientId('diary_entries', $clientId) : null;
        if ($vorhanden !== null) {
            $this->db->run(
                'UPDATE diary_entries SET text_raw = ?, stage_id = ?, day_iso = ?, updated_at = ? WHERE id = ?',
                [$text, $stageId, $tag, date('c'), (int) $vorhanden['id']]
            );
            return $this->eintrag((int) $vorhanden['id']);
        }

        $this->db->run(
            'INSERT INTO diary_entries (stage_id, client_id, day_iso, kind, text_raw, status, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [$stageId, $clientId, $tag, 'text', $text, 'fertig', date('c'), date('c')]
        );
        return $this->eintrag((int) $this->db->pdo()->lastInsertId());
    }

    public function aendereText(int $id, string $text): bool
    {
        $stmt = $this->db->run(
            'UPDATE diary_entries SET text_clean = ?, status = ?, updated_at = ? WHERE id = ?',
            [$text, 'fertig', date('c'), $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function loescheEintrag(int $id): bool
    {
        $e = $this->eintrag($id);
        if ($e === null) {
            return false;
        }
        if ($e['audio_file']) {
            @unlink(data_path('audio') . '/' . basename((string) $e['audio_file']));
        }
        foreach ($this->db->all('SELECT id FROM photos WHERE entry_id = ?', [$id]) as $f) {
            $this->loescheFoto((int) $f['id']);
        }
        $this->db->run('DELETE FROM diary_entries WHERE id = ?', [$id]);
        return true;
    }

    /* ================= Aufnahme annehmen ================================= */

    /**
     * Sprachaufnahme ablegen. Transkribiert wird danach — und wenn das
     * schiefgeht, bleibt die Aufnahme trotzdem liegen.
     */
    public function nimmAudio(array $datei, ?int $stageId, ?string $tag, ?string $clientId, ?int $sekunden): array
    {
        $vorhanden = $clientId ? $this->nachClientId('diary_entries', $clientId) : null;
        if ($vorhanden !== null) {
            return $vorhanden;   // schon da — der Upload lief nur doppelt
        }

        $endung = self::endung($datei['name'] ?? '', ['webm', 'ogg', 'oga', 'mp4', 'm4a', 'mp3', 'wav'], 'webm');
        $name   = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $endung;
        $ziel   = data_path('audio') . '/' . $name;

        if (!self::ablegen($datei['tmp_name'], $ziel)) {
            throw new RuntimeException('Die Aufnahme konnte nicht gespeichert werden.');
        }

        $this->db->run(
            'INSERT INTO diary_entries (stage_id, client_id, day_iso, kind, audio_file, audio_seconds, status, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?)',
            [$stageId, $clientId, $tag, 'audio', $name, $sekunden, 'neu', date('c'), date('c')]
        );
        return $this->eintrag((int) $this->db->pdo()->lastInsertId());
    }

    /**
     * Foto ablegen: verkleinern, drehen, Vorschaubild erzeugen.
     * Das Original wird bewusst nicht behalten — 12 Tage Handyfotos in voller
     * Größe sprengen jedes Volume, und für ein Reisetagebuch reicht 1600 px.
     */
    public function nimmFoto(array $datei, ?int $stageId, ?int $entryId, ?string $clientId, ?string $aufgenommen): array
    {
        $vorhanden = $clientId ? $this->nachClientId('photos', $clientId) : null;
        if ($vorhanden !== null) {
            return $vorhanden;
        }

        $ordner = data_path('fotos');
        $basis  = date('Ymd-His') . '-' . bin2hex(random_bytes(6));
        $roh    = $datei['tmp_name'];

        $info = @getimagesize($roh);
        $bild = $info ? self::ladeBild($roh, (int) $info[2]) : null;

        if ($bild === null) {
            // Kein Format, das GD kennt (etwa HEIC). Dann eben unverändert
            // ablegen — ein Foto, das keiner sieht, ist schlechter als eins,
            // das der Browser vielleicht doch anzeigt.
            $endung = self::endung($datei['name'] ?? '', ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'], 'jpg');
            $name   = $basis . '.' . $endung;
            if (!self::ablegen($roh, $ordner . '/' . $name)) {
                throw new RuntimeException('Das Bild konnte nicht gespeichert werden.');
            }
            return $this->merkeFoto($name, null, $stageId, $entryId, $clientId, $aufgenommen, null, null, $ordner . '/' . $name);
        }

        $bild = self::dreheNachExif($bild, $roh);
        $gross = self::skaliere($bild, self::MAX_KANTE);
        $klein = self::skaliere($bild, self::MAX_THUMB);

        $name  = $basis . '.jpg';
        $thumb = $basis . '_k.jpg';
        imagejpeg($gross, $ordner . '/' . $name, 82);
        imagejpeg($klein, $ordner . '/' . $thumb, 78);

        $breite = imagesx($gross);
        $hoehe  = imagesy($gross);
        imagedestroy($bild);
        imagedestroy($gross);
        imagedestroy($klein);

        return $this->merkeFoto($name, $thumb, $stageId, $entryId, $clientId, $aufgenommen, $breite, $hoehe, $ordner . '/' . $name);
    }

    private function merkeFoto(
        string $name, ?string $thumb, ?int $stageId, ?int $entryId, ?string $clientId,
        ?string $aufgenommen, ?int $breite, ?int $hoehe, string $pfad
    ): array {
        $this->db->run(
            'INSERT INTO photos (stage_id, entry_id, client_id, file, thumb, width, height, bytes, taken_at, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$stageId, $entryId, $clientId, $name, $thumb, $breite, $hoehe, @filesize($pfad) ?: null, $aufgenommen, date('c')]
        );
        return $this->foto((int) $this->db->pdo()->lastInsertId());
    }

    public function foto(int $id): ?array
    {
        return $this->db->one('SELECT * FROM photos WHERE id = ?', [$id]);
    }

    public function fotos(?int $stageId = null): array
    {
        if ($stageId === null) {
            return $this->db->all('SELECT * FROM photos ORDER BY COALESCE(taken_at, created_at), id');
        }
        return $this->db->all(
            'SELECT * FROM photos WHERE stage_id = ? ORDER BY COALESCE(taken_at, created_at), id',
            [$stageId]
        );
    }

    public function setzeBildtext(int $id, string $text): bool
    {
        return $this->db->run('UPDATE photos SET caption = ? WHERE id = ?', [$text, $id])->rowCount() > 0;
    }

    public function loescheFoto(int $id): bool
    {
        $f = $this->foto($id);
        if ($f === null) {
            return false;
        }
        $ordner = data_path('fotos');
        @unlink($ordner . '/' . basename((string) $f['file']));
        if ($f['thumb']) {
            @unlink($ordner . '/' . basename((string) $f['thumb']));
        }
        $this->db->run('DELETE FROM photos WHERE id = ?', [$id]);
        return true;
    }

    /* ================= Transkribieren und glätten ======================== */

    /**
     * Aufnahme in Text verwandeln und den Text lesbar machen.
     * Jeder Schritt für sich; scheitert einer, bleibt der Stand davor stehen.
     */
    public function veredele(int $id): array
    {
        $e = $this->eintrag($id);
        if ($e === null) {
            return ['ok' => false, 'error' => 'Eintrag nicht gefunden.'];
        }

        $roh = (string) $e['text_raw'];

        if ($roh === '' && $e['audio_file']) {
            $key = $this->einstellung('openai_key');
            if ($key === null) {
                return ['ok' => false, 'error' => 'Kein Schlüssel für die Transkription hinterlegt.'];
            }
            $pfad = data_path('audio') . '/' . basename((string) $e['audio_file']);
            if (!is_readable($pfad)) {
                return ['ok' => false, 'error' => 'Die Aufnahme liegt nicht mehr im Ablageordner.'];
            }
            $roh = $this->transkribiere($pfad, $key);
            if ($roh === null) {
                $this->status($id, 'fehler', 'Transkription hat nicht geantwortet.');
                return ['ok' => false, 'error' => 'Die Transkription hat nicht geantwortet.'];
            }
            $this->db->run(
                'UPDATE diary_entries SET text_raw = ?, status = ?, updated_at = ? WHERE id = ?',
                [$roh, 'transkribiert', date('c'), $id]
            );
        }

        if (trim($roh) === '') {
            return ['ok' => false, 'error' => 'Es ist kein Text da, den man glätten könnte.'];
        }

        $key = $this->einstellung('anthropic_key');
        if ($key === null) {
            $this->status($id, 'transkribiert', 'Ohne Claude-Schlüssel bleibt es beim Rohtext.');
            return ['ok' => true, 'eintrag' => $this->eintrag($id), 'hinweis' => 'Rohtext — kein Schlüssel für die Glättung.'];
        }

        $sauber = $this->glaette($roh, $key, $e);
        if ($sauber === null) {
            $this->status($id, 'transkribiert', 'Glättung hat nicht geantwortet.');
            return ['ok' => false, 'error' => 'Die Glättung hat nicht geantwortet — der Rohtext steht.'];
        }

        $this->db->run(
            'UPDATE diary_entries SET text_clean = ?, status = ?, status_note = NULL, updated_at = ? WHERE id = ?',
            [$sauber, 'fertig', date('c'), $id]
        );
        return ['ok' => true, 'eintrag' => $this->eintrag($id)];
    }

    private function status(int $id, string $status, ?string $notiz): void
    {
        $this->db->run(
            'UPDATE diary_entries SET status = ?, status_note = ?, updated_at = ? WHERE id = ?',
            [$status, $notiz, date('c'), $id]
        );
    }

    private function transkribiere(string $pfad, string $key): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init(self::WHISPER);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 180,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key],
            CURLOPT_POSTFIELDS     => [
                'file'     => new CURLFile($pfad),
                'model'    => 'whisper-1',
                'language' => 'de',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($code !== 200 || !is_string($body)) {
            error_log('pilger: Transkription HTTP ' . $code . ' — ' . substr((string) $body, 0, 300));
            return null;
        }
        $data = json_decode($body, true);
        $text = is_array($data) ? ($data['text'] ?? null) : null;
        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }

    /**
     * Diktiertes zu lesbarem Text machen — und sonst nichts. Das Modell darf
     * kürzen und Versprecher wegräumen, aber nichts dazuerfinden: ein
     * Reisetagebuch, in dem Dinge stehen, die nicht passiert sind, ist wertlos.
     */
    private function glaette(string $roh, string $key, array $eintrag): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $modell = $this->einstellung('claude_model') ?? 'claude-opus-5';
        $tag    = $eintrag['day_iso'] ? ' (Tag: ' . $eintrag['day_iso'] . ')' : '';

        $system = 'Du machst aus einer diktierten Sprachnotiz einen lesbaren Tagebucheintrag. '
            . 'Es geht um eine Pilgerwanderung auf dem Camino Portugués da Costa von Porto nach Santiago. '
            . 'Regeln: Schreibe in der ersten Person, im Ton des Sprechers, deutsch. '
            . 'Räume Versprecher, Füllwörter und Wiederholungen weg und setze Absätze. '
            . 'Erfinde nichts dazu — keine Orte, keine Zahlen, keine Erlebnisse, die nicht gesagt wurden. '
            . 'Wenn etwas unverständlich ist, lass es weg statt zu raten. '
            . 'Antworte ausschließlich mit dem fertigen Text, ohne Vorrede und ohne Überschrift.';

        $ch = curl_init(self::CLAUDE);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'      => $modell,
                'max_tokens' => 2000,
                'system'     => $system,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => 'Hier ist die Sprachnotiz' . $tag . ":\n\n" . $roh,
                ]],
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($code !== 200 || !is_string($body)) {
            error_log('pilger: Glättung HTTP ' . $code . ' — ' . substr((string) $body, 0, 300));
            return null;
        }

        $data = json_decode($body, true);
        $text = '';
        foreach ($data['content'] ?? [] as $teil) {
            if (($teil['type'] ?? '') === 'text') {
                $text .= $teil['text'];
            }
        }
        $text = trim($text);
        return $text === '' ? null : $text;
    }

    /* ================= Bildwerkzeug ====================================== */

    private static function ablegen(string $tmp, string $ziel): bool
    {
        if (is_uploaded_file($tmp)) {
            return move_uploaded_file($tmp, $ziel);
        }
        return @rename($tmp, $ziel) || @copy($tmp, $ziel);
    }

    private static function endung(string $name, array $erlaubt, string $fallback): string
    {
        $e = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        return in_array($e, $erlaubt, true) ? $e : $fallback;
    }

    private static function ladeBild(string $pfad, int $typ)
    {
        $bild = match ($typ) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($pfad),
            IMAGETYPE_PNG  => @imagecreatefrompng($pfad),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($pfad) : false,
            default        => false,
        };
        return $bild === false ? null : $bild;
    }

    /** Handyfotos liegen sonst quer — die Lage steht im EXIF, nicht im Bild. */
    private static function dreheNachExif($bild, string $pfad)
    {
        if (!function_exists('exif_read_data')) {
            return $bild;
        }
        $exif = @exif_read_data($pfad);
        $o = (int) ($exif['Orientation'] ?? 0);
        $winkel = match ($o) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($winkel === 0) {
            return $bild;
        }
        $gedreht = imagerotate($bild, $winkel, 0);
        if ($gedreht === false) {
            return $bild;
        }
        imagedestroy($bild);
        return $gedreht;
    }

    private static function skaliere($bild, int $maxKante)
    {
        $b = imagesx($bild);
        $h = imagesy($bild);
        $faktor = min(1.0, $maxKante / max($b, $h));
        $nb = max(1, (int) round($b * $faktor));
        $nh = max(1, (int) round($h * $faktor));

        $neu = imagecreatetruecolor($nb, $nh);
        imagecopyresampled($neu, $bild, 0, 0, 0, 0, $nb, $nh, $b, $h);
        return $neu;
    }
}
