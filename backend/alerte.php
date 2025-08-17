<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM alerte ORDER BY date_creation DESC');
    return $stmt->fetchAll();
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM alerte WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAlertThresholds() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM seuil_alerte ORDER BY type_capteur, valeur_max');
    return $stmt->fetchAll();
}

// Fonction pour vérifier les seuils critiques et envoyer des notifications
function checkCriticalThresholds() {
    $pdo = getPDO();
    
    // Récupérer tous les seuils critiques
    $thresholds = getAlertThresholds();
    
    foreach ($thresholds as $threshold) {
        // Vérifier les données actuelles selon le type
        $currentValue = getCurrentValue($threshold['type']);
        
        if ($currentValue !== null && $currentValue > $threshold['valeur_max']) {
            // Seuil critique dépassé - créer une alerte et envoyer des notifications
            $alertData = [
                'type' => $threshold['type_capteur'],
                'message' => "Seuil critique dépassé: {$currentValue} > {$threshold['valeur_max']}",
                'statut' => 'active',
                'rack_id' => $threshold['rack_id']
            ];
            
            // Créer l'alerte
            store($alertData);
            
            // Envoyer les notifications
            sendCriticalNotifications($threshold, $currentValue);
        }
    }
}

// Fonction pour obtenir la valeur actuelle selon le type
function getCurrentValue($type) {
    $pdo = getPDO();
    
    switch ($type) {
        case 'temperature':
            // Récupérer la température moyenne actuelle
            $stmt = $pdo->query('
                SELECT AVG(cd.valeur) as moyenne 
                FROM capteurdeclime cd 
                WHERE cd.type = "temperature" 
                AND cd.date_mesure >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $result = $stmt->fetch();
            return $result ? $result['moyenne'] : null;
            
        case 'energie':
            // Récupérer la consommation énergétique moyenne actuelle
            $stmt = $pdo->query('
                SELECT AVG(ce.valeur) as moyenne 
                FROM capteurelectrique ce 
                WHERE ce.date_mesure >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $result = $stmt->fetch();
            return $result ? $result['moyenne'] : null;
            
        case 'humidite':
            // Récupérer l'humidité moyenne actuelle
            $stmt = $pdo->query('
                SELECT AVG(cd.valeur) as moyenne 
                FROM capteurdeclime cd 
                WHERE cd.type = "humidite" 
                AND cd.date_mesure >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $result = $stmt->fetch();
            return $result ? $result['moyenne'] : null;
            
        default:
            return null;
    }
}

// Fonction pour envoyer les notifications critiques
function sendCriticalNotifications($threshold, $currentValue) {
    $pdo = getPDO();
    
    // Récupérer tous les administrateurs
    $stmt = $pdo->query('SELECT * FROM utilisateur WHERE role = "Admin"');
    $admins = $stmt->fetchAll();
    
    // Récupérer toutes les directions
    $stmt = $pdo->query('SELECT * FROM direction');
    $directions = $stmt->fetchAll();
    
    $message = "🚨 ALERTE CRITIQUE DATACENTER 🚨\n\n";
    $message .= "Type: {$threshold['type']}\n";
    $message .= "Seuil critique: {$threshold['valeur_seuil']} {$threshold['unite']}\n";
    $message .= "Valeur actuelle: {$currentValue} {$threshold['unite']}\n";
    $message .= "Description: {$threshold['description']}\n";
    $message .= "Date: " . date('d/m/Y H:i:s') . "\n\n";
    $message .= "Action requise immédiatement!";
    
    // Envoyer aux administrateurs
    foreach ($admins as $admin) {
        if (!empty($admin['email'])) {
            sendEmail($admin['email'], "Alerte Critique - {$threshold['type']}", $message);
        }
        if (!empty($admin['telephone'])) {
            sendSMS($admin['telephone'], $message);
        }
    }
    
    // Envoyer aux directions
    foreach ($directions as $direction) {
        if (!empty($direction['email_contact'])) {
            sendEmail($direction['email_contact'], "Alerte Critique - {$threshold['type']}", $message);
        }
        if (!empty($direction['telephone'])) {
            sendSMS($direction['telephone'], $message);
        }
    }
}

// Fonction pour envoyer un email
function sendEmail($to, $subject, $message) {
    // Configuration pour l'envoi d'email
    $headers = "From: datacenter@example.com\r\n";
    $headers .= "Reply-To: datacenter@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Envoi de l'email (dans un environnement de production, utilisez une bibliothèque comme PHPMailer)
    mail($to, $subject, $message, $headers);
    
    // Log de l'envoi
    error_log("Email envoyé à {$to}: {$subject}");
}

// Fonction pour envoyer un SMS (simulation - dans un environnement réel, utilisez un service SMS)
function sendSMS($phone, $message) {
    // Simulation d'envoi SMS
    // Dans un environnement réel, utilisez un service comme Twilio, Nexmo, etc.
    error_log("SMS envoyé à {$phone}: {$message}");
}

function store($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO alerte (type, message, statut, rack_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $data['type'],
        $data['message'] ?? 'Alerte système',
        $data['statut'] ?? 'active',
        $data['rack_id'] ?? null
    ]);
    return ['message' => 'Alerte créée'];
}

function storeThreshold($data) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO seuil_alerte (type, valeur_seuil, unite, description) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $data['type'],
        $data['valeur_seuil'],
        $data['unite'],
        $data['description']
    ]);
    
    // Vérifier immédiatement si le seuil est dépassé
    checkCriticalThresholds();
    
    return ['message' => 'Seuil critique créé et vérifié'];
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
    $sql = 'UPDATE alerte SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Alerte mise à jour'];
}

function updateThreshold($id, $data) {
    $pdo = getPDO();
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE seuil_alerte SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Vérifier immédiatement si le seuil est dépassé
    checkCriticalThresholds();
    
    return ['message' => 'Seuil critique mis à jour et vérifié'];
}

function destroy($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM alerte WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Alerte supprimée'];
}

function destroyThreshold($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM seuil_alerte WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Seuil critique supprimé'];
}


