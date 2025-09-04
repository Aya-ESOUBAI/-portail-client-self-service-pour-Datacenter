<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT a.*, r.nom as rack_nom 
                        FROM alerte a 
                        LEFT JOIN rak r ON a.rack_id = r.id 
                        ORDER BY a.date_creation DESC');
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
    $stmt = $pdo->query('SELECT id, type, valeur_seuil, message, rack_id FROM seuil_alerte ORDER BY type, valeur_seuil');
    return $stmt->fetchAll();
}

function checkCriticalThresholds() {
    $pdo = getPDO();
    
    $thresholds = getAlertThresholds();
    
    foreach ($thresholds as $threshold) {
        $currentValue = getCurrentValue($threshold['type']);
        
        if ($currentValue !== null && $currentValue > $threshold['valeur_seuil']) {
            $alertData = [
                'type' => $threshold['type'],
                'message' => "Seuil critique dépassé: {$currentValue} > {$threshold['valeur_seuil']} {$threshold['unite']}",
                'rack_id' => null  
            ];
            
            store($alertData);
            
            sendCriticalNotifications($threshold, $currentValue);
        }
    }
}

function getCurrentValue($type) {
    $pdo = getPDO();
    
    switch ($type) {
        case 'temperature':
            $stmt = $pdo->query('
                SELECT AVG(cd.valeur) as moyenne 
                FROM capteurdeclime cd 
                WHERE cd.type = "temperature" 
                AND cd.date_mesure >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $result = $stmt->fetch();
            return $result ? $result['moyenne'] : null;
            
        case 'energie':
            $stmt = $pdo->query('
                SELECT AVG(ce.valeur) as moyenne 
                FROM capteurelectrique ce 
                WHERE ce.date_mesure >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $result = $stmt->fetch();
            return $result ? $result['moyenne'] : null;
            
        case 'humidite':
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

function sendCriticalNotifications($threshold, $currentValue) {
    $pdo = getPDO();
    
    $stmt = $pdo->query('SELECT * FROM utilisateur WHERE role = "Admin"');
    $admins = $stmt->fetchAll();
    
    $stmt = $pdo->query('SELECT * FROM direction');
    $directions = $stmt->fetchAll();
    
    $message = "🚨 ALERTE CRITIQUE DATACENTER 🚨\n\n";
    $message .= "Type: {$threshold['type']}\n";
    $message .= "Seuil critique: {$threshold['valeur_seuil']} {$threshold['unite']}\n";
    $message .= "Valeur actuelle: {$currentValue} {$threshold['unite']}\n";
    $message .= "Description: {$threshold['description']}\n";
    $message .= "Date: " . date('d/m/Y H:i:s') . "\n\n";
    $message .= "Action requise immédiatement!";
    
    foreach ($admins as $admin) {
        if (!empty($admin['email'])) {
            sendEmail($admin['email'], "Alerte Critique - {$threshold['type']}", $message);
        }
        if (!empty($admin['telephone'])) {
            sendSMS($admin['telephone'], $message);
        }
    }
    
    foreach ($directions as $direction) {
        if (!empty($direction['email_contact'])) {
            sendEmail($direction['email_contact'], "Alerte Critique - {$threshold['type']}", $message);
        }
        if (!empty($direction['telephone'])) {
            sendSMS($direction['telephone'], $message);
        }
    }
}

function sendEmail($to, $subject, $message) {
    $headers = "From: datacenter@example.com\r\n";
    $headers .= "Reply-To: datacenter@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    mail($to, $subject, $message, $headers);
    
    error_log("Email envoyé à {$to}: {$subject}");
}

function sendSMS($phone, $message) {
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
    $stmt = $pdo->prepare('INSERT INTO seuil_alerte (type, valeur_seuil, message, rack_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $data['type'],
        $data['valeur_seuil'],
        $data['message'] ?? null,
        $data['rack_id'] ?? null
    ]);
    
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


