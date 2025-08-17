<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM utilisateur');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, email, mot_de_passe, role, direction_id) VALUES (?, ?, ?, ?, ?)');
    $hashed = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
    $stmt->execute([
        $data['nom'],
        $data['email'],
        $hashed,
        $data['role'],
        $data['direction_id'] ?? null
    ]);
    return ['message' => 'Utilisateur créé'];
}

function update($id, $data) {
    $pdo = getPDO();
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        if ($key === 'mot_de_passe') {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE utilisateur SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Utilisateur mis à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM utilisateur WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Utilisateur supprimé'];
}
