<?php
function serve($filename) {
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);

    $uploadDir = __DIR__ . '/uploads/justificatifs/';
    $filepath = $uploadDir . $filename;

    if (!file_exists($filepath) || !is_file($filepath)) {
        http_response_code(404);
        echo 'Fichier non trouvé';
        return;
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        http_response_code(400);
        echo 'Type de fichier non autorisé';
        return;
    }

    header('Content-Type: text/plain; charset=utf-8'); 
    header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: inline; filename="' . $filename . '"');
    header('Cache-Control: public, max-age=3600');

    $content = file_get_contents($filepath);
    if ($content === false) {
        http_response_code(500);
        echo 'Erreur lors de la lecture du fichier';
        return;
    }

    echo $content;
} 