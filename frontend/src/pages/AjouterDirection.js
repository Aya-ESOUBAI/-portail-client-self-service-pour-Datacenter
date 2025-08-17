import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

const AjouterDirection = () => {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    nom: '',
    adresse: '',
    email_contact: '',
    telephone: '',
    mot_de_passe: ''
  });
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [credentials, setCredentials] = useState(null);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');
    setCredentials(null);

    try {
      const response = await api.post('/directions', formData);
      setMessage('Direction et compte utilisateur créés avec succès !');
      setCredentials({
        nom: formData.nom,
        email: formData.email_contact,
        mot_de_passe: response.data.mot_de_passe
      });
      
      // Rediriger après 5 secondes pour laisser le temps de voir les informations
      setTimeout(() => {
        navigate('/dashboard');
      }, 5000);
    } catch (error) {
      console.error('Erreur lors de l\'ajout de la direction:', error);
      setMessage('Erreur lors de l\'ajout de la direction. Veuillez réessayer.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="ajouter-direction">
      <div className="ajouter-direction-header">
        <h1>Ajouter une Direction</h1>
        <p>Remplissez le formulaire ci-dessous pour ajouter une nouvelle direction et créer son compte utilisateur</p>
      </div>

      <div className="ajouter-direction-content">
        <form onSubmit={handleSubmit} className="direction-form">
          <div className="form-group">
            <label htmlFor="nom">Nom de la Direction *</label>
            <input
              type="text"
              id="nom"
              name="nom"
              value={formData.nom}
              onChange={handleChange}
              required
              placeholder="Ex: Direction Informatique"
            />
          </div>

          <div className="form-group">
            <label htmlFor="adresse">Adresse *</label>
            <textarea
              id="adresse"
              name="adresse"
              value={formData.adresse}
              onChange={handleChange}
              required
              placeholder="Adresse complète de la direction"
              rows="3"
            />
          </div>

          <div className="form-group">
            <label htmlFor="email_contact">Email de Contact *</label>
            <input
              type="email"
              id="email_contact"
              name="email_contact"
              value={formData.email_contact}
              onChange={handleChange}
              required
              placeholder="contact@direction.com"
            />
          </div>

          <div className="form-group">
            <label htmlFor="telephone">Téléphone *</label>
            <input
              type="tel"
              id="telephone"
              name="telephone"
              value={formData.telephone}
              onChange={handleChange}
              required
              placeholder="+33 1 23 45 67 89"
            />
          </div>

          <div className="form-group">
            <label htmlFor="mot_de_passe">Mot de passe du compte Direction *</label>
            <input
              type="password"
              id="mot_de_passe"
              name="mot_de_passe"
              value={formData.mot_de_passe}
              onChange={handleChange}
              required
              placeholder="Mot de passe pour le compte utilisateur"
              minLength="6"
            />
            <small style={{ color: '#666', fontSize: '0.9rem', marginTop: '0.25rem', display: 'block' }}>
              Ce mot de passe sera utilisé pour le compte utilisateur de cette direction
            </small>
          </div>

          {message && (
            <div className={`message ${message.includes('succès') ? 'success-message' : 'error-message'}`}>
              {message}
            </div>
          )}

          {credentials && (
            <div className="credentials-info">
              <h3>✅ Compte créé avec succès !</h3>
              <p><strong>Informations de connexion :</strong></p>
              <div className="credentials-details">
                <p><strong>Nom d'utilisateur :</strong> {credentials.nom}</p>
                <p><strong>Email :</strong> {credentials.email}</p>
                <p><strong>Mot de passe :</strong> {credentials.mot_de_passe}</p>
                <p><strong>Rôle :</strong> Direction</p>
              </div>
              <p style={{ color: '#e74c3c', fontWeight: 'bold', marginTop: '1rem' }}>
                ⚠️ Notez ces informations ! Elles ne seront plus affichées après la redirection.
              </p>
            </div>
          )}

          <div className="form-actions">
            <button
              type="button"
              onClick={() => navigate('/dashboard')}
              className="btn-secondary"
              disabled={loading}
            >
              Annuler
            </button>
            <button
              type="submit"
              className="btn-primary"
              disabled={loading}
            >
              {loading ? 'Création en cours...' : 'Créer la Direction et le Compte'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default AjouterDirection; 