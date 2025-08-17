<?php
header('Content-Type: application/json');
require_once 'config.php';

// Fonction pour envoyer une réponse JSON
function send_json($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Gestion des CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (prévol)
if ($method === 'OPTIONS') {
    send_json(['message' => 'OK']);
}

// Vérifier l'authentification (à implémenter selon votre système d'authentification)
function check_auth() {
    // Implémentez votre logique d'authentification ici
    // Par exemple, vérification du token JWT
    return true;
}

// Routes
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);
$endpoint = end($uri);

// Connexion à la base de données
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Vérifier la connexion
if ($conn->connect_error) {
    send_json(['error' => 'Database connection failed: ' . $conn->connect_error], 500);
}

// Définir le jeu de caractères
$conn->set_charset('utf8mb4');

// Routes pour les directions
if ($endpoint === 'directions' && $method === 'GET') {
    // Récupérer toutes les directions
    $result = $conn->query("SELECT * FROM direction");
    $directions = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $directions[] = $row;
        }
    }
    
    send_json($directions);
}

// Routes pour les racks
elseif ($endpoint === 'racks' && $method === 'POST') {
    // Vérifier l'authentification
    if (!check_auth()) {
        send_json(['error' => 'Non autorisé'], 401);
    }
    
    // Récupérer les données de la requête
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Valider les données
    if (empty($data['nom']) || empty($data['localisation']) || empty($data['direction_id'])) {
        send_json(['error' => 'Tous les champs sont obligatoires'], 400);
    }
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare("INSERT INTO rak (nom, localisation, client_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $data['nom'], $data['localisation'], $data['direction_id']);
    
    if ($stmt->execute()) {
        $rack_id = $conn->insert_id;
        send_json([
            'message' => 'Rack ajouté avec succès',
            'id' => $rack_id
        ], 201);
    } else {
        send_json(['error' => 'Erreur lors de l\'ajout du rack: ' . $conn->error], 500);
    }
}

// Routes pour les PDU
elseif ($endpoint === 'pdus' && $method === 'POST') {
    // Vérifier l'authentification
    if (!check_auth()) {
        send_json(['error' => 'Non autorisé'], 401);
    }
    
    // Récupérer les données de la requête
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Valider les données
    if (empty($data['marque']) || empty($data['modele']) || empty($data['ip_address']) || !isset($data['rak_id'])) {
        send_json(['error' => 'Tous les champs sont obligatoires'], 400);
    }
    
    // Valeurs par défaut
    $nombre_prises = $data['nombre_prises'] ?? 16;
    $type_connexion = $data['type_connexion'] ?? 'C13/C14';
    $snmp_community = $data['snmp_community'] ?? 'public';
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare("INSERT INTO pdu (marque, modele, nombre_prises, type_connexion, rak_id, ip_address, snmp_community) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisiss", 
        $data['marque'],
        $data['modele'],
        $nombre_prises,
        $type_connexion,
        $data['rak_id'],
        $data['ip_address'],
        $snmp_community
    );
    
    if ($stmt->execute()) {
        $pdu_id = $conn->insert_id;
        send_json([
            'message' => 'PDU ajoutée avec succès',
            'id' => $pdu_id
        ], 201);
    } else {
        send_json(['error' => 'Erreur lors de l\'ajout de la PDU: ' . $conn->error], 500);
    }
}

// Routes pour les armoires de climatisation
elseif ($endpoint === 'clim' && $method === 'POST') {
    // Vérifier l'authentification
    if (!check_auth()) {
        send_json(['error' => 'Non autorisé'], 401);
    }
    
    // Récupérer les données de la requête
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Valider les données
    if (empty($data['marque']) || empty($data['modele']) || !isset($data['rak_id'])) {
        send_json(['error' => 'Tous les champs sont obligatoires'], 400);
    }
    
    // Préparer et exécuter la requête
    $stmt = $conn->prepare("INSERT INTO armoiredeclime (marque, modele, rak_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $data['marque'], $data['modele'], $data['rak_id']);
    
    if ($stmt->execute()) {
        $clim_id = $conn->insert_id;
        send_json([
            'message' => 'Armoire de climatisation ajoutée avec succès',
            'id' => $clim_id
        ], 201);
    } else {
        send_json(['error' => 'Erreur lors de l\'ajout de l\'armoire de climatisation: ' . $conn->error], 500);
    }
}

// Route non trouvée
else {
    send_json(['error' => 'Endpoint non trouvé'], 404);
}

// Fermer la connexion
$conn->close();
?>
