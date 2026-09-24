<?php
declare(strict_types=1);

/**
 * Wo ein Foto entstanden ist.
 *
 * Die Koordinaten stehen im Bild selbst, im EXIF-Block. Auf dem Server kommen
 * sie nie an: das Handy verkleinert das Bild vor dem Hochladen über eine
 * Leinwand, und die malt nur Pixel ab — die Metadaten bleiben liegen. Gelesen
 * werden sie deshalb im Browser, vor dem Verkleinern, und als eigene Felder
 * mitgeschickt.
 *
 * Für die Bilder, die vorher schon hochgeladen wurden, bleiben die Spalten
 * leer. Deren Originale liegen noch auf dem Telefon, mit allem drin — ein
 * Nachtragen wäre also möglich, ist aber eine eigene Baustelle.
 *
 * REAL statt DECIMAL mit Absicht: gerechnet wird damit nicht, es wird nur
 * angezeigt, und sechs Nachkommastellen sind rund zehn Zentimeter.
 */
function migration_030(Database $db): void
{
    $db->addColumn('photos', 'lat', 'REAL NULL');
    $db->addColumn('photos', 'lng', 'REAL NULL');
}
