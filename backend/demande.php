<?php
require_once 'config.php';

function index() {
    $pdo = getPDO();
    $stmt = $pdo->query('
        SELECT d.*, u.nom as utilisateur_nom, dir.nom as direction_nom 
        FROM demande d 
        LEFT JOIN utilisateur u ON d.utilisateur_id = u.id 
        LEFT JOIN direction dir ON d.direction_id = dir.id 
        ORDER BY d.date_soumission DESC
    ');
    return $stmt->fetchAll();
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

// Fonction pour vérifier les seuils critiques et envoyer des notifications
function checkCriticalThresholds() {
    $pdo = getPDO();
    
    // Récupérer tous les seuils critiques
    $thresholds = getAlertThresholds();
    
    foreach ($thresholds as $threshold) {
        // Vérifier les données actuelles selon le type
        $currentValue = getCurrentValue($threshold['type']);
        
        if ($currentValue !== null && $currentValue > $threshold['valeur_seuil']) {
            // Seuil critique dépassé - créer une alerte et envoyer des notifications
            $alertData = [
                'type' => $threshold['type'],
                'valeur_detectee' => $currentValue,
                'seuil_critique' => $threshold['valeur_seuil'],
                'date_alert' => date('Y-m-d H:i:s'),
                'envoyee_par' => 'Système',
                'statut' => 'active'
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

    // Si la requête est POST et multipart/form-data, lire depuis $_POST
    if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
    }

    // Récupérer l'utilisateur connecté depuis le token ou le frontend
    $userId = null;
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = null;
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        }
    }
    // Essayer de récupérer l'utilisateur depuis le frontend (FormData)
    if (isset($data['utilisateur_id']) && !empty($data['utilisateur_id'])) {
        $userId = $data['utilisateur_id'];
    } else if ($token) {
        // Si vous avez un décodage JWT, utilisez-le ici
        // $userId = decodeJWT($token);
        $userId = null; // fallback si pas de décodage
    }

    // Récupérer la direction de l'utilisateur
    $directionId = null;
    if ($userId) {
        $stmt = $pdo->prepare('SELECT direction_id FROM utilisateur WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $directionId = $user['direction_id'] ?? null;
    }
    
    // Récupérer la direction de l'utilisateur
    $directionId = null;
    if ($userId) {
        $stmt = $pdo->prepare('SELECT direction_id FROM utilisateur WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $directionId = $user['direction_id'] ?? null;
    }

    // Préparer les données pour l'insertion
    $pdfFilename = null;

    // Validation pour les demandes d'accès
    if ($data['type'] === 'ACCES') {
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['cin']) || empty($data['date_acces'])) {
            http_response_code(400);
            return ['error' => 'Pour les demandes d\'accès, le nom, prénom, CIN et la date d\'accès sont obligatoires'];
        }
    }
    
    // Validation pour les demandes d'installation et retrait
    if ($data['type'] === 'INSTALLATION' || $data['type'] === 'RETRAIT') {
        if (empty($data['nom_equipement']) || empty($data['serial_number'])) {
            http_response_code(400);
            return ['error' => 'Pour les demandes d\'installation et retrait, le nom d\'équipement et le numéro de série sont obligatoires'];
        }
    }
    
    // Insérer la demande sans PDF
    $stmt = $pdo->prepare('
        INSERT INTO demande (type, statut, justificatif_pdf, utilisateur_id, direction_id, nom, prenom, cin, code, date_acces, nom_equipement, serial_number) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $data['type'],
        'EN_ATTENTE',
        null,
        $userId,
        $directionId,
        $data['nom'] ?? null,
        $data['prenom'] ?? null,
        $data['cin'] ?? null,
        $data['code'] ?? null,
        $data['date_acces'] ?? null,
        $data['nom_equipement'] ?? null,
        $data['serial_number'] ?? null
    ]);

    $demandeId = $pdo->lastInsertId();

    // Générer le PDF avec l'ID réel
    $pdfData = array_merge($data, [
        'direction_id' => $directionId,
        'statut' => 'EN_ATTENTE'
    ]);
    $generatedPdfFilename = generatePDFForDemande($demandeId, $pdfData);

    // Mettre à jour la demande avec le nom du PDF généré
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

// Fonction pour générer un PDF pour une demande
function generatePDFForDemande($demandeId, $data) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $uploadDir = __DIR__ . '/uploads/justificatifs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = "justificatif_demande_{$demandeId}_" . date('Y-m-d_H-i-s') . ".pdf";
    $filepath = $uploadDir . $filename;

    $content = generatePDFContent($demandeId, $data);

    // Générer le PDF avec FPDF et encodage correct
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

    // Sauvegarder le PDF
    $pdf->Output('F', $filepath);
    if (file_exists($filepath)) {
        return $filename;
    }
    return null;
}

// Fonction pour générer le contenu du PDF
function generatePDFContent($demandeId, $data) {
    $direction = getDirectionName($data['direction_id']);
    $date = date('d/m/Y');
    
    $content = "=== JUSTIFICATIF DE DEMANDE ===\n\n";
    $content .= "ID Demande: {$demandeId}\n";
    $content .= "Type: " . getTypeText($data['type']) . "\n";
    $content .= "Direction: {$direction}\n";
    $content .= "Date de soumission: {$date}\n";
    $content .= "Statut: " . getStatusText($data['statut'] ?? 'EN_ATTENTE') . "\n\n";
    
    // Contenu spécifique selon le type avec les informations saisies
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
    
    // Vérifier immédiatement si le seuil est dépassé
    checkCriticalThresholds();
    
    return ['message' => 'Seuil critique créé et vérifié'];
}

function update($id, $data) {
    $pdo = getPDO();
    
    // Validation pour les demandes d'accès
    if (isset($data['type']) && $data['type'] === 'ACCES') {
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['cin']) || empty($data['date_acces'])) {
            http_response_code(400);
            return ['error' => 'Pour les demandes d\'accès, le nom, prénom, CIN et la date d\'accès sont obligatoires'];
        }
    }
    
    // Validation pour les demandes d'installation et retrait
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
    
    // Vérifier immédiatement si le seuil est dépassé
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

