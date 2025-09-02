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
    
    try {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare('SELECT id FROM utilisateur WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            return ['error' => 'Utilisateur non trouvé'];
        }
        
        // Désactiver temporairement la vérification des clés étrangères
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        
        // Supprimer l'utilisateur
        $stmt = $pdo->prepare('DELETE FROM utilisateur WHERE id = ?');
        $stmt->execute([$id]);
        
        // Réactiver la vérification des clés étrangères
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        // Vérifier si la suppression a réussi
        if ($stmt->rowCount() > 0) {
            error_log("Utilisateur supprimé avec succès (ID: $id)");
            return ['message' => 'Utilisateur supprimé avec succès'];
        } else {
            error_log("Échec de la suppression de l'utilisateur (ID: $id)");
            http_response_code(500);
            return ['error' => 'Échec de la suppression de l\'utilisateur'];
        }
    } catch (PDOException $e) {
        // En cas d'erreur, s'assurer que la vérification des clés étrangères est réactivée
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        error_log("Erreur lors de la suppression de l'utilisateur (ID: $id): " . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Erreur lors de la suppression de l\'utilisateur', 'details' => $e->getMessage()];
    }
}
