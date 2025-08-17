-- =====================================================
-- BASE DE DONNÉES COMPLÈTE POUR SUPERVISION DATACENTER
-- Direction ID: 1, Utilisateur ID: 5
-- Structure: 2 racks, 4 PDU (2 par rack), 16 prises par PDU (8 par block)
-- =====================================================

-- Suppression des tables existantes
DROP TABLE IF EXISTS notification_envoyee;
DROP TABLE IF EXISTS dashboard;
DROP TABLE IF EXISTS message_automatique;
DROP TABLE IF EXISTS seuil_alerte;
DROP TABLE IF EXISTS alerte;
DROP TABLE IF EXISTS demande;
DROP TABLE IF EXISTS donneeenv;
DROP TABLE IF EXISTS capteurdeclime;
DROP TABLE IF EXISTS capteurelectrique;
DROP TABLE IF EXISTS armoiredeclime;
DROP TABLE IF EXISTS pdu;
DROP TABLE IF EXISTS rak;
DROP TABLE IF EXISTS utilisateur;
DROP TABLE IF EXISTS direction;

-- =====================================================
-- 1. CRÉATION DE LA DIRECTION (ID: 1)
-- =====================================================
CREATE TABLE direction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse TEXT NOT NULL,
    email_contact VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL
);

INSERT INTO direction (id, nom, adresse, email_contact, telephone) VALUES
(1, 'TechCorp Solutions', '123 Rue de l\'Innovation, 75001 Paris', 'contact@techcorp.fr', '01 42 34 56 78');

-- =====================================================
-- 2. CRÉATION DE L'UTILISATEUR (ID: 5)
-- =====================================================
CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Direction') NOT NULL,
    telephone VARCHAR(20),
    direction_id INT,
    FOREIGN KEY (direction_id) REFERENCES direction(id) ON DELETE SET NULL
);

INSERT INTO utilisateur (id, nom, email, mot_de_passe, role, telephone, direction_id) VALUES
(1, 'Admin System', 'admin@techcorp.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '01 23 45 67 89', NULL),
(5, 'Jean Dupont', 'jean.dupont@techcorp.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Direction', '06 12 34 56 78', 1);

-- =====================================================
-- 3. CRÉATION DES RACKS (2 racks)
-- =====================================================
CREATE TABLE rak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    localisation VARCHAR(100) NOT NULL,
    client_id INT,
    FOREIGN KEY (client_id) REFERENCES direction(id) ON DELETE CASCADE
);

INSERT INTO rak (id, nom, localisation, client_id) VALUES
(1, 'RACK-A1', 'Salle A - Zone 1', 1),
(2, 'RACK-A2', 'Salle A - Zone 2', 1);

-- =====================================================
-- 4. CRÉATION DES PDU (2 PDU par rack = 4 PDU total)
-- =====================================================
CREATE TABLE pdu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(50) NOT NULL,
    nombre_prises INT NOT NULL CHECK (nombre_prises > 0),
    type_connexion VARCHAR(50) NOT NULL,
    rak_id INT,
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE CASCADE,
    ip_address VARCHAR(15) ,
    snmp_community VARCHAR(50) DEFAULT 'public' 
);

INSERT INTO pdu (id, marque, modele, nombre_prises, type_connexion, rak_id) VALUES
(1, 'Schneider Electric', 'APC AP8959', 16, 'C13/C14', 1),  -- RACK-A1, PDU 1
(2, 'Schneider Electric', 'APC AP8959', 16, 'C13/C14', 1),  -- RACK-A1, PDU 2
(3, 'Eaton', 'ePDU G3', 16, 'C13/C14', 2),                  -- RACK-A2, PDU 1
(4, 'Eaton', 'ePDU G3', 16, 'C13/C14', 2);                  -- RACK-A2, PDU 2

-- =====================================================
-- 5. CRÉATION DES ARMOIRES DE CLIMATISATION
-- =====================================================
CREATE TABLE armoiredeclime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(50) NOT NULL,
    rak_id INT,
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE CASCADE
);

INSERT INTO armoiredeclime (id, marque, modele, rak_id) VALUES
(1, 'Liebert', 'Vertiv GXT4', 1),
(2, 'Liebert', 'Vertiv GXT4', 2);

