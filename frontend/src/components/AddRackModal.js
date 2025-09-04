import React, { useState, useEffect } from 'react';
import api from '../services/api';
import './Modal.css';

function AddRackModal({ isOpen, onClose, onSuccess }) {
  const [directions, setDirections] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  
  const [rackData, setRackData] = useState({
    nom: '',
    localisation: '',
    client_id: ''
  });
  
  const [pdus, setPdus] = useState([
    {
      marque: '',
      modele: '',
      nombre_prises: 1,
      type_connexion: 'Ethernet'
    }
  ]);
  
  const [armoires, setArmoires] = useState([
    {
      marque: '',
      modele: ''
    }
  ]);

  useEffect(() => {
    if (isOpen) {
      loadDirections();
    }
  }, [isOpen]);

  const loadDirections = async () => {
    try {
      const response = await api.get('/directions');
      setDirections(Array.isArray(response.data) ? response.data : []);
    } catch (err) {
      console.error('Erreur chargement directions:', err);
      setError('Erreur lors du chargement des directions');
    }
  };

  const handleRackChange = (field, value) => {
    setRackData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const addPdu = () => {
    setPdus(prev => [...prev, {
      marque: '',
      modele: '',
      nombre_prises: 1,
      type_connexion: 'Ethernet'
    }]);
  };

  const removePdu = (index) => {
    setPdus(prev => prev.filter((_, i) => i !== index));
  };

  const updatePdu = (index, field, value) => {
    setPdus(prev => prev.map((pdu, i) => 
      i === index ? { ...pdu, [field]: value } : pdu
    ));
  };

  const addArmoire = () => {
    setArmoires(prev => [...prev, {
      marque: '',
      modele: ''
    }]);
  };

  const removeArmoire = (index) => {
    setArmoires(prev => prev.filter((_, i) => i !== index));
  };

  const updateArmoire = (index, field, value) => {
    setArmoires(prev => prev.map((armoire, i) => 
      i === index ? { ...armoire, [field]: value } : armoire
    ));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      // Validation
      if (!rackData.nom || !rackData.localisation || !rackData.client_id) {
        throw new Error('Veuillez remplir tous les champs obligatoires du rack');
      }

      const rackResponse = await api.post('/raks', rackData);
      const rackId = rackResponse.data.id;

      for (const pdu of pdus) {
        if (pdu.marque && pdu.modele) {
          await api.post('/pdus', {
            ...pdu,
            rak_id: rackId
          });
        }
      }

      for (const armoire of armoires) {
        if (armoire.marque && armoire.modele) {
          await api.post('/armoiresdeclime', {
            ...armoire,
            rak_id: rackId
          });
        }
      }

      onSuccess();
      onClose();
    } catch (err) {
      console.error('Erreur création rack:', err);
      setError(err.response?.data?.error || err.message || 'Erreur lors de la création du rack');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <div className="modal-header">
          <h2>Ajouter un Nouveau Rack</h2>
          <button className="close-button" onClick={onClose}>&times;</button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="form-section">
            <h3>Informations du Rack</h3>
            
            <div className="form-group">
              <label>Nom du Rack *</label>
              <input
                type="text"
                value={rackData.nom}
                onChange={(e) => handleRackChange('nom', e.target.value)}
                placeholder="Ex: Rack-A1"
                required
              />
            </div>

            <div className="form-group">
              <label>Localisation *</label>
              <input
                type="text"
                value={rackData.localisation}
                onChange={(e) => handleRackChange('localisation', e.target.value)}
                placeholder="Ex: Salle 101 - Zone A"
                required
              />
            </div>

            <div className="form-group">
              <label>Direction *</label>
              <select
                value={rackData.client_id}
                onChange={(e) => handleRackChange('client_id', e.target.value)}
                required
              >
                <option value="">Sélectionner une direction</option>
                {directions.map(direction => (
                  <option key={direction.id} value={direction.id}>
                    {direction.nom}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="form-section">
            <h3>PDU (Power Distribution Unit)</h3>
            {pdus.map((pdu, index) => (
              <div key={index} className="pdu-item">
                <div className="pdu-header">
                  <h4>PDU {index + 1}</h4>
                  {pdus.length > 1 && (
                    <button
                      type="button"
                      className="remove-button"
                      onClick={() => removePdu(index)}
                    >
                      🗑️ Supprimer
                    </button>
                  )}
                </div>
                
                <div className="form-row">
                  <div className="form-group">
                    <label>Marque</label>
                    <input
                      type="text"
                      value={pdu.marque}
                      onChange={(e) => updatePdu(index, 'marque', e.target.value)}
                      placeholder="Ex: APC"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Modèle</label>
                    <input
                      type="text"
                      value={pdu.modele}
                      onChange={(e) => updatePdu(index, 'modele', e.target.value)}
                      placeholder="Ex: AP8959"
                    />
                  </div>
                </div>
                
                <div className="form-row">
                  <div className="form-group">
                    <label>Nombre de Prises</label>
                    <input
                      type="number"
                      min="1"
                      value={pdu.nombre_prises}
                      onChange={(e) => updatePdu(index, 'nombre_prises', parseInt(e.target.value))}
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Type de Connexion</label>
                    <select
                      value={pdu.type_connexion}
                      onChange={(e) => updatePdu(index, 'type_connexion', e.target.value)}
                    >
                      <option value="Ethernet">Ethernet</option>
                      <option value="USB">USB</option>
                      <option value="Serial">Serial</option>
                      <option value="WiFi">WiFi</option>
                    </select>
                  </div>
                </div>
              </div>
            ))}
            
            <button type="button" className="add-button" onClick={addPdu}>
                              Ajouter un PDU
            </button>
          </div>

          <div className="form-section">
            <h3>Armoires de Climatisation</h3>
            {armoires.map((armoire, index) => (
              <div key={index} className="armoire-item">
                <div className="armoire-header">
                  <h4>Armoire {index + 1}</h4>
                  {armoires.length > 1 && (
                    <button
                      type="button"
                      className="remove-button"
                      onClick={() => removeArmoire(index)}
                    >
                      🗑️ Supprimer
                    </button>
                  )}
                </div>
                
                <div className="form-row">
                  <div className="form-group">
                    <label>Marque</label>
                    <input
                      type="text"
                      value={armoire.marque}
                      onChange={(e) => updateArmoire(index, 'marque', e.target.value)}
                      placeholder="Ex: Liebert"
                    />
                  </div>
                  
                  <div className="form-group">
                    <label>Modèle</label>
                    <input
                      type="text"
                      value={armoire.modele}
                      onChange={(e) => updateArmoire(index, 'modele', e.target.value)}
                      placeholder="Ex: GXT3"
                    />
                  </div>
                </div>
              </div>
            ))}
            
            <button type="button" className="add-button" onClick={addArmoire}>
                              Ajouter une Armoire
            </button>
          </div>

          {error && <div className="error-message">{error}</div>}

          <div className="modal-actions">
            <button type="button" className="cancel-button" onClick={onClose}>
              Annuler
            </button>
            <button type="submit" className="submit-button" disabled={loading}>
              {loading ? 'Création...' : 'Créer le Rack'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default AddRackModal; 