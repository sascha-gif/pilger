<?php
declare(strict_types=1);

/**
 * Startdaten: der komplette Masterplan-Inhalt aus camino-masterplan-2026.html.
 * Läuft genau einmal — danach ist die Datenbank die Quelle der Wahrheit und
 * Änderungen passieren über die Oberfläche, nicht mehr hier.
 */
function seed_database(Database $db): void
{
    $db->transaction(function (Database $db) {

        /* ---------- Settings ---------------------------------------------- */
        $settings = [
            'title'        => 'Camino Portugués 2026 — Masterplan',
            'eyebrow'      => 'Masterplan · 2026',
            'h1_top'       => 'Camino Portugués',
            'h1_em'        => 'da Costa',
            'route'        => 'Porto → Santiago de Compostela',
            'footer_left'  => 'Camino Portugués da Costa · 266 km · 17.09.–01.10.2026',
            'footer_right' => 'FRA → OPO · SCQ → FRA',
            'map_center'   => '42.0,-8.72',
            'map_zoom'     => '8',
        ];
        foreach ($settings as $k => $v) {
            $db->run('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$k, $v]);
        }

        /* ---------- Hero-Kennzahlen --------------------------------------- */
        $hero = [
            [1, '266', 'km gesamt', 0],
            [2, '12', 'Etappen', 0],
            [3, '~25', 'km / Tag', 0],
            [4, '17.09.', 'bis 01.10.2026', 1],
        ];
        foreach ($hero as $r) {
            $db->run('INSERT INTO hero_facts (seq, number, label, mono) VALUES (?, ?, ?, ?)', $r);
        }

        /* ---------- 01 Profil --------------------------------------------- */
        $profile = [
            [1, 'Alter / Größe', '47 J · 183 cm', null],
            [2, 'Job', 'Büro', 'sitzend'],
            [3, 'Gym', 'Mo·Mi·Fr', '45 Min Kraft'],
            [4, 'Gewicht aktuell', '93 kg', 'Start 12.01.'],
            [5, 'Schritte/Tag', '7–10k', 'Vorbereitung'],
            [6, 'Ziel', 'Camino-fit', null],
        ];
        foreach ($profile as $r) {
            $db->run('INSERT INTO profile_facts (seq, label, value, sub) VALUES (?, ?, ?, ?)', $r);
        }

        /* ---------- 02 Ernährung ------------------------------------------ */
        $pills = [
            [1, '1.600–1.800', 'kcal'],
            [2, '140–150 g', 'Protein'],
            [3, '16:8', 'Fenster'],
        ];
        foreach ($pills as $r) {
            $db->run('INSERT INTO nutrition_pills (seq, strong, rest) VALUES (?, ?, ?)', $r);
        }

        $slots = [
            [1, '12:00', 'Fasten brechen — 1 Portion <b>Iso Clear</b>', 0],
            [2, '12:30', 'Mittagessen + 1 Kapsel <b>Vitamin B</b>', 1],
            [3, '15:00', '2. Portion <b>Iso Clear</b> (gegen Zuckerlust)', 0],
            [4, '19:30', 'Abendessen — Volumen &amp; Protein: <b>Huhn, Fisch, großer Salat</b>', 1],
            [5, '20:00', '<b>Zähne putzen</b> — Snack-Bremse', 0],
            [6, '21:30', '<b>Magnesium</b> (300–400 mg) + <b>Zink</b> (15–25 mg) — Regeneration', 1],
        ];
        foreach ($slots as $r) {
            $db->run('INSERT INTO nutrition_slots (seq, time_label, body, accent) VALUES (?, ?, ?, ?)', $r);
        }

        /* ---------- 03 Anreise -------------------------------------------- */
        $travel = [
            [
                1,
                'Hinflug · 17.09.2026 · TAP TP6682 (LH) · gebucht',
                1,
                'FRA → OPO',
                0,
                '<b>13:50 → 15:40</b> · Direkt 2 h 50 · Economy · danach Metro <b>E</b> (violett) bis Trindade',
            ],
            [
                2,
                'Orga-Tag · 18.09.2026',
                0,
                'Sé-Kathedrale Porto',
                1,
                '<b>Credencial</b> (~2 €, Reisepass mit!) · 1. Stempel abholen<br>Adresse: <b>Terreiro da Sé, 4050-573 Porto</b> · ~5 Min von São Bento · offen bis ~18:30',
            ],
            [
                3,
                'Rückflug · 01.10.2026 · gebucht',
                0,
                'SCQ → FRA',
                0,
                'Zeiten lt. Buchung · nachmittags Bus zum Flughafen SCQ',
            ],
        ];
        foreach ($travel as $r) {
            $db->run('INSERT INTO travel_cards (seq, tag, tag_ok, route, route_small, meta) VALUES (?, ?, ?, ?, ?, ?)', $r);
        }

        /* ---------- 04 Etappen -------------------------------------------- */
        $bk = 'https://www.booking.com/searchresults.html?ss=%s&checkin=%s&checkout=%s&group_adults=1&no_rooms=1&order=price';

        $stages = [
            [
                'seq' => 0, 'code' => 'START', 'date_label' => '17.09. + 18.09. · 2 Nächte',
                'title' => 'Porto — Basislager', 'title_suffix' => null,
                'dist' => '17.09. + 18.09. · 2 Nächte',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Carpe Diem Porto by Dualgroup · Deluxe Doppelzimmer · <b>186,83 €</b> gesamt (2 Nächte) · zentral bei São Bento',
                'note' => 'Buchungsnr. 5888925188 (PIN in deiner Booking-Mail). Orga-Tag: Credencial &amp; 1. Stempel — Sé nur ~5 Min zu Fuß.',
                'alt_note' => '<b>Variante:</b> Ab Foz/Matosinhos auf der Senda Litoral am Atlantik entlang (Holzstege).',
                'booking_url' => null, 'booking_label' => null,
                'km_big' => '266', 'km_sub' => 'km vor dir', 'variant' => 'anchor',
                'lat' => 41.142853, 'lng' => -8.611116,
                'map_eyebrow' => 'START · 17.09.', 'map_meta' => '2 Nächte · Credencial & 1. Stempel',
                'map_hub' => 1, 'map_name' => 'Porto — Sé Kathedrale',
            ],
            [
                'seq' => 1, 'code' => 'E1 · 19.09.', 'date_label' => '19.09.2026',
                'title' => 'Matosinhos → Vila do Conde', 'title_suffix' => null,
                'dist' => '20 km · Start: Metro A bis Matosinhos Sul',
                'target' => '<b>Budget-Ziel:</b> Pension/3★ am Ortskern · ca. 55–75 €',
                'note' => null,
                'alt_note' => '<b>Senda Litoral:</b> komplett an der Küste, flach &amp; gelenkschonend.',
                'booking_url' => sprintf($bk, 'Vila%20do%20Conde%2C%20Portugal', '2026-09-19', '2026-09-20'),
                'booking_label' => 'Booking Vila do Conde',
                'km_big' => '246', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.333336, 'lng' => -8.682063,
                'map_eyebrow' => 'Etappe 1 · 19.09.', 'map_meta' => '20 km · noch 246 km',
                'map_hub' => 0, 'map_name' => 'Vila do Conde',
            ],
            [
                'seq' => 2, 'code' => 'E2 · 20.09.', 'date_label' => '20.09.2026',
                'title' => 'Vila do Conde → Esposende', 'title_suffix' => null,
                'dist' => '24 km',
                'target' => '<b>Budget-Ziel:</b> Guesthouse/3★ am Fluss · ca. 55–75 €',
                'note' => null,
                'alt_note' => '<b>Senda Litoral:</b> über Póvoa de Varzim &amp; Apúlia direkt am Strand.',
                'booking_url' => sprintf($bk, 'Esposende%2C%20Portugal', '2026-09-20', '2026-09-21'),
                'booking_label' => 'Booking Esposende',
                'km_big' => '222', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.532351, 'lng' => -8.78308,
                'map_eyebrow' => 'Etappe 2 · 20.09.', 'map_meta' => '24 km · noch 222 km',
                'map_hub' => 0, 'map_name' => 'Esposende',
            ],
            [
                'seq' => 3, 'code' => 'E3 · 21.09.', 'date_label' => '21.09.2026',
                'title' => 'Esposende → Viana do Castelo', 'title_suffix' => null,
                'dist' => '25 km',
                'target' => '<b>Budget-Ziel:</b> Pension Altstadt nahe Eiffel-Brücke · ca. 60–80 €',
                'note' => null,
                'alt_note' => '<b>Senda Litoral:</b> letzter durchgehender Küstenabschnitt — danach läuft sie mit der Costa zusammen.',
                'booking_url' => sprintf($bk, 'Viana%20do%20Castelo%2C%20Portugal', '2026-09-21', '2026-09-22'),
                'booking_label' => 'Booking Viana do Castelo',
                'km_big' => '197', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.694376, 'lng' => -8.837134,
                'map_eyebrow' => 'Etappe 3 · 21.09.', 'map_meta' => '25 km · noch 197 km',
                'map_hub' => 0, 'map_name' => 'Viana do Castelo',
            ],
            [
                'seq' => 4, 'code' => 'E4 · 22.09.', 'date_label' => '22.09.2026',
                'title' => 'Viana do Castelo → Caminha', 'title_suffix' => null,
                'dist' => '26 km',
                'target' => '<b>Budget-Ziel:</b> Pension am Hauptplatz · ca. 60–85 €',
                'note' => 'Vor der Fähre nach Spanien — Bootsfahrplan vorab checken.',
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Caminha%2C%20Portugal', '2026-09-22', '2026-09-23'),
                'booking_label' => 'Booking Caminha',
                'km_big' => '171', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.873208, 'lng' => -8.837845,
                'map_eyebrow' => 'Etappe 4 · 22.09.', 'map_meta' => '26 km · noch 171 km · Fähre nach ESP',
                'map_hub' => 0, 'map_name' => 'Caminha',
            ],
            [
                'seq' => 5, 'code' => 'E5 · 23.09.', 'date_label' => '23.09.2026',
                'title' => 'Caminha → Oia', 'title_suffix' => '(Spanien)',
                'dist' => '23 km · Fähre/Taxiboot über den Minho · ab hier 2 Stempel/Tag',
                'target' => '<b>Budget-Ziel:</b> Pension/Hostal · ca. 65–90 € <span style="color:var(--stone)">(dünnes Angebot — früh buchen)</span>',
                'note' => null,
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Oia%2C%20Pontevedra%2C%20Spain', '2026-09-23', '2026-09-24'),
                'booking_label' => 'Booking Oia',
                'km_big' => '148', 'km_sub' => 'Grenze ↦ ESP', 'variant' => 'special',
                'lat' => 42.03204, 'lng' => -8.85833,
                'map_eyebrow' => 'Etappe 5 · 23.09.', 'map_meta' => '23 km · noch 148 km · ab hier 2 Stempel/Tag',
                'map_hub' => 0, 'map_name' => 'Oia',
            ],
            [
                'seq' => 6, 'code' => 'E6 · 24.09.', 'date_label' => '24.09.2026',
                'title' => 'Oia → Baiona', 'title_suffix' => null,
                'dist' => '18 km · kurze Etappe',
                'target' => '<b>Budget-Ziel:</b> Hostal Altstadt (statt Parador) · ca. 65–95 €',
                'note' => 'Parador wäre das teure Highlight — als bewusste Ausnahme behaltbar.',
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Baiona%2C%20Pontevedra%2C%20Spain', '2026-09-24', '2026-09-25'),
                'booking_label' => 'Booking Baiona',
                'km_big' => '130', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.120152, 'lng' => -8.852366,
                'map_eyebrow' => 'Etappe 6 · 24.09.', 'map_meta' => '18 km · noch 130 km',
                'map_hub' => 0, 'map_name' => 'Baiona',
            ],
            [
                'seq' => 7, 'code' => 'E7 · 25.09.', 'date_label' => '25.09.2026',
                'title' => 'Baiona → Vigo', 'title_suffix' => null,
                'dist' => '27 km · längste Etappe',
                'target' => '<b>Budget-Ziel:</b> Stadthotel/Hostal Zentrum · ca. 55–80 €',
                'note' => null,
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Vigo%2C%20Spain', '2026-09-25', '2026-09-26'),
                'booking_label' => 'Booking Vigo',
                'km_big' => '103', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.240598, 'lng' => -8.720726,
                'map_eyebrow' => 'Etappe 7 · 25.09.', 'map_meta' => '27 km · noch 103 km · längste Etappe',
                'map_hub' => 0, 'map_name' => 'Vigo',
            ],
            [
                'seq' => 8, 'code' => 'E8 · 26.09.', 'date_label' => '26.09.2026',
                'title' => 'Vigo → Arcade', 'title_suffix' => null,
                'dist' => '22 km',
                'target' => '<b>Budget-Ziel:</b> Pension vor der Brücke · ca. 55–75 €',
                'note' => 'Arcade = Austern &amp; Meeresfrüchte — Proteinspeicher füllen.',
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Arcade%2C%20Pontevedra%2C%20Spain', '2026-09-26', '2026-09-27'),
                'booking_label' => 'Booking Arcade',
                'km_big' => '81', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.340256, 'lng' => -8.60898,
                'map_eyebrow' => 'Etappe 8 · 26.09.', 'map_meta' => '22 km · noch 81 km · Austern',
                'map_hub' => 0, 'map_name' => 'Arcade',
            ],
            [
                'seq' => 9, 'code' => 'E9 · 27.09.', 'date_label' => '27.09.2026',
                'title' => 'Arcade → Pontevedra', 'title_suffix' => null,
                'dist' => '15 km · kürzeste Etappe',
                'target' => '<b>Budget-Ziel:</b> Hostal Altstadt (casco vello) · ca. 60–85 €',
                'note' => null,
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Pontevedra%2C%20Spain', '2026-09-27', '2026-09-28'),
                'booking_label' => 'Booking Pontevedra',
                'km_big' => '66', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.429884, 'lng' => -8.64462,
                'map_eyebrow' => 'Etappe 9 · 27.09.', 'map_meta' => '15 km · noch 66 km · kürzeste Etappe',
                'map_hub' => 0, 'map_name' => 'Pontevedra',
            ],
            [
                'seq' => 10, 'code' => 'E10 · 28.09.', 'date_label' => '28.09.2026',
                'title' => 'Pontevedra → Caldas de Reis', 'title_suffix' => null,
                'dist' => '22 km',
                'target' => '<b>Budget-Ziel:</b> Pension/kleines Balneario · ca. 55–80 €',
                'note' => 'Thermalort — heiße Quellen fürs Finale, oft auch günstig nutzbar.',
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Caldas%20de%20Reis%2C%20Spain', '2026-09-28', '2026-09-29'),
                'booking_label' => 'Booking Caldas de Reis',
                'km_big' => '44', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.604064, 'lng' => -8.642019,
                'map_eyebrow' => 'Etappe 10 · 28.09.', 'map_meta' => '22 km · noch 44 km · Thermalort',
                'map_hub' => 0, 'map_name' => 'Caldas de Reis',
            ],
            [
                'seq' => 11, 'code' => 'E11 · 29.09.', 'date_label' => '29.09.2026',
                'title' => 'Caldas de Reis → Padrón', 'title_suffix' => null,
                'dist' => '19 km',
                'target' => '<b>Budget-Ziel:</b> Pension am Ort · ca. 50–70 €',
                'note' => null,
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Padron%2C%20A%20Coruna%2C%20Spain', '2026-09-29', '2026-09-30'),
                'booking_label' => 'Booking Padrón',
                'km_big' => '25', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.736643, 'lng' => -8.660243,
                'map_eyebrow' => 'Etappe 11 · 29.09.', 'map_meta' => '19 km · noch 25 km',
                'map_hub' => 0, 'map_name' => 'Padrón',
            ],
            [
                'seq' => 12, 'code' => 'E12 · 30.09.', 'date_label' => '30.09.2026',
                'title' => 'Padrón → Santiago de Compostela', 'title_suffix' => null,
                'dist' => '25 km · Einzug Praza do Obradoiro · 1 Nacht (bis 01.10.)',
                'target' => '<b>Budget-Ziel:</b> Pension/Hostal nahe Altstadt · ca. 70–110 € <span style="color:var(--stone)">(hohe Nachfrage — früh buchen)</span>',
                'note' => 'Compostela-Urkunde: Pilgerbüro Rúa de Carretas 33. Pilgermesse 12:00.',
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Santiago%20de%20Compostela%2C%20Spain', '2026-09-30', '2026-10-01'),
                'booking_label' => 'Booking Santiago',
                'km_big' => '0', 'km_sub' => 'ZIEL', 'variant' => 'special',
                'lat' => 42.880688, 'lng' => -8.544395,
                'map_eyebrow' => 'ZIEL · 30.09.', 'map_meta' => '25 km · Praza do Obradoiro · Urkunde',
                'map_hub' => 1, 'map_name' => 'Santiago de Compostela',
            ],
        ];

        foreach ($stages as $s) {
            $db->run(
                'INSERT INTO stages (seq, code, date_label, title, title_suffix, dist, target, note, alt_note,
                    booking_url, booking_label, km_big, km_sub, variant, lat, lng, map_name, map_eyebrow, map_meta, map_hub, on_map)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)',
                [
                    $s['seq'], $s['code'], $s['date_label'], $s['title'], $s['title_suffix'], $s['dist'],
                    $s['target'], $s['note'], $s['alt_note'], $s['booking_url'], $s['booking_label'],
                    $s['km_big'], $s['km_sub'], $s['variant'], $s['lat'], $s['lng'],
                    $s['map_name'], $s['map_eyebrow'], $s['map_meta'], $s['map_hub'],
                ]
            );
        }

        /* Senda Litoral als eigene Linie — die Costa-Linie ergibt sich aus den Etappen. */
        $litoral = [
            [41.142853, -8.611116], [41.149, -8.670], [41.182, -8.703], [41.247, -8.726],
            [41.293, -8.742], [41.345, -8.747], [41.383, -8.766], [41.452, -8.785],
            [41.531, -8.789], [41.617, -8.810], [41.690, -8.840],
        ];
        $db->run(
            'INSERT INTO map_routes (seq, name, color, weight, dashed, points) VALUES (?,?,?,?,?,?)',
            [1, 'Senda Litoral (Küste)', '#2f9c95', 3, 1, json_encode($litoral)]
        );

        /* ---------- 05 Equipment ------------------------------------------ */
        $equipment = [
            ['Rucksack &amp; Gewicht', [
                'Zielgewicht voll gepackt: <b>&lt; 8 kg</b> (max. 10 % Körpergewicht)',
                '30–40 L Trekkingrucksack, Hüftgurt entlastet Schultern',
                'Brustgurt &amp; gute Lastübertragung — Gelenkschutz',
                'Regen-Cover für den Rucksack',
            ]],
            ['Füße &amp; Haut', [
                'Trailrunner, max. gedämpft (Hoka / Altra)',
                'Zweilagige Socken (Wrightsock)',
                'Morgens Füße mit Hirschtalg fetten',
            ]],
            ['Sonne &amp; Regen', [
                'ISDIN Fotoprotector Fusion Gel Sport, LSF 50',
                'Zieht ein, fettet nicht, keine Ränder auf Merino',
                'Leichter Poncho/Regenjacke (Atlantik-Wetter)',
            ]],
            ['Elektronik &amp; Tagesbedarf', [
                'Carbon-Powerbank Nitecore NB10000 (150 g)',
                'Ohropax',
                'Wasser · Iso Clear (Pulver) · Snacks',
            ]],
        ];
        $seq = 0;
        foreach ($equipment as [$title, $items]) {
            $seq++;
            $db->run('INSERT INTO equipment_cards (seq, title) VALUES (?, ?)', [$seq, $title]);
            $cardId = (int) $db->pdo()->lastInsertId();
            $i = 0;
            foreach ($items as $body) {
                $i++;
                $db->run('INSERT INTO equipment_items (card_id, seq, body) VALUES (?, ?, ?)', [$cardId, $i, $body]);
            }
        }

        /* ---------- 06 Packliste ------------------------------------------ */
        $pack = [
            ['1 · Rucksack &amp; Tragen', [
                ['Wanderrucksack', '30–40 L', '1', 'Hauptgepäck, gute Lastübertragung (Hüftgurt)'],
                ['Rucksack-Regenhülle', 'passend zur Größe', '1', 'Nässeschutz'],
                ['Packbeutel / Kompressionssäcke', 'S/M/L', '3–4', 'Ordnung + zusätzlicher Nässeschutz'],
                ['Karabinerhaken', '2–3', '2–3', 'Schuhe/Nasses außen befestigen, Flaschen'],
                ['Trinkflaschen / Trinksystem', '1,5–2 L gesamt', '1', 'Wasser unterwegs'],
            ]],
            ['2 · Schuhe &amp; Füße', [
                ['Trailrunner, gedämpft (Hoka/Altra)', '½ Nr. größer', '1', 'Haupt-Wanderschuh, eingelaufen!'],
                ['Flip-Flops', 'leicht', '1', 'Abends, Dusche, Füße lüften'],
                ['Wandersocken 2-lagig (Wrightsock)', '—', '3', 'Blasenprävention'],
                ['Hirschtalg-Creme', 'kleine Dose', '1', 'Füße morgens fetten'],
                ['Blasenpflaster (Compeed)', 'Sortiment', '1 Pack', 'Sofortversorgung'],
            ]],
            ['3 · Wanderkleidung', [
                ['Merino-T-Shirt (kurz)', '—', '2', 'Wandern, geruchsarm'],
                ['Merino-Longsleeve', '—', '1', 'Kühle Morgen / UV-Schutz'],
                ['Wanderhose leicht (Zip-off)', '—', '1', 'Wandern / wird zur Shorts'],
                ['Funktions-Unterhosen', 'schnelltrocknend', '3', 'Täglich, handwaschbar'],
                ['Sonnenhut / Cap', '—', '1', 'Sonnenschutz (Küste schattenlos)'],
            ]],
            ['4 · Abend &amp; Schlafen', [
                ['Leichte lange Hose / Jogger', '—', '1', 'Abends im Ort'],
                ['Casual Shirt / Longsleeve', '—', '1', 'Abends, Restaurant'],
                ['Schlafshirt + Boxer', '—', '1', 'Schlafen'],
                ['Schlafsack', '—', '1', 'Gewicht im Blick behalten — Ziel bleibt unter 8 kg'],
            ]],
            ['5 · Regen &amp; Wärme', [
                ['Regenjacke atmungsaktiv / Poncho', 'packbar', '1', 'Atlantik-Regen, windexponiert'],
            ]],
            ['6 · Sonnen- &amp; Hautschutz', [
                ['ISDIN Fusion Gel Sport LSF 50', '≤100 ml Handgep.', '1', 'Gesicht &amp; Körper'],
                ['Sonnenbrille', '—', '1', 'Blendung am Wasser'],
            ]],
            ['7 · Wäsche &amp; Hygiene', [
                ['Mikrofaser-Reisehandtuch', 'M/L', '1', 'Ergänzend (Hotels haben Handtücher)'],
                ['Zahnbürste + Zahnpasta', 'Reisegröße', '1', 'Auch Snack-Bremse (20:00)'],
                ["Dr. Bronner's Almond Pure Castile Bar Soap", '1 × 140 g, fest', '1', 'Körper + Haare + Wäsche in einem · flugtauglich, kein Auslaufen'],
                ['Deo (Stick/Creme)', '—', '1', '—'],
                ['Nagelschere + Nagelfeile', '→ Aufgabegepäck', '1', 'Fußnägel kurz &amp; glatt halten (Blasen!)'],
                ['Handdesinfektion / Feuchttücher', '≤100 ml', '1', 'Unterwegs'],
            ]],
            ['8 · Reiseapotheke', [
                ['Ibuprofen 400', '—', '1 Pack', 'Schmerz / Entzündung'],
                ['Kopfschmerztabletten', 'z. B. Paracetamol/ASS', '1 Pack', 'Kopfschmerzen'],
                ['Blasenset: Nadel, Desinfektion, Tape', '—', '1', 'Blasen fachgerecht versorgen'],
                ['Pflaster + Wunddesinfektion', 'Sortiment', '1', 'Kleine Wunden'],
                ['Loperamid + Elektrolyt', '—', '1', 'Magen-Darm'],
                ['Persönliche Medikamente', 'nach Bedarf', '—', 'Mit Rezept-Kopie'],
            ]],
            ['9 · Supplements &amp; Ernährung', [
                ['Iso Clear (Portionsbeutel)', 'Pulver, Handgep. ok', '~28', '2/Tag · Fastenbrechen + Zuckerlust'],
                ['Vitamin B', 'in Tagestütchen', '14 T.', 'Täglich mittags'],
                ['Magnesium 300–400 mg', 'in Tagestütchen', '14 T.', 'Regeneration (21:30)'],
                ['Zink 15–25 mg', 'in Tagestütchen', '14 T.', 'Regeneration (21:30)'],
                ['Papiertütchen (Tagesportionen)', 'klein', '~14', 'Supplements pro Tag vorportionieren — spart Platz'],
            ]],
            ['10 · Elektronik', [
                ['Smartphone', '—', '1', 'Navi, Fotos, Buchungen'],
                ['Powerbank Nitecore NB10000', '150 g', '1', 'Laden unterwegs'],
                ['Ladegerät USB-C + Kabel', '—', '1', 'Über Nacht laden'],
                ['Ohropax', '—', '1', 'Schlaf'],
                ['Kopfhörer', 'optional', '1', 'Podcast / Musik'],
                ['Fitbit', 'am Arm', '1', 'Schritte, Puls, Schlaf — speist die Seite'],
                ['Ladekabel Fitbit', 'eigener Klemmadapter', '1', 'Passt zu nichts anderem — leicht vergessen'],
            ]],
            ['11 · Dokumente, Geld &amp; Pilger', [
                ['Reisepass / Personalausweis', '—', '1', 'Flug, Grenze, Credencial'],
                ['Credencial (Pilgerausweis)', 'in Porto holen', '1', 'Stempel → Compostela'],
                ['Flugtickets (FRA↔OPO/SCQ)', 'digital + Print', '1', 'Hin/Rück · gebucht'],
                ['EHIC + Auslandskranken-Police', 'Karte + Kopie', '1', 'Notfall-Absicherung'],
                ['Kreditkarte + Bargeld', '~150 € bar', '1', 'Kleine Orte oft nur bar'],
                ['Kopien Pass/Versicherung (Cloud)', 'digital', '—', 'Verlust-Absicherung'],
            ]],
            ['12 · Kleinkram &amp; Optional', [
                ['Sicherheitsnadeln', '—', '4–6', 'Wäsche trocknen, Reparatur'],
                ['Mini-Taschenmesser', '→ Aufgabegepäck', '1', 'Obst, Käse, Allzweck'],
                ['Zip-Beutel (diverse)', '—', '3–4', 'Doku/Handy wasserdicht'],
            ]],
        ];
        $cseq = 0;
        foreach ($pack as [$title, $items]) {
            $cseq++;
            $db->run('INSERT INTO pack_categories (seq, title) VALUES (?, ?)', [$cseq, $title]);
            $catId = (int) $db->pdo()->lastInsertId();
            $i = 0;
            foreach ($items as [$name, $size, $qty, $purpose]) {
                $i++;
                $db->run(
                    'INSERT INTO pack_items (category_id, seq, name, size, qty, purpose, checked) VALUES (?,?,?,?,?,?,0)',
                    [$catId, $i, $name, $size, $qty, $purpose]
                );
            }
        }

        /* ---------- 07 Kosten --------------------------------------------- */
        $costs = [
            ['Flug Hinflug', 'FRA→OPO · TAP TP6682', 258.00, 'ok', 'gebucht'],
            ['Flug Rückflug', 'SCQ→FRA', null, 'open', 'offen'],
            ['Porto (2 N)', 'Carpe Diem by Dualgroup', 186.83, 'ok', 'gebucht'],
            ['Vila do Conde (E1)', '~ Pension', null, 'open', 'offen'],
            ['Esposende (E2)', '~ Guesthouse', null, 'open', 'offen'],
            ['Viana do Castelo (E3)', '~ Pension', null, 'open', 'offen'],
            ['Caminha (E4)', '~ Pension', null, 'open', 'offen'],
            ['Oia (E5)', '~ dünnes Angebot', null, 'open', 'offen'],
            ['Baiona (E6)', '~ Hostal', null, 'open', 'offen'],
            ['Vigo (E7)', '~ Stadthotel', null, 'open', 'offen'],
            ['Arcade (E8)', '~ Pension', null, 'open', 'offen'],
            ['Pontevedra (E9)', '~ Hostal casco vello', null, 'open', 'offen'],
            ['Caldas de Reis (E10)', '~ Pension/Balneario', null, 'open', 'offen'],
            ['Padrón (E11)', '~ Pension', null, 'open', 'offen'],
            ['Santiago (E12, 1 N)', '~ Hochsaison', null, 'open', 'offen'],
            ['Credencial (Pilgerpass)', 'Sé do Porto', 2.00, 'est', 'fix ~2 €'],
            ['Nahverkehr', 'Metro Porto · Fähre Caminha · Bus SCQ-Flughafen', null, 'est', 'Schätzung'],
            ['Verpflegung', '14 Tage Essen/Kaffee/Pilgermenüs', null, 'est', 'Schätzung'],
            ['Ausrüstung / Apotheke', 'Restkäufe (Seife, Powerbank, Pflaster …)', null, 'est', 'Schätzung'],
            ['Puffer / Sonstiges', 'Trinkgeld, Souvenirs, Unvorhergesehenes', null, 'est', 'Schätzung'],
        ];
        $i = 0;
        foreach ($costs as [$name, $detail, $amount, $status, $label]) {
            $i++;
            $db->run(
                'INSERT INTO cost_items (seq, name, detail, amount, status, status_label) VALUES (?,?,?,?,?,?)',
                [$i, $name, $detail, $amount, $status, $label]
            );
        }

        /* ---------- 08 Countdown ------------------------------------------ */
        $weeks = [
            ['Start', '09.08.', '93,0 kg', 93.0, 'Baseline', '—', 'Ausgangswert'],
            ['W1', '11.–17.08.', '91,5', null, '8–9k', '10 km', 'Defizit sauber, wenig Salz/KH → Wasser fällt'],
            ['W2', '18.–24.08.', '90,5', null, '9–10k', '12–14 km', 'Protein hoch, Kraft halten'],
            ['W3', '25.–31.08.', '89,6', null, '10–12k', '16 km', 'Trailrunner einlaufen, Blasenstellen testen'],
            ['W4', '01.–07.09.', '88,8', null, '12k', '18–20 km mit Pack', 'Rucksack-Setup testen, &lt; 8 kg'],
            ['W5', '08.–14.09.', '88,0', null, '12–15k', '20+ km Generalprobe', 'Alles einmal komplett durchspielen'],
            ['W6', '15.–17.09.', '~88 halten', null, 'locker', '1× 8 km locker', 'Taper: Beine frisch, KH wieder hoch, kein Crash'],
        ];
        $i = 0;
        foreach ($weeks as [$label, $period, $target, $actual, $steps, $walk, $focus]) {
            $i++;
            $db->run(
                'INSERT INTO weight_weeks (seq, label, period, target, actual, steps, long_walk, focus) VALUES (?,?,?,?,?,?,?,?)',
                [$i, $label, $period, $target, $actual, $steps, $walk, $focus]
            );
        }

        /* ---------- Hinweisboxen ------------------------------------------ */
        $notes = [
            ['pack_intro', '<b>Zielgewicht: unter 8 kg</b> (max. 10 % Körpergewicht) — du trägst alles selbst über Ø 22 km. <b>Kein Schlafsack nötig</b> (Hotels/Pensionen mit Bettwäsche &amp; Handtüchern). Waschen alle 2–3 Tage → wenig Kleidung. <b>Flug:</b> Messer &amp; Flüssiges &gt;100 ml ins Aufgabegepäck. Powerbank ins Handgepäck. Steckdosen PT/ES = EU Typ F → kein Adapter nötig.'],
            ['cost_outro', '<b>Orientierung:</b> Unterkünfte grob ~70 €/Nacht × 12 ≈ 840 €. Mit Rückflug, Verpflegung (~25–35 €/Tag) und Transport landet das realistische Gesamtbudget bei etwa <b>1.400–1.900 €</b>. Trag echte Beträge ein — die Summe rechnet live und wird gespeichert.'],
            ['weight_intro', '<b>Bewusst moderat, nicht Crash:</b> ~500–700 kcal Defizit, <b>Protein 140–150 g</b>, Kraft Mo/Mi/Fr weiter — so verlierst du Fett, nicht Muskeln/Kraft, die du für 266 km brauchst. Woche 1 fällt durch weniger Salz/verarbeitete KH schnell Wasser (Bonus auf der Waage). <b>Kein Alkohol</b> in den 6 Wochen. Letzte Woche <b>Taper</b>: Beine frisch, nicht weiter hart abnehmen.'],
            ['weight_outro', '<b>Realistisch:</b> 1–2 kg „schnell" über Wasser in Woche 1, danach ~0,7–0,9 kg/Woche echtes Fett → die 5 kg sind bis Mitte September drin — und du stehst <b>fit</b> am Start, nicht ausgezehrt. Wöchentlich montags früh wiegen (nüchtern) und Ist eintragen.'],
        ];
        foreach ($notes as [$key, $body]) {
            $db->run('INSERT INTO notes (nkey, body) VALUES (?, ?)', [$key, $body]);
        }
    });
}
