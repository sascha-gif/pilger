<?php
declare(strict_types=1);

/**
 * Gewicht aus dem Google-Health-Konto.
 *
 * Google liefert es in Gramm als Tagesmittel (`weightGramsAvg`). Hier steht
 * es in Kilogramm mit einer Nachkommastelle — genauer wiegt eine Personenwaage
 * ohnehin nicht verlässlich, und die Zieltabelle rechnet in kg.
 *
 * Der Wert ersetzt die getippte Zahl nicht: `weight_weeks.actual` bleibt, was
 * Sascha eingetragen hat. Gemessenes und Eingetragenes stehen nebeneinander,
 * und das Übernehmen ist ein Klick — kein stilles Überschreiben.
 */
function migration_010(Database $db): void
{
    $dec = $db->driver() === 'mysql' ? 'DECIMAL(5,1)' : 'REAL';
    $db->addColumn('health_days', 'gewicht_kg', "$dec NULL");
}
