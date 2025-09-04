<?php
require_once 'config.php';

function archiveOldSensorData() {
    $pdo = getPDO();
    $now = date('Y-m-d H:i:s');
    $yesterday = date('Y-m-d H:i:s', strtotime('-1 day'));

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

}

if (php_sapi_name() === 'cli') {
    archiveOldSensorData();
    echo "Archivage terminé.\n";
}
