<?php
require_once 'config.php';
require_once 'surveillance_capteurs.php';

echo "🔧 Configuration des Seuils Critiques\n";
echo "=====================================\n\n";

try {
    $pdo = getPDO();
    
    $defaultThresholds = [
        [
            'type' => 'temperature',
            'valeur_seuil' => 35,
            'unite' => '°C',
            'description' => 'Seuil critique de température - Alerte si > 35°C'
        ],
        [
            'type' => 'humidite',
            'valeur_seuil' => 80,
            'unite' => '%',
            'description' => 'Seuil critique d\'humidité - Alerte si > 80%'
        ],
        [
            'type' => 'energie',
            'valeur_seuil' => 2000,
            'unite' => 'W',
            'description' => 'Seuil critique de consommation électrique - Alerte si > 2000W'
        ]
    ];
    
    echo "📊 Configuration des seuils critiques par défaut...\n\n";
    
    foreach ($defaultThresholds as $threshold) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM seuil_alerte WHERE type = ?");
            $stmt->execute([$threshold['type']]);
            
            if ($stmt->fetch()) {
                echo "ℹ️ Seuil {$threshold['type']} existe déjà\n";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO seuil_alerte (type, valeur_seuil, unite, description)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $threshold['type'],
                    $threshold['valeur_seuil'],
                    $threshold['unite'],
                    $threshold['description']
                ]);
                echo "✅ Seuil {$threshold['type']} ajouté: {$threshold['valeur_seuil']} {$threshold['unite']}\n";
            }
        } catch (PDOException $e) {
            echo "❌ Erreur lors de l'ajout du seuil {$threshold['type']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📋 Seuils critiques configurés:\n";
    $stmt = $pdo->query("SELECT * FROM seuil_alerte ORDER BY type");
    $thresholds = $stmt->fetchAll();
    
    foreach ($thresholds as $threshold) {
        echo "   - {$threshold['type']}: {$threshold['valeur_seuil']} {$threshold['unite']} ({$threshold['description']})\n";
    }
    
    echo "\n🎉 Configuration terminée!\n";
    echo "\n📋 Prochaines étapes:\n";
    echo "1. Configurez vos clés API Mailgun et Twilio dans backend/config_notifications.php\n";
    echo "2. Ajoutez votre numéro de téléphone dans votre profil utilisateur\n";
    echo "3. Testez le système avec: php backend/test_alertes_automatiques.php\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
}
?> 