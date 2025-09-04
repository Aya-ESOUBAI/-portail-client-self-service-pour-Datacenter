import React, { useState, useEffect } from 'react';
import api from '../services/api';

function Profile() {
  const [userInfo, setUserInfo] = useState(null);
  const [editing, setEditing] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [formData, setFormData] = useState({
    nom: '',
    email: '',
    telephone: '',
    mot_de_passe: '',
    nouveau_mot_de_passe: '',
    confirmer_mot_de_passe: ''
  });

  useEffect(() => {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    setUserInfo(currentUser);
    setFormData({
      nom: currentUser.nom || '',
      email: currentUser.email || '',
      telephone: currentUser.telephone || '',
      mot_de_passe: '',
      nouveau_mot_de_passe: '',
      confirmer_mot_de_passe: ''
    });
  }, []);

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (formData.nouveau_mot_de_passe && formData.nouveau_mot_de_passe !== formData.confirmer_mot_de_passe) {
      setError('Les nouveaux mots de passe ne correspondent pas');
      return;
    }

    if (formData.nouveau_mot_de_passe && formData.nouveau_mot_de_passe.length < 6) {
      setError('Le nouveau mot de passe doit contenir au moins 6 caractères');
      return;
    }

    try {
      setLoading(true);
      setError('');
      setSuccess('');

      const updateData = {
        nom: formData.nom,
        email: formData.email,
        telephone: formData.telephone
      };

      if (formData.mot_de_passe) {
        updateData.mot_de_passe = formData.mot_de_passe;
      }

      if (formData.nouveau_mot_de_passe) {
        updateData.nouveau_mot_de_passe = formData.nouveau_mot_de_passe;
      }

      await api.put(`/utilisateurs/${userInfo.id}`, updateData);

      const updatedUser = {
        ...userInfo,
        nom: formData.nom,
        email: formData.email,
        telephone: formData.telephone
      };
      localStorage.setItem('user', JSON.stringify(updatedUser));
      setUserInfo(updatedUser);

      setSuccess('Profil mis à jour avec succès');
      setEditing(false);
      
      setFormData(prev => ({
        ...prev,
        mot_de_passe: '',
        nouveau_mot_de_passe: '',
        confirmer_mot_de_passe: ''
      }));

    } catch (err) {
      setError('Erreur lors de la mise à jour du profil');
      console.error('Erreur mise à jour profil:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleCancel = () => {
    setEditing(false);
    setError('');
    setSuccess('');
    setFormData({
      nom: userInfo?.nom || '',
      email: userInfo?.email || '',
      telephone: userInfo?.telephone || '',
      mot_de_passe: '',
      nouveau_mot_de_passe: '',
      confirmer_mot_de_passe: ''
    });
  };

  const getRoleText = (role) => {
    switch (role) {
      case 'Admin': return '👨‍💼 Administrateur';
      case 'Direction': return '🏢 Direction';
      default: return role;
    }
  };

  const getRoleColor = (role) => {
    switch (role) {
      case 'Admin': return 'admin';
      case 'Direction': return 'direction';
      default: return 'default';
    }
  };

  if (!userInfo) {
    return <div className="loading">Chargement du profil...</div>;
  }

  return (
  <div className="profile">

      {/* Messages de succès/erreur */}
      {success && <div className="success-message">{success}</div>}
      {error && <div className="error-message">{error}</div>}

      <div className="profile-content">
        <div className="profile-card">
          <div className="profile-avatar">
            <div className="avatar-circle">
              {userInfo.nom ? userInfo.nom.charAt(0).toUpperCase() : 'U'}
            </div>
          </div>

          <div className="profile-info">
            <div className="profile-section">
              <h2>Informations Personnelles</h2>
              
              {!editing ? (
                <div className="info-display">
                  <div className="info-item">
                    <label>Nom complet:</label>
                    <span>{userInfo.nom || 'Non défini'}</span>
                  </div>
                  <div className="info-item">
                    <label>Email:</label>
                    <span>{userInfo.email}</span>
                  </div>
                  <div className="info-item">
                    <label>Numéro de téléphone:</label>
                    <span>{userInfo.telephone || 'Non configuré'}</span>
                  </div>
                  <div className="info-item">
                    <label>Rôle:</label>
                    <span className={`role-badge ${getRoleColor(userInfo.role)}`}>
                      {getRoleText(userInfo.role)}
                    </span>
                  </div>
                  <div className="info-item">
                    <label>ID Utilisateur:</label>
                    <span>{userInfo.id}</span>
                  </div>
                  
                  <button 
                    onClick={() => setEditing(true)}
                    className="btn-edit-profile"
                  >
                    ✏️ Modifier le Profil
                  </button>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="profile-form">
                  <div className="form-group">
                    <label>Nom complet:</label>
                    <input
                      type="text"
                      name="nom"
                      value={formData.nom}
                      onChange={handleInputChange}
                      required
                    />
                  </div>

                  <div className="form-group">
                    <label>Email:</label>
                    <input
                      type="email"
                      name="email"
                      value={formData.email}
                      onChange={handleInputChange}
                      required
                    />
                  </div>

                  <div className="form-group">
                    <label>Numéro de téléphone:</label>
                    <input
                      type="tel"
                      name="telephone"
                      value={formData.telephone}
                      onChange={handleInputChange}
                      placeholder="+33 1 23 45 67 89"
                    />
                    <small>Format international recommandé pour les alertes SMS</small>
                  </div>

                  <div className="form-group">
                    <label>Rôle:</label>
                    <span className={`role-badge ${getRoleColor(userInfo.role)}`}>
                      {getRoleText(userInfo.role)}
                    </span>
                    <small>Le rôle ne peut pas être modifié</small>
                  </div>

                  <div className="password-section">
                    <h3>🔐 Changer le Mot de Passe</h3>
                    <div className="form-group">
                      <label>Mot de passe actuel:</label>
                      <input
                        type="password"
                        name="mot_de_passe"
                        value={formData.mot_de_passe}
                        onChange={handleInputChange}
                        placeholder="Laissez vide si pas de changement"
                      />
                    </div>

                    <div className="form-group">
                      <label>Nouveau mot de passe:</label>
                      <input
                        type="password"
                        name="nouveau_mot_de_passe"
                        value={formData.nouveau_mot_de_passe}
                        onChange={handleInputChange}
                        placeholder="Minimum 6 caractères"
                      />
                    </div>

                    <div className="form-group">
                      <label>Confirmer le nouveau mot de passe:</label>
                      <input
                        type="password"
                        name="confirmer_mot_de_passe"
                        value={formData.confirmer_mot_de_passe}
                        onChange={handleInputChange}
                        placeholder="Répétez le nouveau mot de passe"
                      />
                    </div>
                  </div>

                  <div className="form-actions">
                    <button 
                      type="submit" 
                      className="btn-save-profile"
                      disabled={loading}
                    >
                      {loading ? '💾 Sauvegarde...' : '💾 Sauvegarder'}
                    </button>
                    <button 
                      type="button" 
                      onClick={handleCancel}
                      className="btn-cancel-profile"
                    >
                      ❌ Annuler
                    </button>
                  </div>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Profile; 