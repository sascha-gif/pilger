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
- Rückflug **gebucht**: 01.10.2026 SCQ→FRA, Zeiten lt. Buchung, nachmittags Bus zum Flughafen
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
| E5 | 23.09. | Caminha → Oia (Fähre über den Minho, Grenze ESP, ab hier 2 Stempel/Tag) | 23 | 148 |
| E6 | 24.09. | Oia → Baiona | 18 | 130 |
| E7 | 25.09. | Baiona → Vigo (längste Etappe) | 27 | 103 |
| E8 | 26.09. | Vigo → Arcade (Austern) | 22 | 81 |
| E9 | 27.09. | Arcade → Pontevedra (kürzeste Etappe) | 15 | 66 |
| E10 | 28.09. | Pontevedra → Caldas de Reis (Thermalort) | 22 | 44 |
| E11 | 29.09. | Caldas de Reis → Padrón | 19 | 25 |
| E12 | 30.09. | Padrón → Santiago (Praza do Obradoiro) | 25 | 0 |

Senda-Litoral-Variante möglich bis Viana do Castelo (Küste, flach, gelenkschonend);
danach läuft sie mit der Costa zusammen.

## Buchungsstand

- **Gebucht:** Porto, Carpe Diem Porto by Dualgroup, Deluxe Doppelzimmer,
  186,83 € gesamt (2 Nächte), zentral bei São Bento, Buchungsnr. 5888925188
- **Offen:** alle 12 Etappenorte. Booking-Links im HTML, nach Preis sortiert.
  Kritisch früh buchen: **Oia** (dünnes Angebot) und **Santiago** (hohe Nachfrage).
- Budget-Ausrichtung: Pension/Hostal statt Hotel, ca. 50–110 €/Nacht

## Kosten (Orientierung)

Unterkünfte ~70 €/Nacht × 12 ≈ 840 € · Verpflegung ~25–35 €/Tag ·
Gesamtbudget realistisch **1.400–1.900 €**.
Bereits fix: Hinflug 258,00 € · Porto 186,83 € · Credencial 2,00 €.

## Equipment-Kernpunkte

- Rucksack 30–40 L mit Hüft- und Brustgurt + Regenhülle, voll gepackt < 8 kg
- Trailrunner max. gedämpft (Hoka/Altra), ½ Nr. größer, eingelaufen
- Zweilagige Socken (Wrightsock), morgens Füße mit Hirschtalg fetten
- ISDIN Fotoprotector Fusion Gel Sport LSF 50
- Powerbank Nitecore NB10000 (150 g), Ohropax
- Schlafsack steht auf der Packliste (Stand 19.08.). Ursprünglich war keiner geplant —
  Hotels und Pensionen haben Bettwäsche. Art und Gewicht sind offen; bei Zielgewicht
  unter 8 kg ist der Unterschied Hüttenschlafsack/Vollschlafsack erheblich.
- Waschen alle 2–3 Tage
- Flug: Messer & Flüssiges > 100 ml ins Aufgabegepäck, Powerbank ins Handgepäck.
  Steckdosen PT/ES = EU Typ F, kein Adapter nötig.

Packliste im HTML in 12 Kategorien (Rucksack, Füße, Wanderkleidung, Abend, Regen,
Sonnenschutz, Hygiene, Reiseapotheke, Supplements, Elektronik, Dokumente, Kleinkram).

## Countdown –5 kg (93 → ~88 kg)

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

Transkription (Whisper) und Glättung (Claude) sind optional; ohne hinterlegte
Schlüssel bleibt die Aufnahme trotzdem erhalten und abspielbar. Beim Glätten
darf gekürzt und aufgeräumt, aber nichts dazuerfunden werden.

## Offene Punkte

- **Passwort auf der Seite setzen** — beim nächsten Aufruf
- 12 Unterkünfte buchen (Oia und Santiago zuerst)
- Fährfahrplan Caminha → A Guarda/Spanien prüfen
- Rückflugzeiten SCQ→FRA aus der Buchung nachtragen (Kostenfeld noch leer)
- Compostela-Urkunde: Pilgerbüro Rúa de Carretas 33, Pilgermesse 12:00
- Tagebuch-Schlüssel hinterlegen, falls aus Sprachnotizen Text werden soll
