import React, { useState, useEffect } from 'react';
import api from '../services/api';
import './Archivage.css';

const Archivage = () => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(false);
  const [archiving, setArchiving] = useState(false);
  const [cleaning, setCleaning] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  // Charger les statistiques
  const loadStats = async () => {
    try {
      setLoading(true);
      setError('');
      const response = await api.get('/statistiques-archivage');
      setStats(response.data);
    } catch (err) {
      setError('Erreur lors du chargement des statistiques');
      console.error('Erreur stats:', err);
    } finally {
      setLoading(false);
    }
  };

  // Effectuer l'archivage
  const handleArchiver = async () => {
    try {
      setArchiving(true);
      setError('');
      setMessage('');
      
      const response = await api.post('/archiver-donnees');
      
      if (response.data.success) {
        setMessage(`Archivage réussi ! ${response.data.total} enregistrements archivés (${response.data.electrique} électrique, ${response.data.clime} climatisation)`);
        // Recharger les statistiques
        await loadStats();
      } else {
        setError('Erreur lors de l\'archivage');
      }
    } catch (err) {
      setError('Erreur lors de l\'archivage');
      console.error('Erreur archivage:', err);
    } finally {
      setArchiving(false);
    }
  };

  // Nettoyer les anciennes données
  const handleNettoyer = async () => {
    if (!window.confirm('Êtes-vous sûr de vouloir nettoyer les anciennes données archivées (plus d\'un an) ? Cette action est irréversible.')) {
      return;
    }

    try {
      setCleaning(true);
      setError('');
      setMessage('');
      
      const response = await api.post('/nettoyer-anciennes-donnees');
      
      if (response.data.success) {
        setMessage(`Nettoyage réussi ! ${response.data.supprimes} anciens enregistrements supprimés`);
        // Recharger les statistiques
        await loadStats();
      } else {
        setError('Erreur lors du nettoyage');
      }
    } catch (err) {
      setError('Erreur lors du nettoyage');
      console.error('Erreur nettoyage:', err);
    } finally {
      setCleaning(false);
    }
  };

  useEffect(() => {
    loadStats();
  }, []);

  const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
  };

  return (
  <div className="archivage light">

      <div className="archivage-content">
        {/* Statistiques */}
        <div className="stats-section">
          <h2>Statistiques d'Archivage</h2>
          
          {loading ? (
            <div className="loading">
              <div className="loading-spinner"></div>
              <p>Chargement des statistiques...</p>
            </div>
          ) : stats ? (
            <div className="stats-grid">
              <div className="stat-card">
                <div className="stat-icon">📊</div>
                <div className="stat-content">
                  <h3>Données Récentes</h3>
                  <p>Moins de 24 heures</p>
                  <div className="stat-details">
                    {stats.recentes && stats.recentes.map((data, index) => (
                      <div key={index} className="stat-item">
                        <span className="stat-label">{data.type === 'electrique' ? 'Électrique' : 'Climatisation'}:</span>
                        <span className="stat-value">{formatNumber(data.count)} enregistrements</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <div className="stat-card">
                <div className="stat-icon">📦</div>
                <div className="stat-content">
                  <h3>Données Archivées</h3>
                  <p>Plus de 24 heures</p>
                  <div className="stat-details">
                    {stats.archivees && stats.archivees.map((data, index) => (
                      <div key={index} className="stat-item">
                        <span className="stat-label">{data.type === 'electrique' ? 'Électrique' : 'Climatisation'}:</span>
                        <span className="stat-value">{formatNumber(data.count)} enregistrements</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <div className="stat-card">
                <div className="stat-icon">⏰</div>
                <div className="stat-content">
                  <h3>Seuil d'Archivage</h3>
                  <p>Date limite</p>
                  <div className="stat-details">
                    <div className="stat-item">
                      <span className="stat-label">Données antérieures à:</span>
                      <span className="stat-value">{new Date(stats.date_limite).toLocaleString('fr-FR')}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <div className="no-data">
              <p>Aucune statistique disponible</p>
            </div>
          )}
        </div>

        {/* Actions */}
        <div className="actions-section">
          <h2>Actions d'Archivage</h2>
          
          <div className="actions-grid">
            <div className="action-card">
              <div className="action-header">
                <h3>Archivage Manuel</h3>
                <p>Transférer les données de plus de 24h vers l'archive</p>
              </div>
              <div className="action-content">
                <p>Cette action va :</p>
                <ul>
                  <li>Transférer les données anciennes vers la table d'archive</li>
                  <li>Supprimer les données transférées des tables originales</li>
                  <li>Optimiser les performances de la base de données</li>
                </ul>
                <button 
                  onClick={handleArchiver}
                  disabled={archiving}
                  className="btn-archiver"
                >
                  {archiving ? 'Archivage en cours...' : 'Lancer l\'Archivage'}
                </button>
              </div>
            </div>

            <div className="action-card">
              <div className="action-header">
                <h3>Nettoyage des Archives</h3>
                <p>Supprimer les données archivées de plus d'un an</p>
              </div>
              <div className="action-content">
                <p>Cette action va :</p>
                <ul>
                  <li>Supprimer les données archivées de plus de 365 jours</li>
                  <li>Libérer de l'espace disque</li>
                  <li>Maintenir les performances optimales</li>
                </ul>
                <button 
                  onClick={handleNettoyer}
                  disabled={cleaning}
                  className="btn-nettoyer"
                >
                  {cleaning ? 'Nettoyage en cours...' : 'Nettoyer les Archives'}
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Informations */}
        <div className="info-section">
          <h2>Informations sur l'Archivage</h2>
          
          <div className="info-grid">
            <div className="info-card">
              <h3>🔄 Archivage Automatique</h3>
              <p>Le système d'archivage peut être configuré pour s'exécuter automatiquement via un cron job quotidien.</p>
              <code>0 2 * * * /usr/bin/php /chemin/vers/cron_archivage.php</code>
            </div>

            <div className="info-card">
              <h3>📁 Structure des Données</h3>
              <p>Les données archivées conservent toutes les informations originales :</p>
              <ul>
                <li>Type de capteur (électrique/climatisation)</li>
                <li>Valeur et unité de mesure</li>
                <li>Date et heure de mesure</li>
                <li>Références aux racks et équipements</li>
              </ul>
            </div>

            <div className="info-card">
              <h3>⚡ Performance</h3>
              <p>L'archivage améliore les performances en :</p>
              <ul>
                <li>Réduisant la taille des tables actives</li>
                <li>Accélérant les requêtes sur les données récentes</li>
                <li>Optimisant l'utilisation de la mémoire</li>
              </ul>
            </div>
          </div>
        </div>

        {/* Messages */}
        {message && (
          <div className="message success">
            <p>{message}</p>
            <button onClick={() => setMessage('')} className="btn-close">×</button>
          </div>
        )}

        {error && (
          <div className="message error">
            <p>{error}</p>
            <button onClick={() => setError('')} className="btn-close">×</button>
          </div>
        )}
      </div>
    </div>
  );
};

export default Archivage;
