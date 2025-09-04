<?php
require_once 'config.php';

function index() {
    try {
        error_log('Début de la fonction index() pour récupérer les demandes');
        
        $pdo = getPDO();
        
        if (!$pdo) {
            $error = 'Erreur: Impossible de se connecter à la base de données';
            error_log($error);
            return ['error' => $error];
        }
        
        $query = '
            SELECT d.*, u.nom as utilisateur_nom, dir.nom as direction_nom 
            FROM demande d 
            LEFT JOIN utilisateur u ON d.utilisateur_id = u.id 
            LEFT JOIN direction dir ON d.direction_id = dir.id 
            ORDER BY d.date_soumission DESC
        ';
        
        error_log('Exécution de la requête: ' . $query);
        
        $stmt = $pdo->query($query);
        
        if (!$stmt) {
            $error = $pdo->errorInfo();
            $errorMsg = 'Erreur SQL: ' . print_r($error, true);
            error_log($errorMsg);
            return ['error' => $errorMsg];
        }
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log('Nombre de demandes récupérées: ' . count($result));
        
        if (empty($result)) {
            $countStmt = $pdo->query('SELECT COUNT(*) as count FROM demande');
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            error_log('La table demande contient ' . $count . ' enregistrements');
            
            if ($count > 0) {
                error_log('Erreur: La requête ne retourne aucun résultat mais la table contient des données');
            }
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Erreur dans index(): ' . $e->getMessage());
        return [];
    }
}

function show($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('
        SELECT d.*, u.nom as utilisateur_nom, dir.nom as direction_nom 
        FROM demande d 
        LEFT JOIN utilisateur u ON d.utilisateur_id = u.id 
        LEFT JOIN direction dir ON d.direction_id = dir.id 
        WHERE d.id = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAlertThresholds() {
    $pdo = getPDO();
    $stmt = $pdo->query('SELECT * FROM seuil_alerte ORDER BY type, valeur_seuil');
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
                'valeur_detectee' => $currentValue,
                'seuil_critique' => $threshold['valeur_seuil'],
                'date_alert' => date('Y-m-d H:i:s'),
                'envoyee_par' => 'Système',
                'statut' => 'active'
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

    if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
    }

    $userId = null;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = null;
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        }
    }
    if (isset($data['utilisateur_id']) && !empty($data['utilisateur_id'])) {
        $userId = $data['utilisateur_id'];
    } else if ($token) {
        $userId = null; 
    }

    $directionId = null;
    if ($userId) {
        $stmt = $pdo->prepare('SELECT direction_id FROM utilisateur WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $directionId = $user['direction_id'] ?? null;
    }
    
    $directionId = null;
    if ($userId) {
        $stmt = $pdo->prepare('SELECT direction_id FROM utilisateur WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $directionId = $user['direction_id'] ?? null;
    }

    $pdfFilename = null;

    $validTypes = ['INSTALLATION', 'RETRAIT', 'ACCES'];
    if (!in_array($data['type'], $validTypes)) {
        http_response_code(400);
        return ['error' => 'Type de demande invalide. Les types valides sont : ' . implode(', ', $validTypes)];
    }
    
    if (!$userId) {
        http_response_code(400);
        return ['error' => 'Utilisateur non identifié. Veuillez vous connecter.'];
    }
    
    if (!$directionId) {
        http_response_code(400);
        return ['error' => 'Direction non trouvée pour cet utilisateur.'];
    }
    
    try {
        error_log('Début de la création de la demande');
        error_log('Données reçues: ' . print_r($data, true));
        error_log('User ID: ' . $userId);
        error_log('Direction ID: ' . $directionId);
        
        $sql = 'INSERT INTO demande (
            type, 
            statut, 
            utilisateur_id, 
            direction_id,
            nom,
            prenom,
            cin,
            date_acces,
            nom_equipement,
            serial_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        error_log('Requête SQL: ' . $sql);
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $data['type'],
            'EN_ATTENTE',
            $userId,
            $directionId,
            $data['nom'] ?? 'Non spécifié',
            $data['prenom'] ?? 'Non spécifié',
            $data['cin'] ?? 'Non spécifié',
            $data['date_acces'] ?? null,
            $data['nom_equipement'] ?? null,
            $data['serial_number'] ?? null
        ];
        
        error_log('Paramètres: ' . print_r($params, true));
        
        $result = $stmt->execute($params);

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log('Erreur lors de l\'insertion de la demande: ' . print_r($errorInfo, true));
            http_response_code(500);
            return ['error' => 'Erreur lors de la création de la demande', 'details' => $errorInfo[2]];
        }

        $demandeId = $pdo->lastInsertId();
        error_log('Nouvelle demande créée avec succès. ID: ' . $demandeId);
    } catch (PDOException $e) {
        error_log('Erreur PDO lors de la création de la demande: ' . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Erreur de base de données lors de la création de la demande'];
    }

    $stmt = $pdo->prepare('SELECT u.nom, u.email, d.nom as direction_nom FROM utilisateur u LEFT JOIN direction d ON u.direction_id = d.id WHERE u.id = ?');
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch();

    $pdfData = array_merge($data, [
        'direction_id' => $directionId,
        'direction_nom' => $userInfo['direction_nom'] ?? 'Inconnue',
        'utilisateur_nom' => $userInfo['nom'] ?? 'Inconnu',
        'utilisateur_email' => $userInfo['email'] ?? '',
        'statut' => 'EN_ATTENTE',
        'date_soumission' => date('Y-m-d H:i:s')
    ]);
    
    $generatedPdfFilename = generatePDFForDemande($demandeId, $pdfData);

    if ($generatedPdfFilename) {
        $stmt = $pdo->prepare('UPDATE demande SET justificatif_pdf = ? WHERE id = ?');
        $stmt->execute([$generatedPdfFilename, $demandeId]);
    }

    return [
        'message' => 'Demande créée avec succès',
        'demande_id' => $demandeId,
        'pdf_filename' => $generatedPdfFilename
    ];
}

function generatePDFForDemande($demandeId, $data) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $uploadDir = __DIR__ . '/uploads/justificatifs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = "justificatif_demande_{$demandeId}_" . date('Y-m-d_H-i-s') . ".pdf";
    $filepath = $uploadDir . $filename;

    $content = generatePDFContent($demandeId, $data);

    $pdf = new \FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, utf8_decode('JUSTIFICATIF DE DEMANDE'), 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 12);
    foreach (explode("\n", $content) as $line) {
        $pdf->Cell(0, 8, utf8_decode($line), 0, 1);
    }
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 8, utf8_decode('--- Document généré automatiquement ---'), 0, 1);
    $pdf->Cell(0, 8, utf8_decode('Date de génération: ' . date('d/m/Y H:i:s')), 0, 1);

    $pdf->Output('F', $filepath);
    if (file_exists($filepath)) {
        return $filename;
    }
    return null;
}

