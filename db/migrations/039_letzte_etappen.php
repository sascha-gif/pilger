<?php
declare(strict_types=1);

/**
 * Die letzten fünf Tage, wie sie wirklich laufen.
 *
 * Die Nacht in Pontevedra fiel auf den 26., also auf das Ende von E8 — dessen
 * Ziel aber Arcade ist, 15 km davor. Statt die 15 km am Sonntagmorgen
 * nachzuholen, wird weitergelaufen: **die Strecke Arcade → Pontevedra geht
 * nicht zu Fuß.** Das ist so entschieden, mit allem, was daran hängt; der Plan
 * bildet es ab, statt zu behaupten, es wäre anders.
 *
 * Dadurch wird ein Tag frei, und der geht in den **letzten** Tag: statt 25 km
 * von Padrón nach Santiago am 30. sind es nun 18 km bis O Milladoiro am 29.
 * und **7 km** am 30. Damit steht er vormittags auf der Praza do Obradoiro —
 * rechtzeitig fürs Pilgerbüro und für die Messe um 12:00, die nach dem alten
 * Plan gar nicht zu schaffen gewesen wäre.
 *
 * Die Etappen wandern deshalb um eine Stelle:
 *
 * | Tag | vorher | jetzt |
 * |---|---|---|
 * | E9  · 27.09. | Arcade → Pontevedra, 15 | Pontevedra → Caldas de Reis, 22 |
 * | E10 · 28.09. | Pontevedra → Caldas, 22 | Caldas de Reis → Padrón, 19 |
 * | E11 · 29.09. | Caldas → Padrón, 19     | Padrón → O Milladoiro, 18 |
 * | E12 · 30.09. | Padrón → Santiago, 25   | O Milladoiro → Santiago, 7 |
 *
 * **Gelaufen werden damit 251 der 266 km.** Beide Zahlen stehen auf der Seite,
 * im Seitenfuß als „251 von 266 km". Die Fortschrittsanzeige rechnet mit den
 * 251, weil sie zeigt, was zu gehen ist.
 *
 * Die Stempel bleiben bei 21: es sind weiterhin fünf Wandertage bis Santiago.
 *
 * In `db/seed.php` steht das **nicht**. Der Seed hält den ursprünglichen Plan,
 * und die Migrationen sind die Geschichte der Änderungen daran — genau dafür
 * sind sie da. Eine frische Datenbank läuft ohnehin durch alle hindurch und
 * kommt auf denselben Stand; geprüft wird das beim Kaltstart-Vergleich.
 */
