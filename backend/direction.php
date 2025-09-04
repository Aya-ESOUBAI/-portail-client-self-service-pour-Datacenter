<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM direction');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM direction WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function store($data) {
    $pdo = getPDO();
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare('INSERT INTO direction (nom, adresse, email_contact, telephone) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $data['nom'],
            $data['adresse'],
            $data['email_contact'],
            $data['telephone']
        ]);
        
        $direction_id = $pdo->lastInsertId();
        
        $mot_de_passe = $data['mot_de_passe'] ?? 'Direction123!'; 
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, email, mot_de_passe, role, direction_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['nom'], 
            $data['email_contact'], 
            $mot_de_passe_hash,
            'Direction',
            $direction_id
        ]);
        
        $pdo->commit();
        
        return [
            'message' => 'Direction et compte utilisateur créés avec succès',
            'direction_id' => $direction_id,
            'mot_de_passe' => $mot_de_passe 
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
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
    $sql = 'UPDATE direction SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Direction mise à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM direction WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Direction supprimée'];
}
