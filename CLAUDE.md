# Camino Portugués da Costa 2026 — Projektkontext

Der Plan läuft als Web-App unter **pilger.milsh.com** (PHP 8 + MariaDB).
Quelle der Wahrheit sind die **Datenbank** und dieses Repo, nicht mehr die
statische Datei `camino-masterplan-2026.html` — die liegt nur noch als Referenz
der Ursprungsfassung dabei.

Projektstand, Absprachen und offene Punkte: **[HANDOVER.md](HANDOVER.md)**.
Technik: `docs/DEPLOYMENT.md`, `docs/DATENBANK.md`, `docs/API.md`.

## So wird hier gearbeitet

- Alles über GitHub, **kein Terminal-Kram für Sascha**.
- Wissen gehört in `.md`-Dateien, nicht in den Chatverlauf.
- Merge nach `main` löst den Deploy aus; Merge und Deploy macht Claude selbst.
- Etappen, Kosten, Packliste ändern heißt: `db/seed.php` für neue Datenbanken
  **und** eine Migration unter `db/migrations/` für die laufende — nicht nur das
  HTML anfassen.
- Was nicht sicher bekannt ist, wird nicht ausgedacht. Lieber „Mittel der
  Vorjahre" dranschreiben als eine Wettervorhersage erfinden, die es für
  September noch gar nicht gibt.

## Zutritt

Die Seite steht hinter einem Passwort — die ganze Seite, nicht nur das
Speichern. Gesetzt wird es in der Oberfläche beim ersten Aufruf. Solange keins
da ist, zeigt die Seite nichts als die Einrichtung. Zurücksetzen geht nur an der
Datenbank (`settings.auth_hash` und `auth_tokens` löschen).

## Eckdaten

- Route: Porto → Santiago de Compostela, Caminho da Costa (Variante: Senda Litoral)
- 266 km, 12 Etappen, Ø ~25 km/Tag (real Ø ~22 km)
- Zeitraum: 17.09.–01.10.2026
- Kein Gepäcktransport — alles selbst tragen, Zielgewicht Rucksack < 8 kg

## Profil

47 J · 183 cm · sitzender Bürojob · Kraft Mo/Mi/Fr 45 Min · 7–10k Schritte/Tag
Gewicht 93 kg (Start 12.01.) → Ziel ~88 kg bis Abflug

## Ernährung

16:8 Intervallfasten · 1.600–1.800 kcal · 140–150 g Protein · kein Alkohol in den 6 Wochen

| Zeit | Was |
|---|---|
| 12:00 | Fasten brechen, 1 Portion Iso Clear |
| 12:30 | Mittagessen + Vitamin B |
| 15:00 | 2. Portion Iso Clear (gegen Zuckerlust) |
| 19:30 | Abendessen: Huhn/Fisch/großer Salat |
| 20:00 | Zähne putzen (Snack-Bremse) |
| 21:30 | Magnesium 300–400 mg + Zink 15–25 mg |

## Anreise

- Hinflug **gebucht**: 17.09.2026, TAP TP6682 (LH), FRA→OPO 13:50→15:40, direkt 2 h 50,
  danach Metro E (violett) bis Trindade
- Rückflug **gebucht** über Kiwi.com (Booking-ID 838426721), 235,00 € bezahlt,
  01.10.2026 in zwei Etappen: **SCQ 12:30 → Palma 14:25** (Vueling VY3981) und
  **Palma 16:20 → FRA 18:50** (TUI fly X32433). **Zwei getrennte Tickets** —
  in Palma wird selbst umgestiegen, 1 h 55 Zeit, „Connection protection" ist
  mitgebucht (bei Verspätung Kiwi anrufen, nicht die Airline).
  Zum Flughafen: **Stadtlinie 6A** ab Praza de Galicia, alle 20–30 Min,
  ~35 Min, bar beim Fahrer (~1 €); Taxi als Rückfall ~23 €. Losgehen 09:20,
  am Flughafen 10:30.
- 18.09. Orga-Tag: Credencial an der Sé-Kathedrale Porto (~2 €, Reisepass mitnehmen),
  Terreiro da Sé, 4050-573 Porto, offen bis ~18:30, 1. Stempel

## Etappen

