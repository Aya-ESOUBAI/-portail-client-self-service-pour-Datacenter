<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM armoiredeclime');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM armoiredeclime WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getByRack($rackId) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM armoiredeclime WHERE rak_id = ?');
    $stmt->execute([$rackId]);
    return $stmt->fetchAll();
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO armoiredeclime (marque, modele, rak_id) VALUES (?, ?, ?)');
    $stmt->execute([
        $data['marque'],
        $data['modele'],
        $data['rak_id']
    ]);
    return ['message' => 'Armoire de climatisation créée', 'id' => $pdo->lastInsertId()];
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
    $sql = 'UPDATE armoiredeclime SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Armoire de climatisation mise à jour'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM armoiredeclime WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Armoire de climatisation supprimée'];
} 