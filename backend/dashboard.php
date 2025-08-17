<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM dashboard');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM dashboard WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO dashboard (utilisateur_id, config_perso) VALUES (?, ?)');
    $stmt->execute([
        $data['utilisateur_id'],
        json_encode($data['config_perso'] ?? new stdClass())
    ]);
    return ['message' => 'Dashboard créé'];
}

function update($id, $data) {
    $pdo = getPDO();
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        if ($key === 'config_perso') {
            $value = json_encode($value);
        }
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE dashboard SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Dashboard mis à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM dashboard WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Dashboard supprimé'];
}
