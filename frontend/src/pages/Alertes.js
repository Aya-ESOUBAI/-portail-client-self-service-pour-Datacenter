import React, { useEffect, useState } from 'react';
import api from '../services/api';

function Alertes() {
  const [alertes, setAlertes] = useState([]);
  const [thresholds, setThresholds] = useState([]);
  const [activeTab, setActiveTab] = useState('alertes');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [isChecking, setIsChecking] = useState(false);
  const [userInfo, setUserInfo] = useState(null);

  // États pour les formulaires
  const [newThreshold, setNewThreshold] = useState({
    type: '',
    valeur_seuil: '',
    unite: '',
    description: ''
  });

  useEffect(() => {
    // Récupérer les informations de l'utilisateur connecté
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    setUserInfo(currentUser);
    
    loadAlertesData();
  }, []);

  const loadAlertesData = async () => {
    try {
      const [alertesRes, thresholdsRes] = await Promise.all([
        api.get('/alertes'),
        api.get('/alert-thresholds')
      ]);
      
      // Vérifier que les données sont des tableaux
      setAlertes(Array.isArray(alertesRes.data) ? alertesRes.data : []);
      setThresholds(Array.isArray(thresholdsRes.data) ? thresholdsRes.data : []);
    } catch (err) {
      setError('Erreur lors du chargement des données d\'alertes');
      console.error('Erreur alertes:', err);
      // Initialiser avec des tableaux vides en cas d'erreur
      setAlertes([]);
      setThresholds([]);
    }
  };

  const handleCreateThreshold = async (e) => {
    e.preventDefault();
    try {
      await api.post('/alert-thresholds', newThreshold);
      setSuccess('Seuil critique créé avec succès et vérification automatique activée');
      setNewThreshold({ type: '', valeur_seuil: '', unite: '', description: '' });
      loadAlertesData();
    } catch (err) {
      setError('Erreur lors de la création du seuil critique');
    }
  };

  const handleDeleteThreshold = async (id) => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer ce seuil critique ?')) {
      try {
        await api.delete(`/alert-thresholds/${id}`);
        setSuccess('Seuil critique supprimé avec succès');
        loadAlertesData();
      } catch (err) {
        setError('Erreur lors de la suppression du seuil critique');
      }
    }
  };

  const handleManualCheck = async () => {
    setIsChecking(true);
    try {
      await api.post('/check-thresholds');
      setSuccess('Vérification des seuils critiques effectuée. Notifications envoyées si nécessaire.');
      loadAlertesData(); // Recharger les alertes pour voir les nouvelles
    } catch (err) {
      setError('Erreur lors de la vérification des seuils critiques');
    } finally {
      setIsChecking(false);
    }
  };

  const getStatusColor = (statut) => {
    switch (statut) {
      case 'active': return 'red';
      case 'resolved': return 'green';
      case 'pending': return 'orange';
      default: return 'gray';
    }
  };

  const getStatusText = (statut) => {
    switch (statut) {
      case 'active': return 'Active';
      case 'resolved': return 'Résolue';
      case 'pending': return 'En attente';
      default: return 'Inconnu';
    }
  };

  const getTypeIcon = (type) => {
    switch (type) {
      case 'temperature': return '';
      case 'energie': return '';
      case 'humidite': return '';
      case 'cpu': return '';
      case 'memoire': return '';
      case 'Surtension': return '';
      case 'Température critique': return '';
      default: return '';
    }
  };

  const formatAlertMessage = (alert) => {
    if (alert.valeur_detectee && alert.seuil_critique) {
      return `${alert.type}: Valeur détectée ${alert.valeur_detectee}, Seuil critique ${alert.seuil_critique}`;
    }
    return alert.type;
  };

  return (
  <div className="alertes">

      {/* Bouton de vérification manuelle */}
      <div className="manual-check-section">
        <button 
          onClick={handleManualCheck} 
          disabled={isChecking}
          className="btn-check-thresholds"
        >
          {isChecking ? '🔄 Vérification en cours...' : '🔍 Vérifier les Seuils Critiques'}
        </button>
        <p className="check-info">
          💡 Ce bouton déclenche une vérification manuelle de tous les seuils critiques. 
          Les notifications automatiques (email/SMS) sont envoyées aux administrateurs et directions.
        </p>
      </div>

      {/* Onglets */}
      <div className="tabs">
        <button 
          className={`tab ${activeTab === 'alertes' ? 'active' : ''}`}
          onClick={() => setActiveTab('alertes')}
        >
          Alertes ({alertes.length})
        </button>
        {userInfo?.role === 'Admin' && (
          <button 
            className={`tab ${activeTab === 'thresholds' ? 'active' : ''}`}
            onClick={() => setActiveTab('thresholds')}
          >
            Seuils Critiques ({thresholds.length})
          </button>
        )}
      </div>

      {/* Messages de succès/erreur */}
      {success && <div className="success-message">{success}</div>}
      {error && <div className="error-message">{error}</div>}

      {/* Onglet Alertes */}
      {activeTab === 'alertes' && (
        <div className="alertes-content">
          <div className="alertes-list">
            <h2>Liste des Alertes</h2>
            {!alertes || alertes.length === 0 ? (
              <p className="no-data">Aucune alerte enregistrée</p>
            ) : (
              <div className="alertes-grid">
                {alertes.map(alerte => (
                  <div key={alerte.id} className="alerte-card">
                    <div className="alerte-header">
                      <div className="alerte-type">
                        <span className={`status-indicator ${getStatusColor(alerte.statut)}`}>
                          ● {getStatusText(alerte.statut)}
                        </span>
                      </div>
                      <div className="alerte-date">
                        {new Date(alerte.date_creation).toLocaleString()}
                      </div>
                    </div>
                    <div className="alerte-content">
                      <h3>
                        {getTypeIcon(alerte.type)} {alerte.type}
                      </h3>
                      <p>{formatAlertMessage(alerte)}</p>
                      {alerte.envoyee_par && (
                        <p className="alerte-rack">
                          <strong>Envoyée par:</strong> {alerte.envoyee_par}
                        </p>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Onglet Seuils Critiques - Seulement pour les admins */}
      {activeTab === 'thresholds' && userInfo?.role === 'Admin' && (
        <div className="thresholds-content">
          <div className="thresholds-section">
            <h2>Configuration des Seuils Critiques</h2>
            
            {/* Formulaire de création */}
            <div className="form-section">
              <h3>Ajouter un Seuil Critique</h3>
              <p className="form-info">
                Les seuils critiques déclenchent automatiquement des alertes et envoient 
                des notifications par email et SMS aux administrateurs et directions.
              </p>
              <form onSubmit={handleCreateThreshold} className="threshold-form">
                <div className="form-row">
                  <div className="form-group">
                    <label>Type de Mesure</label>
                    <select 
                      value={newThreshold.type} 
                      onChange={(e) => setNewThreshold({...newThreshold, type: e.target.value})}
                      required
                    >
                      <option value="">Sélectionner un type</option>
                                        <option value="temperature">Température</option>
                  <option value="energie">Consommation Énergétique</option>
                  <option value="humidite">Humidité</option>
                  <option value="cpu">Utilisation CPU</option>
                  <option value="memoire">Utilisation Mémoire</option>
                    </select>
                  </div>
                  <div className="form-group">
                    <label>Valeur Seuil</label>
                    <input 
                      type="number" 
                      value={newThreshold.valeur_seuil}
                      onChange={(e) => setNewThreshold({...newThreshold, valeur_seuil: e.target.value})}
                      required
                    />
                  </div>
                  <div className="form-group">
                    <label>Unité</label>
                    <input 
                      type="text" 
                      value={newThreshold.unite}
                      onChange={(e) => setNewThreshold({...newThreshold, unite: e.target.value})}
                      placeholder="°C, W, %, etc."
                      required
                    />
                  </div>
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea 
                    value={newThreshold.description}
                    onChange={(e) => setNewThreshold({...newThreshold, description: e.target.value})}
                    placeholder="Description du seuil critique..."
                    required
                  />
                </div>
                <button type="submit" className="btn-primary">Créer le Seuil</button>
              </form>
            </div>

            {/* Liste des seuils existants */}
            <div className="thresholds-list">
              <h3>Seuils Configurés</h3>
              {!thresholds || thresholds.length === 0 ? (
                <p className="no-data">Aucun seuil critique configuré</p>
              ) : (
                <div className="thresholds-grid">
                  {thresholds.map(threshold => (
                    <div key={threshold.id} className="threshold-card">
                      <div className="threshold-header">
                        <h4>
                          {getTypeIcon(threshold.type)} {threshold.type}
                        </h4>
                        <button 
                          onClick={() => handleDeleteThreshold(threshold.id)}
                          className="btn-delete"
                        >
                          Supprimer
                        </button>
                      </div>
                      <div className="threshold-content">
                        <p><strong>Seuil:</strong> {threshold.valeur_seuil} {threshold.unite}</p>
                        <p><strong>Description:</strong> {threshold.description}</p>
                        <p><strong>Créé le:</strong> {new Date(threshold.date_creation).toLocaleString()}</p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default Alertes;
