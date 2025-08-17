<?php
require_once 'config.php';

try {
    $pdo = getPDO();
    
    // Créer une direction de test
    $stmt = $pdo->prepare('INSERT INTO direction (nom, adresse, email_contact, telephone) VALUES (?, ?, ?, ?)');
    $stmt->execute(['Direction Test', '123 Rue de Test, 75001 Paris', 'contact@test.com', '0123456789']);
    $direction_id = $pdo->lastInsertId();
    
    // Créer un compte administrateur
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, email, mot_de_passe, role, direction_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['Administrateur', 'admin@datacenter.com', $admin_password, 'Admin', null]);
    
    // Créer un compte client (Direction)
    $client_password = password_hash('client123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO utilisateur (nom, email, mot_de_passe, role, direction_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute(['Client Test', 'client@test.com', $client_password, 'Direction', $direction_id]);
    
    echo "Comptes créés avec succès!\n";
    echo "Administrateur:\n";
    echo "  Email: admin@datacenter.com\n";
    echo "  Mot de passe: admin123\n";
    echo "  Rôle: Admin\n\n";
    echo "Client:\n";
    echo "  Email: client@test.com\n";
    echo "  Mot de passe: client123\n";
    echo "  Rôle: Direction\n";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
?> 