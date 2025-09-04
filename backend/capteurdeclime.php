<?php
require_once 'config.php';

function indexClimate() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM capteurdeclime');
    return $stmt->fetchAll();
}

function showClimate($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM capteurdeclime WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getByRack($rackId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('
        SELECT 
            cc.*,
            ac.marque as armoire_marque,
            ac.modele as armoire_modele
        FROM capteurdeclime cc
        JOIN armoiredeclime ac ON cc.armoire_id = ac.id
        WHERE ac.rak_id = ?
        ORDER BY cc.date_mesure DESC
    ');
    $stmt->execute([$rackId]);
    return $stmt->fetchAll();
}

function getTemperatureByUserDirection($userId) {
    $pdo = getPDO();
    
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare('SELECT role, direction_id FROM utilisateur WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [];
    }
    
    if ($user['role'] === 'Admin') {
        $stmt = $pdo->query('
            SELECT 
                r.nom as rack_nom,
                r.localisation,
                d.nom as direction_nom,
                cc.id as capteur_id,
                cc.type,
                cc.valeur,
                cc.unite,
                cc.date_mesure,
                ac.marque as armoire_marque,
                ac.modele as armoire_modele
            FROM capteurdeclime cc
            JOIN armoiredeclime ac ON cc.armoire_id = ac.id
            JOIN rak r ON ac.rak_id = r.id
            LEFT JOIN direction d ON r.client_id = d.id
            ORDER BY d.nom, r.nom, cc.type, cc.date_mesure DESC
        ');
        return $stmt->fetchAll();
    }
    
    if ($user['role'] === 'Direction' && $user['direction_id']) {
        $stmt = $pdo->prepare('
            SELECT 
                r.nom as rack_nom,
                r.localisation,
                d.nom as direction_nom,
                cc.id as capteur_id,
                cc.type,
                cc.valeur,
                cc.unite,
                cc.date_mesure,
                ac.marque as armoire_marque,
                ac.modele as armoire_modele
            FROM capteurdeclime cc
            JOIN armoiredeclime ac ON cc.armoire_id = ac.id
            JOIN rak r ON ac.rak_id = r.id
            LEFT JOIN direction d ON r.client_id = d.id
            WHERE r.client_id = ?
            ORDER BY r.nom, cc.type, cc.date_mesure DESC
        ');
        $stmt->execute([$user['direction_id']]);
        return $stmt->fetchAll();
    }
    
    return [];
}

function getTemperatureStatsByRack() {
    $pdo = getPDO();
    
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $rackId = $_GET['rack_id'] ?? null;
    
    $whereConditions = [];
    $params = [];
    
    if ($rackId) {
        $whereConditions[] = 'r.id = ?';
        $params[] = $rackId;
    }
    
    if ($startDate && $endDate) {
        $whereConditions[] = 'c.date_mesure BETWEEN ? AND ?';
        $params[] = $startDate . ' 00:00:00';
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    $sql = "
        SELECT 
            c.date_mesure,
            r.id as rack_id,
            r.nom as rack_nom,
            r.localisation as rack_localisation,
            d.nom as direction_nom,
            MAX(CASE WHEN c.type = 'temperature_entrant' THEN c.valeur END) as air_entrant,
            MAX(CASE WHEN c.type = 'temperature_sortant' THEN c.valeur END) as air_sortant
        FROM capteurdeclime c
        JOIN armoiredeclime ac ON c.armoire_id = ac.id
        JOIN rak r ON ac.rak_id = r.id
        LEFT JOIN direction d ON r.client_id = d.id
        $whereClause
        GROUP BY c.date_mesure, r.id, r.nom, r.localisation, d.nom
        ORDER BY c.date_mesure ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function storeClimate($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO capteurdeclime (type, valeur, unite, armoire_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $data['type'],
        $data['valeur'],
        $data['unite'],
        $data['armoire_id']
    ]);
    return ['message' => 'Capteur de climatisation créé'];
}

function updateClimate($id, $data) {
    $pdo = getPDO();
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE capteurdeclime SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Capteur de climatisation mis à jour'];
}

function destroyClimate($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM capteurdeclime WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Capteur de climatisation supprimé'];
}
