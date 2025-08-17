<?php
// Définition des routes principales de l'API

return [
    // Authentification
    ['POST', '/login', 'auth.php@login'],
    ['POST', '/register', 'auth.php@register'],

    // Utilisateurs
    ['GET', '/utilisateurs', 'utilisateur.php@index'],
    ['GET', '/utilisateurs/{id}', 'utilisateur.php@show'],
    ['POST', '/utilisateurs', 'utilisateur.php@store'],
    ['PUT', '/utilisateurs/{id}', 'utilisateur.php@update'],
    ['DELETE', '/utilisateurs/{id}', 'utilisateur.php@destroy'],

    // Directions (clients)
    ['GET', '/directions', 'direction.php@index'],
    ['GET', '/directions/{id}', 'direction.php@show'],
    ['POST', '/directions', 'direction.php@store'],
    ['PUT', '/directions/{id}', 'direction.php@update'],
    ['DELETE', '/directions/{id}', 'direction.php@destroy'],

    // Racks
    ['GET', '/raks', 'rak.php@index'],
    ['GET', '/raks/{id}', 'rak.php@show'],
    ['GET', '/raks-with-directions', 'rak.php@getRacksWithDirections'],
    ['GET', '/raks-by-user-direction/{userId}', 'rak.php@getRacksByUserDirection'],
    ['GET', '/raks-complete-data/{userId}', 'rak.php@getRacksWithCompleteData'],
    ['POST', '/raks', 'rak.php@store'],
    ['PUT', '/raks/{id}', 'rak.php@update'],
    ['DELETE', '/raks/{id}', 'rak.php@destroy'],
    
    // Armoires de climatisation
    ['GET', '/armoiresdeclime', 'armoiredeclime.php@index'],
    ['GET', '/armoiresdeclime/{id}', 'armoiredeclime.php@show'],
    ['GET', '/armoiresdeclime-by-rack/{rackId}', 'armoiredeclime.php@getByRack'],
    ['POST', '/armoiresdeclime', 'armoiredeclime.php@store'],
    ['PUT', '/armoiresdeclime/{id}', 'armoiredeclime.php@update'],
    ['DELETE', '/armoiresdeclime/{id}', 'armoiredeclime.php@destroy'],

    // PDU
    ['GET', '/pdus', 'pdu.php@index'],
    ['GET', '/pdus/{id}', 'pdu.php@show'],
    ['GET', '/pdus-by-rack/{rackId}', 'pdu.php@getByRack'],
    ['GET', '/consumption-by-user-direction/{userId}', 'pdu.php@getConsumptionByUserDirection'],
    ['POST', '/pdus', 'pdu.php@store'],
    ['PUT', '/pdus/{id}', 'pdu.php@update'],
    ['DELETE', '/pdus/{id}', 'pdu.php@destroy'],

    // Capteurs électriques
    ['GET', '/capteurelectriques', 'capteurelectrique.php@indexElectric'],
    ['GET', '/capteurelectriques/{id}', 'capteurelectrique.php@showElectric'],
    ['GET', '/capteurelectriques-by-rack/{rackId}', 'capteurelectrique.php@getByRack'],
    ['GET', '/energy-stats-by-rack', 'capteurelectrique.php@getEnergyStatsByRack'],
    ['POST', '/capteurelectriques', 'capteurelectrique.php@storeElectric'],
    ['PUT', '/capteurelectriques/{id}', 'capteurelectrique.php@updateElectric'],
    ['DELETE', '/capteurelectriques/{id}', 'capteurelectrique.php@destroyElectric'],

    // Capteurs de climatisation
    ['GET', '/capteurdeclimes', 'capteurdeclime.php@indexClimate'],
    ['GET', '/capteurdeclimes/{id}', 'capteurdeclime.php@showClimate'],
    ['GET', '/capteurdeclimes-by-rack/{rackId}', 'capteurdeclime.php@getByRack'],
    ['GET', '/temperature-stats-by-rack', 'capteurdeclime.php@getTemperatureStatsByRack'],
    ['GET', '/temperature-by-user-direction/{userId}', 'capteurdeclime.php@getTemperatureByUserDirection'],
    ['POST', '/capteurdeclimes', 'capteurdeclime.php@storeClimate'],
    ['PUT', '/capteurdeclimes/{id}', 'capteurdeclime.php@updateClimate'],
    ['DELETE', '/capteurdeclimes/{id}', 'capteurdeclime.php@destroyClimate'],

    // Données environnementales
    ['GET', '/donneeenvs', 'donneeenv.php@index'],
    ['GET', '/donneeenvs/{id}', 'donneeenv.php@show'],
    ['POST', '/donneeenvs', 'donneeenv.php@store'],
    ['PUT', '/donneeenvs/{id}', 'donneeenv.php@update'],
    ['DELETE', '/donneeenvs/{id}', 'donneeenv.php@destroy'],

    // Demandes
    ['GET', '/demandes', 'demande.php@index'],
    ['GET', '/demandes/{id}', 'demande.php@show'],
    ['POST', '/demandes', 'demande.php@store'],
    ['PUT', '/demandes/{id}', 'demande.php@update'],
    ['DELETE', '/demandes/{id}', 'demande.php@destroy'],
    ['POST', '/demandes/{id}/valider', 'demande.php@valider'],
    ['POST', '/demandes/{id}/rejeter', 'demande.php@rejeter'],
    ['POST', '/demandes/{id}/terminer', 'demande.php@terminer'],
    ['GET', '/demandes-stats', 'demande.php@getStats'],
    ['GET', '/demandes-by-direction', 'demande.php@getDemandesByDirection'],
    ['GET', '/demandes/{id}/pdf', 'demande.php@downloadPDF'],

    // Alertes
    ['GET', '/alertes', 'alerte.php@index'],
    ['GET', '/alertes/{id}', 'alerte.php@show'],
    ['GET', '/alert-thresholds', 'alerte.php@getAlertThresholds'],
    ['POST', '/alertes', 'alerte.php@store'],
    ['POST', '/alert-thresholds', 'alerte.php@storeThreshold'],
    ['POST', '/check-thresholds', 'alerte.php@checkCriticalThresholds'],
    ['PUT', '/alertes/{id}', 'alerte.php@update'],
    ['PUT', '/alert-thresholds/{id}', 'alerte.php@updateThreshold'],
    ['DELETE', '/alertes/{id}', 'alerte.php@destroy'],
    ['DELETE', '/alert-thresholds/{id}', 'alerte.php@destroyThreshold'],

    // Surveillance automatique des capteurs
    ['POST', '/check-sensor-thresholds', 'surveillance_capteurs.php@checkAllSensorThresholds'],
    ['POST', '/add-critical-threshold', 'surveillance_capteurs.php@addCriticalThreshold'],
    ['GET', '/notification-history', 'surveillance_capteurs.php@getNotificationHistory'],
    ['POST', '/test-notifications', 'surveillance_capteurs.php@testNotificationServices'],

    // Archivage automatique des données
    ['POST', '/archiver-donnees', 'archivage_capteurs.php@archiverDonneesCapteurs'],
    ['GET', '/statistiques-archivage', 'archivage_capteurs.php@getStatistiquesArchivage'],
    ['POST', '/nettoyer-anciennes-donnees', 'archivage_capteurs.php@nettoyerAnciennesDonneesArchivees'],

    // Dashboards
    ['GET', '/dashboards', 'dashboard.php@index'],
    ['GET', '/dashboards/{id}', 'dashboard.php@show'],
    ['POST', '/dashboards', 'dashboard.php@store'],
    ['PUT', '/dashboards/{id}', 'dashboard.php@update'],
    ['DELETE', '/dashboards/{id}', 'dashboard.php@destroy'],

    // Fichiers PDF
    ['GET', '/pdf/{file}', 'serve_pdf.php@serve'],
];
