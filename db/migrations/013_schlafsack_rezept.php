<?php
declare(strict_types=1);

/**
 * Schlafsack in die Packliste, Rezept und Apotheke in die Erledigungen.
 *
 * Zum Schlafsack: im Plan stand bisher ausdrücklich „kein Schlafsack nötig",
 * weil es Hotels und Pensionen mit Bettwäsche sind. Auf Wunsch steht er
 * trotzdem auf der Liste — Größe und Art bleiben leer, das entscheidet Sascha
 * beim Kauf. Ein Hüttenschlafsack wiegt ein paar hundert Gramm, ein richtiger
 * Schlafsack schnell über ein Kilo; bei einem Zielgewicht unter 8 kg ist das
 * der Unterschied, auf den es ankommt.
 */
function migration_013(Database $db): void
{
    /* ---- Schlafsack in „Abend & Schlafen" ------------------------------- */
    $kat = $db->one("SELECT id FROM pack_categories WHERE title LIKE '%Abend%' ORDER BY seq LIMIT 1");
    if ($kat !== null) {
        $catId = (int) $kat['id'];
        // Beide Schreibweisen prüfen: der Seed legt inzwischen gleich den
        // Seidenschlafsack an, Migration 014 benennt den alten Eintrag um. Ohne
        // diese Prüfung stünde er auf einer frischen Datenbank doppelt.
        $da = (int) $db->value(
            "SELECT COUNT(*) FROM pack_items WHERE category_id = ? AND name IN ('Schlafsack', 'Seidenschlafsack')",
            [$catId]
        );
        if ($da === 0) {
            $seq = (int) $db->value('SELECT COALESCE(MAX(seq), 0) FROM pack_items WHERE category_id = ?', [$catId]) + 1;
            $db->run(
                'INSERT INTO pack_items (category_id, seq, name, size, qty, purpose, checked) VALUES (?,?,?,?,?,?,0)',
                [$catId, $seq, 'Schlafsack', null, '1', 'Gewicht im Blick behalten — Ziel bleibt unter 8 kg']
            );
        }
    }

    /* ---- Rezept und Apotheke unter „Vor der Abreise" -------------------- */
    $punkte = [
        ['Rezept Herztabletten', 'Für die ganze Reise plus Puffer — Nachschub unterwegs ist umständlich'],
        ['Apotheke',             'Rezept einlösen, Reiseapotheke auffüllen'],
    ];

    $seq = (int) $db->value('SELECT COALESCE(MAX(seq), 0) FROM todos');
    foreach ($punkte as [$titel, $zweck]) {
        if ((int) $db->value('SELECT COUNT(*) FROM todos WHERE titel = ?', [$titel]) > 0) {
            continue;
        }
        $seq++;
        $db->run(
            'INSERT INTO todos (seq, titel, zweck, created_at) VALUES (?,?,?,?)',
            [$seq, $titel, $zweck, date('c')]
        );
    }
}
