<?php
declare(strict_types=1);

/**
 * Wo geschlafen wurde — als Daten, nicht als Fließtext.
 *
 * Auf der Etappenkarte steht das alles schon, aber als fertig formulierter
 * HTML-Absatz: gut zum Lesen am Abend, unbrauchbar für alles andere. Das
 * Journal will daraus etwas erzählen können, und dafür muss es die Teile
 * einzeln in die Hand nehmen.
 *
 * **Nicht alles davon sind Hotels.** Ein *Residencial* ist ein portugiesisches
 * Gästehaus, ein *Hostal* das spanische Gegenstück — ein kleines Haus mit
 * eigenem Zimmer. Ein *Albergue* ist die Pilgerherberge mit Schlafsaal und
 * kam hier nie infrage. Wo der Name die Art nennt („Residencial Galo d'Ouro",
 * „B&B HOTEL"), steht sie hier. Wo er es nicht tut — „Hello Esposende",
 * „Lemonade Stays", „Carpe Diem", „Alda Estación" —, bleibt das Feld **leer**.
 * Ein Haus zum Hotel zu erklären, weil es sich so anhört, wäre geraten.
 *
 * Aus demselben Grund gibt es keine Koordinaten: die Adressen stehen fest, die
 * Punkte dazu müsste man nachschlagen, und ein um 200 m danebenliegender
 * Stecknadelkopf ist schlechter als keiner. Verlinkt wird deshalb auf die
 * Adresse, nicht auf einen Punkt.
 *
 * `quelle` sagt, woher es stammt: `bestaetigung` ist die Buchungsbestätigung,
 * `uebersicht` nur der Bildschirm davor. Vigo steht auf `uebersicht` — da
 * fehlt die Nummer, und das soll man sehen.
 *
 * @return array<int,array<string,mixed>>
 */
