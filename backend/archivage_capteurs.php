<?php
require_once 'config.php';


function archiverDonneesCapteurs() {
    $pdo = getPDO();
    
    try {
        $dateLimite = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        echo "Archivage des données antérieures à : " . $dateLimite . "\n";
        
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


function getStatistiquesArchivage() {
    $pdo = getPDO();
    
    try {
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

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "=== Système d'Archivage des Capteurs ===\n\n";
    
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
