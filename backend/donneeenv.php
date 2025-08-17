<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM donneeenv');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM donneeenv WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO donneeenv (type, valeur, unite, date_heure, source_capteur_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['type'],
        $data['valeur'],
        $data['unite'],
        $data['date_heure'] ?? date('Y-m-d H:i:s'),
        $data['source_capteur_id']
    ]);
    return ['message' => 'Donnée environnementale créée'];
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
    $sql = 'UPDATE donneeenv SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Donnée environnementale mise à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM donneeenv WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Donnée environnementale supprimée'];
}
