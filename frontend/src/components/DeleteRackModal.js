import React, { useState } from 'react';
import api from '../services/api';
import './Modal.css';

function DeleteRackModal({ isOpen, onClose, rack, onSuccess }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleDelete = async () => {
    setLoading(true);
    setError('');

    try {
      await api.delete(`/raks/${rack.id}`);
      
      onSuccess();
      onClose();
    } catch (err) {
      console.error('Erreur suppression rack:', err);
      setError(err.response?.data?.error || err.message || 'Erreur lors de la suppression du rack');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen || !rack) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content delete-modal">
        <div className="modal-header">
          <h2>🗑️ Supprimer le Rack</h2>
          <button className="close-button" onClick={onClose}>&times;</button>
        </div>

        <div className="delete-warning">
          <div className="warning-icon">⚠️</div>
          <h3>Attention !</h3>
          <p>Vous êtes sur le point de supprimer le rack <strong>{rack.nom}</strong>.</p>
          
          <div className="rack-details">
            <p><strong>Localisation:</strong> {rack.localisation}</p>
            <p><strong>Direction:</strong> {rack.direction_nom || 'Non assignée'}</p>
          </div>

          <div className="warning-consequences">
            <h4>Cette action supprimera également :</h4>
            <ul>
              <li>✅ Tous les PDU associés à ce rack</li>
              <li>✅ Toutes les armoires de climatisation</li>
              <li>✅ Tous les capteurs électriques</li>
              <li>✅ Tous les capteurs de température</li>
              <li>✅ Toutes les données de mesure</li>
            </ul>
          </div>

          <div className="final-warning">
            <p><strong>Cette action est irréversible !</strong></p>
            <p>Êtes-vous sûr de vouloir continuer ?</p>
          </div>
        </div>

        {error && <div className="error-message">{error}</div>}

        <div className="modal-actions">
          <button type="button" className="cancel-button" onClick={onClose}>
            Annuler
          </button>
          <button 
            type="button" 
            className="delete-button" 
            onClick={handleDelete}
            disabled={loading}
          >
            {loading ? 'Suppression...' : 'Oui, Supprimer le Rack'}
          </button>
        </div>
      </div>
    </div>
  );
}

export default DeleteRackModal; 