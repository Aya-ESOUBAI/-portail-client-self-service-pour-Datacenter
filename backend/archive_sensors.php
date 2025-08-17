<?php
require_once 'config.php';

// Script d'archivage des données capteurs vers donneeenv
function archiveOldSensorData() {
    $pdo = getPDO();
    $now = date('Y-m-d H:i:s');
    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));

    // 1. Capteurs climatiques
    $climeQuery = 'SELECT * FROM capteurdeclime WHERE date_mesure < ?';
    $climeStmt = $pdo->prepare($climeQuery);
    $climeStmt->execute([$yesterday]);
    $climeData = $climeStmt->fetchAll();

    foreach ($climeData as $row) {
        $insert = $pdo->prepare('INSERT INTO donneeenv (type, valeur, unite, date_heure, source_capteur_id) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([
            'clime',
            $row['valeur'],
            $row['unite'],
            $row['date_mesure'],
            $row['id']
        ]);
    }

    // 2. Capteurs électriques
    $elecQuery = 'SELECT * FROM capteurelectrique WHERE date_mesure < ?';
    $elecStmt = $pdo->prepare($elecQuery);
    $elecStmt->execute([$yesterday]);
    $elecData = $elecStmt->fetchAll();

    foreach ($elecData as $row) {
        $insert = $pdo->prepare('INSERT INTO donneeenv (type, valeur, unite, date_heure, source_capteur_id) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([
            'electrique',
            $row['valeur'],
            $row['unite'],
            $row['date_mesure'],
            $row['id']
        ]);
    }

    // Optionnel : supprimer les données transférées
    // $pdo->prepare('DELETE FROM capteurdeclime WHERE date_mesure < ?')->execute([$yesterday]);
    // $pdo->prepare('DELETE FROM capteurelectrique WHERE date_mesure < ?')->execute([$yesterday]);
}

// Exécution directe si appelé en CLI
if (php_sapi_name() === 'cli') {
    archiveOldSensorData();
    echo "Archivage terminé.\n";
}