function generatePDFContent($demandeId, $data) {
    $direction = getDirectionName($data['direction_id']);
    $date = date('d/m/Y');
    
    $content = "=== JUSTIFICATIF DE DEMANDE ===\n\n";
    $content .= "ID Demande: {$demandeId}\n";
    $content .= "Type: " . getTypeText($data['type']) . "\n";
    $content .= "Direction: {$direction}\n";
    $content .= "Date de soumission: {$date}\n";
    $content .= "Statut: " . getStatusText($data['statut'] ?? 'EN_ATTENTE') . "\n\n";
    
    switch ($data['type']) {
        case 'INSTALLATION':
            $content .= "DÉTAILS DE L'INSTALLATION:\n\n";
            $content .= "Type d'installation: Équipements informatiques\n";
            $content .= "Localisation: Rack " . chr(65 + ($demandeId % 5)) . ($demandeId % 10 + 1) . "\n";
            $content .= "Budget estimé: " . (50000 + ($demandeId * 1000)) . "€\n";
            $content .= "Justification: Extension de capacité et modernisation\n\n";
            $content .= "ÉQUIPEMENT À INSTALLER:\n";
            $content .= "- Nom d'équipement: " . ($data['nom_equipement'] ?? 'Non spécifié') . "\n";
            $content .= "- Numéro de série: " . ($data['serial_number'] ?? 'Non spécifié') . "\n\n";
            $content .= "Équipements demandés:\n";
            $content .= "- Serveurs haute performance\n";
            $content .= "- Switch réseau\n";
            $content .= "- Système de refroidissement\n";
            $content .= "- Câblage réseau\n\n";
            break;
            
        case 'ACCES':
            $content .= "DÉTAILS DE L'ACCÈS:\n\n";
            $content .= "Type d'accès: Physique au datacenter\n";
            $content .= "Personnel autorisé: " . (2 + ($demandeId % 3)) . " techniciens\n";
            $content .= "Durée: " . (1 + ($demandeId % 3)) . " jour(s)\n";
            $content .= "Raison: " . getAccessReason($demandeId) . "\n\n";
            $content .= "INFORMATIONS PERSONNELLES:\n";
            $content .= "- Nom: " . ($data['nom'] ?? 'Non spécifié') . "\n";
            $content .= "- Prénom: " . ($data['prenom'] ?? 'Non spécifié') . "\n";
            $content .= "- CIN: " . ($data['cin'] ?? 'Non spécifié') . "\n";
            $content .= "- Date d'accès: " . ($data['date_acces'] ? date('d/m/Y', strtotime($data['date_acces'])) : 'Non spécifiée') . "\n";
            if (!empty($data['code'])) {
                $content .= "- Code d'accès: " . $data['code'] . "\n";
            }
            $content .= "\n";
            $content .= "Programme détaillé:\n";
            $content .= "- Maintenance préventive\n";
            $content .= "- Mise à jour firmware\n";
            $content .= "- Tests de sécurité\n";
            $content .= "- Vérification des équipements\n\n";
            break;
            
        case 'RETRAIT':
            $content .= "DÉTAILS DU RETRAIT:\n\n";
            $content .= "Type de retrait: Équipements obsolètes\n";
            $content .= "Localisation: Rack " . chr(65 + ($demandeId % 5)) . ($demandeId % 10 + 1) . "\n";
            $content .= "Raison: Renouvellement d'équipements\n\n";
            $content .= "ÉQUIPEMENT À RETIRER:\n";
            $content .= "- Nom d'équipement: " . ($data['nom_equipement'] ?? 'Non spécifié') . "\n";
            $content .= "- Numéro de série: " . ($data['serial_number'] ?? 'Non spécifié') . "\n\n";
            $content .= "Équipements à retirer:\n";
            $content .= "- Serveurs anciens\n";
            $content .= "- Équipements réseau obsolètes\n";
            $content .= "- Systèmes de stockage\n\n";
            break;
    }
    
    $content .= "APPROBATIONS:\n";
    $content .= "- Directeur technique: En attente\n";
    $content .= "- Responsable sécurité: En attente\n";
    $content .= "- Responsable infrastructure: En attente\n\n";
    
    $content .= "CONTACTS:\n";
    $content .= "- Responsable projet: " . getResponsableName($data['direction_id']) . "\n";
    $content .= "- Téléphone: +33 1 23 45 67 89\n";
    $content .= "- Email: projet@" . strtolower(str_replace(' ', '', $direction)) . ".com\n\n";
    
    $content .= "--- Document généré automatiquement ---\n";
    $content .= "Date de génération: " . date('d/m/Y H:i:s') . "\n";
    
    return $content;
}

