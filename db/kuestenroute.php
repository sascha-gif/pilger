<?php
declare(strict_types=1);

/**
 * Die tatsächlich gelaufene Route: an der Küste, nicht auf dem Binnenweg.
 *
 * Von Porto bis Caminha die Senda Litoral am Atlantik, über den Minho nach
 * A Guarda, dann die galicische Küste über Oia, Baiona und Vigo. Ab Pontevedra
 * dreht der Weg landeinwärts nach Santiago — dort gibt es keine Küste mehr.
 *
 * Es sind Stützpunkte entlang des Küstenverlaufs, keine GPS-Aufzeichnung des
 * markierten Weges. Für die Übersichtskarte reicht das; wer navigieren will,
 * nimmt eine Wander-App.
 *
 * @return array<int,array{0:float,1:float}>
 */
function kuesten_route_punkte(): array
{
    return [
        [41.1429, -8.6111],  // Porto, Sé
        [41.1500, -8.6800],  // Foz do Douro
        [41.1830, -8.7000],  // Matosinhos
        [41.2050, -8.7130],  // Leça da Palmeira
        [41.2600, -8.7280],  // Angeiras
        [41.2900, -8.7350],  // Vila Chã
        [41.3533, -8.7425],  // Vila do Conde
        [41.3833, -8.7667],  // Póvoa de Varzim
        [41.4200, -8.7800],  // Aguçadoura
        [41.4800, -8.7900],  // Apúlia
        [41.5333, -8.7833],  // Esposende
        [41.5600, -8.7900],  // Marinhas
        [41.6100, -8.8100],  // Castelo do Neiva
        [41.6600, -8.8300],  // Chafé
        [41.6944, -8.8329],  // Viana do Castelo
        [41.7300, -8.8700],  // Carreço
        [41.8150, -8.8700],  // Vila Praia de Âncora
        [41.8450, -8.8700],  // Moledo
        [41.8721, -8.8394],  // Caminha — Fähre über den Minho
        [41.9020, -8.8730],  // A Guarda (Spanien)
        [42.0006, -8.8756],  // Oia
        [42.0600, -8.8800],  // Mougás
        [42.1200, -8.8500],  // Baiona
        [42.1500, -8.8100],  // Panxón / Nigrán
        [42.2406, -8.7207],  // Vigo
        [42.2836, -8.6094],  // Redondela
        [42.3450, -8.6350],  // Arcade
        [42.4310, -8.6444],  // Pontevedra — ab hier landeinwärts
        [42.6050, -8.6417],  // Caldas de Reis
        [42.7369, -8.6600],  // Padrón
        [42.8805, -8.5456],  // Santiago de Compostela
    ];
}

/**
 * Etappenorte, deren Koordinaten korrigiert gehören.
 * Vila do Conde lag rund 6 km landeinwärts statt an der Mündung des Ave.
 *
 * @return array<string,array{0:float,1:float}>
 */
function stage_koordinaten_korrekturen(): array
{
    return [
        'Matosinhos → Vila do Conde'  => [41.3533, -8.7425],
        'Esposende → Viana do Castelo' => [41.6944, -8.8329],
        'Caminha → Oia'               => [42.0006, -8.8756],
        'Vigo → Arcade'               => [42.3450, -8.6350],
    ];
}
