CREATE TABLE direction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse TEXT NOT NULL,
    email_contact VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20) NOT NULL
);

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

CREATE TABLE rak (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    localisation VARCHAR(100) NOT NULL,
    client_id INT,
    FOREIGN KEY (client_id) REFERENCES direction(id) ON DELETE CASCADE
);

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
CREATE TABLE armoiredeclime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(50) NOT NULL,
    rak_id INT,
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE CASCADE
);

CREATE TABLE capteurelectrique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prise_id INT NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_mesure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdu_id INT,
    FOREIGN KEY (pdu_id) REFERENCES pdu(id) ON DELETE CASCADE
);
CREATE TABLE capteurdeclime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_mesure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    armoire_id INT,
    FOREIGN KEY (armoire_id) REFERENCES armoiredeclime(id) ON DELETE CASCADE
);

CREATE TABLE demande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    type ENUM('INSTALLATION', 'RETRAIT', 'ACCES') NOT NULL,
    statut ENUM('EN_ATTENTE', 'VALIDEE', 'REJETEE', 'TERMINEE') DEFAULT 'EN_ATTENTE',
    justificatif_pdf TEXT,
    utilisateur_id INT,
    direction_id INT,
    nom VARCHAR(100) DEFAULT 'Non spécifié',
    prenom VARCHAR(100) DEFAULT 'Non spécifié',
    cin VARCHAR(50) DEFAULT 'Non spécifié',
    date_acces DATE,
    nom_equipement VARCHAR(255),
    serial_number VARCHAR(100),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE SET NULL,
    FOREIGN KEY (direction_id) REFERENCES direction(id) ON DELETE CASCADE
);
CREATE TABLE seuil_alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    valeur_seuil FLOAT,
    message TEXT NOT NULL,
    rak_id INT,
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE SET NULL
);

CREATE TABLE alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rak_id INT,
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE SET NULL
);

CREATE TABLE donneeenv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('electrique', 'clime') NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source_capteur_id INT NOT NULL
);



CREATE TABLE notification_envoyee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    destinataire VARCHAR(100) NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('envoyee', 'lue', 'erreur') DEFAULT 'envoyee'
);

-- Vérification de la structure des blocks
SELECT 
    pdu_id,
    CASE 
        WHEN prise_id <= 8 THEN 'Block A'
        ELSE 'Block B'
    END as block,
    COUNT(*) as nombre_prises,
    AVG(valeur) as intensite_moyenne
FROM capteurelectrique 
WHERE pdu_id = 1
GROUP BY pdu_id, CASE WHEN prise_id <= 8 THEN 'Block A' ELSE 'Block B' END
ORDER BY pdu_id, block;

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

