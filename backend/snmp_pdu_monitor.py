#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script de supervision SNMP pour les PDU APC
Stocke directement les mesures dans la table capteurelectrique

Configuration requise:
- Python 3.6+
- Modules: pysnmp, mysql-connector-python, configparser, logging.handlers

Installation:
pip install pysnmp mysql-connector-python

Utilisation:
1. Configurez le fichier snmp_config.ini avec vos paramètres
2. Exécutez le script: python snmp_pdu_monitor.py
"""

import os
import sys
import time
import configparser
import logging
from logging.handlers import RotatingFileHandler
from datetime import datetime, time as dtime
from pysnmp.hlapi import *
import mysql.connector
from mysql.connector import Error

def load_config():
    """Charge la configuration depuis le fichier snmp_config.ini"""
    config = configparser.ConfigParser()
    config_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'snmp_config.ini')
    
    # Charger les valeurs par défaut
    config['database'] = {
        'host': 'localhost',
        'database': 'stage',
        'user': 'root',
        'password': ''
    }
    
    config['snmp'] = {
        'timeout': '5',
        'retries': '1',
        'port': '161',
        'default_community': 'public'
    }
    
    config['logging'] = {
        'level': 'INFO',
        'log_file': 'snmp_monitor.log',
        'max_size_mb': '10',
        'backup_count': '5'
    }
    
    config['schedule'] = {
        'interval_minutes': '5',
        'start_time': '',
        'end_time': '',
        'days_of_week': '1,2,3,4,5',
        'weekend': 'false'
    }
    
    # Charger la configuration depuis le fichier s'il existe
    if os.path.exists(config_file):
        config.read(config_file)
    else:
        # Créer le fichier de configuration avec les valeurs par défaut
        with open(config_file, 'w') as f:
            config.write(f)
    
    return config

# Charger la configuration globale
config = load_config()

# Configuration du logger
def setup_logging():
    """Configure la journalisation"""
    log_level = getattr(logging, config['logging'].get('level', 'INFO'))
    log_file = os.path.join(os.path.dirname(os.path.abspath(__file__)), 
                           config['logging'].get('log_file', 'snmp_monitor.log'))
    max_size = int(config['logging'].get('max_size_mb', '10')) * 1024 * 1024  # Convertir en octets
    backup_count = int(config['logging'].get('backup_count', '5'))
    
    # Créer le répertoire de logs s'il n'existe pas
    log_dir = os.path.dirname(log_file)
    if log_dir and not os.path.exists(log_dir):
        os.makedirs(log_dir)
    
    # Configurer le gestionnaire de fichiers rotatifs
    file_handler = RotatingFileHandler(
        log_file, 
        maxBytes=max_size, 
        backupCount=backup_count
    )
    
    # Formateur de log
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    
    file_handler.setFormatter(formatter)
    
    # Configurer le logger racine
    logger = logging.getLogger()
    logger.setLevel(log_level)
    
    # Supprimer les gestionnaires existants
    for handler in logger.handlers[:]:
        logger.removeHandler(handler)
    
    # Ajouter le gestionnaire de fichier
    logger.addHandler(file_handler)
    
    # Ajouter également la sortie console pour le mode interactif
    if sys.stdout.isatty():
        console_handler = logging.StreamHandler()
        console_handler.setFormatter(formatter)
        logger.addHandler(console_handler)
    
    return logger

# Initialiser le logger
logger = setup_logger()

# Configuration de la base de données
db_config = {
    'host': config['database'].get('host', 'localhost'),
    'database': config['database'].get('database', 'stage'),
    'user': config['database'].get('user', 'root'),
    'password': config['database'].get('password', '')
}

# Configuration SNMP
SNMP_TIMEOUT = int(config['snmp'].get('timeout', 5))
SNMP_RETRIES = int(config['snmp'].get('retries', 1))
SNMP_PORT = int(config['snmp'].get('port', 161))
DEFAULT_COMMUNITY = config['snmp'].get('default_community', 'public')

def get_pdus_from_database():
    """
    Récupère la liste des PDU à surveiller depuis la base de données
    
    Returns:
        list: Liste des PDU avec leur configuration
    """
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(dictionary=True)
        
        # Récupérer les PDU avec leur configuration
        query = """
        SELECT p.id, p.ip_address as ip, p.snmp_community as community, 
               p.nombre_prises as outlet_count, p.marque, p.modele
        FROM pdu p
        WHERE p.ip_address IS NOT NULL AND p.ip_address != ''
        """
        
        cursor.execute(query)
        pdus = cursor.fetchall()
        
        # Ajouter les valeurs par défaut si nécessaire
        for pdu in pdus:
            pdu['community'] = pdu.get('community', 'public')
            pdu['outlet_count'] = pdu.get('outlet_count', 0)
        
        return pdus
        
    except mysql.connector.Error as e:
        print(f"Erreur lors de la récupération des PDU depuis la base de données: {e}")
        return []
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()

# OID SNMP pour les PDU APC
# Documentation des OID: https://www.oidview.com/mibs/318/APC-POWERNET-MIB.html
APC_OIDS = {
    # Mesures globales
    'system_name': '1.3.6.1.4.1.318.1.1.1.1.2.0',      # Nom du système
    'system_location': '1.3.6.1.4.1.318.1.1.1.2.2.0',  # Emplacement
    'current': '1.3.6.1.4.1.318.1.1.12.2.2.1.1.2.1',   # Courant en Ampères
    'voltage': '1.3.6.1.4.1.318.1.1.12.2.3.1.1.2.1',   # Tension en Volts
    'active_power': '1.3.6.1.4.1.318.1.1.12.2.3.1.1.2.1',  # Puissance active en Watts
    
    # Mesures par prise
    'outlet_current': '1.3.6.1.4.1.318.1.1.12.3.5.1.1.2.{0}',  # Courant par prise
    'outlet_status': '1.3.6.1.4.1.318.1.1.12.3.5.1.1.4.{0}',   # État de la prise (1=ON, 2=OFF)
    'outlet_name': '1.3.6.1.4.1.318.1.1.12.3.5.1.1.2.{0}'      # Nom de la prise
}

def get_snmp_data(ip, oid, community=None, port=None):
    """
    Récupère une valeur SNMP à partir d'un OID
    
    Args:
        ip (str): Adresse IP de la PDU
        oid (str): OID à interroger
        community (str, optional): Communauté SNMP. Si None, utilise la valeur par défaut
        port (int, optional): Port SNMP. Si None, utilise la valeur par défaut
        
    Returns:
        str: Valeur SNMP ou None en cas d'erreur
    """
    if community is None:
        community = DEFAULT_COMMUNITY
    if port is None:
        port = SNMP_PORT
    
    logger.debug(f"Requête SNMP vers {ip}:{port} - OID: {oid}")
    
    try:
        errorIndication, errorStatus, errorIndex, varBinds = next(
            getCmd(SnmpEngine(),
                  CommunityData(community),
                  UdpTransportTarget((ip, port), 
                                  timeout=SNMP_TIMEOUT, 
                                  retries=SNMP_RETRIES),
                  ContextData(),
                  ObjectType(ObjectIdentity(oid)))
        )

        if errorIndication:
            logger.warning(f'Erreur SNMP pour {ip} - {oid}: {errorIndication}')
            return None
        elif errorStatus:
            logger.warning(f'Erreur SNMP pour {ip} - {oid}: {errorStatus.prettyPrint()}')
            return None
        else:
            for varBind in varBinds:
                result = str(varBind[1])
                logger.debug(f'Réponse SNMP de {ip} - {oid}: {result}')
                return result
    except Exception as e:
        logger.error(f'Exception lors de la requête SNMP vers {ip}: {str(e)}', exc_info=True)
        return None

def get_pdu_metrics(ip, community=None, outlet_count=0):
    """
    Récupère les métriques électriques d'une PDU et de ses prises
    
    Args:
        ip (str): Adresse IP de la PDU
        community (str, optional): Communauté SNMP. Si None, utilise la valeur par défaut
        outlet_count (int): Nombre de prises à surveiller
        
    Returns:
        dict: Dictionnaire contenant les métriques
    """
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    logger.info(f"Récupération des métriques pour la PDU {ip}")
    
    # Récupération des métriques globales
    logger.debug(f"Récupération des métriques globales pour {ip}")
    system_name = get_snmp_data(ip, APC_OIDS['system_name'], community) or 'Inconnu'
    current = get_snmp_data(ip, APC_OIDS['current'], community)
    voltage = get_snmp_data(ip, APC_OIDS['voltage'], community)
    active_power = get_snmp_data(ip, APC_OIDS['active_power'], community)
    
    # Préparation du résultat
    result = {
        'timestamp': timestamp,
        'ip': ip,
        'system_name': system_name,
        'current': safe_float(current),
        'voltage': safe_float(voltage),
        'active_power': safe_float(active_power),
        'outlets': []
    }
    
    logger.debug(f"Métriques globales pour {ip}: {result}")
    
    # Récupération des métriques par prise si nécessaire
    if outlet_count > 0:
        logger.debug(f"Récupération des données pour {outlet_count} prises")
        
        for outlet_num in range(1, outlet_count + 1):
            logger.debug(f"Traitement de la prise {outlet_num}")
            
            outlet_current = get_snmp_data(ip, APC_OIDS['outlet_current'].format(outlet_num), community)
            outlet_status = get_snmp_data(ip, APC_OIDS['outlet_status'].format(outlet_num), community)
            outlet_name = get_snmp_data(ip, APC_OIDS['outlet_name'].format(outlet_num), community) or f'Prise {outlet_num}'
            
            outlet_data = {
                'number': outlet_num,
                'name': outlet_name,
                'current': safe_float(outlet_current),
                'status': safe_int(outlet_status),
                'status_text': 'ON' if outlet_status == '1' else 'OFF' if outlet_status == '2' else 'INCONNU'
            }
            
            logger.debug(f"Données pour la prise {outlet_num}: {outlet_data}")
            result['outlets'].append(outlet_data)
    
    logger.info(f"Métriques récupérées avec succès pour {ip}")
    return result

def safe_float(value, default=None):
    """Convertit une valeur en float de manière sécurisée"""
    if value is None:
        return default
    try:
        return float(value)
    except (ValueError, TypeError):
        return default

def safe_int(value, default=None):
    """Convertit une valeur en int de manière sécurisée"""
    if value is None:
        return default
    try:
        return int(float(value))  # Convertit d'abord en float pour gérer les chaînes comme '1.0'
    except (ValueError, TypeError):
        return default

def save_to_database(pdu_id, metrics):
    """
    Enregistre les mesures dans la table capteurelectrique
    
    Args:
        pdu_id (int): ID de la PDU dans la base de données
        metrics (dict): Dictionnaire contenant les métriques à enregistrer
        
    Returns:
        bool: True si l'enregistrement a réussi, False sinon
    """
    conn = None
    cursor = None
    
    try:
        logger.debug(f"Connexion à la base de données pour enregistrer les métriques de la PDU {pdu_id}")
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        query = """
        INSERT INTO capteurelectrique (pdu_id, prise_id, valeur, unite, date_mesure)
        VALUES (%s, %s, %s, %s, %s)
        """
        
        # Préparer les données pour le batch insert
        batch_data = []
        
        # Enregistrer les mesures globales
        # Courant (A) - prise_id = 0
        if metrics.get('current') is not None:
            batch_data.append((
                pdu_id,
                0,  # 0 pour les mesures globales
                metrics['current'],
                'A',
                metrics['timestamp']
            ))
        
        # Tension (V) - prise_id = -1
        if metrics.get('voltage') is not None:
            batch_data.append((
                pdu_id,
                -1,  # -1 pour la tension
                metrics['voltage'],
                'V',
                metrics['timestamp']
            ))
        
        # Puissance active (W) - prise_id = -2
        if metrics.get('active_power') is not None:
            batch_data.append((
                pdu_id,
                -2,  # -2 pour la puissance active
                metrics['active_power'],
                'W',
                metrics['timestamp']
            ))
        
        # Enregistrer les mesures par prise
        for outlet in metrics.get('outlets', []):
            if outlet.get('current') is not None:
                batch_data.append((
                    pdu_id,
                    outlet['number'],  # Numéro de la prise (1 à N)
                    outlet['current'],
                    'A',
                    metrics['timestamp']
                ))
            
            # Enregistrer l'état de la prise (0=OFF, 1=ON)
            if outlet.get('status') is not None:
                batch_data.append((
                    pdu_id,
                    outlet['number'] * 1000,  # Pour les états, on utilise prise_id * 1000
                    outlet['status'],
                    'STATUS',
                    metrics['timestamp']
                ))
        
        # Exécuter l'insertion par lots
        if batch_data:
            cursor.executemany(query, batch_data)
            conn.commit()
            logger.info(f"{len(batch_data)} enregistrements insérés pour la PDU {pdu_id}")
            return True
        else:
            logger.warning(f"Aucune donnée à enregistrer pour la PDU {pdu_id}")
            return False
        
    except mysql.connector.Error as e:
        logger.error(f"Erreur MySQL lors de l'enregistrement pour la PDU {pdu_id}: {e}", exc_info=True)
        if conn and conn.is_connected():
            conn.rollback()
        return False
    except Exception as e:
        logger.error(f"Erreur inattendue lors de l'enregistrement pour la PDU {pdu_id}: {e}", exc_info=True)
        if conn and conn.is_connected():
            conn.rollback()
        return False
    finally:
        if cursor:
            cursor.close()
        if conn and conn.is_connected():
            conn.close()

def get_pdus_to_monitor():
    """
    Récupère la liste des PDU à surveiller depuis la base de données
    
    Returns:
        list: Liste des PDU avec leur configuration
    """
    return get_pdus_from_database()

def should_run_now():
    """
    Vérifie si le script doit s'exécuter maintenant en fonction de la configuration de planification
    
    Returns:
        bool: True si le script doit s'exécuter, False sinon
    """
    try:
        now = datetime.now()
        
        # Vérifier si on est en semaine ou week-end
        is_weekend = now.weekday() >= 5  # 5=Sam, 6=Dim
        
        # Si c'est le week-end et que weekend=false dans la config, on ne s'exécute pas
        if is_weekend and not config['schedule'].getboolean('weekend', False):
            logger.info("C'est le week-end et l'exécution le week-end est désactivée")
            return False
        
        # Vérifier les jours de la semaine
        days_of_week = [int(d.strip()) for d in config['schedule'].get('days_of_week', '1,2,3,4,5').split(',')]
        if now.weekday() + 1 not in days_of_week:  # weekday() renvoie 0 pour lundi, on veut 1
            logger.info(f"Aujourd'hui n'est pas un jour d'exécution configuré (jour {now.weekday() + 1})")
            return False
        
        # Vérifier l'heure de début et de fin
        start_time_str = config['schedule'].get('start_time')
        end_time_str = config['schedule'].get('end_time')
        
        if start_time_str and end_time_str:
            try:
                start_time = datetime.strptime(start_time_str, '%H:%M').time()
                end_time = datetime.strptime(end_time_str, '%H:%M').time()
                now_time = now.time()
                
                if not (start_time <= now_time <= end_time):
                    logger.info(f"En dehors des heures d'exécution configurées ({start_time_str} - {end_time_str})")
                    return False
            except ValueError as e:
                logger.error(f"Format d'heure invalide dans la configuration: {e}")
                return False
        
        return True
    except Exception as e:
        logger.error(f"Erreur lors de la vérification de la planification: {e}", exc_info=True)
        return False

def run_single_cycle():
    """Exécute un seul cycle de surveillance des PDU"""
    logger.info(f"=== Début du cycle de surveillance - {datetime.now().strftime('%Y-%m-%d %H:%M:%S')} ===")
    
    # Récupérer la liste des PDU à surveiller depuis la base de données
    pdus = get_pdus_to_monitor()
    
    if not pdus:
        logger.warning("Aucune PDU trouvée dans la base de données.")
        logger.warning("Veuillez configurer les PDU dans la table 'pdu' avec leurs adresses IP.")
        return
    
    logger.info(f"{len(pdus)} PDU(s) trouvée(s) dans la base de données")
    
    # Pour chaque PDU, récupérer les métriques et les enregistrer
    for pdu in pdus:
        pdu_id = pdu['id']
        ip = pdu.get('ip')
        community = pdu.get('community')
        outlet_count = int(pdu.get('outlet_count', 0))
        
        if not ip:
            logger.warning(f"PDU #{pdu_id} n'a pas d'adresse IP configurée, ignorée")
            continue
            
        logger.info(f"Traitement de la PDU #{pdu_id} - {pdu.get('marque', 'Inconnu')} {pdu.get('modele', '')} ({ip})")
        
        try:
            # Récupérer les métriques SNMP
            metrics = get_pdu_metrics(ip, community, outlet_count)
            
            # Enregistrer dans la base de données
            if save_to_database(pdu_id, metrics):
                logger.info(f"Données enregistrées avec succès pour la PDU {pdu_id}")
            else:
                logger.error(f"Échec de l'enregistrement des données pour la PDU {pdu_id}")
                
        except Exception as e:
            logger.error(f"Erreur lors de la surveillance de la PDU {ip}: {str(e)}", exc_info=True)
    
    logger.info(f"=== Fin du cycle de surveillance - {datetime.now().strftime('%H:%M:%S')} ===\n")

def main():
    """Fonction principale du script"""
    try:
        logger.info("=== Démarrage du superviseur SNMP ===")
        logger.info(f"Version: 1.0.0")
        logger.info(f"Répertoire de travail: {os.getcwd()}")
        
        # Mode d'exécution unique ou planifié
        is_interactive = sys.stdout.isatty()
        
        if is_interactive:
            logger.info("Mode interactif détecté - Exécution unique")
            run_single_cycle()
        else:
            logger.info("Mode non interactif détecté - Exécution planifiée")
            
            # Boucle principale pour l'exécution planifiée
            while True:
                if should_run_now():
                    run_single_cycle()
                else:
                    logger.debug("En attente du prochain créneau d'exécution...")
                
                # Attendre l'intervalle configuré avant la prochaine exécution
                interval = int(config['schedule'].get('interval_minutes', '5')) * 60  # en secondes
                logger.debug(f"Attente de {interval} secondes avant la prochaine exécution...")
                time.sleep(interval)
                
    except KeyboardInterrupt:
        logger.info("Arrêt demandé par l'utilisateur")
    except Exception as e:
        logger.critical(f"Erreur critique: {e}", exc_info=True)
        return 1
    
    logger.info("=== Arrêt du superviseur SNMP ===")
    return 0

if __name__ == "__main__":
    # Code de sortie par défaut
    exit_code = 0
    
    try:
        exit_code = main()
    except KeyboardInterrupt:
        logger.info("Arrêt demandé par l'utilisateur (interruption clavier)")
        exit_code = 0
    except Exception as e:
        logger.critical(f"Erreur inattendue: {e}", exc_info=True)
        exit_code = 1
    
    # Pause sous Windows en mode interactif pour voir les messages
    if os.name == 'nt' and sys.stdout.isatty():
        input("\nAppuyez sur Entrée pour quitter...")
    
    sys.exit(exit_code)