| # | Datum | Strecke | km | Rest bis SCQ |
|---|---|---|---|---|
| — | 17.–18.09. | Porto (Basislager, 2 Nächte) | — | 266 |
| E1 | 19.09. | Matosinhos → Vila do Conde (Start Metro A, Matosinhos Sul) | 20 | 246 |
| E2 | 20.09. | Vila do Conde → Esposende | 24 | 222 |
| E3 | 21.09. | Esposende → Viana do Castelo | 25 | 197 |
| E4 | 22.09. | Viana do Castelo → Caminha (Fährfahrplan checken!) | 26 | 171 |
| E5 | 23.09. | Caminha → Viladesuso/Oia (Fähre über den Minho, Grenze ESP, ab hier 2 Stempel/Tag) | 27 | 144 |
| E6 | 24.09. | Viladesuso → Baiona | 14 | 130 |
| E7 | 25.09. | Baiona → Vigo (längste Etappe) | 27 | 103 |
| E8 | 26.09. | Vigo → Arcade (Austern) | 22 | 81 |
| E9 | 27.09. | Arcade → Pontevedra | 15 | 66 |
| E10 | 28.09. | Pontevedra → Caldas de Reis (Thermalort) | 22 | 44 |
| E11 | 29.09. | Caldas de Reis → Padrón | 19 | 25 |
| E12 | 30.09. | Padrón → Santiago (Praza do Obradoiro) | 25 | 0 |

Senda-Litoral-Variante möglich bis Viana do Castelo (Küste, flach, gelenkschonend);
danach läuft sie mit der Costa zusammen.

## Buchungsstand

- **Gebucht:** Porto, Carpe Diem Porto by Dualgroup, Deluxe Doppelzimmer,
  186,83 € gesamt (2 Nächte), zentral bei São Bento, Buchungsnr. 5888925188
- **Gebucht:** Vila do Conde (E1), Residencial Princesa do Ave, Einzelzimmer,
  19.–20.09., 78,00 € gesamt, Buchungsnr. 5656009787. Rua Dr. António José
  Sousa Pereira 261, 4480-807, Tel. +351 252 642 065 — rund 400 m vom Ortskern.
  Die Hausnummer steht in den Verzeichnissen mal als 261, mal als 395;
  verbindlich ist die Buchungsbestätigung.
- **Gebucht:** Esposende (E2), Hello Esposende, 20.–21.09., 74,00 € bezahlt,
  Buchungsnr. 5557270875. Rua Dom Dinis 8, 4740-267 — gut einen Kilometer
  nördlich des Ortskerns (Freguesia Marinhas), also schon in Laufrichtung für
  E3. Die Lage stammt aus dem Postleitzahlenregister, nicht aus der Buchung;
  die Entfernungsangaben der Portale widersprechen sich, deshalb steht auf der
  Seite nur die Adresse und ein Kartenlink auf den Namen.
- **Gebucht:** Viana do Castelo (E3), B&B HOTEL Viana do Castelo ★★★,
  Doppelzimmer, 21.–22.09., 65,00 €, Check-in ab 14:00, Check-out bis 12:00.
  Estrada da Papanata 74, 4900-462, Tel. +351 258 121 906 — rund anderthalb
  Kilometer **östlich** der Altstadt, also ein Umweg hin und am nächsten Morgen
  zurück. Am Empfang: **Check-in-Nummer B1541743170** und Ausweis auf denselben
  Namen. **Partnerangebot:** der Buchungsvertrag besteht mit LINKALL HONGKONG
  LIMITED, **Änderungen sind nicht möglich**; bei Problemen ist der
  Booking.com-Kundenservice zuständig, nicht der Empfang.
- **Gebucht:** Caminha (E4), Residencial Galo d'Ouro, Einzelzimmer, 22.–23.09.,
  56,50 € (51,89 Zimmer + 3,11 MwSt + 1,50 Übernachtungssteuer). Rua da
  Corredoura 15, 4910-133, Tel. +351 258 921 160 — 76 m vom Ortszentrum, eine
  Minute von der Torre do Relógio, kein Umweg. **Check-in nur 15:30–18:30,
  Check-out 08:30–11:30** (wichtig für die Fähre am nächsten Morgen).
  Nicht stornierbar, keine Änderungen, Vorauszahlung kann jederzeit fällig
  werden, kein Frühstück.
- **Gebucht:** Oia (E5), Hotel-Restaurante Glasgow ★★★, Einzelzimmer, 23.–24.09.,
  100,00 € (90,91 + 9,09 MwSt). Estrada Xeral 103, 36309 **Viladesuso**,
  Tel. +34 986 361 552. **Viladesuso liegt rund 4 km nördlich von Oia** — in
  Laufrichtung. Deshalb ist E5 jetzt 27 km und E6 nur noch 14 km; die Summe
  bleibt 266 km. Nicht stornierbar, keine Änderungen.
- **Gebucht:** Santiago (E12), Lemonade Stays, Standard-Einzelzimmer,
  30.09.–01.10., 76,95 € bezahlt, Buchungsnr. 6412320933. Rúa das Galeras 44,
  15705, Tel. +34 981 072 903 — rund 385 m von der Praza do Obradoiro.
  Check-in ab 15:00, Check-out bis 11:00 (der Bus zum Flughafen geht 9:45).
  Nicht stornierbar, keine Änderungen.
