<?php
require_once 'config.php';

function login($data) {
    $pdo = getPDO();
    if (!isset($data['email']) || !isset($data['mot_de_passe'])) {
        http_response_code(400);
        return ['error' => 'Email et mot de passe requis'];
    }
    $stmt = $pdo->prepare('SELECT * FROM utilisateur WHERE email = ?');
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();
    if ($user && password_verify($data['mot_de_passe'], $user['mot_de_passe'])) {
        $token = base64_encode(random_bytes(32));
        return ['token' => $token, 'user' => $user];
    }
    http_response_code(401);
    return ['error' => 'Identifiants invalides'];
}

function register($data) {
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
