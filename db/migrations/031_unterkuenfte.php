<?php
declare(strict_types=1);

/**
 * Wo geschlafen wurde — als Tabelle.
 *
 * Bisher steht das alles in `stages.note` und `stages.target`, als fertiger
 * HTML-Absatz. Für die Etappenkarte am Abend ist das genau richtig; für das
 * Journal ist es unbrauchbar, weil man an die einzelnen Angaben nicht
 * herankommt, ohne HTML auseinanderzunehmen.
 *
 * Die Daten selbst stehen in `db/unterkuenfte.php`, eingespielt werden sie nur
 * von hier. In `db/seed.php` stehen sie bewusst **nicht**: eine frische
 * Datenbank läuft ohnehin jede Migration durch, und die Tabelle entsteht auch
 * erst hier. Zwei Stellen mit denselben Zeilen wären nur eine Stelle mehr, die
 * veralten kann. Für Etappen und Kosten gilt die Doppelpflege weiter — die
 * stehen schon im Seed und werden von Migrationen nur nachgebessert.
 *
 * **Die Etappenkarte bleibt, wie sie ist.** Sie umzustellen, während er
 * unterwegs ist und jeden Abend darauf schaut, wo sein Bett steht, wäre der
 * falsche Moment. Bis dahin gilt: eine neue Buchung wird an beiden Stellen
 * eingetragen — so wie bisher schon Kosten und Etappe beide angefasst werden.
 * Der Hinweis steht in HANDOVER.md.
 */
function migration_031(Database $db): void
{
    $mysql = $db->driver() === 'mysql';
    $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $str   = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $kurz  = $mysql ? 'VARCHAR(64)'  : 'TEXT';
    $tag   = $mysql ? 'VARCHAR(10)'  : 'TEXT';
    $dec   = $mysql ? 'DECIMAL(10,2)' : 'REAL';
    $tail  = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS lodgings (
        id $pk,
        seq INT NOT NULL DEFAULT 0,
        stage_id INT NULL,
        name $str NOT NULL,
        art $kurz NULL,
        zimmer $str NULL,
        strasse $str NULL,
        plz $kurz NULL,
        ort $str NULL,
        land $kurz NULL,
        telefon $kurz NULL,
        date_from $tag NULL,
        date_to $tag NULL,
        naechte INT NULL,
        preis $dec NULL,
        buchungsnr $kurz NULL,
        checkin $kurz NULL,
        checkout $kurz NULL,
        lage TEXT NULL,
        hinweis TEXT NULL,
        quelle $kurz NOT NULL DEFAULT 'bestaetigung'
    )$tail");

    require_once APP_ROOT . '/db/unterkuenfte.php';
    unterkuenfte_einspielen($db, unterkuenfte());
}

/**
 * Einspielen, ohne etwas zu überschreiben, das schon da ist.
 *
 * Erkannt wird eine Unterkunft am Namen zusammen mit dem Anreisedatum — die
 * Buchungsnummer taugt nicht dazu, weil nicht jede Buchung eine hat.
 */
function unterkuenfte_einspielen(Database $db, array $liste): void
{
    $db->transaction(function (Database $db) use ($liste): void {
        foreach ($liste as $u) {
            $stage = $db->one('SELECT id FROM stages WHERE seq = ?', [$u['stage_seq']]);
            $da = $db->one(
                'SELECT id FROM lodgings WHERE name = ? AND date_from = ?',
                [$u['name'], $u['von']]
            );
            if ($da !== null) {
                continue;
            }
            $db->run(
                'INSERT INTO lodgings
                 (seq, stage_id, name, art, zimmer, strasse, plz, ort, land, telefon,
                  date_from, date_to, naechte, preis, buchungsnr, checkin, checkout,
                  lage, hinweis, quelle)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $u['seq'], $stage ? (int) $stage['id'] : null,
                    $u['name'], $u['art'], $u['zimmer'],
                    $u['strasse'], $u['plz'], $u['ort'], $u['land'], $u['telefon'],
                    $u['von'], $u['bis'], $u['naechte'], $u['preis'], $u['buchungsnr'],
                    $u['checkin'], $u['checkout'], $u['lage'], $u['hinweis'], $u['quelle'],
                ]
            );
        }
    });
}
