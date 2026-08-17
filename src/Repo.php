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