-- =====================================================
-- 6. CRÉATION DES CAPTEURS ÉLECTRIQUES
-- Structure: 16 prises par PDU (8 prises par block)
-- Block A: prises 1-8, Block B: prises 9-16
-- =====================================================
CREATE TABLE capteurelectrique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prise_id INT NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_mesure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdu_id INT,
    FOREIGN KEY (pdu_id) REFERENCES pdu(id) ON DELETE CASCADE
);

-- Données de test pour les capteurs électriques (24h de données)
-- RACK-A1, PDU 1 (prises 1-16)
INSERT INTO capteurelectrique (prise_id, valeur, unite, date_mesure, pdu_id) VALUES
-- Block A - Prise 1
(1, 12.5, 'A', '2024-01-15 00:00:00', 1),
(1, 13.2, 'A', '2024-01-15 01:00:00', 1),
(1, 14.1, 'A', '2024-01-15 02:00:00', 1),
(1, 13.8, 'A', '2024-01-15 03:00:00', 1),
(1, 12.9, 'A', '2024-01-15 04:00:00', 1),
(1, 13.5, 'A', '2024-01-15 05:00:00', 1),
(1, 14.2, 'A', '2024-01-15 06:00:00', 1),
(1, 15.1, 'A', '2024-01-15 07:00:00', 1),
(1, 16.3, 'A', '2024-01-15 08:00:00', 1),
(1, 17.2, 'A', '2024-01-15 09:00:00', 1),
(1, 18.1, 'A', '2024-01-15 10:00:00', 1),
(1, 17.8, 'A', '2024-01-15 11:00:00', 1),
(1, 16.9, 'A', '2024-01-15 12:00:00', 1),
(1, 15.7, 'A', '2024-01-15 13:00:00', 1),
(1, 14.8, 'A', '2024-01-15 14:00:00', 1),
(1, 13.9, 'A', '2024-01-15 15:00:00', 1),
(1, 13.2, 'A', '2024-01-15 16:00:00', 1),
(1, 12.8, 'A', '2024-01-15 17:00:00', 1),
(1, 12.1, 'A', '2024-01-15 18:00:00', 1),
(1, 11.9, 'A', '2024-01-15 19:00:00', 1),
(1, 11.5, 'A', '2024-01-15 20:00:00', 1),
(1, 11.2, 'A', '2024-01-15 21:00:00', 1),
(1, 10.8, 'A', '2024-01-15 22:00:00', 1),
(1, 10.5, 'A', '2024-01-15 23:00:00', 1),

-- Block A - Prise 2
(2, 11.8, 'A', '2024-01-15 00:00:00', 1),
(2, 12.1, 'A', '2024-01-15 01:00:00', 1),
(2, 12.9, 'A', '2024-01-15 02:00:00', 1),
(2, 13.2, 'A', '2024-01-15 03:00:00', 1),
(2, 12.8, 'A', '2024-01-15 04:00:00', 1),
(2, 13.1, 'A', '2024-01-15 05:00:00', 1),
(2, 13.8, 'A', '2024-01-15 06:00:00', 1),
(2, 14.5, 'A', '2024-01-15 07:00:00', 1),
(2, 15.2, 'A', '2024-01-15 08:00:00', 1),
(2, 16.1, 'A', '2024-01-15 09:00:00', 1),
(2, 16.8, 'A', '2024-01-15 10:00:00', 1),
(2, 16.3, 'A', '2024-01-15 11:00:00', 1),
(2, 15.7, 'A', '2024-01-15 12:00:00', 1),
(2, 14.9, 'A', '2024-01-15 13:00:00', 1),
(2, 14.2, 'A', '2024-01-15 14:00:00', 1),
(2, 13.5, 'A', '2024-01-15 15:00:00', 1),
(2, 12.9, 'A', '2024-01-15 16:00:00', 1),
(2, 12.3, 'A', '2024-01-15 17:00:00', 1),
(2, 11.8, 'A', '2024-01-15 18:00:00', 1),
(2, 11.4, 'A', '2024-01-15 19:00:00', 1),
(2, 11.1, 'A', '2024-01-15 20:00:00', 1),
(2, 10.7, 'A', '2024-01-15 21:00:00', 1),
(2, 10.4, 'A', '2024-01-15 22:00:00', 1),
(2, 10.1, 'A', '2024-01-15 23:00:00', 1),