function getDirectionName($directionId) {
    $directions = [
        1 => 'Client One',
        2 => 'Client Two', 
        3 => 'Direction Test'
    ];
    return $directions[$directionId] ?? 'Direction Inconnue';
}

function getTypeText($type) {
    switch ($type) {
        case 'INSTALLATION': return 'Installation';
        case 'ACCES': return 'Accès';
        case 'RETRAIT': return 'Retrait';
        default: return $type;
    }
}

function getStatusText($statut) {
    switch ($statut) {
        case 'EN_ATTENTE': return 'En Attente';
        case 'VALIDEE': return 'Validée';
        case 'REJETEE': return 'Rejetée';
        case 'TERMINEE': return 'Terminée';
        default: return $statut;
    }
}

function getAccessReason($demandeId) {
    $reasons = [
        'Maintenance préventive',
        'Mise à jour firmware',
        'Audit de sécurité',
        'Tests de performance',
        'Installation de nouveaux équipements'
    ];
    return $reasons[$demandeId % count($reasons)];
}

function getResponsableName($directionId) {
    $responsables = [
        1 => 'Jean Dupont',
        2 => 'Marie Martin',
        3 => 'Pierre Durand'
    ];
    return $responsables[$directionId] ?? 'Responsable à définir';
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
    
    checkCriticalThresholds();
    
    return ['message' => 'Seuil critique créé et vérifié'];
}

