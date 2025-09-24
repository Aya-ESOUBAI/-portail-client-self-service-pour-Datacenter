script complet pour Windows: 
1-Installer XAMPP manuellement . 
2-Sauvegarder ce script dans un fichier install.bat. 
3-Lancer le script. 

@echo off
echo ===============================
echo  Installation Environnement Projet Datacenter
echo ===============================

REM Vérification de Node.js
node -v >nul 2>&1
IF ERRORLEVEL 1 (
    echo  Node.js n'est pas installé.
    echo  Téléchargez et installez Node.js depuis https://nodejs.org/
) ELSE (
    echo  Node.js est déjà installé.
)

REM Vérification de npm
npm -v >nul 2>&1
IF ERRORLEVEL 1 (
    echo  npm n'est pas installé.
) ELSE (
    echo  npm est déjà installé.
)

REM Vérification de Composer
composer -V >nul 2>&1
IF ERRORLEVEL 1 (
    echo  Composer n'est pas installé.
    echo  Téléchargez-le depuis https://getcomposer.org/
) ELSE (
    echo  Composer est déjà installé.
)

REM Installation de Laravel (si non présent)
composer global show laravel/installer >nul 2>&1
IF ERRORLEVEL 1 (
    echo  Installation de Laravel...
    composer global require laravel/installer
) ELSE (
    echo  Laravel déjà installé.
)

REM Vérification du dossier backend (Laravel)
if not exist backend (
    echo  Création du projet backend Laravel...
    laravel new backend
) else (
    echo  Projet backend déjà présent.
)

REM Vérification du dossier frontend (React)
if not exist frontend (
    echo  Création du projet frontend React...
    npx create-react-app frontend
    cd frontend
    npm install axios recharts react-router-dom
    cd ..
) else (
    echo  Projet frontend déjà présent.
)

REM Installation de FPDF dans Laravel
cd backend
composer require setasign/fpdf
cd ..

echo ===============================
echo  Installation terminée !
echo  Étapes suivantes :
echo 1. Lancez XAMPP et démarrez Apache + MySQL.
echo 2. Placez le dossier backend dans C:\xampp\htdocs\ pour l'API Laravel.
echo 3. Lancez le frontend avec: cd frontend && npm start
echo ===============================
pause