-- Block A - Prise 3
(3, 13.2, 'A', '2024-01-15 00:00:00', 1),
(3, 13.8, 'A', '2024-01-15 01:00:00', 1),
(3, 14.5, 'A', '2024-01-15 02:00:00', 1),
(3, 14.1, 'A', '2024-01-15 03:00:00', 1),
(3, 13.7, 'A', '2024-01-15 04:00:00', 1),
(3, 14.2, 'A', '2024-01-15 05:00:00', 1),
(3, 14.9, 'A', '2024-01-15 06:00:00', 1),
(3, 15.6, 'A', '2024-01-15 07:00:00', 1),
(3, 16.3, 'A', '2024-01-15 08:00:00', 1),
(3, 17.1, 'A', '2024-01-15 09:00:00', 1),
(3, 17.8, 'A', '2024-01-15 10:00:00', 1),
(3, 17.3, 'A', '2024-01-15 11:00:00', 1),
(3, 16.7, 'A', '2024-01-15 12:00:00', 1),
(3, 15.9, 'A', '2024-01-15 13:00:00', 1),
(3, 15.2, 'A', '2024-01-15 14:00:00', 1),
(3, 14.5, 'A', '2024-01-15 15:00:00', 1),
(3, 13.9, 'A', '2024-01-15 16:00:00', 1),
(3, 13.3, 'A', '2024-01-15 17:00:00', 1),
(3, 12.8, 'A', '2024-01-15 18:00:00', 1),
(3, 12.4, 'A', '2024-01-15 19:00:00', 1),
(3, 12.1, 'A', '2024-01-15 20:00:00', 1),
(3, 11.7, 'A', '2024-01-15 21:00:00', 1),
(3, 11.4, 'A', '2024-01-15 22:00:00', 1),
(3, 11.1, 'A', '2024-01-15 23:00:00', 1),

-- Block A - Prise 4
(4, 12.8, 'A', '2024-01-15 00:00:00', 1),
(4, 13.1, 'A', '2024-01-15 01:00:00', 1),
(4, 13.7, 'A', '2024-01-15 02:00:00', 1),
(4, 13.4, 'A', '2024-01-15 03:00:00', 1),
(4, 13.0, 'A', '2024-01-15 04:00:00', 1),
(4, 13.5, 'A', '2024-01-15 05:00:00', 1),
(4, 14.2, 'A', '2024-01-15 06:00:00', 1),
(4, 14.9, 'A', '2024-01-15 07:00:00', 1),
(4, 15.6, 'A', '2024-01-15 08:00:00', 1),
(4, 16.4, 'A', '2024-01-15 09:00:00', 1),
(4, 17.1, 'A', '2024-01-15 10:00:00', 1),
(4, 16.6, 'A', '2024-01-15 11:00:00', 1),
(4, 16.0, 'A', '2024-01-15 12:00:00', 1),
(4, 15.2, 'A', '2024-01-15 13:00:00', 1),
(4, 14.5, 'A', '2024-01-15 14:00:00', 1),
(4, 13.8, 'A', '2024-01-15 15:00:00', 1),
(4, 13.2, 'A', '2024-01-15 16:00:00', 1),
(4, 12.6, 'A', '2024-01-15 17:00:00', 1),
(4, 12.1, 'A', '2024-01-15 18:00:00', 1),
(4, 11.7, 'A', '2024-01-15 19:00:00', 1),
(4, 11.4, 'A', '2024-01-15 20:00:00', 1),
(4, 11.0, 'A', '2024-01-15 21:00:00', 1),
(4, 10.7, 'A', '2024-01-15 22:00:00', 1),
(4, 10.4, 'A', '2024-01-15 23:00:00', 1);

-- =====================================================
-- 7. CRÉATION DES CAPTEURS DE CLIMATISATION
-- =====================================================
CREATE TABLE capteurdeclime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_mesure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    armoire_id INT,
    FOREIGN KEY (armoire_id) REFERENCES armoiredeclime(id) ON DELETE CASCADE
);

