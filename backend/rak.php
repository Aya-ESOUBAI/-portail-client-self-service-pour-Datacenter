<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM rak');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM rak WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getRacksWithDirections() {
    $pdo = getPDO();
    $stmt = $pdo->query('
        SELECT r.*, d.nom as direction_nom, d.adresse as direction_adresse, d.email_contact, d.telephone
        FROM rak r
        LEFT JOIN direction d ON r.client_id = d.id
        ORDER BY d.nom, r.nom
    ');
    return $stmt->fetchAll();
}

function getRacksByUserDirection($userId) {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare('SELECT role, direction_id FROM utilisateur WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return [];
    }
    
    if ($user['role'] === 'Admin') {
        $stmt = $pdo->query('
            SELECT r.*, d.nom as direction_nom, d.adresse as direction_adresse, d.email_contact, d.telephone
            FROM rak r
            LEFT JOIN direction d ON r.client_id = d.id
            ORDER BY d.nom, r.nom
        ');
        return $stmt->fetchAll();
    }
    
    if ($user['role'] === 'Direction' && $user['direction_id']) {
        $stmt = $pdo->prepare('
            SELECT r.*, d.nom as direction_nom, d.adresse as direction_adresse, d.email_contact, d.telephone
            FROM rak r
            LEFT JOIN direction d ON r.client_id = d.id
            WHERE r.client_id = ?
            ORDER BY r.nom
        ');
        $stmt->execute([$user['direction_id']]);
        return $stmt->fetchAll();
    }
    
    return [];
}

function getRacksWithCompleteData($userId = null) {
    $pdo = getPDO();
    
    $whereClause = '';
    $params = [];
    
    if ($userId) {
        $stmt = $pdo->prepare('SELECT role, direction_id FROM utilisateur WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['role'] === 'Direction' && $user['direction_id']) {
            $whereClause = 'WHERE r.client_id = ?';
            $params = [$user['direction_id']];
        }
    }
    
    $sql = "
        SELECT 
            r.id as rack_id,
            r.nom as rack_nom,
            r.localisation,
            d.nom as direction_nom,
            d.adresse as direction_adresse,
            d.email_contact,
            d.telephone,
            -- PDU gauche
            pdu_g.id as pdu_gauche_id,
            pdu_g.marque as pdu_gauche_marque,
            pdu_g.modele as pdu_gauche_modele,
            pdu_g.nombre_prises as pdu_gauche_prises,
            pdu_g.type_connexion as pdu_gauche_connexion,
            -- PDU droite
            pdu_d.id as pdu_droite_id,
            pdu_d.marque as pdu_droite_marque,
            pdu_d.modele as pdu_droite_modele,
            pdu_d.nombre_prises as pdu_droite_prises,
            pdu_d.type_connexion as pdu_droite_connexion,
            -- Capteurs électriques PDU gauche (bloc 1 et 2)
            ce_g_bloc1.valeur as intensite_gauche_bloc1,
            ce_g_bloc1.unite as unite_gauche_bloc1,
            ce_g_bloc1.date_mesure as date_gauche_bloc1,
            ce_g_bloc2.valeur as intensite_gauche_bloc2,
            ce_g_bloc2.unite as unite_gauche_bloc2,
            ce_g_bloc2.date_mesure as date_gauche_bloc2,
            -- Capteurs électriques PDU droite (bloc 1 et 2)
            ce_d_bloc1.valeur as intensite_droite_bloc1,
            ce_d_bloc1.unite as unite_droite_bloc1,
            ce_d_bloc1.date_mesure as date_droite_bloc1,
            ce_d_bloc2.valeur as intensite_droite_bloc2,
            ce_d_bloc2.unite as unite_droite_bloc2,
            ce_d_bloc2.date_mesure as date_droite_bloc2,
            -- Capteurs de température (air entrant et sortant)
            cc_temp_entrant.valeur as temperature_entrant,
            cc_temp_entrant.unite as unite_temp_entrant,
            cc_temp_entrant.date_mesure as date_temp_entrant,
            cc_temp_sortant.valeur as temperature_sortant,
            cc_temp_sortant.unite as unite_temp_sortant,
            cc_temp_sortant.date_mesure as date_temp_sortant
        FROM rak r
        LEFT JOIN direction d ON r.client_id = d.id
        -- PDU gauche (prise_id = 1)
        LEFT JOIN pdu pdu_g ON pdu_g.rak_id = r.id AND pdu_g.id = (
            SELECT MIN(p.id) FROM pdu p WHERE p.rak_id = r.id
        )
        -- PDU droite (prise_id = 2)
        LEFT JOIN pdu pdu_d ON pdu_d.rak_id = r.id AND pdu_d.id = (
            SELECT MAX(p.id) FROM pdu p WHERE p.rak_id = r.id
        )
        -- Capteurs électriques PDU gauche - Bloc 1 (prise_id = 1)
        LEFT JOIN capteurelectrique ce_g_bloc1 ON ce_g_bloc1.pdu_id = pdu_g.id 
            AND ce_g_bloc1.prise_id = 1
            AND ce_g_bloc1.date_mesure = (
                SELECT MAX(ce.date_mesure) 
                FROM capteurelectrique ce 
                WHERE ce.pdu_id = pdu_g.id AND ce.prise_id = 1
            )
        -- Capteurs électriques PDU gauche - Bloc 2 (prise_id = 2)
        LEFT JOIN capteurelectrique ce_g_bloc2 ON ce_g_bloc2.pdu_id = pdu_g.id 
            AND ce_g_bloc2.prise_id = 2
            AND ce_g_bloc2.date_mesure = (
                SELECT MAX(ce.date_mesure) 
                FROM capteurelectrique ce 
                WHERE ce.pdu_id = pdu_g.id AND ce.prise_id = 2
            )
        -- Capteurs électriques PDU droite - Bloc 1 (prise_id = 1)
        LEFT JOIN capteurelectrique ce_d_bloc1 ON ce_d_bloc1.pdu_id = pdu_d.id 
            AND ce_d_bloc1.prise_id = 1
            AND ce_d_bloc1.date_mesure = (
                SELECT MAX(ce.date_mesure) 
                FROM capteurelectrique ce 
                WHERE ce.pdu_id = pdu_d.id AND ce.prise_id = 1
            )
        -- Capteurs électriques PDU droite - Bloc 2 (prise_id = 2)
        LEFT JOIN capteurelectrique ce_d_bloc2 ON ce_d_bloc2.pdu_id = pdu_d.id 
            AND ce_d_bloc2.prise_id = 2
            AND ce_d_bloc2.date_mesure = (
                SELECT MAX(ce.date_mesure) 
                FROM capteurelectrique ce 
                WHERE ce.pdu_id = pdu_d.id AND ce.prise_id = 2
            )
        -- Capteurs de température air entrant
        LEFT JOIN armoiredeclime ac ON ac.rak_id = r.id
        LEFT JOIN capteurdeclime cc_temp_entrant ON cc_temp_entrant.armoire_id = ac.id 
            AND cc_temp_entrant.type = 'temperature_entrant'
            AND cc_temp_entrant.date_mesure = (
                SELECT MAX(cc.date_mesure) 
                FROM capteurdeclime cc 
                WHERE cc.armoire_id = ac.id AND cc.type = 'temperature_entrant'
            )
        -- Capteurs de température air sortant
        LEFT JOIN capteurdeclime cc_temp_sortant ON cc_temp_sortant.armoire_id = ac.id 
            AND cc_temp_sortant.type = 'temperature_sortant'
            AND cc_temp_sortant.date_mesure = (
                SELECT MAX(cc.date_mesure) 
                FROM capteurdeclime cc 
                WHERE cc.armoire_id = ac.id AND cc.type = 'temperature_sortant'
            )
        $whereClause
        ORDER BY d.nom, r.nom
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO rak (nom, localisation, client_id) VALUES (?, ?, ?)');
    $stmt->execute([
        $data['nom'],
        $data['localisation'],
        $data['client_id']
    ]);
    return ['message' => 'Rak créé'];
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
    $sql = 'UPDATE rak SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Rak mis à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM rak WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Rak supprimé'];
}
