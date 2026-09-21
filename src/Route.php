<?php
declare(strict_types=1);

/**
 * GPX einlesen und auf eine zeichenbare Linie eindampfen.
 *
 * Die von Hand gesetzten Stützpunkte in `db/kuestenroute.php` sind eine
 * Näherung: zwischen zwei Punkten liegt eine Gerade, und je weiter sie
 * auseinander liegen, desto mehr schneidet die Linie ab. Eine echte
 * Aufzeichnung löst das — sie hat alle paar Meter einen Punkt.
 *
 * Genau deshalb muss sie ausgedünnt werden: eine GPX-Datei über 266 km hat
 * schnell 100.000 Punkte, und die will niemand durch ein Mobilnetz schicken
 * und im Browser zeichnen. Douglas-Peucker wirft alles weg, was die Form
 * nicht verändert — aus 100.000 Punkten werden ein paar tausend, und auf der
 * Karte sieht man keinen Unterschied.
 */
final class Route
{
    /** Darüber wird nicht mehr gezeichnet — der Rest ist Ballast. */
    private const ZIEL_PUNKTE = 3000;

    /** Schutz vor einer Datei, die den Speicher sprengt. */
    private const MAX_ROH = 500000;

    /**
     * Punkte aus einer GPX-Datei lesen — Trackpunkte, ersatzweise Routenpunkte.
     *
     * @return array<int,array{0:float,1:float}>
     * @throws RuntimeException wenn die Datei kein lesbares GPX ist
     */
    public static function ausGpx(string $xml): array
    {
        if (trim($xml) === '') {
            throw new RuntimeException('Die Datei ist leer.');
        }

        $doc = new DOMDocument();
        // LIBXML_NONET: keine Netzverbindungen beim Parsen. Entities werden
        // bewusst nicht aufgeloest — eine hochgeladene Datei darf den Server
        // nicht dazu bringen, irgendwo Dateien zu lesen.
        $ok = @$doc->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($ok === false) {
            throw new RuntimeException('Das ist keine lesbare XML-Datei. GPX erwartet.');
        }

        foreach (['trkpt', 'rtept', 'wpt'] as $tag) {
            $punkte = self::lies($doc, $tag);
            if (count($punkte) >= 2) {
                return $punkte;
            }
        }

        throw new RuntimeException('In der Datei stehen keine Wegpunkte (trkpt/rtept).');
    }

