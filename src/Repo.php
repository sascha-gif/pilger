<?php
declare(strict_types=1);

/**
 * Alle Datenbankzugriffe der App an einer Stelle.
 */
final class Repo
{
    public function __construct(private Database $db)
    {
    }

    /* ================= Lesen ============================================= */

    /** @return array<string,string> */
    public function settings(): array
    {
        $out = [];
        foreach ($this->db->all('SELECT skey, svalue FROM settings') as $row) {
            $out[(string) $row['skey']] = (string) $row['svalue'];
        }
        return $out;
    }

    public function setting(string $key, string $fallback = ''): string
    {
        return $this->settings()[$key] ?? $fallback;
    }

    public function heroFacts(): array
    {
        return $this->db->all('SELECT * FROM hero_facts ORDER BY seq');
    }

    public function profileFacts(): array
    {
        return $this->db->all('SELECT * FROM profile_facts ORDER BY seq');
    }

    public function nutritionPills(): array
    {
        return $this->db->all('SELECT * FROM nutrition_pills ORDER BY seq');
    }

    public function nutritionSlots(): array
    {
        return $this->db->all('SELECT * FROM nutrition_slots ORDER BY seq');
    }

    public function travelCards(): array
    {
        return $this->db->all('SELECT * FROM travel_cards ORDER BY seq');
    }

    public function stages(): array
    {
        return $this->db->all('SELECT * FROM stages ORDER BY seq');
    }

    /**
     * Wie weit ist er? Gerechnet wird aus den abgehakten Etappen, nicht aus
     * dem Datum — wer einen Tag mit dem Bus abkürzt, hat die Kilometer nicht.
     *
     * @return array{gelaufen:float,gesamt:float,rest:float,prozent:int,etappen:int,etappen_gesamt:int}
     */
    public function wegProgress(): array
    {
        $gesamt   = round((float) ($this->db->value('SELECT COALESCE(SUM(km_walk), 0) FROM stages') ?? 0), 1);
        $gelaufen = round((float) ($this->db->value('SELECT COALESCE(SUM(km_walk), 0) FROM stages WHERE done = 1') ?? 0), 1);

        // Der Ankunftstag in Porto zählt als Etappe nicht mit — er hat 0 km.
        $etappenGesamt = (int) $this->db->value('SELECT COUNT(*) FROM stages WHERE km_walk > 0');
        $etappen       = (int) $this->db->value('SELECT COUNT(*) FROM stages WHERE km_walk > 0 AND done = 1');

        return [
            'gelaufen'       => $gelaufen,
            'gesamt'         => $gesamt,
            'rest'           => round($gesamt - $gelaufen, 1),
            'prozent'        => $gesamt > 0 ? (int) round($gelaufen / $gesamt * 100) : 0,
            'etappen'        => $etappen,
            'etappen_gesamt' => $etappenGesamt,
        ];
    }

    /** @return array{noetig:int,da:int,fehlt:int} */
    public function stempelProgress(): array
    {
        $noetig = (int) $this->db->value('SELECT COALESCE(SUM(stamps_needed), 0) FROM stages');
        $da     = (int) $this->db->value('SELECT COALESCE(SUM(stamps_done), 0) FROM stages');

        // Fehlend heißt: an einem bereits erledigten Tag fehlt ein Stempel.
        // Was noch vor einem liegt, fehlt nicht — das kommt ja noch.
        $fehlt = (int) $this->db->value(
            'SELECT COALESCE(SUM(stamps_needed - stamps_done), 0)
               FROM stages WHERE done = 1 AND stamps_done < stamps_needed'
        );

        return ['noetig' => $noetig, 'da' => $da, 'fehlt' => $fehlt];
    }

    /** Schritte für „Ankunft & Heimreise", nach Phase gruppiert. */
    public function planSteps(string $phase): array
    {
        return $this->db->all('SELECT * FROM plan_steps WHERE phase = ? ORDER BY seq', [$phase]);
    }

    /** Etappen mit Koordinaten, für die Karte. */
    public function mapStops(): array
    {
        return $this->db->all(
            'SELECT map_name AS n, map_eyebrow AS e, map_meta AS m, lat, lng, map_hub AS hub
               FROM stages
              WHERE on_map = 1 AND lat IS NOT NULL AND lng IS NOT NULL
              ORDER BY seq'
        );
    }

    public function mapRoutes(): array
    {
        $rows = $this->db->all('SELECT * FROM map_routes ORDER BY seq');
        foreach ($rows as &$r) {
            $r['points'] = json_decode((string) $r['points'], true) ?: [];
        }
        return $rows;
    }

    /** Equipment-Karten samt Stichpunkten. */
    public function equipment(): array
    {
        $cards = $this->db->all('SELECT * FROM equipment_cards ORDER BY seq');
        $items = $this->db->all('SELECT * FROM equipment_items ORDER BY card_id, seq');
        foreach ($cards as &$c) {
            $c['items'] = array_values(array_filter(
                $items,
                fn ($i) => (int) $i['card_id'] === (int) $c['id']
            ));
        }
        return $cards;
    }

