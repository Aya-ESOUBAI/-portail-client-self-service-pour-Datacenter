# 🏢 Système de Gestion de Datacenter

Un système complet de surveillance et de gestion de datacenter avec authentification, gestion des racks, capteurs, et alertes automatiques.

## 🚀 Fonctionnalités Principales

### 👥 **Authentification et Rôles**
- **Admin**: Accès complet à toutes les fonctionnalités
- **Direction**: Vue limitée à leurs racks et données
- Gestion des profils utilisateurs avec numéro de téléphone

### 🖥️ **Gestion des Racks**
- Ajout/suppression de racks (Admin)
- Association avec les directions
- Visualisation détaillée des composants
- Gestion des PDU et armoires de climatisation

### 📊 **Surveillance des Capteurs**
- Capteurs de température et humidité
- Capteurs électriques (consommation, tension)
- Données en temps réel
- Historique des mesures

### 🚨 **Système d'Alertes Automatiques**
- Surveillance en temps réel des seuils critiques
- Notifications par email (Mailgun) et SMS (Twilio)
- Prévention du spam (10 minutes entre alertes)
- Historique des notifications

### 📋 **Gestion des Demandes**
- Système de tickets pour les demandes
- Upload de justificatifs
- Suivi des statuts
- Génération de PDF

## 🛠️ Installation

### Prérequis
- XAMPP (Apache + MySQL + PHP)
- Node.js et npm
- Comptes Mailgun et Twilio (pour les alertes)

### 1. Configuration de la base de données
```bash
# Importer le schéma
mysql -u root -p < data.sql

# Créer les utilisateurs de test
php backend/create_users.php

# Mettre à jour la base pour les alertes
php backend/update_database.php

# Configurer les seuils critiques
php backend/config_seuils_critiques.php
```

### 2. Configuration des services externes
Éditez `backend/config_notifications.php` :
```php
define('MAILGUN_API_KEY', 'votre-clé-api-mailgun');
define('MAILGUN_DOMAIN', 'votre-domaine-mailgun.com');
define('TWILIO_ACCOUNT_SID', 'votre-account-sid-twilio');
define('TWILIO_AUTH_TOKEN', 'votre-auth-token-twilio');
define('TWILIO_PHONE_NUMBER', 'votre-numéro-twilio');
```

### 3. Installation du frontend
```bash
cd frontend
npm install
npm start
```

## 👤 **Comptes de Test**

### Admin
- **Email**: `admin@datacenter.com`
- **Mot de passe**: `admin123`

### Direction
- **Email**: `direction@datacenter.com`
- **Mot de passe**: `direction123`

## 📁 **Structure du Projet**

```
├── backend/                 # API PHP
│   ├── config.php          # Configuration DB
│   ├── auth.php            # Authentification
│   ├── routes.php          # Routes API
│   ├── surveillance_capteurs.php  # Alertes automatiques
│   ├── config_notifications.php   # Configuration Mailgun/Twilio
│   └── uploads/            # Fichiers uploadés
├── frontend/               # Application React
│   ├── src/
│   │   ├── components/     # Composants React
│   │   ├── pages/         # Pages principales
│   │   └── services/      # Services API
├── data.sql               # Schéma de base de données
└── GUIDE_ALERTES_AUTOMATIQUES.md  # Guide des alertes
```

## 🔧 **API Endpoints**

### Authentification
- `POST /login` - Connexion
- `POST /register` - Inscription

### Gestion des Racks
- `GET /raks-with-directions` - Liste des racks
- `POST /raks` - Créer un rack
- `DELETE /raks/{id}` - Supprimer un rack
- `GET /pdus-by-rack/{rackId}` - PDU d'un rack
- `GET /capteurelectriques-by-rack/{rackId}` - Capteurs électriques
- `GET /capteurdeclimes-by-rack/{rackId}` - Capteurs climatiques

### Alertes Automatiques
- `POST /check-sensor-thresholds` - Vérifier les seuils
- `POST /add-critical-threshold` - Ajouter un seuil
- `GET /notification-history` - Historique des notifications
- `POST /test-notifications` - Tester les services

## 🚨 **Configuration des Alertes**

### 1. Configuration du profil
1. Connectez-vous à votre compte
2. Allez dans votre profil utilisateur
3. Ajoutez votre numéro de téléphone
4. Sauvegardez

### 2. Test du système
```bash
php backend/test_alertes_automatiques.php
```

### 3. Surveillance automatique
```bash
# Vérification manuelle
curl -X POST http://localhost/backend/index.php/check-sensor-thresholds

# Cron job (recommandé)
*/5 * * * * curl -X POST http://localhost/backend/index.php/check-sensor-thresholds
```


## 🎨 **Interface Utilisateur**

- **Design moderne** avec thème sombre/clair
- **Responsive** pour tous les appareils
- **Navigation intuitive** avec rôles
- **Modales** pour les actions importantes

## 🔒 **Sécurité**

- **Authentification** par token
- **Validation** des données
- **Protection CSRF**
- **Gestion des rôles**
- **Validation des fichiers uploadés**

## 📞 **Support**

Pour toute question ou problème :
1. Consultez le guide des alertes automatiques
2. Vérifiez les logs d'erreur
3. Testez les services avec les scripts fournis

---

**🎉 Système prêt à l'emploi !** 