- **Gebucht:** Vigo (E7), Alda Estación Vigo, 25.–26.09., 57,00 €
  (inkl. 5,18 € MwSt). Calle Alfonso XIII 19, 36201 — 200 m vom Bahnhof
  Vigo-Urzáiz, im Geschäftsviertel nahe der Fußgängerstraße Príncipe; vom Hafen,
  wo der Küstenweg ankommt, rund 20 Minuten bergauf, dafür schon in
  Laufrichtung für E8. **Vorlage war die Buchungsübersicht, nicht die
  Bestätigung** („Letzter Schritt", keine Buchungsnummer) — Bestätigungsmail
  prüfen und die Nummer nachtragen.
- **Offen:** 5 Etappenorte — E6 Baiona, E8 Arcade, E9 Pontevedra,
  E10 Caldas de Reis, E11 Padrón. Booking-Links im HTML, nach Preis sortiert.
  **Oia und Santiago, die beiden kritischen, sind durch.**
- Budget-Ausrichtung: Pension/Hostal statt Hotel, ca. 50–110 €/Nacht
- **Nur Unterkünfte mit eigenem Zimmer** — kein Bett im Schlafsaal. Steht in
  jedem Budget-Ziel. Die Booking-Links können das nicht erzwingen: den Filter
  dafür gibt es auf booking.com nur in der Oberfläche (*Bettenart →
  Privatzimmer*), der URL-Parameter ist nicht öffentlich dokumentiert, und ein
  geratener Parameter könnte still auf null Ergebnisse filtern. Der Hinweis
  steht deshalb über den Etappen.
- **Albergue ≠ Hostal.** Ein *Albergue* ist die Pilgerherberge mit Schlafsaal,
  ein *Hostal* ein kleines günstiges Hotel mit eigenem Zimmer. Die beiden
  Wörter sehen sich ähnlich und meinen das Gegenteil.

## Kosten (Orientierung)

Unterkünfte ~70 €/Nacht × 12 ≈ 840 € · Verpflegung ~25–35 €/Tag ·
Gesamtbudget realistisch **1.400–1.900 €**.
Bereits fix: Hinflug 258,00 € · Rückflug 235,00 € · Porto 186,83 € ·
Vila do Conde 78,00 € · Esposende 74,00 € · Viana do Castelo 65,00 € ·
Caminha 56,50 € · Oia 100,00 € · Vigo 57,00 € · Santiago 76,95 € ·
Credencial 2,00 €.
Gekauft: Wandersocken FALKE TK2 2× 57,26 € · Wandershorts maamgic 2× 55,98 € ·
Reiseapotheke & Hygiene 20,54 € (Compeed, Dr. Bronner's, Zahncreme, Deo) ·
Papiertüten 5,49 € (Tagesportionen Medikamente) · USB-C-Ladegerät 9,99 €.
Erfasst damit 1.338,54 €.

## Equipment-Kernpunkte

- Rucksack 30–40 L mit Hüft- und Brustgurt + Regenhülle, voll gepackt < 8 kg
- Trailrunner max. gedämpft (Hoka/Altra), ½ Nr. größer, eingelaufen
- Zweilagige Socken (Wrightsock), morgens Füße mit Hirschtalg fetten
- ISDIN Fotoprotector Fusion Gel Sport LSF 50
- Powerbank Nitecore NB10000 (150 g), Ohropax
- **Seidenschlafsack** (Inlett) aus Hygienegründen — kein Schlafsack im eigentlichen
  Sinn, sondern ein eigenes Innentuch für fremde Betten. Ursprünglich war gar keiner
  geplant, weil Hotels und Pensionen Bettwäsche stellen. Beim Gewicht unkritisch.
- Waschen alle 2–3 Tage
- Flug: Messer & Flüssiges > 100 ml ins Aufgabegepäck, Powerbank ins Handgepäck.
  Steckdosen PT/ES = EU Typ F, kein Adapter nötig.

Packliste im HTML in 12 Kategorien (Rucksack, Füße, Wanderkleidung, Abend, Regen,
Sonnenschutz, Hygiene, Reiseapotheke, Supplements, Elektronik, Dokumente, Kleinkram).

## Countdown –5 kg (93 → ~88 kg) — Abschnitt ausgeblendet

Der Abschnitt ist seit dem Start auf dem Camino aus der Seite genommen; die
Daten stehen unverändert in `weight_weeks`. Der Google-Health-Block ist dabei
ins Tagebuch umgezogen, weil die Schritte und Kilometer daraus auf den
Etappenkarten und beim Tagebuch-Ausbau gebraucht werden.


~500–700 kcal Defizit, Protein hoch, Kraft Mo/Mi/Fr weiter — Fett verlieren, nicht Muskeln.
Montags nüchtern wiegen, Ist-Wert im HTML eintragen.

| Woche | Zeitraum | Ziel | Schritte | Lange Wanderung | Fokus |
|---|---|---|---|---|---|
| Start | 09.08. | 93,0 | Baseline | — | Ausgangswert |
| W1 | 11.–17.08. | 91,5 | 8–9k | 10 km | Defizit sauber, Wasser fällt |
| W2 | 18.–24.08. | 90,5 | 9–10k | 12–14 km | Protein hoch, Kraft halten |
| W3 | 25.–31.08. | 89,6 | 10–12k | 16 km | Trailrunner einlaufen |
| W4 | 01.–07.09. | 88,8 | 12k | 18–20 km mit Pack | Rucksack-Setup, < 8 kg |
| W5 | 08.–14.09. | 88,0 | 12–15k | 20+ km Generalprobe | alles durchspielen |
| W6 | 15.–17.09. | ~88 halten | locker | 1× 8 km | Taper, Beine frisch |

## Stempel

In Portugal reicht **ein** Stempel pro Tag, ab der spanischen Grenze (E5,
Übergang über den Minho) sind es **zwei**. Zusammen 21 für die ganze Strecke.
Wer da schludert, bekommt in Santiago keine Compostela — deshalb hat jeder Tag
auf der Seite seine Kästchen, und ein abgehakter Tag mit fehlendem Stempel sagt
das deutlich.

## Tagebuch

Sprachnotiz oder getippt, dazu Fotos je Etappe. Alles landet zuerst in der
IndexedDB des Geräts und geht erst dann raus — auf dem Camino ist streckenweise
kein Netz, und ein Eintrag, der erst beim Hochladen entsteht, wäre dann weg.

Bilder gehören zum Eintrag, nicht nur zum Anlegen: mehrere auf einmal, in
mehreren Griffen nacheinander, und auch später noch am fertigen Eintrag.

Wo ein Bild entstanden ist, wird **auf dem Handy** aus dem Bild gelesen — vor
dem Verkleinern, weil die Leinwand die Metadaten sonst wegwirft. Fehlen sie,
fehlen sie; geraten wird nichts. Bilder von vor dem 24.09. haben keine.

Transkription (Whisper) und Ausbau (Claude) sind optional; ohne hinterlegte
Schlüssel bleibt die Aufnahme trotzdem erhalten und abspielbar.

Der Ausbau macht aus der Notiz einen fertigen Eintrag. Claude bekommt dazu den
Tag mitgeliefert, wie er wirklich war: Etappe und Zielort, Schritte, Strecke,
Kalorien und Puls aus dem Google-Health-Konto, Wetter, Höhenmeter und die Fotos
des Eintrags. Ergänzt werden darf nur, was in diesen Daten steht oder auf den
Bildern zu sehen ist — Gefühle, Begegnungen und Bewertungen ausschließlich aus
dem, was gesagt wurde.

**Das Original bleibt immer erhalten.** Die Aufnahme sowieso, und der Rohtext
in `text_raw` — der wird nie überschrieben. Jeder erneute Ausbau setzt wieder
darauf auf, nicht auf der ausgebauten Fassung.

## Offene Punkte

- **Passwort auf der Seite setzen** — beim nächsten Aufruf
- 5 Unterkünfte buchen: Baiona, Arcade, Pontevedra, Caldas de Reis, Padrón —
  E1 bis E5, E7 und E12 sind durch, damit auch die beiden kritischen
- **Vigo: Bestätigungsmail prüfen.** Der Screenshot war die Buchungsübersicht
  mit „Letzter Schritt", nicht die Bestätigung — und ohne Buchungsnummer.
- **Fährfahrplan Caminha → A Guarda/Spanien prüfen** — am Vorabend des 22.09.
  Der Check-out in Caminha beginnt erst um 8:30; eine frühe Fähre passt da
  nicht dazu.
- **Rückflug-Gepäck klären.** Im Tarif steckt nur ein persönliches Gepäckstück
  40 × 30 × 20 cm — da passt der Rucksack nicht hinein. Kabinengepäck
  (55 × 40 × 20 cm, 10 kg) oder Aufgabegepäck bei **Vueling und TUI fly
  einzeln** dazubuchen; am Gate kostet es 60–140 €. Dazu: Messer und
  Flüssiges > 100 ml brauchen auf dem Rückweg ohnehin Aufgabegepäck.
- Compostela-Urkunde am **30.09.** holen, nicht am Abreisetag — Pilgerbüro
  Rúa das Carretas 33, 9:00–19:00. Pilgermesse 12:00 ebenfalls am 30.09.;
  am 01.10. sitzt du zu der Zeit im Flieger.
- Tagebuch-Schlüssel hinterlegen, falls aus Sprachnotizen Text werden soll