    /** Packliste als Kategorien mit Items. */
    public function packList(): array
    {
        $cats  = $this->db->all('SELECT * FROM pack_categories ORDER BY seq');
        $items = $this->db->all('SELECT * FROM pack_items ORDER BY category_id, seq');
        foreach ($cats as &$c) {
            $c['items'] = array_values(array_filter(
                $items,
                fn ($i) => (int) $i['category_id'] === (int) $c['id']
            ));
        }
        return $cats;
    }

    public function packProgress(): array
    {
        $total = (int) $this->db->value('SELECT COUNT(*) FROM pack_items');
        $done  = (int) $this->db->value('SELECT COUNT(*) FROM pack_items WHERE checked = 1');
        return ['done' => $done, 'total' => $total];
    }

    public function costItems(): array
    {
        return $this->db->all('SELECT * FROM cost_items ORDER BY seq');
    }

    public function costTotal(): float
    {
        return round((float) ($this->db->value('SELECT COALESCE(SUM(amount), 0) FROM cost_items') ?? 0), 2);
    }

    public function weightWeeks(): array
    {
        return $this->db->all('SELECT * FROM weight_weeks ORDER BY seq');
    }

    /** Zuletzt eingetragenes Ist-Gewicht. */
    public function latestWeight(): ?float
    {
        $val = $this->db->value(
            'SELECT actual FROM weight_weeks WHERE actual IS NOT NULL ORDER BY seq DESC LIMIT 1'
        );
        return $val === null ? null : (float) $val;
    }

    /** @return array<string,string> */
    public function notes(): array
    {
        $out = [];
        foreach ($this->db->all('SELECT nkey, body FROM notes') as $row) {
            $out[(string) $row['nkey']] = (string) $row['body'];
        }
        return $out;
    }

    /* ================= Schreiben ========================================= */

    public function togglePackItem(int $id, bool $checked): bool
    {
        $stmt = $this->db->run(
            'UPDATE pack_items SET checked = ?, updated_at = ? WHERE id = ?',
            [$checked ? 1 : 0, date('c'), $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function setCostAmount(int $id, ?float $amount): bool
    {
        $stmt = $this->db->run(
            'UPDATE cost_items SET amount = ?, updated_at = ? WHERE id = ?',
            [$amount, date('c'), $id]
        );
        return $stmt->rowCount() > 0;
    }

    public function setWeightActual(int $id, ?float $actual): bool
    {
        $stmt = $this->db->run(
            'UPDATE weight_weeks SET actual = ?, updated_at = ? WHERE id = ?',
            [$actual, date('c'), $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** Etappe als gelaufen markieren (oder das Häkchen wieder wegnehmen). */
    public function setStageDone(int $id, bool $done): bool
    {
        $stmt = $this->db->run(
            'UPDATE stages SET done = ?, done_at = ? WHERE id = ?',
            [$done ? 1 : 0, $done ? date('c') : null, $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** Zahl der gesammelten Stempel eines Tages setzen. */
    public function setStageStamps(int $id, int $anzahl): bool
    {
        $noetig = $this->db->value('SELECT stamps_needed FROM stages WHERE id = ?', [$id]);
        if ($noetig === null) {
            return false;
        }
        $anzahl = max(0, min((int) $noetig, $anzahl));
        $this->db->run('UPDATE stages SET stamps_done = ? WHERE id = ?', [$anzahl, $id]);
        return true;
    }

    public function toggleEquipmentItem(int $id, bool $checked): bool
    {
        $stmt = $this->db->run(
            'UPDATE equipment_items SET checked = ?, checked_at = ? WHERE id = ?',
            [$checked ? 1 : 0, $checked ? date('c') : null, $id]
        );
        return $stmt->rowCount() > 0;
    }

    /** @return array{done:int,total:int} */
    public function equipmentProgress(): array
    {
        return [
            'done'  => (int) $this->db->value('SELECT COUNT(*) FROM equipment_items WHERE checked = 1'),
            'total' => (int) $this->db->value('SELECT COUNT(*) FROM equipment_items'),
        ];
    }

    /** Buchungsstand einer Etappe pflegen. */
    public function updateStage(int $id, array $fields): bool
    {
        $allowed = ['target', 'note', 'booking_url', 'booking_label'];
        $set     = [];
        $params  = [];
        foreach ($fields as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $set[]    = $key . ' = ?';
                $params[] = $value;
            }
        }
        if (!$set) {
            return false;
        }
        $params[] = $id;
        $stmt = $this->db->run('UPDATE stages SET ' . implode(', ', $set) . ' WHERE id = ?', $params);
        return $stmt->rowCount() > 0;
    }

    public function db(): Database
    {
        return $this->db;
    }
}