// Fonction pour valider une demande
function valider($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE demande SET statut = "VALIDEE" WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande validée avec succès'];
}

// Fonction pour rejeter une demande
function rejeter($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE demande SET statut = "REJETEE" WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande rejetée'];
}

// Fonction pour marquer une demande comme terminée
function terminer($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE demande SET statut = "TERMINEE" WHERE id = ?');
    $stmt->execute([$id]);
    return ['message' => 'Demande marquée comme terminée'];
}

// Fonction pour obtenir les statistiques des demandes
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

// Fonction pour obtenir les demandes par direction
function getDemandesByDirection() {
    $pdo = getPDO();
    
    // Récupérer les paramètres de filtrage
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $type = $_GET['type'] ?? null;
    
    $whereConditions = [];
    $params = [];
    
    // Filtre par type de demande
    if ($type) {
        $whereConditions[] = 'd.type = ?';
        $params[] = $type;
    }
    
    // Filtre par date
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

// Fonction pour télécharger le PDF d'une demande
function downloadPDF($id) {
    $pdo = getPDO();
    
    // Récupérer la demande
    $stmt = $pdo->prepare('SELECT * FROM demande WHERE id = ?');
    $stmt->execute([$id]);
    $demande = $stmt->fetch();
    
    if (!$demande) {
        http_response_code(404);
        return ['error' => 'Demande non trouvée'];
    }
    
    // Vérifier si le PDF existe
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
    
    // Définir les headers pour le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $demande['justificatif_pdf'] . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Lire et envoyer le fichier
    readfile($filepath);
    exit;
}
