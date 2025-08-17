# 🔧 Guide de Dépannage - Alertes non affichées

## ✅ **Problème identifié et résolu**

### **Cause du problème :**
- Incohérence entre la structure de la table `alerte` dans la base de données et le code PHP
- Le code utilisait des champs inexistants comme `date_alert`, `valeur_detectee`, `seuil_critique`
- La table `alerte` a les champs : `id`, `type`, `message`, `statut`, `date_creation`, `rack_id`

### **Solutions appliquées :**

#### 1. **Correction du fichier `backend/alerte.php`**
- ✅ Ligne 5 : Changement de `date_alert` vers `date_creation`
- ✅ Ligne 17 : Changement de `type` vers `type_capteur` pour les seuils
- ✅ Ligne 19 : Changement de `valeur_seuil` vers `valeur_max`
- ✅ Fonction `store()` : Correction pour utiliser les bons champs
- ✅ Fonction `getAlertThresholds()` : Correction du nom de colonne

#### 2. **Correction du frontend**
- ✅ `frontend/src/pages/Alertes.js` : Correction de `date_alert` vers `date_creation`
- ✅ `frontend/src/pages/Dashboard.js` : Correction de `date_alert` vers `date_creation`

## 🔍 **Vérification que les corrections fonctionnent**

### **Test 1 : Vérifier la base de données**
```bash
cd backend
php test_alertes_api.php
```

**Résultat attendu :**
- ✅ 7 alertes trouvées
- ✅ 6 seuils trouvés
- ✅ Données correctement formatées

### **Test 2 : Vérifier l'API via le navigateur**
1. Ouvrez les outils de développement (F12)
2. Allez dans l'onglet "Console"
3. Connectez-vous à l'application
4. Allez dans la page "Alertes"
5. Vérifiez s'il y a des erreurs dans la console

### **Test 3 : Vérifier la requête API**
1. Dans les outils de développement, onglet "Network"
2. Rechargez la page "Alertes"
3. Cherchez la requête vers `/alertes`
4. Vérifiez la réponse

## 🚀 **Solutions si les alertes ne s'affichent toujours pas**

### **Solution 1 : Vider le cache du navigateur**
1. Appuyez sur `Ctrl + F5` pour forcer le rechargement
2. Ou videz le cache du navigateur

### **Solution 2 : Vérifier l'authentification**
1. Déconnectez-vous et reconnectez-vous
2. Vérifiez que le token est bien stocké dans le localStorage

### **Solution 3 : Vérifier les logs**
1. Ouvrez la console du navigateur
2. Regardez s'il y a des erreurs JavaScript
3. Vérifiez les erreurs dans les outils de développement

### **Solution 4 : Test direct de l'API**
```bash
# Avec curl (si disponible)
curl -H "Authorization: Bearer VOTRE_TOKEN" http://localhost/3/backend/index.php/alertes

# Ou avec PowerShell
Invoke-WebRequest -Uri "http://localhost/3/backend/index.php/alertes" -Headers @{"Authorization"="Bearer VOTRE_TOKEN"}
```

## 📊 **Données attendues**

### **Alertes dans la base :**
- ID 1: Température élevée détectée: 27.5°C (active)
- ID 2: Intensité électrique élevée: 18.2A (active)
- ID 3: Humidité basse détectée: 38.5% (resolue)
- ID 4: Température élevée détectée: 26.8°C (active)
- ID 5: Intensité électrique élevée: 17.8A (active)
- ID 6: Humidité élevée détectée: 51.2% (resolue)
- ID 7: Température basse détectée: 19.5°C (active)

### **Seuils dans la base :**
- Température: 18.0°C - 28.0°C
- Humidité: 40.0% - 60.0%
- Intensité: 0.0A - 20.0A

## 🎯 **Résultat final attendu**

Après les corrections, vous devriez voir :
- ✅ **Page Alertes** : Liste des 7 alertes avec leurs détails
- ✅ **Dashboard** : Alertes récentes affichées
- ✅ **Dates** : Affichage correct des dates de création
- ✅ **Statuts** : Couleurs correctes (rouge pour actif, vert pour résolu)

## 📞 **Support**

Si le problème persiste :
1. Vérifiez que XAMPP est démarré (Apache + MySQL)
2. Vérifiez que la base de données contient bien les données
3. Testez l'API avec `php backend/test_alertes_api.php`
4. Vérifiez les logs d'erreur de XAMPP

