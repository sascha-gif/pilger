<?php
declare(strict_types=1);

/**
 * Fitbit und ihr Ladekabel in die Packliste.
 *
 * Das Ladekabel bekommt bewusst eine eigene Zeile, obwohl „Ladegerät USB-C +
 * Kabel" schon dabeisteht: Fitbit lädt über einen eigenen Klemmadapter, der zu
 * nichts anderem passt. Wer ihn zu Hause lässt, hat nach vier Tagen eine tote
 * Uhr — und damit keine Schritte, keinen Puls und kein Gewicht auf der Seite.
 */
function migration_011(Database $db): void
{
    $kategorie = $db->one(
        "SELECT id FROM pack_categories WHERE title LIKE '%Elektronik%' ORDER BY seq LIMIT 1"
    );
    if ($kategorie === null) {
        return;
    }
    $catId = (int) $kategorie['id'];
    $seq   = (int) ($db->value('SELECT COALESCE(MAX(seq), 0) FROM pack_items WHERE category_id = ?', [$catId]));

    $neu = [
        ['Fitbit', 'am Arm', '1', 'Schritte, Puls, Schlaf — speist die Seite'],
        ['Ladekabel Fitbit', 'eigener Klemmadapter', '1', 'Passt zu nichts anderem — leicht vergessen'],
    ];

    foreach ($neu as [$name, $groesse, $anzahl, $zweck]) {
        // Nicht doppelt anlegen, falls die Zeile schon von Hand dazukam.
        $da = $db->value('SELECT COUNT(*) FROM pack_items WHERE category_id = ? AND name = ?', [$catId, $name]);
        if ((int) $da > 0) {
            continue;
        }
        $seq++;
        $db->run(
            'INSERT INTO pack_items (category_id, seq, name, size, qty, purpose, checked) VALUES (?,?,?,?,?,?,0)',
            [$catId, $seq, $name, $groesse, $anzahl, $zweck]
        );
    }
}
