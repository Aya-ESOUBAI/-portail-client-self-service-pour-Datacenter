import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { formatLocation } from '../utils/locationUtils';
import AddRackModal from '../components/AddRackModal';
import DeleteRackModal from '../components/DeleteRackModal';
import RackDetailsModal from '../components/RackDetailsModal';
import RackVisualization from '../components/RackVisualization';


function Dashboard() {
  const [recentAlerts, setRecentAlerts] = useState([]);
  const [users, setUsers] = useState([]);
  const [racksWithDirections, setRacksWithDirections] = useState([]);
  const [racksCompleteData, setRacksCompleteData] = useState([]);
  const [error, setError] = useState('');
  const [userInfo, setUserInfo] = useState(null);
  const [loading, setLoading] = useState(false);
  
  // États pour les modals
  const [showAddRackModal, setShowAddRackModal] = useState(false);
  const [showDeleteRackModal, setShowDeleteRackModal] = useState(false);
  const [showRackDetailsModal, setShowRackDetailsModal] = useState(false);
  const [selectedRack, setSelectedRack] = useState(null);

  // Fonction pour charger les données du dashboard
  const loadDashboardData = async () => {
    try {
      setLoading(true);
      setError('');
      
      // Récupérer les informations de l'utilisateur connecté
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
      setUserInfo(currentUser);

      // Charger les utilisateurs (seulement pour les admins)
      if (currentUser.role === 'Admin') {
        const usersRes = await api.get('/utilisateurs');
        setUsers(Array.isArray(usersRes.data) ? usersRes.data : []);
      } else {
        setUsers([]);
      }
      
      // Charger les alertes récentes
      const alertsRes = await api.get('/alertes');
      const alertsData = Array.isArray(alertsRes.data) ? alertsRes.data : [];
      setRecentAlerts(alertsData.slice(0, 5)); // 5 dernières alertes
      
      // Charger les racks selon le rôle de l'utilisateur
      if (currentUser.role === 'Admin') {
        // Pour les admins, charger tous les racks
        const racksRes = await api.get('/raks-with-directions');
        setRacksWithDirections(Array.isArray(racksRes.data) ? racksRes.data : []);
        
        // Charger les données complètes des racks pour la visualisation
        const racksCompleteRes = await api.get(`/raks-complete-data/${currentUser.id}`);
        setRacksCompleteData(Array.isArray(racksCompleteRes.data) ? racksCompleteRes.data : []);
      } else if (currentUser.role === 'Direction' && currentUser.id) {
        // Pour les directions, charger seulement leurs racks
        const racksRes = await api.get(`/raks-by-user-direction/${currentUser.id}`);
        setRacksWithDirections(Array.isArray(racksRes.data) ? racksRes.data : []);
        
        // Charger les données complètes des racks pour la visualisation
        const racksCompleteRes = await api.get(`/raks-complete-data/${currentUser.id}`);
        setRacksCompleteData(Array.isArray(racksCompleteRes.data) ? racksCompleteRes.data : []);
      } else {
        setRacksWithDirections([]);
        setRacksCompleteData([]);
      }
      
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error('Erreur dashboard:', err);
      // Initialiser avec des tableaux vides en cas d'erreur
      setRecentAlerts([]);
      setUsers([]);
      setRacksWithDirections([]);
      setRacksCompleteData([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDashboardData();
  }, []);

  // Fonction pour actualiser les données
  const handleRefresh = async () => {
    await loadDashboardData();
  };

  // Fonctions de gestion des racks
  const handleAddRack = () => {
    setShowAddRackModal(true);
  };

  const handleRackAdded = () => {
    setShowAddRackModal(false);
    loadDashboardData(); // Recharger les données
  };

  const handleDeleteRack = (rack) => {
    setSelectedRack(rack);
    setShowDeleteRackModal(true);
  };

  const handleRackDeleted = () => {
    setShowDeleteRackModal(false);
    setSelectedRack(null);
    loadDashboardData(); // Recharger les données
  };

  const handleViewRackDetails = (rack) => {
    setSelectedRack(rack);
    setShowRackDetailsModal(true);
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
      return `${alert.type}: ${alert.valeur_detectee} (seuil: ${alert.seuil_critique})`;
    }
    return alert.type;
  };

  const getDashboardTitle = () => {
    if (userInfo?.role === 'Admin') {
      return 'Dashboard Administrateur';
    } else if (userInfo?.role === 'Direction') {
      return `Dashboard ${userInfo.nom || 'Direction'}`;
    }
    return 'Dashboard';
  };

  const getDashboardSubtitle = () => {
    if (userInfo?.role === 'Admin') {
      return 'Vue d\'ensemble du datacenter';
    } else if (userInfo?.role === 'Direction') {
      return 'Vue d\'ensemble de vos racks';
    }
    return 'Vue d\'ensemble';
  };

  return (
    <div className="dashboard">
      <div className="dashboard-actions">
        {userInfo?.role === 'Admin' && (
          <button 
            onClick={() => window.location.href = '/ajouter-rack'}
            className="btn-primary"
          >
            <span>+</span> Ajouter un rack
          </button>
        )}
        <button 
          onClick={handleRefresh}
          className="btn-refresh"
          disabled={loading}
        >
          {loading ? 'Actualisation...' : '🔄 Actualiser'}
        </button>
      </div>
      {/* Visualisation des Racks */}
      <div className="racks-visualization-section">
        
        <RackVisualization 
          racksData={racksCompleteData}
          loading={loading}
          error={error}
        />
      </div>

      {/* Alertes récentes et utilisateurs */}
      <div className="bottom-grid">
                 <div className="card">
           <h3>Alertes Récentes</h3>
          <div className="alerts-list">
            {!recentAlerts || recentAlerts.length === 0 ? (
              <p>Aucune alerte récente</p>
            ) : (
              recentAlerts.map(alert => (
                <div key={alert.id} className="alert-item">
                  <div className="alert-type">
                    {getTypeIcon(alert.type)} {alert.type}
                  </div>
                  <div className="alert-message">
                    {formatAlertMessage(alert)}
                  </div>
                  <div className="alert-time">
                    {new Date(alert.date_creation).toLocaleString()}
                  </div>
                </div>
              ))
            )}
          </div>
        </div>

        {userInfo?.role === 'Admin' && (
                     <div className="card">
             <h3>Utilisateurs Actifs</h3>
            <div className="users-list">
              {!users || users.length === 0 ? (
                <p>Aucun utilisateur trouvé</p>
              ) : (
                users
                  .filter(user => user.role === 'Direction')
                  .map(user => (
                  <div key={user.id} className="user-item">
                    <div className="user-avatar">{user.nom ? user.nom.charAt(0) : 'U'}</div>
                    <div className="user-info">
                      <div className="user-name">{user.nom}</div>
                      <div className="user-role">{user.role}</div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        )}
      </div>

      {error && <p className="error-message">{error}</p>}

      {/* Modals */}
      <AddRackModal 
        isOpen={showAddRackModal}
        onClose={() => setShowAddRackModal(false)}
        onSuccess={handleRackAdded}
      />

      <DeleteRackModal 
        isOpen={showDeleteRackModal}
        onClose={() => setShowDeleteRackModal(false)}
        rack={selectedRack}
        onSuccess={handleRackDeleted}
      />

      <RackDetailsModal 
        isOpen={showRackDetailsModal}
        onClose={() => setShowRackDetailsModal(false)}
        rack={selectedRack}
      />
    </div>
  );
}

export default Dashboard;
