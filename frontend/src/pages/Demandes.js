import React, { useEffect, useState } from 'react';
import api from '../services/api';

function Demandes() {
  const [demandes, setDemandes] = useState([]);
  const [stats, setStats] = useState([]);
  const [demandesByDirection, setDemandesByDirection] = useState([]);
  const [activeTab, setActiveTab] = useState('demandes');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [loading, setLoading] = useState(false);
  const [userInfo, setUserInfo] = useState(null);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newDemande, setNewDemande] = useState({
    type: 'INSTALLATION',
    nom: '',
    prenom: '',
    cin: '',
    code: '',
    date_acces: new Date().toISOString().split('T')[0], // Date du système par défaut
    nom_equipement: '',
    serial_number: ''
  });

  useEffect(() => {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    setUserInfo(currentUser);
    loadDemandesData();
  }, []);

  const loadDemandesData = async () => {
    try {
      setLoading(true);
      const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
      
      if (!currentUser || !currentUser.role) {
        setError('Erreur: Utilisateur non connecté');
        return;
      }
      
      if (currentUser.role === 'Admin') {
        const [demandesRes, statsRes, directionRes] = await Promise.all([
          api.get('/demandes').catch(err => {
            console.error('Erreur lors de la récupération des demandes:', err);
            setError('Erreur lors du chargement des demandes');
            return { data: [] };
          }),
          api.get('/demandes-stats').catch(err => {
            console.error('Erreur lors de la récupération des stats:', err);
            setError('Erreur lors du chargement des statistiques');
            return { data: [] };
          }),
          api.get('/demandes-by-direction').catch(err => {
            console.error('Erreur lors de la récupération des demandes par direction:', err);
            setError('Erreur lors du chargement des demandes par direction');
            return { data: [] };
          })
        ]);
        
        console.log('Réponse complète de /demandes:', demandesRes);
        console.log('Données brutes des demandes:', demandesRes.data);
        
        if (demandesRes.data && demandesRes.data.error) {
          console.error('Erreur du serveur:', demandesRes.data.error);
          setError('Erreur lors du chargement des demandes: ' + (demandesRes.data.details || demandesRes.data.error));
        }
        
        const demandes = Array.isArray(demandesRes.data) ? demandesRes.data : [];
        console.log('Demandes après vérification:', demandes);
        
        setDemandes(demandes);
        setStats(Array.isArray(statsRes.data) ? statsRes.data : []);
        setDemandesByDirection(Array.isArray(directionRes.data) ? directionRes.data : []);
      } else if (currentUser.role === 'Direction') {
        const demandesRes = await api.get('/demandes');
        const allDemandes = Array.isArray(demandesRes.data) ? demandesRes.data : [];
        
        const userDemandes = allDemandes.filter(demande => 
          demande.utilisateur_id === currentUser.id || demande.direction_id === currentUser.direction_id
        );
        
        setDemandes(userDemandes);
        setStats([]); 
        setDemandesByDirection([]);
      }
    } catch (err) {
      setError('Erreur lors du chargement des données de demandes');
      console.error('Erreur demandes:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleAction = async (action, demandeId) => {
    try {
      setLoading(true);
      await api.post(`/demandes/${demandeId}/${action}`);
      setSuccess(`Demande ${action === 'valider' ? 'validée' : action === 'rejeter' ? 'rejetée' : 'marquée comme terminée'} avec succès`);
      loadDemandesData(); 
    } catch (err) {
      setError(`Erreur lors de l'action sur la demande`);
      console.error('Erreur action:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateDemande = async (e) => {
    e.preventDefault();
    try {
      setLoading(true);
      
      const formData = new FormData();
      formData.append('type', newDemande.type);

      if (userInfo?.id) formData.append('utilisateur_id', userInfo.id);
      if (userInfo?.direction_id) formData.append('direction_id', userInfo.direction_id);

      if (newDemande.type === 'ACCES') {
        formData.append('nom', newDemande.nom);
        formData.append('prenom', newDemande.prenom);
        formData.append('cin', newDemande.cin);
        formData.append('date_acces', newDemande.date_acces);
        if (newDemande.code) {
          formData.append('code', newDemande.code);
        }
      }

      if (newDemande.type === 'INSTALLATION' || newDemande.type === 'RETRAIT') {
        formData.append('nom_equipement', newDemande.nom_equipement);
        formData.append('serial_number', newDemande.serial_number);
      }
      
      await api.post('/demandes', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      
      setSuccess('Demande créée avec succès');
      setShowCreateForm(false);
      setNewDemande({
        type: 'INSTALLATION',
        nom: '',
        prenom: '',
        cin: '',
        code: '',
        date_acces: new Date().toISOString().split('T')[0], 
        nom_equipement: '',
        serial_number: ''
      });
      loadDemandesData(); 
    } catch (err) {
      setError('Erreur lors de la création de la demande');
      console.error('Erreur création:', err);
    } finally {
      setLoading(false);
    }
  };





  const handleDownloadPDF = async (demandeId) => {
    try {
      const response = await api.get(`/demandes/${demandeId}/pdf`, {
        responseType: 'blob'
      });
      
      const blob = new Blob([response.data], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `justificatif_demande_${demandeId}.pdf`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      
      setSuccess('PDF téléchargé avec succès');
    } catch (error) {
      console.error('Erreur lors du téléchargement du PDF:', error);
      setError('Erreur lors du téléchargement du PDF');
    }
  };

  const getStatusColor = (statut) => {
    switch (statut) {
      case 'EN_ATTENTE': return 'orange';
      case 'VALIDEE': return 'green';
      case 'REJETEE': return 'red';
      case 'TERMINEE': return 'blue';
      default: return 'gray';
    }
  };

  const getStatusText = (statut) => {
    switch (statut) {
      case 'EN_ATTENTE': return 'En Attente';
      case 'VALIDEE': return 'Validée';
      case 'REJETEE': return 'Rejetée';
      case 'TERMINEE': return 'Terminée';
      default: return 'Inconnu';
    }
  };

  const getTypeIcon = (type) => {
    switch (type) {
      case 'INSTALLATION': return '🔧';
      case 'RETRAIT': return '📦';
      case 'ACCES': return '🔑';
      default: return '';
    }
  };

  const getTypeText = (type) => {
    switch (type) {
      case 'INSTALLATION': return 'Installation';
      case 'RETRAIT': return 'Retrait';
      case 'ACCES': return 'Accès';
      default: return type;
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('fr-FR');
  };

  const getPageTitle = () => {
    if (userInfo?.role === 'Admin') {
      return 'Gestion des Demandes';
    } else if (userInfo?.role === 'Direction') {
      return 'Mes Demandes';
    }
    return 'Demandes';
  };

  const getPageDescription = () => {
    if (userInfo?.role === 'Admin') {
      return 'Validation et suivi des demandes des directions';
    } else if (userInfo?.role === 'Direction') {
      return 'Suivi de vos demandes et création de nouvelles demandes';
    }
    return 'Gestion des demandes';
  };

  return (
    <div className="demandes">
      <div style={{textAlign: 'right', marginBottom: '1rem'}}>
        {userInfo?.role === 'Direction' && (
          <button 
            onClick={() => setShowCreateForm(true)}
            className="btn-create-demande"
          >
            Créer une Demande
          </button>
        )}
      </div>

      {/* Formulaire de création de demande */}
      {showCreateForm && (
        <div className="create-demande-modal">
          <div className="modal-content">
            <h2>📝 Créer une Nouvelle Demande</h2>
            <form onSubmit={handleCreateDemande}>
              <div className="form-group">
                <label>Type de Demande:</label>
                <select 
                  value={newDemande.type} 
                  onChange={(e) => setNewDemande(prev => ({ ...prev, type: e.target.value }))}
                  required
                >
                  <option value="INSTALLATION">🔧 Installation</option>
                  <option value="RETRAIT">📦 Retrait</option>
                  <option value="ACCES">🔑 Accès</option>
                </select>
              </div>
              
              {/* Champs spécifiques pour les demandes d'accès */}
              {newDemande.type === 'ACCES' && (
                <>
                  <div className="form-group">
                    <label>Nom *:</label>
                    <input 
                      type="text" 
                      value={newDemande.nom}
                      onChange={(e) => setNewDemande(prev => ({ ...prev, nom: e.target.value }))}
                      required
                      placeholder="Nom de la personne"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Prénom *:</label>
                    <input 
                      type="text" 
                      value={newDemande.prenom}
                      onChange={(e) => setNewDemande(prev => ({ ...prev, prenom: e.target.value }))}
                      required
                      placeholder="Prénom de la personne"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>CIN * (format: 6 chiffres):</label>
                    <input 
                      type="text" 
                      value={newDemande.cin}
                      onChange={(e) => {
                        const value = e.target.value;
                        if (value === '' || /^\d{0,6}$/.test(value)) {
                          setNewDemande(prev => ({ ...prev, cin: value }));
                        }
                      }}
                      required
                      pattern="\d{6}"
                      title="Le CIN doit contenir exactement 6 chiffres"
                      placeholder="123456"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Date d'accès *:</label>
                    <input 
                      type="date" 
                      value={newDemande.date_acces}
                      onChange={(e) => {
                        const selectedDate = new Date(e.target.value);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        if (selectedDate >= today) {
                          setNewDemande(prev => ({ ...prev, date_acces: e.target.value }));
                        } else {
                          setError('La date ne peut pas être dans le passé');
                        }
                      }}
                      required
                      min={new Date().toISOString().split('T')[0]}
                      max={(() => {
                        const maxDate = new Date();
                        maxDate.setMonth(maxDate.getMonth() + 3); 
                        return maxDate.toISOString().split('T')[0];
                      })()}
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Code d'accès (optionnel):</label>
                    <input 
                      type="text" 
                      value={newDemande.code}
                      onChange={(e) => setNewDemande(prev => ({ ...prev, code: e.target.value }))}
                      placeholder="Code d'accès si applicable"
                    />
                  </div>
                </>
              )}
              
              {/* Champs spécifiques pour les demandes d'installation et retrait */}
              {(newDemande.type === 'INSTALLATION' || newDemande.type === 'RETRAIT') && (
                <>
                  <div className="form-group">
                    <label>Nom d'équipement *:</label>
                    <input 
                      type="text" 
                      value={newDemande.nom_equipement}
                      onChange={(e) => setNewDemande(prev => ({ ...prev, nom_equipement: e.target.value }))}
                      required
                      placeholder="Nom de l'équipement"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Numéro de série *:</label>
                    <input 
                      type="text" 
                      value={newDemande.serial_number}
                      onChange={(e) => setNewDemande(prev => ({ ...prev, serial_number: e.target.value }))}
                      required
                      placeholder="Numéro de série de l'équipement"
                    />
                  </div>
                </>
              )}
              
              <div className="form-group">
                <label>📄 Justificatif:</label>
                <div className="justificatif-info">
                  <p>✅ Le justificatif PDF sera généré automatiquement avec toutes les informations saisies</p>
                  <p>📋 Le PDF contiendra les détails de la demande, les informations personnelles/équipement, et les approbations</p>
                </div>
              </div>
              
              <div className="form-actions">
                <button type="submit" className="btn-submit" disabled={loading}>
                  {loading ? 'Création...' : 'Créer la Demande'}
                </button>
                <button 
                  type="button" 
                  onClick={() => setShowCreateForm(false)}
                  className="btn-cancel"
                >
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Statistiques globales (Admin seulement) */}
      {userInfo?.role === 'Admin' && (
        <div className="stats-section">
                      <h2>Statistiques des Demandes</h2>
          <div className="stats-grid">
            {stats.map(stat => (
              <div key={stat.statut} className="stat-card">
                <div className="stat-value">{stat.total}</div>
                <div className="stat-label">{getStatusText(stat.statut)}</div>
                <div className={`stat-indicator ${getStatusColor(stat.statut)}`}></div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Pas d'onglets nécessaires pour l'admin - Affichage direct de la liste des demandes */}

      {/* Messages de succès/erreur */}
      {success && <div className="success-message">{success}</div>}
      {error && <div className="error-message">{error}</div>}

      {/* Contenu principal des demandes */}
      {(<div className="demandes-content">
          <div className="demandes-list">
            <h2>{userInfo?.role === 'Direction' ? 'Mes Demandes' : 'Liste des Demandes'}</h2>
            {loading ? (
              <div className="loading">Chargement en cours...</div>
            ) : !demandes || demandes.length === 0 ? (
              <p className="no-data">
                {userInfo?.role === 'Direction' ? 'Aucune demande créée' : 'Aucune demande enregistrée'}
              </p>
            ) : (
              <div className="demandes-grid">
                {demandes.map(demande => (
                  <div key={demande.id} className="demande-card">
                    <div className="demande-header">
                      <div className="demande-type">
                        {getTypeIcon(demande.type)} {getTypeText(demande.type)}
                      </div>
                      <div className={`demande-status ${getStatusColor(demande.statut)}`}>
                        {getStatusText(demande.statut)}
                      </div>
                    </div>
                    
                    <div className="demande-content">
                      <div className="demande-info">
                        {userInfo?.role === 'Admin' && (
                          <p><strong>🏢 Direction:</strong> {demande.direction_nom || 'Non spécifiée'}</p>
                        )}
                        <p><strong>📅 Date de demande:</strong> {formatDate(demande.date_soumission)}</p>
                        
                        {/* Informations d'accès pour les demandes d'accès */}
                        {demande.type === 'ACCES' && (
                          <div className="access-info">
                            <p><strong>👤 Nom:</strong> {demande.nom || 'Non spécifié'}</p>
                            <p><strong>👤 Prénom:</strong> {demande.prenom || 'Non spécifié'}</p>
                            <p><strong>🆔 CIN:</strong> {demande.cin || 'Non spécifié'}</p>
                            <p><strong>📅 Date d'accès:</strong> {demande.date_acces ? new Date(demande.date_acces).toLocaleDateString('fr-FR') : 'Non spécifiée'}</p>
                            {demande.code && (
                              <p><strong>🔑 Code d'accès:</strong> {demande.code}</p>
                            )}
                          </div>
                        )}
                        
                        {/* Informations d'équipement pour les demandes d'installation et retrait */}
                        {(demande.type === 'INSTALLATION' || demande.type === 'RETRAIT') && (
                          <div className="equipment-info">
                            <p><strong>🖥️ Nom d'équipement:</strong> {demande.nom_equipement || 'Non spécifié'}</p>
                            <p><strong>🔢 Numéro de série:</strong> {demande.serial_number || 'Non spécifié'}</p>
                          </div>
                        )}
                        
                        {/* Justificatif PDF */}
                        <div className="justificatif-section">
                          <p><strong>📎 Justificatif:</strong></p>
                          <div className="pdf-actions">
                            <button 
                              onClick={() => handleDownloadPDF(demande.id)}
                              className="btn-download-pdf"
                              disabled={loading}
                            >
                              📥 Télécharger le PDF
                            </button>
                            <span className="pdf-info">
                              ✅ PDF généré automatiquement avec toutes les informations de la demande
                            </span>
                          </div>
                        </div>
                      </div>
                      
                      {/* Actions admin seulement */}
                      {userInfo?.role === 'Admin' && demande.statut === 'EN_ATTENTE' && (
                        <div className="demande-actions">
                          <button 
                            onClick={() => handleAction('valider', demande.id)}
                            className="btn-validate"
                            disabled={loading}
                          >
                            ✅ Valider
                          </button>
                          <button 
                            onClick={() => handleAction('rejeter', demande.id)}
                            className="btn-reject"
                            disabled={loading}
                          >
                            ❌ Rejeter
                          </button>
                        </div>
                      )}
                      
                      {userInfo?.role === 'Admin' && demande.statut === 'VALIDEE' && (
                        <div className="demande-actions">
                          <button 
                            onClick={() => handleAction('terminer', demande.id)}
                            className="btn-complete"
                            disabled={loading}
                          >
                            ✅ Marquer comme Terminée
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

    </div>
  );
}

export default Demandes;