-- Données de test pour les capteurs de climatisation
INSERT INTO capteurdeclime (type, valeur, unite, date_mesure, armoire_id) VALUES
-- Température RACK-A1
('temperature', 22.5, '°C', '2024-01-15 00:00:00', 1),
('temperature', 22.8, '°C', '2024-01-15 01:00:00', 1),
('temperature', 23.1, '°C', '2024-01-15 02:00:00', 1),
('temperature', 23.4, '°C', '2024-01-15 03:00:00', 1),
('temperature', 23.7, '°C', '2024-01-15 04:00:00', 1),
('temperature', 24.0, '°C', '2024-01-15 05:00:00', 1),
('temperature', 24.3, '°C', '2024-01-15 06:00:00', 1),
('temperature', 24.6, '°C', '2024-01-15 07:00:00', 1),
('temperature', 24.9, '°C', '2024-01-15 08:00:00', 1),
('temperature', 25.2, '°C', '2024-01-15 09:00:00', 1),
('temperature', 25.5, '°C', '2024-01-15 10:00:00', 1),
('temperature', 25.8, '°C', '2024-01-15 11:00:00', 1),
('temperature', 26.1, '°C', '2024-01-15 12:00:00', 1),
('temperature', 26.4, '°C', '2024-01-15 13:00:00', 1),
('temperature', 26.7, '°C', '2024-01-15 14:00:00', 1),
('temperature', 27.0, '°C', '2024-01-15 15:00:00', 1),
('temperature', 26.7, '°C', '2024-01-15 16:00:00', 1),
('temperature', 26.4, '°C', '2024-01-15 17:00:00', 1),
('temperature', 26.1, '°C', '2024-01-15 18:00:00', 1),
('temperature', 25.8, '°C', '2024-01-15 19:00:00', 1),
('temperature', 25.5, '°C', '2024-01-15 20:00:00', 1),
('temperature', 25.2, '°C', '2024-01-15 21:00:00', 1),
('temperature', 24.9, '°C', '2024-01-15 22:00:00', 1),
('temperature', 24.6, '°C', '2024-01-15 23:00:00', 1),

-- Humidité RACK-A1
('humidite', 45.2, '%', '2024-01-15 00:00:00', 1),
('humidite', 45.5, '%', '2024-01-15 01:00:00', 1),
('humidite', 45.8, '%', '2024-01-15 02:00:00', 1),
('humidite', 46.1, '%', '2024-01-15 03:00:00', 1),
('humidite', 46.4, '%', '2024-01-15 04:00:00', 1),
('humidite', 46.7, '%', '2024-01-15 05:00:00', 1),
('humidite', 47.0, '%', '2024-01-15 06:00:00', 1),
('humidite', 47.3, '%', '2024-01-15 07:00:00', 1),
('humidite', 47.6, '%', '2024-01-15 08:00:00', 1),
('humidite', 47.9, '%', '2024-01-15 09:00:00', 1),
('humidite', 48.2, '%', '2024-01-15 10:00:00', 1),
('humidite', 48.5, '%', '2024-01-15 11:00:00', 1),
('humidite', 48.8, '%', '2024-01-15 12:00:00', 1),
('humidite', 49.1, '%', '2024-01-15 13:00:00', 1),
('humidite', 49.4, '%', '2024-01-15 14:00:00', 1),
('humidite', 49.7, '%', '2024-01-15 15:00:00', 1),
('humidite', 49.4, '%', '2024-01-15 16:00:00', 1),
('humidite', 49.1, '%', '2024-01-15 17:00:00', 1),
('humidite', 48.8, '%', '2024-01-15 18:00:00', 1),
('humidite', 48.5, '%', '2024-01-15 19:00:00', 1),
('humidite', 48.2, '%', '2024-01-15 20:00:00', 1),
('humidite', 47.9, '%', '2024-01-15 21:00:00', 1),
('humidite', 47.6, '%', '2024-01-15 22:00:00', 1),
('humidite', 47.3, '%', '2024-01-15 23:00:00', 1),

