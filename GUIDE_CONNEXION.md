# 🔐 Guide de Connexion - Système de Supervision Datacenter

## ✅ Comptes Disponibles

### 1. Compte Administrateur
- **Email :** `admin@techcorp.fr`
- **Mot de passe :** `password`
- **Rôle :** Admin
- **Accès :** Toutes les fonctionnalités

### 2. Compte Direction
- **Email :** `jean.dupont@techcorp.fr`
- **Mot de passe :** `password`
- **Rôle :** Direction
- **Accès :** Fonctionnalités limitées à sa direction

## 🚀 Solutions pour se Connecter

### Option 1 : Utiliser XAMPP (Recommandé)

1. **Démarrer XAMPP**
   - Ouvrez XAMPP Control Panel
   - Démarrez Apache et MySQL

2. **Accéder à l'application**
   - Ouvrez votre navigateur
   - Allez à : `http://localhost/3/frontend/public/index.html`
   - Ou : `http://localhost/3/frontend/`

3. **Se connecter**
   - Utilisez l'un des comptes ci-dessus

### Option 2 : Serveur de Développement

1. **Démarrer le backend**
   ```bash
   cd backend
   php -S localhost:8000
   ```

2. **Démarrer le frontend**
   ```bash
   cd frontend
   npm start
   ```

3. **Se connecter**
   - Allez à : `http://localhost:3000`
   - Utilisez l'un des comptes ci-dessus

## 🔧 Configuration de l'API

### Si vous utilisez XAMPP :
L'URL de l'API est configurée sur : `http://localhost/3/backend/index.php`

### Si vous utilisez le serveur de développement :
L'URL de l'API est configurée sur : `http://localhost:8000`

## 🐛 Dépannage

### Problème : "Service de connexion indisponible"
**Solution :** Vérifiez que le serveur backend fonctionne

### Problème : "Email ou mot de passe incorrect"
**Solution :** Utilisez exactement :
- Email : `admin@techcorp.fr` ou `jean.dupont@techcorp.fr`
- Mot de passe : `password`

### Problème : "Route non trouvée"
**Solution :** Vérifiez que l'URL de l'API est correcte dans `frontend/src/services/api.js`

### Problème : Erreur CORS
**Solution :** Assurez-vous que les headers CORS sont configurés dans le backend

## 📞 Support

Si vous rencontrez encore des problèmes :

1. Vérifiez que MySQL est démarré
2. Vérifiez que la base de données `stage` existe
3. Vérifiez que les tables sont créées avec `database_complete.sql`
4. Testez l'API avec : `php backend/test_api.php`

## ✅ Test Rapide

Pour tester rapidement l'API :
```bash
cd backend
php test_api.php
```

Vous devriez voir des tokens générés pour les deux comptes.

