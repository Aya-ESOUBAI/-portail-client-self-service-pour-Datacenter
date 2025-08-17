<?php
require_once 'config.php';

function indexElectric() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM capteurelectrique');
    return $stmt->fetchAll();
}

function showElectric($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM capteurelectrique WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getByRack($rackId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('
        SELECT 
            ce.*,
            p.marque as pdu_marque,
            p.modele as pdu_modele
        FROM capteurelectrique ce
        JOIN pdu p ON ce.pdu_id = p.id
        WHERE p.rak_id = ?
        ORDER BY ce.date_mesure DESC
    ');
    $stmt->execute([$rackId]);
    return $stmt->fetchAll();
}

function getEnergyStatsByRack() {
    $pdo = getPDO();
    
    // Récupérer les paramètres de filtrage
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $rackId = $_GET['rack_id'] ?? null;
    
    $whereConditions = ['ce.valeur IS NOT NULL'];
    $params = [];
    
    // Filtre par rack
    if ($rackId) {
        $whereConditions[] = 'r.id = ?';
        $params[] = $rackId;
    }
    
    // Filtre par date
    if ($startDate && $endDate) {
        $whereConditions[] = 'ce.date_mesure BETWEEN ? AND ?';
        $params[] = $startDate . ' 00:00:00';
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Calcul de l'intensité par block puis par rack (version compatible MySQL)
    $sql = "
        SELECT 
            r.id as rack_id,
            r.nom as rack_nom,
            r.localisation as rack_localisation,
            d.nom as direction_nom,
            DATE_FORMAT(ce.date_mesure, '%Y-%m-%d %H:00:00') as date_mesure,
            ROUND(AVG(ce.valeur), 2) as valeur,
            ROUND((AVG(ce.valeur) * 230) / 1000, 2) as puissance_kw,
            'A' as unite,
            COUNT(DISTINCT ce.prise_id) as nombre_prises,
            GROUP_CONCAT(
                CONCAT(
                    CASE 
                        WHEN ce.prise_id <= 4 THEN 'A'
                        WHEN ce.prise_id <= 8 THEN 'B'
                        WHEN ce.prise_id <= 12 THEN 'C'
                        WHEN ce.prise_id <= 16 THEN 'D'
                        ELSE 'Unknown'
                    END,
                    ':',
                    ROUND(ce.valeur, 2),
                    'A'
                )
                ORDER BY ce.prise_id
                SEPARATOR ' | '
            ) as block_details
        FROM capteurelectrique ce
        JOIN pdu p ON ce.pdu_id = p.id
        JOIN rak r ON p.rak_id = r.id
        LEFT JOIN direction d ON r.client_id = d.id
        $whereClause
        GROUP BY 
            r.id, r.nom, r.localisation, d.nom,
            DATE_FORMAT(ce.date_mesure, '%Y-%m-%d %H:00:00')
        HAVING COUNT(DISTINCT ce.prise_id) >= 1
        ORDER BY date_mesure ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function storeElectric($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO capteurelectrique (prise_id, valeur, unite, date_mesure, pdu_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['prise_id'],
        $data['valeur'],
        $data['unite'],
        $data['date_mesure'] ?? date('Y-m-d H:i:s'),
        $data['pdu_id']
    ]);
    return ['message' => 'Capteur électrique créé'];
}

function updateElectric($id, $data) {
    $pdo = getPDO();
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE capteurelectrique SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Capteur électrique mis à jour'];
}

function destroyElectric($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM capteurelectrique WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Capteur électrique supprimé'];
}
