<?php
// Configuration de la connexion à la base de données MySQL

define('DB_HOST', 'localhost');
define('DB_NAME', 'stage'); // Nom de la base de données mis à jour
define('DB_USER', 'root'); // À adapter
define('DB_PASS', ''); // À adapter

function getPDO() {
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur connexion BDD', 'details' => $e->getMessage()]);
        exit;
    }
}
