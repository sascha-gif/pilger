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
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Residencial Princesa do Ave · '
                    . 'Einzelzimmer · <b>78,00 €</b> gesamt · Buchungsnr. 5656009787',
                'note' => '<b>Weg dorthin:</b> Der Küstenweg kommt von Süden, quert den <b>Rio Ave</b> '
                    . 'und endet im Ortskern. Von dort rund <b>400 m</b> zur Pension: '
                    . '<b>Rua Dr. António José Sousa Pereira 261, 4480-807</b> — keine 5 Minuten. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'Residencial%20Princesa%20do%20Ave%2C%20Vila%20do%20Conde" '
                    . 'target="_blank" rel="noopener">Karte</a> · '
                    . '<a href="tel:+351252642065">+351 252 642 065</a><br>'
                    . '<small>Die Hausnummer steht in den Verzeichnissen mal als 261, mal als 395 — '
                    . 'verbindlich ist die Buchungsbestätigung, der Kartenlink sucht nach dem Namen. '
                    . 'Die Check-in-Zeit vorher kurz durchgeben: kleine Häuser sind nicht rund um die '
                    . 'Uhr besetzt. PIN-Code steht in der Booking-Mail.</small>',
                'alt_note' => '<b>Senda Litoral:</b> komplett an der Küste, flach &amp; gelenkschonend.',
                'booking_url' => null, 'booking_label' => null,
                'km_big' => '246', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                // Stimmt mit dem Stuetzpunkt in db/kuestenroute.php ueberein.
                'lat' => 41.3533, 'lng' => -8.7425,
                'map_eyebrow' => 'Etappe 1 · 19.09.', 'map_meta' => '20 km · noch 246 km',
                'map_hub' => 0, 'map_name' => 'Vila do Conde',
            ],
            [
                'seq' => 2, 'code' => 'E2 · 20.09.', 'date_label' => '20.09.2026',
                'title' => 'Vila do Conde → Esposende', 'title_suffix' => null,
                'dist' => '24 km',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Hello Esposende · <b>74,00 €</b> · '
                    . 'Buchungsnr. 5557270875',
                'note' => '<b>Weg dorthin:</b> Der Küstenweg quert bei Fão den <b>Rio Cávado</b> — '
                    . 'Fão am Südufer, Esposende am Nordufer. Die Unterkunft liegt <b>gut einen '
                    . 'Kilometer nördlich des Ortskerns</b> (Freguesia Marinhas), also schon in der '
                    . 'Richtung, in die es am nächsten Morgen weitergeht: '
                    . '<b>Rua Dom Dinis 8, 4740-267</b>. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'Hello%20Esposende%2C%20Rua%20Dom%20Dinis%208%2C%20Esposende" '
                    . 'target="_blank" rel="noopener">Karte</a><br>'
                    . '<small>Die Lage stammt aus dem Postleitzahlenregister, nicht aus der Buchung — '
                    . 'für den letzten Kilometer gilt der Kartenlink. Die Entfernungsangaben der '
                    . 'Portale widersprechen sich (Praia de Ofir liegt südlich des Cávado), deshalb '
                    . 'steht davon hier nichts. Check-in-Zeit vorher durchgeben.</small>',
                'alt_note' => '<b>Senda Litoral:</b> über Póvoa de Varzim &amp; Apúlia direkt am Strand.',
                'booking_url' => null,
                'booking_label' => null,
                'km_big' => '222', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.532351, 'lng' => -8.78308,
                'map_eyebrow' => 'Etappe 2 · 20.09.', 'map_meta' => '24 km · noch 222 km',
                'map_hub' => 0, 'map_name' => 'Esposende',
            ],
            [
                'seq' => 3, 'code' => 'E3 · 21.09.', 'date_label' => '21.09.2026',
                'title' => 'Esposende → Viana do Castelo', 'title_suffix' => null,
                'dist' => '25 km',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> B&amp;B HOTEL Viana do Castelo ★★★ · '
                    . 'Doppelzimmer · <b>65,00 €</b> · Check-in ab 14:00, Check-out bis 12:00',
                'note' => '<b>Weg dorthin:</b> Der Küstenweg kommt von Süden und quert den <b>Rio Lima '
                    . 'über die Ponte Eiffel</b> in die Altstadt. Von dort noch einmal <b>rund '
                    . 'anderthalb Kilometer nach Osten</b>: <b>Estrada da Papanata 74, 4900-462</b>. '
                    . 'Nach 25 km aus Esposende ist das kein Nebensatz — und am nächsten Morgen geht '
                    . 'es denselben Weg zurück, weil der Camino nach Norden aus der Altstadt führt. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'B%26B%20HOTEL%20Viana%20do%20Castelo" '
                    . 'target="_blank" rel="noopener">Karte</a> · '
                    . '<a href="tel:+351258121906">+351 258 121 906</a><br>'
                    . '<b>Am Empfang:</b> Check-in-Nummer <b>B1541743170</b> und ein Ausweis auf '
                    . 'denselben Namen.<br>'
                    . '<small><b>Partnerangebot — hier aufpassen:</b> Der Buchungsvertrag besteht '
                    . 'nicht mit dem Hotel, sondern mit LINKALL HONGKONG LIMITED. <b>Änderungen an '
                    . 'der Buchung sind nicht möglich.</b> Fragen kann man das Hotel, zugesagt ist '
                    . 'nichts. Klemmt etwas, ist der Kundenservice von Booking.com zuständig und '
                    . 'nicht der Empfang vor Ort — Bestätigungsnummer und PIN aus der Mail '
                    . 'bereithalten.</small>',
                'alt_note' => '<b>Senda Litoral:</b> letzter durchgehender Küstenabschnitt — danach läuft sie mit der Costa zusammen.',
                'booking_url' => null,
                'booking_label' => null,
                'km_big' => '197', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.694376, 'lng' => -8.837134,
                'map_eyebrow' => 'Etappe 3 · 21.09.', 'map_meta' => '25 km · noch 197 km',
                'map_hub' => 0, 'map_name' => 'Viana do Castelo',
            ],
            [
                'seq' => 4, 'code' => 'E4 · 22.09.', 'date_label' => '22.09.2026',
                'title' => 'Viana do Castelo → Caminha', 'title_suffix' => null,
                'dist' => '26 km',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Residencial Galo d\'Ouro · Einzelzimmer · '
                    . '<b>56,50 €</b> (51,89 Zimmer + 3,11 MwSt + 1,50 Übernachtungssteuer)',
                'note' => '<b>Check-in nur 15:30–18:30</b> — drei Stunden Fenster nach 26 km. Wer gegen 8 Uhr '
                    . 'aus Viana losgeht, passt hinein; deutlich früher steht man vielleicht vor '
                    . 'verschlossener Tür, später besser vorher anrufen. <b>Check-out 08:30–11:30.</b><br>'
                    . '<b>Weg dorthin:</b> Der Küstenweg kommt von Süden über Moledo. Die Pension liegt '
                    . '<b>76 m vom Ortszentrum</b>, eine Minute von der <b>Torre do Relógio</b>, an der '
                    . 'Hauptachse der Praça Central neben der Casa dos Pitas — Blick auf den Platz, auf '
                    . 'den Monte de Santa Tecla drüben in Spanien und auf die Mündung des Minho. '
                    . 'Kein Umweg. <b>Rua da Corredoura 15, 4910-133</b>. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'Residencial%20Galo%20d%27Ouro%2C%20Caminha" '
                    . 'target="_blank" rel="noopener">Karte</a> · '
                    . '<a href="tel:+351258921160">+351 258 921 160</a><br>'
                    . '<small><b>Nicht stornierbar, Daten nicht änderbar</b>, und eine Vorauszahlung des '
                    . 'Gesamtpreises kann jederzeit fällig werden. Frühstück ist nicht dabei. '
                    . '<b>Am nächsten Morgen die Fähre über den Minho</b> — Abfahrtszeiten am Vorabend '
                    . 'prüfen: vor 8:30 kommst du hier nicht raus.</small>',
                'alt_note' => null,
                'booking_url' => null,
                'booking_label' => null,
                'km_big' => '171', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 41.873208, 'lng' => -8.837845,
                'map_eyebrow' => 'Etappe 4 · 22.09.', 'map_meta' => '26 km · noch 171 km · Fähre nach ESP',
                'map_hub' => 0, 'map_name' => 'Caminha',
            ],
            [
                'seq' => 5, 'code' => 'E5 · 23.09.', 'date_label' => '23.09.2026',
                'title' => 'Caminha → Oia', 'title_suffix' => '(Spanien)',
                'dist' => '27 km · längste Etappe · Fähre/Taxiboot über den Minho · ab hier 2 Stempel/Tag',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Hotel-Restaurante Glasgow ★★★, Viladesuso · '
                    . 'Einzelzimmer · <b>100,00 €</b> (90,91 + 9,09 MwSt)',
                'note' => '<b>Achtung, die Etappe ist länger als geplant.</b> Viladesuso gehört zur Gemeinde '
                    . 'Oia, liegt aber <b>rund 4 km nördlich des Klosters</b> — in Laufrichtung. Aus '
                    . '23 km werden damit <b>27 km</b>, und der Tag danach nach Baiona wird um dieselben '
                    . '4 km kürzer. Nach der Fähre über den Minho und dem Grenzübertritt ist das der '
                    . 'zweitlängste Tag der Reise.<br>'
                    . '<b>Estrada Xeral 103, 36309 Viladesuso</b> — direkt an der Küstenstraße, '
                    . 'Restaurant im Haus. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'Hotel%20Restaurante%20Glasgow%2C%20Viladesuso%2C%20Oia" '
                    . 'target="_blank" rel="noopener">Karte</a> · '
                    . '<a href="tel:+34986361552">+34 986 361 552</a><br>'
                    . '<small><b>Nicht stornierbar, Daten nicht änderbar.</b> Ab hier gilt Spanien: '
                    . '<b>zwei Stempel pro Tag</b>. Die 4 km sind aus der Koordinate der Pfarrkirche '
                    . 'gerechnet, nicht aus der Hausnummer — auf die Richtung ist Verlass, auf ein paar '
                    . 'hundert Meter nicht.</small>',
                'alt_note' => null,
                'booking_url' => null,
                'booking_label' => null,
                'km_big' => '144', 'km_sub' => 'Grenze ↦ ESP', 'variant' => 'special',
                'lat' => 42.03204, 'lng' => -8.85833,
                'map_eyebrow' => 'Etappe 5 · 23.09.', 'map_meta' => '27 km · noch 144 km · längste Etappe',
                'map_hub' => 0, 'map_name' => 'Oia',
            ],
            [
                'seq' => 6, 'code' => 'E6 · 24.09.', 'date_label' => '24.09.2026',
                'title' => 'Oia → Nigrán', 'title_suffix' => null,
                'dist' => '21 km · Praia América',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> HOTEL HOLIDAY camino de Santiago '
                    . 'por la costa en playa América ★★★ · Einzelbelegung · <b>50,00 €</b> '
                    . '(inkl. 4,55 € MwSt)',
                'note' => '<b>Carretera Vigo–Baiona (por la costa) 17, 36350 Nigrán</b> — direkt an der '
                    . 'Küstenstraße, an der <b>Praia América</b>, rund 7 km hinter Baiona und damit '
                    . 'schon ein gutes Stück in Laufrichtung. Genau dort läuft die Küstenvariante '
                    . 'entlang: hinter der Ponte da Ramallosa am Wasser bleiben statt über den Monte '
                    . 'San Román zu steigen — den Anstieg sparst du dir damit. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'HOTEL%20HOLIDAY%2C%20Carretera%20Vigo-Baiona%2017%2C%20Nigr%C3%A1n" '
                    . 'target="_blank" rel="noopener">Karte</a><br>'
                    . '<b>Dadurch werden aus 14 und 27 km rund 21 und 20</b> — der halbe Tag '
                    . 'und der längste Tag werden zu zwei normalen. Die Gesamtstrecke bleibt gleich. '
                    . 'Die längste Etappe des ganzen Camino liegt damit hinter dir: die 27 km von '
                    . 'gestern. Das Längste, was noch kommt, ist der letzte Tag nach Santiago.<br>'
                    . '<small>Das Datum stand auf der Übersicht als 25.09. und wurde <b>telefonisch '
                    . 'auf die Nacht vom 24. auf den 25.</b> geändert. Eine Buchungsnummer gibt es '
                    . 'nicht — die Vorlage war die Übersicht, nicht die Bestätigung.</small>',
                'alt_note' => null,
                'booking_url' => null,
                'booking_label' => null,
                'km_big' => '123', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.1500, 'lng' => -8.8100,
                'map_eyebrow' => 'Etappe 6 · 24.09.', 'map_meta' => '21 km · noch 123 km',
                'map_hub' => 0, 'map_name' => 'Nigrán',
            ],
            [
                'seq' => 7, 'code' => 'E7 · 25.09.', 'date_label' => '25.09.2026',
                'title' => 'Nigrán → Vigo', 'title_suffix' => null,
                'dist' => '20 km · am Wasser entlang',
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Alda Estación Vigo · <b>57,00 €</b> '
                    . '(inkl. 5,18 € MwSt)',
                'note' => '<b>Calle Alfonso XIII 19, 36201</b> — 200 m vom Bahnhof Vigo-Urzáiz, mitten im '
                    . 'Geschäftsviertel, wenige Schritte von der Fußgängerstraße <b>Príncipe</b>, die in '
                    . 'die Altstadt hinunterführt. Der Küstenweg kommt von Südwesten am Hafen an: von '
                    . 'dort rund <b>20 Minuten bergauf</b>. Dafür liegt das Haus schon auf der Seite, auf '
                    . 'der es am nächsten Morgen nach Redondela weitergeht. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'Alda%20Estaci%C3%B3n%20Vigo%2C%20Calle%20Alfonso%20XIII%2019%2C%20Vigo" '
                    . 'target="_blank" rel="noopener">Karte</a><br>'
                    . '<small>Vorlage war die <b>Buchungsübersicht</b>, nicht die Bestätigung — unten '
                    . 'stand noch „Letzter Schritt", eine Buchungsnummer gab es nicht. Wenn die '
                    . 'Bestätigungsmail da ist: nachsehen, ob sie wirklich kam.</small>',
                'alt_note' => '<b>Küstenvariante:</b> Hinter der <b>Ponte da Ramallosa</b> biegt der offizielle Weg '
                    . 'ins Land ab und steigt über den Monte San Román. Wer am Wasser bleibt, geht über '
                    . '<b>Nigrán, Praia América und Saiáns</b> nach Vigo — Holzstege, Radwege, flache '
                    . 'Stadtabschnitte, fast durchgehend am Atlantik und flacher als das Original. '
                    . 'Danach dreht der Weg in die Ría und später ins Landesinnere: das hier ist der '
                    . 'letzte Tag am offenen Meer.',
                'booking_url' => null,
                'booking_label' => null,
                'km_big' => '103', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.240598, 'lng' => -8.720726,
                'map_eyebrow' => 'Etappe 7 · 25.09.', 'map_meta' => '27 km · noch 103 km · längste Etappe',
                'map_hub' => 0, 'map_name' => 'Vigo',
            ],
            [
                'seq' => 8, 'code' => 'E8 · 26.09.', 'date_label' => '26.09.2026',
                'title' => 'Vigo → Arcade', 'title_suffix' => null,
                'dist' => '22 km',
                'target' => '<b>Budget-Ziel:</b> Pension vor der Brücke · <b>eigenes Zimmer</b> · ca. 55–75 €',
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
                'dist' => '15 km · kurze Etappe',
                'target' => '<b>Budget-Ziel:</b> Hostal Altstadt (casco vello) · <b>eigenes Zimmer</b> · ca. 60–85 €',
                'note' => null,
                'alt_note' => null,
                'booking_url' => sprintf($bk, 'Pontevedra%2C%20Spain', '2026-09-27', '2026-09-28'),
                'booking_label' => 'Booking Pontevedra',
                'km_big' => '66', 'km_sub' => 'noch bis SCQ', 'variant' => 'normal',
                'lat' => 42.429884, 'lng' => -8.64462,
                'map_eyebrow' => 'Etappe 9 · 27.09.', 'map_meta' => '15 km · noch 66 km · kurze Etappe',
                'map_hub' => 0, 'map_name' => 'Pontevedra',
            ],
            [
                'seq' => 10, 'code' => 'E10 · 28.09.', 'date_label' => '28.09.2026',
                'title' => 'Pontevedra → Caldas de Reis', 'title_suffix' => null,
                'dist' => '22 km',
                'target' => '<b>Budget-Ziel:</b> Pension/kleines Balneario · <b>eigenes Zimmer</b> · ca. 55–80 €',
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
                'target' => '<b>Budget-Ziel:</b> Pension am Ort · <b>eigenes Zimmer</b> · ca. 50–70 €',
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
                'target' => '<b style="color:#2e7d32">Gebucht:</b> Lemonade Stays · Standard-Einzelzimmer · '
                    . '<b>76,95 €</b> bezahlt · Buchungsnr. 6412320933',
                'note' => '<b>Rúa das Galeras 44, 15705</b> — rund <b>385 m von der Praza do Obradoiro</b>, '
                    . 'am Rand der Altstadt. Check-in ab 15:00, Check-out am 01.10. bis 11:00: der Bus '
                    . 'zum Flughafen geht um 9:45, das passt. '
                    . '<a href="https://www.google.com/maps/search/?api=1&amp;query='
                    . 'Lemonade%20Stays%2C%20R%C3%BAa%20das%20Galeras%2044%2C%20Santiago%20de%20Compostela" '
                    . 'target="_blank" rel="noopener">Karte</a> · '
                    . '<a href="tel:+34981072903">+34 981 072 903</a><br>'
                    . '<b>Alles Wichtige passiert am 30.09.:</b> Compostela-Urkunde im Pilgerbüro '
                    . '(Rúa das Carretas 33, 9:00–19:00) und Pilgermesse um 12:00. Am 01.10. sitzt du '
                    . 'zu der Zeit im Flieger.<br>'
                    . '<small><b>Nicht stornierbar, Daten nicht änderbar.</b></small>',
                'alt_note' => null,
                'booking_url' => null,
                'booking_label' => null,
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
                ['Seidenschlafsack', 'Seide, Inlett', '1', 'Hygiene — eigenes Innentuch in fremden Betten'],
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
                ['Papiertütchen (Tagesportionen)', 'klein, braun', '~14', 'Supplements und Medikamente pro Tag vorportionieren — spart Platz'],
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
            ['Flug Rückflug', 'SCQ → Palma → FRA · Vueling + TUI fly · Kiwi.com 838426721', 235.00, 'ok', 'gebucht'],
            ['Porto (2 N)', 'Carpe Diem by Dualgroup', 186.83, 'ok', 'gebucht'],
            ['Vila do Conde (E1)', 'Residencial Princesa do Ave · Einzelzimmer · Buchungsnr. 5656009787', 78.00, 'ok', 'gebucht'],
            ['Esposende (E2)', 'Hello Esposende · Buchungsnr. 5557270875', 74.00, 'ok', 'gebucht'],
            ['Viana do Castelo (E3)', 'B&B HOTEL Viana do Castelo · Doppelzimmer', 65.00, 'ok', 'gebucht'],
            ['Caminha (E4)', 'Residencial Galo d\'Ouro · Einzelzimmer · inkl. 3,11 € MwSt und 1,50 € Übernachtungssteuer', 56.50, 'ok', 'gebucht'],
            ['Oia (E5)', 'Hotel-Restaurante Glasgow ★★★, Viladesuso · Einzelzimmer · inkl. 9,09 € MwSt', 100.00, 'ok', 'gebucht'],
            ['Nigrán (E6)', 'HOTEL HOLIDAY playa América ★★★ · Einzelbelegung · inkl. 4,55 € MwSt', 50.00, 'ok', 'gebucht'],
            ['Vigo (E7)', 'Alda Estación Vigo · Calle Alfonso XIII 19 · inkl. 5,18 € MwSt', 57.00, 'ok', 'gebucht'],
            ['Arcade (E8)', '~ Pension', null, 'open', 'offen'],
            ['Pontevedra (E9)', '~ Hostal casco vello', null, 'open', 'offen'],
            ['Caldas de Reis (E10)', '~ Pension/Balneario', null, 'open', 'offen'],
            ['Padrón (E11)', '~ Pension', null, 'open', 'offen'],
            ['Santiago (E12, 1 N)', 'Lemonade Stays · Standard-Einzelzimmer · Buchungsnr. 6412320933', 76.95, 'ok', 'gebucht'],
            ['Credencial (Pilgerpass)', 'Sé do Porto', 2.00, 'est', 'fix ~2 €'],
            ['Nahverkehr', 'Metro Porto · Fähre Caminha · Bus SCQ-Flughafen', null, 'est', 'Schätzung'],
            ['Verpflegung', '14 Tage Essen/Kaffee/Pilgermenüs', null, 'est', 'Schätzung'],
            ['Wandersocken', 'FALKE TK2 · 2× bestellt · belegt 28,63 € inkl. Versand, 1. Bestellung gleich angesetzt', 57.26, 'ok', 'gekauft'],
            ['Wandershorts', 'maamgic 2-in-1 · 2× bestellt · belegt 27,99 €, 1. Bestellung gleich angesetzt', 55.98, 'ok', 'gekauft'],
            ['Reiseapotheke & Hygiene', 'Compeed Blasenpflaster, Dr. Bronner\'s Seife, Odol-med3 mini, Deo-Roller · Müller 19.08.', 20.54, 'ok', 'gekauft'],
            ['Papiertüten', 'PAKNOR 100 Stück · Tagesportionen für Medikamente · Amazon 16.08.', 5.49, 'ok', 'gekauft'],
            ['USB-C-Ladegerät', 'Tupneuf 60 W, 4 Ports · Amazon 19.08.', 9.99, 'ok', 'gekauft'],
            ['Ausrüstung / Apotheke', 'Was noch fehlt — Powerbank, Trailrunner, Rucksack', null, 'est', 'Schätzung'],
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
            ['pack_intro', '<b>Zielgewicht: unter 8 kg</b> (max. 10 % Körpergewicht) — du trägst alles selbst über Ø 22 km. <b>Seidenschlafsack</b> statt Schlafsack: Bettwäsche stellen die Häuser, das Inlett ist nur für die Hygiene. Waschen alle 2–3 Tage → wenig Kleidung. <b>Hinflug:</b> Messer &amp; Flüssiges &gt;100 ml ins Aufgabegepäck, Powerbank ins Handgepäck. <b>Rückflug — hier aufpassen:</b> im gebuchten Tarif steckt nur <b>ein persönliches Gepäckstück, 40 × 30 × 20 cm</b>. Da passt kein 30–40-Liter-Rucksack hinein. Kabinengepäck (55 × 40 × 20 cm, 10 kg) oder Aufgabegepäck muss <b>bei Vueling und bei TUI fly getrennt</b> dazugebucht werden — es sind zwei Tickets. Am Gate kostet ein zu großes Stück 60 bis 140 €. Steckdosen PT/ES = EU Typ F → kein Adapter nötig.'],
            ['stage_zimmer', '<b>Nur mit eigenem Zimmer.</b> Kein Bett im Schlafsaal — die Suchlinks zeigen aber alles, was es am Ort gibt. Auf booking.com deshalb einmal den Filter <i>Bettenart → Privatzimmer</i> setzen (bzw. bei der Unterkunftsart die Herbergen abwählen). Und aufgepasst bei zwei Wörtern, die sich ähneln und das Gegenteil meinen: ein <b>Albergue</b> ist die Pilgerherberge mit Schlafsaal, ein <b>Hostal</b> ein kleines günstiges Hotel mit eigenem Zimmer.'],
            ['cost_outro', '<b>Orientierung:</b> Unterkünfte grob ~70 €/Nacht × 12 ≈ 840 €. Mit Rückflug, Verpflegung (~25–35 €/Tag) und Transport landet das realistische Gesamtbudget bei etwa <b>1.400–1.900 €</b>. Trag echte Beträge ein — die Summe rechnet live und wird gespeichert.'],
            ['weight_intro', '<b>Bewusst moderat, nicht Crash:</b> ~500–700 kcal Defizit, <b>Protein 140–150 g</b>, Kraft Mo/Mi/Fr weiter — so verlierst du Fett, nicht Muskeln/Kraft, die du für 266 km brauchst. Woche 1 fällt durch weniger Salz/verarbeitete KH schnell Wasser (Bonus auf der Waage). <b>Kein Alkohol</b> in den 6 Wochen. Letzte Woche <b>Taper</b>: Beine frisch, nicht weiter hart abnehmen.'],
            ['weight_outro', '<b>Realistisch:</b> 1–2 kg „schnell" über Wasser in Woche 1, danach ~0,7–0,9 kg/Woche echtes Fett → die 5 kg sind bis Mitte September drin — und du stehst <b>fit</b> am Start, nicht ausgezehrt. Wöchentlich montags früh wiegen (nüchtern) und Ist eintragen.'],
        ];
        foreach ($notes as [$key, $body]) {
            $db->run('INSERT INTO notes (nkey, body) VALUES (?, ?)', [$key, $body]);
        }
    });
}
