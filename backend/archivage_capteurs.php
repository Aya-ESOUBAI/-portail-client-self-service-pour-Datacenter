<?php
require_once 'config.php';

/**
 * Système d'archivage automatique des données des capteurs
 * Transfère les données de plus d'un jour vers la table donneeenv
 */

function archiverDonneesCapteurs() {
    $pdo = getPDO();
    
    try {
        // Date limite : il y a 24 heures
        $dateLimite = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        echo "Archivage des données antérieures à : " . $dateLimite . "\n";
        
        // 1. Archiver les données des capteurs électriques
        $sqlElectrique = "
            INSERT INTO donneeenv (
                type, capteur_type, valeur, unite, date_heure, source_capteur_id, 
                capteur_id, rack_id, pdu_id, prise_id, direction_id
            )
            SELECT 
                'electrique' as type,
                CONCAT('intensite_bloc', ce.prise_id) as capteur_type,
                ce.valeur,
                ce.unite,
                ce.date_mesure as date_heure,
                ce.id as source_capteur_id,
                ce.id as capteur_id,
                r.id as rack_id,
                p.id as pdu_id,
                ce.prise_id,
                r.client_id as direction_id
            FROM capteurelectrique ce
            JOIN pdu p ON ce.pdu_id = p.id
            JOIN rak r ON p.rak_id = r.id
            WHERE ce.date_mesure < ?
        ";
        
        $stmt = $pdo->prepare($sqlElectrique);
        $stmt->execute([$dateLimite]);
        $nbElectrique = $stmt->rowCount();
        echo "Données électriques archivées : " . $nbElectrique . " enregistrements\n";
        
        // 2. Archiver les données des capteurs de climatisation
        $sqlClime = "
            INSERT INTO donneeenv (
                type, capteur_type, valeur, unite, date_heure, source_capteur_id, 
                capteur_id, rack_id, armoire_id, direction_id
            )
            SELECT 
                'clime' as type,
                cc.type as capteur_type,
                cc.valeur,
                cc.unite,
                cc.date_mesure as date_heure,
                cc.id as source_capteur_id,
                cc.id as capteur_id,
                r.id as rack_id,
                ac.id as armoire_id,
                r.client_id as direction_id
            FROM capteurdeclime cc
            JOIN armoiredeclime ac ON cc.armoire_id = ac.id
            JOIN rak r ON ac.rak_id = r.id
            WHERE cc.date_mesure < ?
        ";
        
        $stmt = $pdo->prepare($sqlClime);
        $stmt->execute([$dateLimite]);
        $nbClime = $stmt->rowCount();
        echo "Données climatisation archivées : " . $nbClime . " enregistrements\n";
        
        // 3. Supprimer les données archivées des tables originales
        if ($nbElectrique > 0) {
            $sqlDeleteElectrique = "DELETE FROM capteurelectrique WHERE date_mesure < ?";
            $stmt = $pdo->prepare($sqlDeleteElectrique);
            $stmt->execute([$dateLimite]);
            echo "Données électriques supprimées des capteurs originaux\n";
        }
        
        if ($nbClime > 0) {
            $sqlDeleteClime = "DELETE FROM capteurdeclime WHERE date_mesure < ?";
            $stmt = $pdo->prepare($sqlDeleteClime);
            $stmt->execute([$dateLimite]);
            echo "Données climatisation supprimées des capteurs originaux\n";
        }
        
        $total = $nbElectrique + $nbClime;
        echo "Archivage terminé. Total d'enregistrements archivés : " . $total . "\n";
        
        return [
            'success' => true,
            'electrique' => $nbElectrique,
            'clime' => $nbClime,
            'total' => $total
        ];
        
    } catch (Exception $e) {
        echo "Erreur lors de l'archivage : " . $e->getMessage() . "\n";
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Fonction pour obtenir les statistiques d'archivage
 */
function getStatistiquesArchivage() {
    $pdo = getPDO();
    
    try {
        // Compter les données récentes (moins de 24h)
        $dateLimite = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $sqlRecentes = "
            SELECT 
                'electrique' as type,
                COUNT(*) as count
            FROM capteurelectrique 
            WHERE date_mesure >= ?
            UNION ALL
            SELECT 
                'clime' as type,
                COUNT(*) as count
            FROM capteurdeclime 
            WHERE date_mesure >= ?
        ";
        
        $stmt = $pdo->prepare($sqlRecentes);
        $stmt->execute([$dateLimite, $dateLimite]);
        $donneesRecentes = $stmt->fetchAll();
        
        // Compter les données archivées
        $sqlArchivees = "
            SELECT 
                type,
                COUNT(*) as count
            FROM donneeenv 
            GROUP BY type
        ";
        
        $stmt = $pdo->prepare($sqlArchivees);
        $stmt->execute();
        $donneesArchivees = $stmt->fetchAll();
        
        return [
            'recentes' => $donneesRecentes,
            'archivees' => $donneesArchivees,
            'date_limite' => $dateLimite
        ];
        
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Fonction pour nettoyer les anciennes données archivées (optionnel)
 * Supprime les données de plus de X jours de la table donneeenv
 */
function nettoyerAnciennesDonneesArchivees($jours = 365) {
    $pdo = getPDO();
    
    try {
        $dateLimite = date('Y-m-d H:i:s', strtotime("-{$jours} days"));
        
        $sql = "DELETE FROM donneeenv WHERE date_heure < ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$dateLimite]);
        
        $nbSupprimes = $stmt->rowCount();
        echo "Nettoyage terminé. " . $nbSupprimes . " anciens enregistrements supprimés.\n";
        
        return [
            'success' => true,
            'supprimes' => $nbSupprimes
        ];
        
    } catch (Exception $e) {
        echo "Erreur lors du nettoyage : " . $e->getMessage() . "\n";
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Si le fichier est exécuté directement
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== Système d'Archivage des Capteurs ===\n\n";
    
    // Afficher les statistiques avant archivage
    echo "Statistiques avant archivage :\n";
    $stats = getStatistiquesArchivage();
    if (isset($stats['error'])) {
        echo "Erreur : " . $stats['error'] . "\n";
    } else {
        echo "Données récentes (< 24h) :\n";
        foreach ($stats['recentes'] as $data) {
            echo "- " . $data['type'] . " : " . $data['count'] . " enregistrements\n";
        }
        echo "Données archivées :\n";
        foreach ($stats['archivees'] as $data) {
            echo "- " . $data['type'] . " : " . $data['count'] . " enregistrements\n";
        }
    }
    
    echo "\nDébut de l'archivage...\n";
    $resultat = archiverDonneesCapteurs();
    
    if ($resultat['success']) {
        echo "\nArchivage réussi !\n";
        echo "Total archivé : " . $resultat['total'] . " enregistrements\n";
    } else {
        echo "\nErreur lors de l'archivage : " . $resultat['error'] . "\n";
    }
}
?>
