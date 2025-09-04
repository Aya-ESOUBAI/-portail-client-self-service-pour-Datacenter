<?php
require_once 'config.php';
require_once 'config_notifications.php';

function checkAllSensorThresholds() {
    $pdo = getPDO();
    
    $stmt = $pdo->query('SELECT * FROM seuil_alerte ORDER BY type, valeur_seuil');
    $thresholds = $stmt->fetchAll();
    
    foreach ($thresholds as $threshold) {
        checkSensorThreshold($threshold);
    }
}

function checkSensorThreshold($threshold) {
    $pdo = getPDO();
    
    $currentValues = getCurrentSensorValues($threshold['type']);
    
    foreach ($currentValues as $sensorData) {
        if ($sensorData['valeur'] > $threshold['valeur_seuil']) {
            createCriticalAlert($threshold, $sensorData);
        }
    }
}

function getCurrentSensorValues($type) {
    $pdo = getPDO();
    $values = [];
    
    switch ($type) {
        case 'temperature':
            $stmt = $pdo->query("
                SELECT 
                    ce.id,
                    ce.valeur,
                    ce.unite,
                    ce.date_mesure,
                    r.nom as rack_nom,
                    d.nom as direction_nom,
                    d.email_contact,
                    d.telephone
                FROM capteurdeclime ce
                JOIN armoiredeclime ac ON ce.armoire_id = ac.id
                JOIN rak r ON ac.rak_id = r.id
                JOIN direction d ON r.client_id = d.id
                WHERE ce.type = 'temperature'
                AND ce.date_mesure >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY ce.date_mesure DESC
            ");
            break;
            
        case 'humidite':
            $stmt = $pdo->query("
                SELECT 
                    ce.id,
                    ce.valeur,
                    ce.unite,
                    ce.date_mesure,
                    r.nom as rack_nom,
                    d.nom as direction_nom,
                    d.email_contact,
                    d.telephone
                FROM capteurdeclime ce
                JOIN armoiredeclime ac ON ce.armoire_id = ac.id
                JOIN rak r ON ac.rak_id = r.id
                JOIN direction d ON r.client_id = d.id
                WHERE ce.type = 'humidite'
                AND ce.date_mesure >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY ce.date_mesure DESC
            ");
            break;
            
        case 'energie':
            $stmt = $pdo->query("
                SELECT 
                    ce.id,
                    ce.valeur,
                    ce.unite,
                    ce.date_mesure,
                    r.nom as rack_nom,
                    d.nom as direction_nom,
                    d.email_contact,
                    d.telephone
                FROM capteurelectrique ce
                JOIN pdu p ON ce.pdu_id = p.id
                JOIN rak r ON p.rak_id = r.id
                JOIN direction d ON r.client_id = d.id
                WHERE ce.date_mesure >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY ce.date_mesure DESC
            ");
            break;
            
        default:
            return [];
    }
    
    return $stmt->fetchAll();
}

function createCriticalAlert($threshold, $sensorData) {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("
        SELECT id FROM alerte 
        WHERE type = ? 
        AND rack_id = (SELECT id FROM rak WHERE nom = ?)
        AND date_creation >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute([$threshold['type'], $sensorData['rack_nom']]);
    
    if ($stmt->fetch()) {
        return;
    }
    
    $alertData = [
        'type' => $threshold['type'],
        'message' => "Seuil critique dépassé: {$sensorData['valeur']} {$sensorData['unite']} (seuil: {$threshold['valeur_seuil']} {$threshold['unite']})",
        'statut' => 'active',
        'rack_id' => getRackIdByName($sensorData['rack_nom'])
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO alerte (type, message, statut, rack_id, date_creation) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $alertData['type'],
        $alertData['message'],
        $alertData['statut'],
        $alertData['rack_id']
    ]);
    
    $alertId = $pdo->lastInsertId();
    
    sendCriticalNotifications($alertId, $threshold, $sensorData);
}

function sendCriticalNotifications($alertId, $threshold, $sensorData) {
    $pdo = getPDO();
    
    $stmt = $pdo->query("SELECT email, telephone FROM utilisateur WHERE role = 'Admin' LIMIT 1");
    $admin = $stmt->fetch();
    
    $directionEmail = $sensorData['email_contact'];
    $directionPhone = $sensorData['telephone'];
    
    $alertMessage = formatAlertMessage(
        $threshold['type'],
        $sensorData['valeur'] . ' ' . $sensorData['unite'],
        $threshold['valeur_seuil'] . ' ' . $threshold['unite'],
        $sensorData['rack_nom'],
        $sensorData['direction_nom']
    );
    
    $alertEmail = formatAlertEmail(
        $threshold['type'],
        $sensorData['valeur'] . ' ' . $sensorData['unite'],
        $threshold['valeur_seuil'] . ' ' . $threshold['unite'],
        $sensorData['rack_nom'],
        $sensorData['direction_nom']
    );
    
    $subject = "🚨 ALERTE CRITIQUE - {$threshold['type']} - {$sensorData['rack_nom']}";
    
    $notifications = [];
    
    if ($directionEmail) {
        $emailSent = sendEmailViaMailgun($directionEmail, $subject, $alertEmail);
        $notifications[] = [
            'alerte_id' => $alertId,
            'type_notification' => 'email',
            'destinataire' => $directionEmail,
            'message' => $alertEmail,
            'statut' => $emailSent ? 'envoyee' : 'echec'
        ];
    }
    
    if ($directionPhone) {
        $smsSent = sendSMSViaTwilio($directionPhone, $alertMessage);
        $notifications[] = [
            'alerte_id' => $alertId,
            'type_notification' => 'sms',
            'destinataire' => $directionPhone,
            'message' => $alertMessage,
            'statut' => $smsSent ? 'envoyee' : 'echec'
        ];
    }
    
    if ($admin && $admin['email']) {
        $emailSent = sendEmailViaMailgun($admin['email'], $subject, $alertEmail);
        $notifications[] = [
            'alerte_id' => $alertId,
            'type_notification' => 'email',
            'destinataire' => $admin['email'],
            'message' => $alertEmail,
            'statut' => $emailSent ? 'envoyee' : 'echec'
        ];
    }
    
    if ($admin && $admin['telephone']) {
        $smsSent = sendSMSViaTwilio($admin['telephone'], $alertMessage);
        $notifications[] = [
            'alerte_id' => $alertId,
            'type_notification' => 'sms',
            'destinataire' => $admin['telephone'],
            'message' => $alertMessage,
            'statut' => $smsSent ? 'envoyee' : 'echec'
        ];
    }
    
    foreach ($notifications as $notification) {
        $stmt = $pdo->prepare("
            INSERT INTO notification_envoyee (alerte_id, type_notification, destinataire, message, statut)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $notification['alerte_id'],
            $notification['type_notification'],
            $notification['destinataire'],
            $notification['message'],
            $notification['statut']
        ]);
    }
}

function getRackIdByName($rackName) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id FROM rak WHERE nom = ?");
    $stmt->execute([$rackName]);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

function addCriticalThreshold($type, $value, $unit, $description) {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare("
        INSERT INTO seuil_alerte (type, valeur_seuil, unite, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$type, $value, $unit, $description]);
    
    checkSensorThreshold([
        'type' => $type,
        'valeur_seuil' => $value,
        'unite' => $unit
    ]);
    
    return ['message' => 'Seuil critique ajouté et vérification automatique activée'];
}

function getNotificationHistory($limit = 50) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("
        SELECT 
            ne.*,
            a.type as alerte_type,
            a.message as alerte_message
        FROM notification_envoyee ne
        JOIN alerte a ON ne.alerte_id = a.id
        ORDER BY ne.date_envoi DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function testNotificationServices() {
    $testEmail = 'test@example.com';
    $testPhone = '+1234567890';
    $testMessage = 'Test de notification - ' . date('Y-m-d H:i:s');
    
    $results = [];
    
    $emailResult = sendEmailViaMailgun($testEmail, 'Test Email', $testMessage);
    $results['email'] = $emailResult ? 'Succès' : 'Échec';
    
    $smsResult = sendSMSViaTwilio($testPhone, $testMessage);
    $results['sms'] = $smsResult ? 'Succès' : 'Échec';
    
    return $results;
}
?> 