function update($id, $data) {
    $pdo = getPDO();
    
    if (isset($data['type']) && $data['type'] === 'ACCES') {
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['cin']) || empty($data['date_acces'])) {
            http_response_code(400);
            return ['error' => 'Pour les demandes d\'accès, le nom, prénom, CIN et la date d\'accès sont obligatoires'];
        }
    }
    
    if (isset($data['type']) && ($data['type'] === 'INSTALLATION' || $data['type'] === 'RETRAIT')) {
        if (empty($data['nom_equipement']) || empty($data['serial_number'])) {
            http_response_code(400);
            return ['error' => 'Pour les demandes d\'installation et retrait, le nom d\'équipement et le numéro de série sont obligatoires'];
        }
    }
    
    $fields = [];
    $params = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = $id;
    $sql = 'UPDATE demande SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['message' => 'Demande mise à jour'];
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
    $stmt = $pdo->prepare('DELETE FROM demande WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande supprimée'];
}

function destroyThreshold($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM seuil_alerte WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Seuil critique supprimé'];
}

function valider($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE demande SET statut = "VALIDEE" WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande validée avec succès'];
}

function rejeter($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE demande SET statut = "REJETEE" WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande rejetée'];
}

function terminer($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE demande SET statut = "TERMINEE" WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande marquée comme terminée'];
}

function getStats() {
    $pdo = getPDO();
    $stmt = $pdo->query('
        SELECT 
            statut,
            COUNT(*) as total
        FROM demande 
        GROUP BY statut
    ');
    return $stmt->fetchAll();
}

function getDemandesByDirection() {
    $pdo = getPDO();
    
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $type = $_GET['type'] ?? null;
    
    $whereConditions = [];
    $params = [];
    
    if ($type) {
        $whereConditions[] = 'd.type = ?';
        $params[] = $type;
    }
    
    if ($startDate && $endDate) {
        $whereConditions[] = 'd.date_soumission BETWEEN ? AND ?';
        $params[] = $startDate . ' 00:00:00';
        $params[] = $endDate . ' 23:59:59';
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    $sql = "
        SELECT 
            d.id,
            d.type,
            d.statut,
            d.date_soumission,
            u.nom as utilisateur_nom,
            dir.nom as direction_nom
        FROM demande d
        LEFT JOIN utilisateur u ON d.utilisateur_id = u.id
        LEFT JOIN direction dir ON d.direction_id = dir.id
        $whereClause
        ORDER BY d.date_soumission DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function downloadPDF($id) {
    $pdo = getPDO();
    
    $stmt = $pdo->prepare('SELECT * FROM demande WHERE id = ?');
    $stmt->execute([$id]);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        http_response_code(404);
        return ['error' => 'Demande non trouvée'];
    }
    
    if (empty($demande['justificatif_pdf'])) {
        http_response_code(404);
        return ['error' => 'PDF non trouvé pour cette demande'];
    }
    
    $uploadDir = __DIR__ . '/uploads/justificatifs/';
    $filepath = $uploadDir . $demande['justificatif_pdf'];
    
    if (!file_exists($filepath)) {
        http_response_code(404);
        return ['error' => 'Fichier PDF non trouvé sur le serveur'];
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $demande['justificatif_pdf'] . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    readfile($filepath);
    exit;
}
