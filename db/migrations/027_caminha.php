<?php
declare(strict_types=1);

/**
 * Caminha gebucht — die vierte Etappenunterkunft.
 *
 * Residencial Galo d'Ouro, 22.–23.09.2026, Einzelzimmer, 56,50 € gesamt
 * (51,89 € Zimmer + 3,11 € MwSt + 1,50 € Übernachtungssteuer). Rua da
 * Corredoura 15, 4910-133. Damit sind noch acht Etappenorte offen.
 *
 * Der Name stand wieder nicht in der Bestätigung — Adresse und Telefonnummer
 * führen eindeutig dorthin, die Gemeinde Caminha führt das Haus selbst unter
 * „onde dormir".
 *
 * Drei Dinge daran sind wichtiger als der Preis:
 *
 *  1. **Check-in nur 15:30 bis 18:30.** Ein Fenster von drei Stunden, nach
 *     einer 26-km-Etappe. Wer um acht in Viana losgeht, passt hinein; wer
 *     trödelt oder sehr schnell ist, nicht. Das gehört auf die Etappenkarte
 *     und nicht in eine Mail, die unterwegs niemand aufmacht.
 *  2. **Check-out 08:30 bis 11:30.** Am nächsten Morgen geht die Fähre über
 *     den Minho nach Spanien. Vor halb neun kommt man hier nicht weg — das
 *     muss zum Fährplan passen, der ohnehin noch zu prüfen ist.
 *  3. **Nicht stornierbar, keine Änderungen**, und eine Vorauszahlung des
 *     Gesamtpreises kann jederzeit fällig werden.
 *
 * Die Lage ist dafür die beste bisher: 76 m vom Ortszentrum, eine Minute von
 * der Torre do Relógio, an der Hauptachse der Praça Central neben der Casa
 * dos Pitas. Kein Umweg wie in Viana.
 */
function migration_027(Database $db): void
{
    $db->transaction(function (Database $db): void {

        /* ---- Kosten -------------------------------------------------- */
        // Beschriftung und Stand immer, den Betrag nur, solange nichts drin
        // steht — eine auf der Seite eingetippte Zahl gehoert Sascha.
        $db->run(
            'UPDATE cost_items SET detail = ?, status = ?, status_label = ?, updated_at = ? WHERE name = ?',
            [
                'Residencial Galo d\'Ouro · Einzelzimmer · inkl. 3,11 € MwSt und 1,50 € Übernachtungssteuer',
                'ok', 'gebucht', date('c'), 'Caminha (E4)',
            ]
        );
        $db->run(
            'UPDATE cost_items SET amount = ? WHERE name = ? AND amount IS NULL',
            [56.50, 'Caminha (E4)']
        );

        /* ---- Etappe 4 ------------------------------------------------ */
        $db->run(
            'UPDATE stages SET target = ?, note = ?, booking_url = NULL, booking_label = NULL WHERE seq = 4',
            [
                '<b style="color:#2e7d32">Gebucht:</b> Residencial Galo d\'Ouro · Einzelzimmer · '
                . '<b>56,50 €</b> (51,89 Zimmer + 3,11 MwSt + 1,50 Übernachtungssteuer)',

                '<b>Check-in nur 15:30–18:30</b> — drei Stunden Fenster nach 26 km. Wer gegen 8 Uhr '
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
            ]
        );
    });
}
