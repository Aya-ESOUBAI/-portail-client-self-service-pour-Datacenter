<?php
// Point d'entrée principal de l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'routes.php';

$routes = include 'routes.php';
$method = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?');
// Nettoyer l'URI pour le serveur de développement
$uri = str_replace('/3/backend/index.php', '', $uri);
$uri = str_replace('/index.php', '', $uri);

error_log('URI: ' . $uri);
error_log('Method: ' . $method);
// Fonction pour extraire les paramètres d'URL
function matchRoute($routes, $method, $uri) {
    foreach ($routes as $route) {
        list($routeMethod, $routePath, $handler) = $route;
        if ($routeMethod !== $method) continue;
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
            array_shift($matches);
            return [$handler, $matches];
        }
    }
    return [null, []];
}


list($handler, $params) = matchRoute($routes, $method, $uri);
if (!$handler) {
    http_response_code(404);
    echo json_encode(['error' => 'Route non trouvée']);
    exit;
}

// Authentification basique par token (sauf /login et /register)
$publicRoutes = [
    'auth.php@login',
    'auth.php@register'
];
if (!in_array($handler, $publicRoutes)) {
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
    if (!$auth || !preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token manquant ou invalide']);
        exit;
    }
    // Ici, on accepte n'importe quel token non vide (à remplacer par une vraie vérification JWT en prod)
    $token = $matches[1];
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Token vide'] );
        exit;
    }
}

list($file, $function) = explode('@', $handler);
require_once $file;

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'GET') {
    $result = $params ? call_user_func_array($function, $params) : $function();
} elseif ($method === 'POST') {
    $result = $function($input);
} elseif ($method === 'PUT') {
    $result = call_user_func_array($function, array_merge($params, [$input]));
} elseif ($method === 'DELETE') {
    $result = call_user_func_array($function, $params);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

echo json_encode($result);