-- Température air entrant RACK-A1
('temperature_entrant', 20.5, '°C', '2024-01-15 00:00:00', 1),
('temperature_entrant', 20.8, '°C', '2024-01-15 01:00:00', 1),
('temperature_entrant', 21.1, '°C', '2024-01-15 02:00:00', 1),
('temperature_entrant', 21.4, '°C', '2024-01-15 03:00:00', 1),
('temperature_entrant', 21.7, '°C', '2024-01-15 04:00:00', 1),
('temperature_entrant', 22.0, '°C', '2024-01-15 05:00:00', 1),
('temperature_entrant', 22.3, '°C', '2024-01-15 06:00:00', 1),
('temperature_entrant', 22.6, '°C', '2024-01-15 07:00:00', 1),
('temperature_entrant', 22.9, '°C', '2024-01-15 08:00:00', 1),
('temperature_entrant', 23.2, '°C', '2024-01-15 09:00:00', 1),
('temperature_entrant', 23.5, '°C', '2024-01-15 10:00:00', 1),
('temperature_entrant', 23.8, '°C', '2024-01-15 11:00:00', 1),
('temperature_entrant', 24.1, '°C', '2024-01-15 12:00:00', 1),
('temperature_entrant', 24.4, '°C', '2024-01-15 13:00:00', 1),
('temperature_entrant', 24.7, '°C', '2024-01-15 14:00:00', 1),
('temperature_entrant', 25.0, '°C', '2024-01-15 15:00:00', 1),
('temperature_entrant', 24.7, '°C', '2024-01-15 16:00:00', 1),
('temperature_entrant', 24.4, '°C', '2024-01-15 17:00:00', 1),
('temperature_entrant', 24.1, '°C', '2024-01-15 18:00:00', 1),
('temperature_entrant', 23.8, '°C', '2024-01-15 19:00:00', 1),
('temperature_entrant', 23.5, '°C', '2024-01-15 20:00:00', 1),
('temperature_entrant', 23.2, '°C', '2024-01-15 21:00:00', 1),
('temperature_entrant', 22.9, '°C', '2024-01-15 22:00:00', 1),
('temperature_entrant', 22.6, '°C', '2024-01-15 23:00:00', 1),

-- Température air sortant RACK-A1
('temperature_sortant', 25.5, '°C', '2024-01-15 00:00:00', 1),
('temperature_sortant', 25.8, '°C', '2024-01-15 01:00:00', 1),
('temperature_sortant', 26.1, '°C', '2024-01-15 02:00:00', 1),
('temperature_sortant', 26.4, '°C', '2024-01-15 03:00:00', 1),
('temperature_sortant', 26.7, '°C', '2024-01-15 04:00:00', 1),
('temperature_sortant', 27.0, '°C', '2024-01-15 05:00:00', 1),
('temperature_sortant', 27.3, '°C', '2024-01-15 06:00:00', 1),
('temperature_sortant', 27.6, '°C', '2024-01-15 07:00:00', 1),
('temperature_sortant', 27.9, '°C', '2024-01-15 08:00:00', 1),
('temperature_sortant', 28.2, '°C', '2024-01-15 09:00:00', 1),
('temperature_sortant', 28.5, '°C', '2024-01-15 10:00:00', 1),
('temperature_sortant', 28.8, '°C', '2024-01-15 11:00:00', 1),
('temperature_sortant', 29.1, '°C', '2024-01-15 12:00:00', 1),
('temperature_sortant', 29.4, '°C', '2024-01-15 13:00:00', 1),
('temperature_sortant', 29.7, '°C', '2024-01-15 14:00:00', 1),
('temperature_sortant', 30.0, '°C', '2024-01-15 15:00:00', 1),
('temperature_sortant', 29.7, '°C', '2024-01-15 16:00:00', 1),
('temperature_sortant', 29.4, '°C', '2024-01-15 17:00:00', 1),
('temperature_sortant', 29.1, '°C', '2024-01-15 18:00:00', 1),
('temperature_sortant', 28.8, '°C', '2024-01-15 19:00:00', 1),
('temperature_sortant', 28.5, '°C', '2024-01-15 20:00:00', 1),
('temperature_sortant', 28.2, '°C', '2024-01-15 21:00:00', 1),
('temperature_sortant', 27.9, '°C', '2024-01-15 22:00:00', 1),
('temperature_sortant', 27.6, '°C', '2024-01-15 23:00:00', 1),

