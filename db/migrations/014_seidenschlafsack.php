<?php
declare(strict_types=1);

/**
 * Präzisierung: es ist ein Seidenschlafsack, und zwar aus Hygienegründen.
 *
 * Damit erledigt sich der Gewichtsvorbehalt aus der Migration davor — ein
 * Seidenliner wiegt einen Bruchteil eines richtigen Schlafsacks und fällt bei
 * einem Zielgewicht unter 8 kg nicht ins Gewicht. Er ersetzt keinen Schlafsack,
 * sondern das Bettlaken: ein eigenes Innentuch in fremden Betten.
 */
function migration_014(Database $db): void
{
    $db->run(
        "UPDATE pack_items
            SET name = ?, size = ?, purpose = ?
          WHERE name = ?",
        [
            'Seidenschlafsack',
            'Seide, Inlett',
            'Hygiene — eigenes Innentuch in fremden Betten',
            'Schlafsack',
        ]
    );
}
