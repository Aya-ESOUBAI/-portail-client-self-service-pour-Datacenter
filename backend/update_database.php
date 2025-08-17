<?php
require_once 'config.php';

echo "=== MISE À JOUR DE LA BASE DE DONNÉES ===\n\n";

try {
    $pdo = getPDO();
    echo "✅ Connexion à la base de données réussie\n\n";
    
    // Vérifier si les données d'air entrant/sortant existent déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM capteurdeclime WHERE type IN ('temperature_entrant', 'temperature_sortant')");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "⚠️  Les données d'air entrant/sortant existent déjà. Suppression des anciennes données...\n";
        $stmt = $pdo->prepare("DELETE FROM capteurdeclime WHERE type IN ('temperature_entrant', 'temperature_sortant')");
        $stmt->execute();
        echo "✅ Anciennes données supprimées\n\n";
    }
    
    // Insérer les nouvelles données d'air entrant/sortant pour RACK-A1
    echo "📊 Insertion des données d'air entrant/sortant pour RACK-A1...\n";
    
    // Température air entrant RACK-A1
    $tempEntrantRack1 = [
        ['temperature_entrant', 20.5, '°C', '2024-01-15 00:00:00', 1],
        ['temperature_entrant', 20.8, '°C', '2024-01-15 01:00:00', 1],
        ['temperature_entrant', 21.1, '°C', '2024-01-15 02:00:00', 1],
        ['temperature_entrant', 21.4, '°C', '2024-01-15 03:00:00', 1],
        ['temperature_entrant', 21.7, '°C', '2024-01-15 04:00:00', 1],
        ['temperature_entrant', 22.0, '°C', '2024-01-15 05:00:00', 1],
        ['temperature_entrant', 22.3, '°C', '2024-01-15 06:00:00', 1],
        ['temperature_entrant', 22.6, '°C', '2024-01-15 07:00:00', 1],
        ['temperature_entrant', 22.9, '°C', '2024-01-15 08:00:00', 1],
        ['temperature_entrant', 23.2, '°C', '2024-01-15 09:00:00', 1],
        ['temperature_entrant', 23.5, '°C', '2024-01-15 10:00:00', 1],
        ['temperature_entrant', 23.8, '°C', '2024-01-15 11:00:00', 1],
        ['temperature_entrant', 24.1, '°C', '2024-01-15 12:00:00', 1],
        ['temperature_entrant', 24.4, '°C', '2024-01-15 13:00:00', 1],
        ['temperature_entrant', 24.7, '°C', '2024-01-15 14:00:00', 1],
        ['temperature_entrant', 25.0, '°C', '2024-01-15 15:00:00', 1],
        ['temperature_entrant', 24.7, '°C', '2024-01-15 16:00:00', 1],
        ['temperature_entrant', 24.4, '°C', '2024-01-15 17:00:00', 1],
        ['temperature_entrant', 24.1, '°C', '2024-01-15 18:00:00', 1],
        ['temperature_entrant', 23.8, '°C', '2024-01-15 19:00:00', 1],
        ['temperature_entrant', 23.5, '°C', '2024-01-15 20:00:00', 1],
        ['temperature_entrant', 23.2, '°C', '2024-01-15 21:00:00', 1],
        ['temperature_entrant', 22.9, '°C', '2024-01-15 22:00:00', 1],
        ['temperature_entrant', 22.6, '°C', '2024-01-15 23:00:00', 1]
    ];
    
    // Température air sortant RACK-A1
    $tempSortantRack1 = [
        ['temperature_sortant', 25.5, '°C', '2024-01-15 00:00:00', 1],
        ['temperature_sortant', 25.8, '°C', '2024-01-15 01:00:00', 1],
        ['temperature_sortant', 26.1, '°C', '2024-01-15 02:00:00', 1],
        ['temperature_sortant', 26.4, '°C', '2024-01-15 03:00:00', 1],
        ['temperature_sortant', 26.7, '°C', '2024-01-15 04:00:00', 1],
        ['temperature_sortant', 27.0, '°C', '2024-01-15 05:00:00', 1],
        ['temperature_sortant', 27.3, '°C', '2024-01-15 06:00:00', 1],
        ['temperature_sortant', 27.6, '°C', '2024-01-15 07:00:00', 1],
        ['temperature_sortant', 27.9, '°C', '2024-01-15 08:00:00', 1],
        ['temperature_sortant', 28.2, '°C', '2024-01-15 09:00:00', 1],
        ['temperature_sortant', 28.5, '°C', '2024-01-15 10:00:00', 1],
        ['temperature_sortant', 28.8, '°C', '2024-01-15 11:00:00', 1],
        ['temperature_sortant', 29.1, '°C', '2024-01-15 12:00:00', 1],
        ['temperature_sortant', 29.4, '°C', '2024-01-15 13:00:00', 1],
        ['temperature_sortant', 29.7, '°C', '2024-01-15 14:00:00', 1],
        ['temperature_sortant', 30.0, '°C', '2024-01-15 15:00:00', 1],
        ['temperature_sortant', 29.7, '°C', '2024-01-15 16:00:00', 1],
        ['temperature_sortant', 29.4, '°C', '2024-01-15 17:00:00', 1],
        ['temperature_sortant', 29.1, '°C', '2024-01-15 18:00:00', 1],
        ['temperature_sortant', 28.8, '°C', '2024-01-15 19:00:00', 1],
        ['temperature_sortant', 28.5, '°C', '2024-01-15 20:00:00', 1],
        ['temperature_sortant', 28.2, '°C', '2024-01-15 21:00:00', 1],
        ['temperature_sortant', 27.9, '°C', '2024-01-15 22:00:00', 1],
        ['temperature_sortant', 27.6, '°C', '2024-01-15 23:00:00', 1]
    ];
    
    // Insérer les données
    $stmt = $pdo->prepare("INSERT INTO capteurdeclime (type, valeur, unite, date_mesure, armoire_id) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($tempEntrantRack1 as $data) {
        $stmt->execute($data);
    }
    echo "✅ 24 enregistrements d'air entrant RACK-A1 insérés\n";
    
    foreach ($tempSortantRack1 as $data) {
        $stmt->execute($data);
    }
    echo "✅ 24 enregistrements d'air sortant RACK-A1 insérés\n";
    
    // Insérer les données pour RACK-A2
    echo "\n📊 Insertion des données d'air entrant/sortant pour RACK-A2...\n";
    
    // Température air entrant RACK-A2
    $tempEntrantRack2 = [
        ['temperature_entrant', 19.5, '°C', '2024-01-15 00:00:00', 2],
        ['temperature_entrant', 19.8, '°C', '2024-01-15 01:00:00', 2],
        ['temperature_entrant', 20.1, '°C', '2024-01-15 02:00:00', 2],
        ['temperature_entrant', 20.4, '°C', '2024-01-15 03:00:00', 2],
        ['temperature_entrant', 20.7, '°C', '2024-01-15 04:00:00', 2],
        ['temperature_entrant', 21.0, '°C', '2024-01-15 05:00:00', 2],
        ['temperature_entrant', 21.3, '°C', '2024-01-15 06:00:00', 2],
        ['temperature_entrant', 21.6, '°C', '2024-01-15 07:00:00', 2],
        ['temperature_entrant', 21.9, '°C', '2024-01-15 08:00:00', 2],
        ['temperature_entrant', 22.2, '°C', '2024-01-15 09:00:00', 2],
        ['temperature_entrant', 22.5, '°C', '2024-01-15 10:00:00', 2],
        ['temperature_entrant', 22.8, '°C', '2024-01-15 11:00:00', 2],
        ['temperature_entrant', 23.1, '°C', '2024-01-15 12:00:00', 2],
        ['temperature_entrant', 23.4, '°C', '2024-01-15 13:00:00', 2],
        ['temperature_entrant', 23.7, '°C', '2024-01-15 14:00:00', 2],
        ['temperature_entrant', 24.0, '°C', '2024-01-15 15:00:00', 2],
        ['temperature_entrant', 23.7, '°C', '2024-01-15 16:00:00', 2],
        ['temperature_entrant', 23.4, '°C', '2024-01-15 17:00:00', 2],
        ['temperature_entrant', 23.1, '°C', '2024-01-15 18:00:00', 2],
        ['temperature_entrant', 22.8, '°C', '2024-01-15 19:00:00', 2],
        ['temperature_entrant', 22.5, '°C', '2024-01-15 20:00:00', 2],
        ['temperature_entrant', 22.2, '°C', '2024-01-15 21:00:00', 2],
        ['temperature_entrant', 21.9, '°C', '2024-01-15 22:00:00', 2],
        ['temperature_entrant', 21.6, '°C', '2024-01-15 23:00:00', 2]
    ];
    
    // Température air sortant RACK-A2
    $tempSortantRack2 = [
        ['temperature_sortant', 24.5, '°C', '2024-01-15 00:00:00', 2],
        ['temperature_sortant', 24.8, '°C', '2024-01-15 01:00:00', 2],
        ['temperature_sortant', 25.1, '°C', '2024-01-15 02:00:00', 2],
        ['temperature_sortant', 25.4, '°C', '2024-01-15 03:00:00', 2],
        ['temperature_sortant', 25.7, '°C', '2024-01-15 04:00:00', 2],
        ['temperature_sortant', 26.0, '°C', '2024-01-15 05:00:00', 2],
        ['temperature_sortant', 26.3, '°C', '2024-01-15 06:00:00', 2],
        ['temperature_sortant', 26.6, '°C', '2024-01-15 07:00:00', 2],
        ['temperature_sortant', 26.9, '°C', '2024-01-15 08:00:00', 2],
        ['temperature_sortant', 27.2, '°C', '2024-01-15 09:00:00', 2],
        ['temperature_sortant', 27.5, '°C', '2024-01-15 10:00:00', 2],
        ['temperature_sortant', 27.8, '°C', '2024-01-15 11:00:00', 2],
        ['temperature_sortant', 28.1, '°C', '2024-01-15 12:00:00', 2],
        ['temperature_sortant', 28.4, '°C', '2024-01-15 13:00:00', 2],
        ['temperature_sortant', 28.7, '°C', '2024-01-15 14:00:00', 2],
        ['temperature_sortant', 29.0, '°C', '2024-01-15 15:00:00', 2],
        ['temperature_sortant', 28.7, '°C', '2024-01-15 16:00:00', 2],
        ['temperature_sortant', 28.4, '°C', '2024-01-15 17:00:00', 2],
        ['temperature_sortant', 28.1, '°C', '2024-01-15 18:00:00', 2],
        ['temperature_sortant', 27.8, '°C', '2024-01-15 19:00:00', 2],
        ['temperature_sortant', 27.5, '°C', '2024-01-15 20:00:00', 2],
        ['temperature_sortant', 27.2, '°C', '2024-01-15 21:00:00', 2],
        ['temperature_sortant', 26.9, '°C', '2024-01-15 22:00:00', 2],
        ['temperature_sortant', 26.6, '°C', '2024-01-15 23:00:00', 2]
    ];
    
    foreach ($tempEntrantRack2 as $data) {
        $stmt->execute($data);
    }
    echo "✅ 24 enregistrements d'air entrant RACK-A2 insérés\n";
    
    foreach ($tempSortantRack2 as $data) {
        $stmt->execute($data);
    }
    echo "✅ 24 enregistrements d'air sortant RACK-A2 insérés\n";
    
    // Vérifier le nombre total d'enregistrements
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM capteurdeclime");
    $result = $stmt->fetch();
    echo "\n📊 Total d'enregistrements dans capteurdeclime: " . $result['total'] . "\n";
    
    // Vérifier les types de capteurs disponibles
    $stmt = $pdo->query("SELECT type, COUNT(*) as count FROM capteurdeclime GROUP BY type");
    $types = $stmt->fetchAll();
    
    echo "\n📋 Types de capteurs disponibles:\n";
    foreach ($types as $type) {
        echo "  - {$type['type']}: {$type['count']} enregistrements\n";
    }
    
    echo "\n✅ Mise à jour terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?> 