-- Température RACK-A2
('temperature', 21.5, '°C', '2024-01-15 00:00:00', 2),
('temperature', 21.8, '°C', '2024-01-15 01:00:00', 2),
('temperature', 22.1, '°C', '2024-01-15 02:00:00', 2),
('temperature', 22.4, '°C', '2024-01-15 03:00:00', 2),
('temperature', 22.7, '°C', '2024-01-15 04:00:00', 2),
('temperature', 23.0, '°C', '2024-01-15 05:00:00', 2),
('temperature', 23.3, '°C', '2024-01-15 06:00:00', 2),
('temperature', 23.6, '°C', '2024-01-15 07:00:00', 2),
('temperature', 23.9, '°C', '2024-01-15 08:00:00', 2),
('temperature', 24.2, '°C', '2024-01-15 09:00:00', 2),
('temperature', 24.5, '°C', '2024-01-15 10:00:00', 2),
('temperature', 24.8, '°C', '2024-01-15 11:00:00', 2),
('temperature', 25.1, '°C', '2024-01-15 12:00:00', 2),
('temperature', 25.4, '°C', '2024-01-15 13:00:00', 2),
('temperature', 25.7, '°C', '2024-01-15 14:00:00', 2),
('temperature', 26.0, '°C', '2024-01-15 15:00:00', 2),
('temperature', 25.7, '°C', '2024-01-15 16:00:00', 2),
('temperature', 25.4, '°C', '2024-01-15 17:00:00', 2),
('temperature', 25.1, '°C', '2024-01-15 18:00:00', 2),
('temperature', 24.8, '°C', '2024-01-15 19:00:00', 2),
('temperature', 24.5, '°C', '2024-01-15 20:00:00', 2),
('temperature', 24.2, '°C', '2024-01-15 21:00:00', 2),
('temperature', 23.9, '°C', '2024-01-15 22:00:00', 2),
('temperature', 23.6, '°C', '2024-01-15 23:00:00', 2),

-- Humidité RACK-A2
('humidite', 42.2, '%', '2024-01-15 00:00:00', 2),
('humidite', 42.5, '%', '2024-01-15 01:00:00', 2),
('humidite', 42.8, '%', '2024-01-15 02:00:00', 2),
('humidite', 43.1, '%', '2024-01-15 03:00:00', 2),
('humidite', 43.4, '%', '2024-01-15 04:00:00', 2),
('humidite', 43.7, '%', '2024-01-15 05:00:00', 2),
('humidite', 44.0, '%', '2024-01-15 06:00:00', 2),
('humidite', 44.3, '%', '2024-01-15 07:00:00', 2),
('humidite', 44.6, '%', '2024-01-15 08:00:00', 2),
('humidite', 44.9, '%', '2024-01-15 09:00:00', 2),
('humidite', 45.2, '%', '2024-01-15 10:00:00', 2),
('humidite', 45.5, '%', '2024-01-15 11:00:00', 2),
('humidite', 45.8, '%', '2024-01-15 12:00:00', 2),
('humidite', 46.1, '%', '2024-01-15 13:00:00', 2),
('humidite', 46.4, '%', '2024-01-15 14:00:00', 2),
('humidite', 46.7, '%', '2024-01-15 15:00:00', 2),
('humidite', 46.4, '%', '2024-01-15 16:00:00', 2),
('humidite', 46.1, '%', '2024-01-15 17:00:00', 2),
('humidite', 45.8, '%', '2024-01-15 18:00:00', 2),
('humidite', 45.5, '%', '2024-01-15 19:00:00', 2),
('humidite', 45.2, '%', '2024-01-15 20:00:00', 2),
('humidite', 44.9, '%', '2024-01-15 21:00:00', 2),
('humidite', 44.6, '%', '2024-01-15 22:00:00', 2),
('humidite', 44.3, '%', '2024-01-15 23:00:00', 2),