function unterkuenfte(): array
{
    return [
        [
            'seq' => 0, 'stage_seq' => 0,
            'name' => 'Carpe Diem Porto by Dualgroup',
            'art' => null,
            'zimmer' => 'Deluxe-Doppelzimmer',
            'strasse' => null, 'plz' => null, 'ort' => 'Porto', 'land' => 'PT',
            'telefon' => null,
            'von' => '2026-09-17', 'bis' => '2026-09-19', 'naechte' => 2,
            'preis' => 186.83, 'buchungsnr' => '5888925188',
            'checkin' => null, 'checkout' => null,
            'lage' => 'Zentral bei São Bento — das Basislager für die ersten zwei Tage, '
                    . 'Ankunftstag und Orga-Tag.',
            'hinweis' => 'Bezahlt.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 1, 'stage_seq' => 1,
            'name' => 'Residencial Princesa do Ave',
            'art' => 'Residencial',
            'zimmer' => 'Einzelzimmer',
            'strasse' => 'Rua Dr. António José Sousa Pereira 261', 'plz' => '4480-807',
            'ort' => 'Vila do Conde', 'land' => 'PT',
            'telefon' => '+351 252 642 065',
            'von' => '2026-09-19', 'bis' => '2026-09-20', 'naechte' => 1,
            'preis' => 78.00, 'buchungsnr' => '5656009787',
            'checkin' => null, 'checkout' => null,
            'lage' => 'Rund 400 m vom Ortskern.',
            'hinweis' => 'Die Hausnummer steht in den Verzeichnissen mal als 261, mal als 395 — '
                       . 'verbindlich ist die Buchungsbestätigung.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 2, 'stage_seq' => 2,
            'name' => 'Hello Esposende',
            'art' => null,
            'zimmer' => null,
            'strasse' => 'Rua Dom Dinis 8', 'plz' => '4740-267',
            'ort' => 'Esposende', 'land' => 'PT',
            'telefon' => null,
            'von' => '2026-09-20', 'bis' => '2026-09-21', 'naechte' => 1,
            'preis' => 74.00, 'buchungsnr' => '5557270875',
            'checkin' => null, 'checkout' => null,
            'lage' => 'Gut einen Kilometer nördlich des Ortskerns, in der Freguesia Marinhas — '
                    . 'also schon in Laufrichtung für den nächsten Tag.',
            'hinweis' => 'Die Lage stammt aus dem Postleitzahlenregister, nicht aus der Buchung; '
                       . 'die Entfernungsangaben der Portale widersprechen sich.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 3, 'stage_seq' => 3,
            'name' => 'B&B HOTEL Viana do Castelo',
            'art' => 'Hotel',
            'zimmer' => 'Doppelzimmer',
            'strasse' => 'Estrada da Papanata 74', 'plz' => '4900-462',
            'ort' => 'Viana do Castelo', 'land' => 'PT',
            'telefon' => '+351 258 121 906',
            'von' => '2026-09-21', 'bis' => '2026-09-22', 'naechte' => 1,
            'preis' => 65.00, 'buchungsnr' => null,
            'checkin' => 'ab 14:00', 'checkout' => 'bis 12:00',
            'lage' => 'Rund anderthalb Kilometer östlich der Altstadt — ein Umweg hin und am '
                    . 'nächsten Morgen wieder zurück.',
            'hinweis' => 'Partnerangebot: der Buchungsvertrag besteht mit LINKALL HONGKONG LIMITED, '
                       . 'Änderungen sind nicht möglich. Bei Problemen ist der Booking.com-'
                       . 'Kundenservice zuständig, nicht der Empfang.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 4, 'stage_seq' => 4,
            'name' => 'Residencial Galo d\'Ouro',
            'art' => 'Residencial',
            'zimmer' => 'Einzelzimmer',
            'strasse' => 'Rua da Corredoura 15', 'plz' => '4910-133',
            'ort' => 'Caminha', 'land' => 'PT',
            'telefon' => '+351 258 921 160',
            'von' => '2026-09-22', 'bis' => '2026-09-23', 'naechte' => 1,
            'preis' => 56.50, 'buchungsnr' => null,
            'checkin' => '15:30–18:30', 'checkout' => '08:30–11:30',
            'lage' => '76 m vom Ortszentrum, eine Minute von der Torre do Relógio, an der '
                    . 'Hauptachse der Praça Central. Kein Umweg — die beste Lage der ganzen Reise.',
            'hinweis' => 'Nicht stornierbar, keine Änderungen, kein Frühstück. Das Check-in-Fenster '
                       . 'ist nur drei Stunden lang, und am nächsten Morgen geht die Fähre über '
                       . 'den Minho — vor halb neun kommt man hier nicht weg.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 5, 'stage_seq' => 5,
            'name' => 'Hotel-Restaurante Glasgow',
            'art' => 'Hotel',
            'zimmer' => 'Einzelzimmer',
            'strasse' => 'Estrada Xeral 103', 'plz' => '36309',
            'ort' => 'Viladesuso', 'land' => 'ES',
            'telefon' => '+34 986 361 552',
            'von' => '2026-09-23', 'bis' => '2026-09-24', 'naechte' => 1,
            'preis' => 100.00, 'buchungsnr' => null,
            'checkin' => null, 'checkout' => null,
            'lage' => 'Viladesuso liegt rund 4 km nördlich von Oia — in Laufrichtung. Deshalb ist '
                    . 'die fünfte Etappe 27 km lang und die sechste nur noch 14.',
            'hinweis' => 'Nicht stornierbar, keine Änderungen. Die erste Nacht in Spanien.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 6, 'stage_seq' => 6,
            'name' => 'HOTEL HOLIDAY camino de Santiago por la costa en playa América',
            'art' => 'Hotel',
            'zimmer' => 'Einzelbelegung',
            'strasse' => 'Carretera Vigo–Baiona (por la costa) 17', 'plz' => '36350',
            'ort' => 'Nigrán', 'land' => 'ES',
            'telefon' => null,
            'von' => '2026-09-24', 'bis' => '2026-09-25', 'naechte' => 1,
            'preis' => 50.00, 'buchungsnr' => null,
            'checkin' => null, 'checkout' => null,
            'lage' => 'An der Praia América, direkt an der Küstenstraße — rund 7 km hinter '
                    . 'Baiona und damit schon in Laufrichtung. Genau da, wo der Küstenweg '
                    . 'entlangläuft, statt hinter der Ponte da Ramallosa über den Monte San '
                    . 'Román zu steigen.',
            'hinweis' => 'Nicht geplant gewesen: das Quartier hier macht aus einem halben Tag '
                       . '(14 km) und dem längsten Tag der Reise (27 km) zwei normale von 21 und '
                       . '20 km. Das Datum stand auf der Übersicht als 25.09. und wurde '
                       . 'telefonisch auf die Nacht vom 24. geändert. Eine Buchungsnummer gibt '
                       . 'es nicht.',
            'quelle' => 'bestaetigung',
        ],
        [
            'seq' => 7, 'stage_seq' => 7,
            'name' => 'Alda Estación Vigo',
            'art' => null,
            'zimmer' => null,
            'strasse' => 'Calle Alfonso XIII 19', 'plz' => '36201',
            'ort' => 'Vigo', 'land' => 'ES',
            'telefon' => null,
            'von' => '2026-09-25', 'bis' => '2026-09-26', 'naechte' => 1,
            'preis' => 57.00, 'buchungsnr' => null,
            'checkin' => null, 'checkout' => null,
            'lage' => '200 m vom Bahnhof Vigo-Urzáiz, im Geschäftsviertel nahe der Fußgängerstraße '
                    . 'Príncipe. Vom Hafen, wo der Küstenweg ankommt, rund 20 Minuten bergauf — '
                    . 'dafür schon in Laufrichtung für den nächsten Tag.',
            'hinweis' => 'Vorlage war die Buchungsübersicht, nicht die Bestätigung — die '
                       . 'Buchungsnummer fehlt noch.',
            'quelle' => 'uebersicht',
        ],
        [
            'seq' => 12, 'stage_seq' => 12,
            'name' => 'Lemonade Stays',
            'art' => null,
            'zimmer' => 'Standard-Einzelzimmer',
            'strasse' => 'Rúa das Galeras 44', 'plz' => '15705',
            'ort' => 'Santiago de Compostela', 'land' => 'ES',
            'telefon' => '+34 981 072 903',
            'von' => '2026-09-30', 'bis' => '2026-10-01', 'naechte' => 1,
            'preis' => 76.95, 'buchungsnr' => '6412320933',
            'checkin' => 'ab 15:00', 'checkout' => 'bis 11:00',
            'lage' => 'Rund 385 m von der Praza do Obradoiro — die letzte Nacht, am Ziel.',
            'hinweis' => 'Bezahlt, nicht stornierbar, keine Änderungen. Der Check-out um 11:00 '
                       . 'passt zum Bus 6A um 9:45.',
            'quelle' => 'bestaetigung',
        ],
    ];
}
