-- Création des types ENUM directement dans les colonnes (MySQL ne supporte pas CREATE TYPE)
-- Pas besoin de les déclarer séparément comme en PostgreSQL

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
    FOREIGN KEY (rak_id) REFERENCES rak(id) ON DELETE CASCADE
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

CREATE TABLE donneeenv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('electrique', 'clime') NOT NULL,
    valeur FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    date_heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    source_capteur_id INT NOT NULL
);

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

CREATE TABLE alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    statut VARCHAR(20) DEFAULT 'active',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rack_id INT,
    FOREIGN KEY (rack_id) REFERENCES rak(id) ON DELETE SET NULL
);

CREATE TABLE seuil_alerte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    valeur_seuil FLOAT NOT NULL,
    unite VARCHAR(10) NOT NULL,
    description TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE message_automatique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_alerte VARCHAR(50) NOT NULL,
    titre VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    destinataires TEXT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE dashboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    config_perso JSON NOT NULL DEFAULT ('{}'),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);

CREATE TABLE notification_envoyee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alerte_id INT,
    type_notification ENUM('email', 'sms') NOT NULL,
    destinataire VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    statut ENUM('envoyee', 'echec') DEFAULT 'envoyee',
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alerte_id) REFERENCES alerte(id) ON DELETE CASCADE
);