-- Température air entrant RACK-A2
('temperature_entrant', 19.5, '°C', '2024-01-15 00:00:00', 2),
('temperature_entrant', 19.8, '°C', '2024-01-15 01:00:00', 2),
('temperature_entrant', 20.1, '°C', '2024-01-15 02:00:00', 2),
('temperature_entrant', 20.4, '°C', '2024-01-15 03:00:00', 2),
('temperature_entrant', 20.7, '°C', '2024-01-15 04:00:00', 2),
('temperature_entrant', 21.0, '°C', '2024-01-15 05:00:00', 2),
('temperature_entrant', 21.3, '°C', '2024-01-15 06:00:00', 2),
('temperature_entrant', 21.6, '°C', '2024-01-15 07:00:00', 2),
('temperature_entrant', 21.9, '°C', '2024-01-15 08:00:00', 2),
('temperature_entrant', 22.2, '°C', '2024-01-15 09:00:00', 2),
('temperature_entrant', 22.5, '°C', '2024-01-15 10:00:00', 2),
('temperature_entrant', 22.8, '°C', '2024-01-15 11:00:00', 2),
('temperature_entrant', 23.1, '°C', '2024-01-15 12:00:00', 2),
('temperature_entrant', 23.4, '°C', '2024-01-15 13:00:00', 2),
('temperature_entrant', 23.7, '°C', '2024-01-15 14:00:00', 2),
('temperature_entrant', 24.0, '°C', '2024-01-15 15:00:00', 2),
('temperature_entrant', 23.7, '°C', '2024-01-15 16:00:00', 2),
('temperature_entrant', 23.4, '°C', '2024-01-15 17:00:00', 2),
('temperature_entrant', 23.1, '°C', '2024-01-15 18:00:00', 2),
('temperature_entrant', 22.8, '°C', '2024-01-15 19:00:00', 2),
('temperature_entrant', 22.5, '°C', '2024-01-15 20:00:00', 2),
('temperature_entrant', 22.2, '°C', '2024-01-15 21:00:00', 2),
('temperature_entrant', 21.9, '°C', '2024-01-15 22:00:00', 2),
('temperature_entrant', 21.6, '°C', '2024-01-15 23:00:00', 2),

-- Température air sortant RACK-A2
('temperature_sortant', 24.5, '°C', '2024-01-15 00:00:00', 2),
('temperature_sortant', 24.8, '°C', '2024-01-15 01:00:00', 2),
('temperature_sortant', 25.1, '°C', '2024-01-15 02:00:00', 2),
('temperature_sortant', 25.4, '°C', '2024-01-15 03:00:00', 2),
('temperature_sortant', 25.7, '°C', '2024-01-15 04:00:00', 2),
('temperature_sortant', 26.0, '°C', '2024-01-15 05:00:00', 2),
('temperature_sortant', 26.3, '°C', '2024-01-15 06:00:00', 2),
('temperature_sortant', 26.6, '°C', '2024-01-15 07:00:00', 2),
('temperature_sortant', 26.9, '°C', '2024-01-15 08:00:00', 2),
('temperature_sortant', 27.2, '°C', '2024-01-15 09:00:00', 2),
('temperature_sortant', 27.5, '°C', '2024-01-15 10:00:00', 2),
('temperature_sortant', 27.8, '°C', '2024-01-15 11:00:00', 2),
('temperature_sortant', 28.1, '°C', '2024-01-15 12:00:00', 2),
('temperature_sortant', 28.4, '°C', '2024-01-15 13:00:00', 2),
('temperature_sortant', 28.7, '°C', '2024-01-15 14:00:00', 2),
('temperature_sortant', 29.0, '°C', '2024-01-15 15:00:00', 2),
('temperature_sortant', 28.7, '°C', '2024-01-15 16:00:00', 2),
('temperature_sortant', 28.4, '°C', '2024-01-15 17:00:00', 2),
('temperature_sortant', 28.1, '°C', '2024-01-15 18:00:00', 2),
('temperature_sortant', 27.8, '°C', '2024-01-15 19:00:00', 2),
('temperature_sortant', 27.5, '°C', '2024-01-15 20:00:00', 2),
('temperature_sortant', 27.2, '°C', '2024-01-15 21:00:00', 2),
('temperature_sortant', 26.9, '°C', '2024-01-15 22:00:00', 2),
('temperature_sortant', 26.6, '°C', '2024-01-15 23:00:00', 2);

-- =====================================================
-- 8. CRÉATION DES DEMANDES D'ACCÈS
-- =====================================================
CREATE TABLE demande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    type ENUM('INSTALLATION', 'RETRAIT', 'ACCES') NOT NULL,
    statut ENUM('EN_ATTENTE', 'VALIDEE', 'REJETEE', 'TERMINEE') DEFAULT 'EN_ATTENTE',
    justificatif_pdf TEXT,
    utilisateur_id INT,
    direction_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE SET NULL,
    FOREIGN KEY (direction_id) REFERENCES direction(id) ON DELETE CASCADE
);