function migration_039(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- E7 und E8: die Restkilometer stimmen nicht mehr ---------- */
        $db->run('UPDATE stages SET km_big = ? WHERE seq = 7', ['88']);

        $db->run(
            'UPDATE stages SET km_big = ?, alt_note = ? WHERE seq = 8',
            [
                '66',
                '<b>Die 15 km von Arcade nach Pontevedra gehen nicht zu Fuß.</b> Gelaufen werden '
                . 'heute die 22 km bis Arcade, danach geht es mit Zug, Bus oder Taxi zum Quartier '
                . 'in Pontevedra — und am Sonntag von dort aus weiter, nicht zurück.<br>'
                . '<small>Damit sind es 251 der 266 km zu Fuß. Der frei gewordene Tag steckt im '
                . 'letzten: statt 25 km am 30. sind es 18 am 29. und 7 am 30.</small>',
            ]
        );

        /* ---- E9: Pontevedra → Caldas de Reis -------------------------- */
        $db->run(
            'UPDATE stages SET title = ?, dist = ?, km_walk = ?, km_big = ?,
                    lat = ?, lng = ?, map_name = ?, map_meta = ?,
                    target = ?, note = ?, alt_note = NULL, booking_url = NULL, booking_label = NULL
             WHERE seq = 9',
            [
                'Pontevedra → Caldas de Reis',
                '22 km · Thermalort',
                22, '44',
                42.6050, -8.6417, 'Caldas de Reis', '22 km · noch 44 km',

                '<b style="color:#2e7d32">Gebucht:</b> Apartamento Loft II Select Real · '
                . '<b>65,96 €</b> (inkl. 6,00 € MwSt)',

                '<b>Rúa Real 49, 36650 Caldas de Reis</b> — die Hauptstraße der Altstadt, mitten '
                . 'im Ortskern. Kein Umweg, abends Bars und Essen in Laufweite, und am nächsten '
                . 'Morgen stehst du sofort wieder auf dem Weg.<br>'
                . '<small><b>Apartment, also vermutlich kein Empfang — und damit kein Stempel.</b> '
                . 'Beide Stempel für den Tag woanders holen: Albergue, Concello, Kirche oder das '
                . 'Café auf dem Weg. Nach der Buchung gleich nachsehen, wie der Check-in läuft '
                . '(Code, Schlüsselkasten, Nachricht). Eine Buchungsnummer ist nicht '
                . 'festgehalten worden.</small>',
            ]
        );

        /* ---- E10: Caldas de Reis → Padrón ----------------------------- */
        $db->run(
            'UPDATE stages SET title = ?, dist = ?, km_walk = ?, km_big = ?,
                    lat = ?, lng = ?, map_name = ?, map_meta = ?,
                    target = ?, note = ?, alt_note = NULL, booking_url = NULL, booking_label = NULL
             WHERE seq = 10',
            [
                'Caldas de Reis → Padrón',
                '19 km',
                19, '25',
                42.7369, -8.6600, 'Padrón', '19 km · noch 25 km',

                '<b style="color:#2e7d32">Gebucht:</b> Huna Apartamentos · Apartment mit einem '
                . 'Schlafzimmer · <b>73,50 €</b> (inkl. 6,68 € MwSt und Übernachtungssteuer) · '
                . 'Buchungsnr. 5642460191',

                '<b>Travesía Iría 131, 15917 Padrón</b> — Postleitzahl und Straßenname deuten auf '
                . '<b>Iria Flavia</b>, rund einen Kilometer nördlich des Ortskerns und damit '
                . 'schon in Laufrichtung. Abends ist der Ort dafür nicht vor der Tür: '
                . '<b>Essen und Stempel erledigen, bevor du rausgehst.</b><br>'
                . '<small>Mit 9,4 die beste Bewertung der ganzen Reise. Apartment, also '
                . 'vermutlich kein Empfang und kein Stempel. <b>Check-in-Zeit mit dem Gastgeber '
                . 'abstimmen</b> — nach 19 km kommt niemand zur Bürozeit an. Den PIN hat die '
                . 'Booking-Mail; hier steht er nicht.</small>',
            ]
        );

        /* ---- E11: Padrón → O Milladoiro ------------------------------- */
        $db->run(
            'UPDATE stages SET title = ?, dist = ?, km_walk = ?, km_big = ?,
                    lat = ?, lng = ?, map_name = ?, map_eyebrow = ?, map_meta = ?,
                    target = ?, note = ?, alt_note = NULL, booking_url = NULL, booking_label = NULL
             WHERE seq = 11',
            [
                'Padrón → O Milladoiro',
                '18 km · der vorletzte Tag',
                18, '7',
                42.8428, -8.5792, 'O Milladoiro', 'Etappe 11 · 29.09.', '18 km · noch 7 km',

                '<b style="color:#2e7d32">Gebucht:</b> B&B HOTEL Santiago Milladoiro ★★ · '
                . 'Doppelzimmer · <b>90,13 €</b> · Buchungsnr. 2457423459923289565',

                '<b>Rúa das Palmeiras, 15895 O Milladoiro</b> — die Ortschaft gehört zur Gemeinde '
                . 'Ames und liegt am Südrand von Santiago, auf dem Camino. Von hier sind es nur '
                . 'noch <b>rund 7 km</b> bis zur Praza do Obradoiro; deshalb steht das Bett '
                . 'überhaupt hier und nicht in der Stadt.<br>'
                . '<b>Ein Hotel mit Empfang</b> — die erste Unterkunft seit Vigo, die selbst '
                . 'stempelt.<br>'
                . '<small>Bei der Buchung stand „Bestätigung ausstehend"; die Zusage des Hauses '
                . 'kam danach. Den PIN hat die Booking-Mail; hier steht er nicht.</small>',
            ]
        );

        /* ---- E12: O Milladoiro → Santiago ----------------------------- */
        $db->run(
            'UPDATE stages SET title = ?, dist = ?, km_walk = ?, km_big = ?, map_meta = ?, note = ?
             WHERE seq = 12',
            [
                'O Milladoiro → Santiago de Compostela',
                '7 km · Ankunft',
                7, '0', '7 km · Ankunft',

                '<b>Sieben Kilometer.</b> Nach dem Frühstück los, gegen zehn oder halb elf auf '
                . 'der <b>Praza do Obradoiro</b> — mit frischen Beinen, nicht nach 25 km '
                . 'hineingeschleppt.<br>'
                . '<b>Dein Zimmer im Lemonade Stays geht erst ab 15:00.</b> Rucksack an der '
                . 'Rezeption abgeben (Rúa das Galeras 44), dann ohne acht Kilo auf dem Rücken '
                . 'weiter: <b>Pilgerbüro</b> in der Rúa das Carretas 33, 9:00–19:00, und '
                . '<b>Pilgermesse um 12:00</b> in der Kathedrale. Beides geht sich an diesem Tag '
                . 'in Ruhe aus — nach dem ursprünglichen Plan wäre die Messe nicht zu schaffen '
                . 'gewesen.',
            ]
        );

        /* ---- Stempelstellen: die Orte haben sich verschoben ------------ */
        $orte = [9 => 'Caldas de Reis', 10 => 'Padrón', 11 => 'O Milladoiro'];
        foreach ($orte as $seq => $ort) {
            $st = $db->one('SELECT id FROM stages WHERE seq = ?', [$seq]);
            if ($st === null) {
                continue;
            }
            $id = (int) $st['id'];
            foreach ([
                'Albergue de Peregrinos' => 'Albergue de Peregrinos ' . $ort,
                'Oficina de Turismo'     => 'Oficina de Turismo ' . $ort,
                'Kirche'                 => 'Iglesia ' . $ort,
                'Concello'               => 'Concello de ' . $ort,
            ] as $name => $suche) {
                $db->run(
                    'UPDATE stamp_spots SET suche = ? WHERE stage_id = ? AND name = ?',
                    [$suche, $id, $name]
                );
            }
        }

        /* ---- Kosten: eins zu eins weitergeschoben ---------------------- */
        $kosten = [
            ['Pontevedra (E9)',      'Caldas de Reis (E9)',
             'Apartamento Loft II Select Real · Rúa Real 49, Ortskern · inkl. 6,00 € MwSt', 65.96],
            ['Caldas de Reis (E10)', 'Padrón (E10)',
             'Huna Apartamentos · Travesía Iría 131 · Buchungsnr. 5642460191', 73.50],
            ['Padrón (E11)',         'O Milladoiro (E11)',
             'B&B HOTEL Santiago Milladoiro ★★ · Buchungsnr. 2457423459923289565', 90.13],
        ];
        foreach ($kosten as [$alt, $neu, $detail, $betrag]) {
            $db->run(
                'UPDATE cost_items SET name = ?, detail = ?, status = ?, status_label = ?, updated_at = ?
                 WHERE name = ?',
                [$neu, $detail, 'ok', 'gebucht', date('c'), $alt]
            );
            $db->run('UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL', [$betrag, $neu]);
        }

        /* ---- Die Zahlen oben und unten auf der Seite ------------------- */
        $db->run('UPDATE hero_facts SET number = ?, label = ? WHERE seq = 1', ['251', 'km zu Fuß']);
        $db->run('UPDATE hero_facts SET number = ? WHERE seq = 3', ['~21']);
        $db->setSetting(
            'footer_left',
            'Camino Portugués da Costa · 251 von 266 km · 17.09.–01.10.2026'
        );
    });

    /* Laeuft ausserhalb, weil es eine eigene Transaktion aufmacht. */
    require_once APP_ROOT . '/db/unterkuenfte.php';
    require_once APP_ROOT . '/db/migrations/031_unterkuenfte.php';
    unterkuenfte_einspielen($db, unterkuenfte());
}
