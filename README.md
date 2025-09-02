#  Portail Client Self-Service pour Datacenter

Un système complet de gestion et de surveillance de datacenter avec interface moderne et sécurisée.

## Fonctionnalités Principales

###  Gestion des Utilisateurs
- **Rôles** : Admin et Direction
- Authentification sécurisée
- Gestion des profils utilisateurs
- Tableau de bord personnalisé par rôle

###  Gestion des Équipements
- Gestion complète des racks
- Suivi des équipements (PDU, climatisation)
- Visualisation en temps réel
- Historique des données

###  Surveillance en Temps Réel
- Température et humidité
- Consommation électrique
- État des équipements
- Alertes personnalisables

## Installation

### Prérequis
- Serveur web (Apache/Nginx)
- PHP 8.0+
- MySQL 5.7+
- Node.js 16+
- Comptes Mailgun et Twilio (optionnel pour les notifications)

### Configuration Initiale

1. **Base de données**
```bash
# Importer le schéma
mysql -u root -p < database_complete.sql
```

2. **Configuration du Backend**
Copiez le fichier de configuration exemple :
```bash
cp backend/config.example.php backend/config.php
```

Éditez `backend/config.php` avec vos paramètres de base de données.

3. **Installation du Frontend**
```bash
cd frontend
npm install
npm run build
```

## Démarrage

1. Démarrer le serveur web
2. Accéder à l'application via votre navigateur
3. Se connecter avec les identifiants par défaut :
   - **Admin** : admin@techcorp.fr / password
   - **Direction** : jean.dupont@techcorp.fr / password

## Structure du Projet

```
.
├── backend/                 # API PHP
│   ├── config.php          # Configuration
│   ├── routes.php         # Routes API
│   └── uploads/           # Fichiers uploadés
├── frontend/              # Application React
│   ├── public/           # Fichiers statiques
│   └── src/              # Code source
│       ├── components/   # Composants réutilisables
│       ├── pages/        # Pages de l'application
│       └── services/     # Appels API
└── database_complete.sql  # Schéma de la base de données
```

## 🔧 API Endpoints

### Authentification
- `POST /login` - Connexion utilisateur
- `GET /me` - Profil utilisateur

### Gestion des Racks
- `GET /raks` - Liste des racks
- `POST /raks` - Créer un rack
- `GET /raks/:id` - Détails d'un rack
- `PUT /raks/:id` - Mettre à jour un rack
- `DELETE /raks/:id` - Supprimer un rack

### Surveillance
- `GET /sensors` - Données des capteurs
- `GET /alerts` - Historique des alertes
- `POST /alerts` - Créer une alerte

## Sécurité

- Authentification par JWT
- Protection CSRF
- Validation des entrées
- Gestion des rôles
- Chiffrement des données sensibles

## Support

Pour toute question ou problème :
1. Consultez la documentation technique
2. Vérifiez les logs d'erreur
3. Contactez l'équipe support
