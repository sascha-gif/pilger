<?php
declare(strict_types=1);

/**
 * Die Heimfahrt bekommt einen Tag im Tagebuch.
 *
 * Die Auswahl „Zu welchem Tag?" wurde bisher aus den **Etappen** gebaut. Das
 * trägt, solange die Reise läuft, und bricht genau am Ende: der 01.10. ist
 * Rückflug und keine Etappe — für die Heimfahrt gab es also keinen Tag, auf
 * den ein Eintrag gepasst hätte.
 *
 * Schlimmer noch war, was danach gekommen wäre: in der Liste stehen nur offene
 * Etappen und solche, die heute oder gestern abgehakt wurden. Zwei Tage nach
 * der letzten Etappe ist beides leer — und mit einer leeren Liste hätte sich
 * überhaupt kein neuer Eintrag mehr anlegen lassen. Die Fotos und Videos, die
 * noch hochzuladen sind, hätten kein Zuhause mehr gehabt.
 *
 * Die Oberfläche zeigt darum jetzt **jeden Tag der Reise**, und diese beiden
 * Werte sagen ihr, wie weit die Reise reicht. Ein Eintrag braucht ohnehin
 * keine Etappe: `diary_entries.stage_id` darf leer bleiben, gebündelt wird im
 * Zeitstrahl nach `day_iso`.
 */
function migration_045(Database $db): void
{
    $db->setSetting('reise_ende', '2026-10-01');
    $db->setSetting('reise_ende_name', 'Heimreise — Santiago → Frankfurt');
}
