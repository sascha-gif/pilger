<?php
declare(strict_types=1);

/**
 * Große Dateien in Stücken annehmen.
 *
 * Ein Handyvideo ist schnell dreihundert Megabyte. Im Container stehen
 * `post_max_size = 72M`, davor sitzt ein Reverse-Proxy mit eigenen Grenzen,
 * und dazwischen liegt ein portugiesisches Mobilnetz. Am Stück hochzuladen
 * heißt hier: es geht nicht, und wenn doch, dann bricht es bei 80 % ab und
 * fängt wieder bei null an.
 *
 * Deshalb kommen die Dateien in Brocken von ein paar Megabyte. Jeder Brocken
 * ist eine eigene Anfrage, die für sich gelingt oder scheitert; was schon
 * liegt, bleibt liegen. Ein abgerissener Upload macht dort weiter, wo er war.
 *
 * **Warum das auch für Fotos der bessere Weg ist:** seit Tagen scheitern Bilder
 * auf dem Server mit „Es kam keine Datei an", während Sprachnotizen über
 * denselben Endpunkt durchgehen. Woran es liegt, ist noch offen — aber ein
 * Weg, der gar keine großen Rümpfe mehr schickt, umgeht die Frage.
 *
 * Sicherheit: die Marke ist zufällig und wird streng geprüft, die Brocken
 * landen in einem eigenen Ordner außerhalb des ausgelieferten Verzeichnisses,
 * und über allem steht die Anmeldung von `api.php`. Eine Gesamtgrenze gibt es
 * trotzdem — sonst füllt ein Fehler in der Schleife die Platte.
 */
final class Stueckweise
{
    /** Mehr nimmt kein Tagebuchvideo in Anspruch, und die Platte ist endlich. */
    public const MAX_BYTES = 400 * 1024 * 1024;

    /** Angefangene Uploads, die niemand zu Ende gebracht hat. */
    private const ALT_NACH = 12 * 3600;

    private string $ordner;

    public function __construct()
    {
        $this->ordner = data_path('haelften');
    }

    /**
     * Neue Marke. Der Name ist nur für die Endung da — abgelegt wird unter
     * der Marke, nie unter dem, was das Gerät geschickt hat.
     */
    public function beginne(): string
    {
        $this->raeumeAuf();
        $marke = bin2hex(random_bytes(16));
        if (file_put_contents($this->pfad($marke), '') === false) {
            throw new RuntimeException('Der Zwischenspeicher lässt sich nicht anlegen.');
        }
        return $marke;
    }

    /**
     * Ein Stück anhängen. `$nr` ist die laufende Nummer und dient nur dazu,
     * ein doppelt geschicktes Stück zu erkennen: die Brocken kommen der Reihe
     * nach, weil der Browser sie der Reihe nach schickt.
     *
     * @return int  wie viele Bytes jetzt liegen
     */
    public function nimm(string $marke, string $roh): int
    {
        $pfad = $this->pfad($marke);
        if (!is_file($pfad)) {
            throw new RuntimeException('Diese Übertragung gibt es nicht (mehr).');
        }

        $bytes = base64_decode($roh, true);
        if ($bytes === false) {
            throw new RuntimeException('Das Stück ist nicht lesbar (Base64).');
        }

        $bisher = (int) @filesize($pfad);
        if ($bisher + strlen($bytes) > self::MAX_BYTES) {
            @unlink($pfad);
            throw new RuntimeException(sprintf(
                'Die Datei ist größer als %d MB — so viel nimmt der Server nicht an.',
                (int) (self::MAX_BYTES / 1048576)
            ));
        }

        if (file_put_contents($pfad, $bytes, FILE_APPEND) === false) {
            throw new RuntimeException('Das Stück ließ sich nicht ablegen.');
        }
        return (int) @filesize($pfad);
    }

    /** Wie weit ist diese Übertragung? −1, wenn es sie nicht gibt. */
    public function stand(string $marke): int
    {
        $pfad = $this->pfad($marke);
        return is_file($pfad) ? (int) @filesize($pfad) : -1;
    }

    /** Der fertige Pfad — zum Weiterverarbeiten, nicht zum Ausliefern. */
    public function fertig(string $marke): string
    {
        $pfad = $this->pfad($marke);
        if (!is_file($pfad) || filesize($pfad) === 0) {
            throw new RuntimeException('Es ist nichts angekommen.');
        }
        return $pfad;
    }

    public function verwirf(string $marke): void
    {
        $pfad = @$this->pfad($marke);
        if ($pfad !== '' && is_file($pfad)) {
            @unlink($pfad);
        }
    }

    /**
     * Streng: nur was hier selbst vergeben wurde. Ein Name vom Gerät darf nie
     * in einen Pfad geraten.
     */
    private function pfad(string $marke): string
    {
        if (!preg_match('~^[0-9a-f]{32}$~', $marke)) {
            throw new RuntimeException('Ungültige Übertragungsmarke.');
        }
        return $this->ordner . '/' . $marke . '.teil';
    }

    /** Abgebrochene Uploads verschwinden von selbst. */
    private function raeumeAuf(): void
    {
        $grenze = time() - self::ALT_NACH;
        foreach (glob($this->ordner . '/*.teil') ?: [] as $datei) {
            if (@filemtime($datei) < $grenze) {
                @unlink($datei);
            }
        }
    }
}
