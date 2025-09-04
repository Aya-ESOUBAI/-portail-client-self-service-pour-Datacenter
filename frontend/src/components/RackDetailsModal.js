import React, { useState, useEffect } from 'react';
import api from '../services/api';
import './Modal.css';

function RackDetailsModal({ isOpen, onClose, rack }) {
  const [pdus, setPdus] = useState([]);
  const [armoires, setArmoires] = useState([]);
  const [capteursElectriques, setCapteursElectriques] = useState([]);
  const [capteursTemperature, setCapteursTemperature] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (isOpen && rack) {
      loadRackDetails();
    }
  }, [isOpen, rack]);

  const loadRackDetails = async () => {
    setLoading(true);
    setError('');

    try {
      const pdusRes = await api.get(`/pdus-by-rack/${rack.id}`);
      setPdus(Array.isArray(pdusRes.data) ? pdusRes.data : []);

      const armoiresRes = await api.get(`/armoiresdeclime-by-rack/${rack.id}`);
      setArmoires(Array.isArray(armoiresRes.data) ? armoiresRes.data : []);

      const capteursElecRes = await api.get(`/capteurelectriques-by-rack/${rack.id}`);
      setCapteursElectriques(Array.isArray(capteursElecRes.data) ? capteursElecRes.data : []);

      const capteursTempRes = await api.get(`/capteurdeclimes-by-rack/${rack.id}`);
      setCapteursTemperature(Array.isArray(capteursTempRes.data) ? capteursTempRes.data : []);

    } catch (err) {
      console.error('Erreur chargement détails rack:', err);
      setError('Erreur lors du chargement des détails du rack');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen || !rack) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content details-modal">
        <div className="modal-header">
          <h2>Détails du Rack</h2>
          <button className="close-button" onClick={onClose}>&times;</button>
        </div>

        {loading ? (
          <div className="loading">Chargement des détails...</div>
        ) : (
          <div className="rack-details-content">
            {/* Informations générales du rack */}
            <div className="rack-info-section">
              <h3>Informations Générales</h3>
              <div className="info-grid">
                <div className="info-item">
                  <label>Nom:</label>
                  <span>{rack.nom}</span>
                </div>
                <div className="info-item">
                  <label>Localisation:</label>
                  <span>{rack.localisation}</span>
                </div>
                <div className="info-item">
                  <label>Direction:</label>
                  <span>{rack.direction_nom || 'Non assignée'}</span>
                </div>
                <div className="info-item">
                  <label>Adresse Direction:</label>
                  <span>{rack.direction_adresse || 'Non spécifiée'}</span>
                </div>
              </div>
            </div>

            {/* PDU */}
            <div className="equipment-section">
              <h3>PDU (Power Distribution Units)</h3>
              {pdus.length > 0 ? (
                <div className="equipment-list">
                  {pdus.map(pdu => (
                    <div key={pdu.id} className="equipment-item">
                      <div className="equipment-header">
                        <h4>{pdu.marque} {pdu.modele}</h4>
                        <span className="equipment-type">PDU</span>
                      </div>
                      <div className="equipment-details">
                        <p><strong>Marque:</strong> {pdu.marque}</p>
                        <p><strong>Modèle:</strong> {pdu.modele}</p>
                        <p><strong>Nombre de prises:</strong> {pdu.nombre_prises}</p>
                        <p><strong>Type de connexion:</strong> {pdu.type_connexion}</p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="no-data">Aucun PDU associé à ce rack</p>
              )}
            </div>

            {/* Armoires de climatisation */}
            <div className="equipment-section">
              <h3>Armoires de Climatisation</h3>
              {armoires.length > 0 ? (
                <div className="equipment-list">
                  {armoires.map(armoire => (
                    <div key={armoire.id} className="equipment-item">
                      <div className="equipment-header">
                        <h4>{armoire.marque} {armoire.modele}</h4>
                        <span className="equipment-type">Climatisation</span>
                      </div>
                      <div className="equipment-details">
                        <p><strong>Marque:</strong> {armoire.marque}</p>
                        <p><strong>Modèle:</strong> {armoire.modele}</p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="no-data">Aucune armoire de climatisation associée à ce rack</p>
              )}
            </div>

            {/* Capteurs électriques */}
            <div className="sensors-section">
              <h3>Capteurs Électriques</h3>
              {capteursElectriques.length > 0 ? (
                <div className="sensors-list">
                  {capteursElectriques.map(capteur => (
                    <div key={capteur.id} className="sensor-item">
                      <div className="sensor-header">
                        <h4>Capteur #{capteur.id}</h4>
                        <span className="sensor-type">Électrique</span>
                      </div>
                      <div className="sensor-details">
                        <p><strong>Prise ID:</strong> {capteur.prise_id}</p>
                        <p><strong>Valeur:</strong> {capteur.valeur} {capteur.unite}</p>
                        <p><strong>Date de mesure:</strong> {new Date(capteur.date_mesure).toLocaleString()}</p>
                        <p><strong>PDU:</strong> {capteur.pdu_marque} {capteur.pdu_modele}</p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="no-data">Aucun capteur électrique associé à ce rack</p>
              )}
            </div>

            {/* Capteurs de température */}
            <div className="sensors-section">
              <h3>Capteurs de Température</h3>
              {capteursTemperature.length > 0 ? (
                <div className="sensors-list">
                  {capteursTemperature.map(capteur => (
                    <div key={capteur.id} className="sensor-item">
                      <div className="sensor-header">
                        <h4>Capteur #{capteur.id}</h4>
                        <span className="sensor-type">Température</span>
                      </div>
                      <div className="sensor-details">
                        <p><strong>Type:</strong> {capteur.type}</p>
                        <p><strong>Valeur:</strong> {capteur.valeur} {capteur.unite}</p>
                        <p><strong>Date de mesure:</strong> {new Date(capteur.date_mesure).toLocaleString()}</p>
                        <p><strong>Armoire:</strong> {capteur.armoire_marque} {capteur.armoire_modele}</p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="no-data">Aucun capteur de température associé à ce rack</p>
              )}
            </div>

            {/* Statistiques */}
            <div className="stats-section">
              <h3>Statistiques</h3>
              <div className="stats-grid">
                <div className="stat-item">
                  <span className="stat-number">{pdus.length}</span>
                  <span className="stat-label">PDU</span>
                </div>
                <div className="stat-item">
                  <span className="stat-number">{armoires.length}</span>
                  <span className="stat-label">Armoires Climatisation</span>
                </div>
                <div className="stat-item">
                  <span className="stat-number">{capteursElectriques.length}</span>
                  <span className="stat-label">Capteurs Électriques</span>
                </div>
                <div className="stat-item">
                  <span className="stat-number">{capteursTemperature.length}</span>
                  <span className="stat-label">Capteurs Température</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {error && <div className="error-message">{error}</div>}

        <div className="modal-actions">
          <button type="button" className="close-button" onClick={onClose}>
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
}

export default RackDetailsModal; 