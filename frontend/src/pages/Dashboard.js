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
  const [deletingUserId, setDeletingUserId] = useState(null);
  
  const [showAddRackModal, setShowAddRackModal] = useState(false);
  const [showDeleteRackModal, setShowDeleteRackModal] = useState(false);
  const [showRackDetailsModal, setShowRackDetailsModal] = useState(false);
  const [selectedRack, setSelectedRack] = useState(null);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      setError('');
      
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
      setUserInfo(currentUser);

      if (currentUser.role === 'Admin') {
        const usersRes = await api.get('/utilisateurs');
        setUsers(Array.isArray(usersRes.data) ? usersRes.data : []);
      } else {
        setUsers([]);
      }
      
      const alertsRes = await api.get('/alertes');
      const alertsData = Array.isArray(alertsRes.data) ? alertsRes.data : [];
      setRecentAlerts(alertsData.slice(0, 5)); // 5 dernières alertes
      
      if (currentUser.role === 'Admin') {
        const racksRes = await api.get('/raks-with-directions');
        setRacksWithDirections(Array.isArray(racksRes.data) ? racksRes.data : []);
        
        const racksCompleteRes = await api.get(`/raks-complete-data/${currentUser.id}`);
        setRacksCompleteData(Array.isArray(racksCompleteRes.data) ? racksCompleteRes.data : []);
      } else if (currentUser.role === 'Direction' && currentUser.id) {
        const racksRes = await api.get(`/raks-by-user-direction/${currentUser.id}`);
        setRacksWithDirections(Array.isArray(racksRes.data) ? racksRes.data : []);
        
        const racksCompleteRes = await api.get(`/raks-complete-data/${currentUser.id}`);
        setRacksCompleteData(Array.isArray(racksCompleteRes.data) ? racksCompleteRes.data : []);
      } else {
        setRacksWithDirections([]);
        setRacksCompleteData([]);
      }
      
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error('Erreur dashboard:', err);
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

  const handleRefresh = async () => {
    await loadDashboardData();
  };

  const handleAddRack = () => {
    setShowAddRackModal(true);
  };

  const handleRackAdded = () => {
    setShowAddRackModal(false);
    loadDashboardData();
  };

  const handleDeleteRack = (rack) => {
    setSelectedRack(rack);
    setShowDeleteRackModal(true);
  };

  const handleRackDeleted = () => {
    setShowDeleteRackModal(false);
    setSelectedRack(null);
    loadDashboardData(); 
  };

  const handleDeleteUser = async (userId) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
      return;
    }
    
    try {
      setDeletingUserId(userId);
      await api.delete(`/utilisateurs/${userId}`);
      const usersRes = await api.get('/utilisateurs');
      setUsers(Array.isArray(usersRes.data) ? usersRes.data : []);
      setSuccess('Utilisateur supprimé avec succès');
    } catch (err) {
      setError('Erreur lors de la suppression de l\'utilisateur');
      console.error('Erreur suppression utilisateur:', err);
    } finally {
      setDeletingUserId(null);
    }
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
                    <button 
                      className="btn-delete"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleDeleteUser(user.id);
                      }}
                      disabled={deletingUserId === user.id}
                      title="Supprimer l'utilisateur"
                    >
                      {deletingUserId === user.id ? 'Suppression...' : '🗑️'}
                    </button>
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
