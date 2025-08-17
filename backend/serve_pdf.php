<?php
// Fonction pour servir les fichiers PDF de manière sécurisée
function serve($filename) {
    // Nettoyer le nom de fichier pour éviter les attaques
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

    // Chemin vers le dossier des justificatifs
    $uploadDir = __DIR__ . '/uploads/justificatifs/';
    $filepath = $uploadDir . $filename;

    // Vérifier que le fichier existe et est dans le bon dossier
    if (!file_exists($filepath) || !is_file($filepath)) {
        http_response_code(404);
        echo 'Fichier non trouvé';
        return;
    }

    // Vérifier l'extension du fichier
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        http_response_code(400);
        echo 'Type de fichier non autorisé';
        return;
    }

    // Définir les headers pour l'affichage
    header('Content-Type: text/plain; charset=utf-8'); // Pour les fichiers texte simulés
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: public, max-age=3600');

    // Lire et afficher le contenu du fichier
    $content = file_get_contents($filepath);
    if ($content === false) {
        http_response_code(500);
        echo 'Erreur lors de la lecture du fichier';
        return;
    }

    // Afficher le contenu
    echo $content;
} 