INSERT INTO demande (type, statut, utilisateur_id, direction_id) VALUES
('ACCES', 'VALIDEE', 5, 1),
('ACCES', 'EN_ATTENTE', 5, 1),
('INSTALLATION', 'TERMINEE', 5, 1),
('RETRAIT', 'VALIDEE', 5, 1),
('ACCES', 'REJETEE', 5, 1);

-- =====================================================
-- 9. CRÉATION DES SEUILS D'ALERTE
-- =====================================================
CREATE TABLE seuil_alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_capteur VARCHAR(50) NOT NULL,
    valeur_min FLOAT,
    valeur_max FLOAT,
    message TEXT NOT NULL,
    rack_id INT,
    FOREIGN KEY (rack_id) REFERENCES rak(id) ON DELETE SET NULL
);

INSERT INTO seuil_alerte (type_capteur, valeur_min, valeur_max, message, rack_id) VALUES
('temperature', 18.0, 28.0, 'Température hors limites', 1),
('humidite', 40.0, 60.0, 'Humidité hors limites', 1),
('intensite', 0.0, 20.0, 'Intensité électrique critique', 1);

-- =====================================================
-- 10. CRÉATION DES ALERTES
-- =====================================================
CREATE TABLE alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    statut VARCHAR(20) DEFAULT 'active',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rack_id INT,
    FOREIGN KEY (rack_id) REFERENCES rak(id) ON DELETE SET NULL
);

INSERT INTO alerte (type, message, statut, rack_id) VALUES
('temperature', 'Température élevée détectée: 27.5°C', 'active', 1),
('intensite', 'Intensité électrique élevée: 18.2A', 'active', 1),
('humidite', 'Humidité basse détectée: 38.5%', 'resolue', 1);

-- =====================================================
-- 11. CRÉATION DES TABLES SUPPLÉMENTAIRES
-- =====================================================

CREATE TABLE donneeenv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('electrique', 'clime') NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source_capteur_id INT NOT NULL
);

CREATE TABLE message_automatique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    contenu TEXT NOT NULL,
    conditions TEXT,
    actif BOOLEAN DEFAULT TRUE
);

CREATE TABLE dashboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    configuration JSON,
    utilisateur_id INT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);

CREATE TABLE notification_envoyee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    destinataire VARCHAR(100) NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('envoyee', 'lue', 'erreur') DEFAULT 'envoyee'
);

-- =====================================================
-- RÉSUMÉ DE LA BASE DE DONNÉES
-- =====================================================

SELECT 'BASE DE DONNÉES CRÉÉE AVEC SUCCÈS' as message;
SELECT 'Direction ID: 1 - TechCorp Solutions' as direction_info;
SELECT 'Utilisateur ID: 5 - Jean Dupont (Direction)' as user_info;
SELECT COUNT(*) as nb_racks FROM rak;
SELECT COUNT(*) as nb_pdu FROM pdu;
SELECT COUNT(*) as nb_capteurs_electriques FROM capteurelectrique;
SELECT COUNT(*) as nb_capteurs_climatisation FROM capteurdeclime;
SELECT COUNT(*) as nb_demandes FROM demande;
SELECT COUNT(*) as nb_alertes FROM alerte;

-- =====================================================
-- CODES DE CONNEXION DES COMPTES
-- =====================================================

/*
COMPTES UTILISATEURS DISPONIBLES :

1. COMPTE ADMINISTRATEUR
   - Email: admin@techcorp.fr
   - Mot de passe: password
   - Rôle: Admin
   - ID: 1
   - Accès: Toutes les fonctionnalités du système

2. COMPTE DIRECTION
   - Email: jean.dupont@techcorp.fr
   - Mot de passe: password
   - Rôle: Direction
   - ID: 5
   - Direction: TechCorp Solutions (ID: 1)
   - Accès: Fonctionnalités limitées à sa direction

NOTES:
- Le mot de passe 'password' est hashé avec bcrypt
- L'admin n'est pas lié à une direction spécifique (direction_id = NULL)
- L'utilisateur Direction est lié à la direction TechCorp Solutions
*/