    /** @return array<int,array{0:float,1:float}> */
    private static function lies(DOMDocument $doc, string $tag): array
    {
        $punkte = [];
        // getElementsByTagNameNS('*', …) trifft den Namen unabhaengig davon,
        // welchen Namensraum oder welches Praefix der Schreiber benutzt hat —
        // GPX 1.0 und 1.1 unterscheiden sich genau darin.
        foreach ($doc->getElementsByTagNameNS('*', $tag) as $el) {
            /** @var DOMElement $el */
            $lat = $el->getAttribute('lat');
            $lng = $el->getAttribute('lon');
            if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }
            $lat = (float) $lat;
            $lng = (float) $lng;
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }
            $punkte[] = [round($lat, 6), round($lng, 6)];
            if (count($punkte) >= self::MAX_ROH) {
                break;
            }
        }
        return $punkte;
    }

    /**
     * So weit ausdünnen, dass höchstens `ZIEL_PUNKTE` übrig bleiben.
     *
     * Die Toleranz wird verdoppelt, bis es passt. Angefangen wird bei 10 m —
     * feiner als das sieht auf einer Karte über 266 km ohnehin niemand.
     *
     * @param  array<int,array{0:float,1:float}> $punkte
     * @return array{0:array<int,array{0:float,1:float}>,1:float} Linie und benutzte Toleranz
     */
    public static function eindampfen(array $punkte, int $ziel = self::ZIEL_PUNKTE): array
    {
        if (count($punkte) <= $ziel) {
            return [$punkte, 0.0];
        }
        $toleranz = 10.0;
        $raus = $punkte;
        while ($toleranz <= 4000.0) {
            $raus = self::douglasPeucker($punkte, $toleranz);
            if (count($raus) <= $ziel) {
                break;
            }
            $toleranz *= 2;
        }
        return [$raus, $toleranz];
    }

    /**
     * Douglas-Peucker, iterativ statt rekursiv.
     *
     * Rekursiv wäre kürzer, aber eine GPX-Datei mit hunderttausend Punkten
     * legt damit den Stack um — und die Datei kommt von außen.
     *
     * @param  array<int,array{0:float,1:float}> $p
     * @return array<int,array{0:float,1:float}>
     */
    private static function douglasPeucker(array $p, float $toleranz): array
    {
        $n = count($p);
        if ($n < 3) {
            return $p;
        }

        $behalten = array_fill(0, $n, false);
        $behalten[0] = true;
        $behalten[$n - 1] = true;

        $stapel = [[0, $n - 1]];
        while ($stapel) {
            [$a, $b] = array_pop($stapel);
            if ($b - $a < 2) {
                continue;
            }
            $weitester = -1;
            $maxAbstand = 0.0;
            for ($i = $a + 1; $i < $b; $i++) {
                $d = self::abstandZurGeraden($p[$i], $p[$a], $p[$b]);
                if ($d > $maxAbstand) {
                    $maxAbstand = $d;
                    $weitester = $i;
                }
            }
            if ($weitester > 0 && $maxAbstand > $toleranz) {
                $behalten[$weitester] = true;
                $stapel[] = [$a, $weitester];
                $stapel[] = [$weitester, $b];
            }
        }

        $raus = [];
        for ($i = 0; $i < $n; $i++) {
            if ($behalten[$i]) {
                $raus[] = $p[$i];
            }
        }
        return $raus;
    }

    /**
     * Abstand eines Punktes von der Geraden A–B, in Metern.
     *
     * Gerechnet wird in einer flachen Näherung: Längengrade werden mit dem
     * Kosinus der Breite gestaucht. Auf den paar Kilometern zwischen zwei
     * Trackpunkten ist der Fehler daraus bedeutungslos.
     */
    private static function abstandZurGeraden(array $p, array $a, array $b): float
    {
        $m = 111320.0;                                  // Meter je Breitengrad
        $k = cos(deg2rad($a[0]));                       // Stauchung der Länge

        $px = ($p[1] - $a[1]) * $m * $k;
        $py = ($p[0] - $a[0]) * $m;
        $bx = ($b[1] - $a[1]) * $m * $k;
        $by = ($b[0] - $a[0]) * $m;

        $laenge2 = $bx * $bx + $by * $by;
        if ($laenge2 <= 0.0) {
            return sqrt($px * $px + $py * $py);
        }

        // Fusspunkt auf die Strecke begrenzen, sonst misst man an einer
        // unendlichen Geraden und behaelt Punkte, die weit hinter dem Ende
        // liegen.
        $t = max(0.0, min(1.0, ($px * $bx + $py * $by) / $laenge2));
        $dx = $px - $t * $bx;
        $dy = $py - $t * $by;
        return sqrt($dx * $dx + $dy * $dy);
    }

    /** Länge einer Linie in Kilometern — für die Rückmeldung nach dem Hochladen. */
    public static function laengeKm(array $p): float
    {
        $s = 0.0;
        for ($i = 1; $i < count($p); $i++) {
            $s += self::haversine($p[$i - 1], $p[$i]);
        }
        return round($s, 1);
    }

    private static function haversine(array $a, array $b): float
    {
        $R = 6371.0;
        $dLat = deg2rad($b[0] - $a[0]);
        $dLng = deg2rad($b[1] - $a[1]);
        $x = sin($dLat / 2) ** 2
           + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLng / 2) ** 2;
        return 2 * $R * asin(min(1.0, sqrt($x)));
    }
}
