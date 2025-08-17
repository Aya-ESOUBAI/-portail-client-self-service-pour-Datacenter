<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM pdu');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM pdu WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getByRack($rackId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM pdu WHERE rak_id = ?');
    $stmt->execute([$rackId]);
    return $stmt->fetchAll();
}

function getConsumptionByUserDirection($userId) {
    $pdo = getPDO();
    
    // Récupérer les informations de l'utilisateur
    $stmt = $pdo->prepare('SELECT role, direction_id FROM utilisateur WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [];
    }
    
    // Si c'est un admin, retourner toutes les données
    if ($user['role'] === 'Admin') {
        $stmt = $pdo->query('
            SELECT 
                r.nom as rack_nom,
                r.localisation,
                d.nom as direction_nom,
                p.id as pdu_id,
                p.marque,
                p.modele,
                p.nombre_prises,
                p.type_connexion,
                ce.prise_id,
                ce.valeur,
                ce.unite,
                ce.date_mesure
            FROM pdu p
            JOIN rak r ON p.rak_id = r.id
            LEFT JOIN direction d ON r.client_id = d.id
            LEFT JOIN capteurelectrique ce ON p.id = ce.pdu_id
            ORDER BY d.nom, r.nom, p.id, ce.prise_id
        ');
        return $stmt->fetchAll();
    }
    
    // Si c'est une direction, retourner seulement les données de sa direction
    if ($user['role'] === 'Direction' && $user['direction_id']) {
        $stmt = $pdo->prepare('
            SELECT 
                r.nom as rack_nom,
                r.localisation,
                d.nom as direction_nom,
                p.id as pdu_id,
                p.marque,
                p.modele,
                p.nombre_prises,
                p.type_connexion,
                ce.prise_id,
                ce.valeur,
                ce.unite,
                ce.date_mesure
            FROM pdu p
            JOIN rak r ON p.rak_id = r.id
            LEFT JOIN direction d ON r.client_id = d.id
            LEFT JOIN capteurelectrique ce ON p.id = ce.pdu_id
            WHERE r.client_id = ?
            ORDER BY r.nom, p.id, ce.prise_id
        ');
        $stmt->execute([$user['direction_id']]);
        return $stmt->fetchAll();
    }
    
    return [];
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO pdu (marque, modele, nombre_prises, type_connexion, rak_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['marque'],
        $data['modele'],
        $data['nombre_prises'],
        $data['type_connexion'],
        $data['rak_id']
    ]);
    return ['message' => 'PDU créé'];
}

function update($id, $data) {
    $pdo = getPDO();
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE pdu SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'PDU mis à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM pdu WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'PDU supprimé'];
}
