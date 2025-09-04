import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { formatLocation } from '../utils/locationUtils';

function Consommation() {
  const [racksData, setRacksData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [userInfo, setUserInfo] = useState(null);

  useEffect(() => {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    setUserInfo(currentUser);

    const loadRacksData = async () => {
      try {
        setLoading(true);
        
        const response = await api.get(`/raks-complete-data/${currentUser.id || 1}`);
        setRacksData(Array.isArray(response.data) ? response.data : []);
        
      } catch (err) {
        setError('Erreur lors du chargement des données des racks');
        console.error('Erreur racks:', err);
        setRacksData([]);
      } finally {
        setLoading(false);
      }
    };

    loadRacksData();
  }, []);

  const getIntensityColor = (intensity) => {
    if (!intensity || intensity === 0) return 'text-gray-500';
    const value = parseFloat(intensity);
    if (value < 10) return 'text-green-600';
    if (value < 20) return 'text-yellow-600';
    return 'text-red-600';
  };

  const formatIntensity = (intensity, unite) => {
    if (!intensity || intensity === 0) return 'N/A';
    return `${parseFloat(intensity).toFixed(1)} ${unite || 'A'}`;
  };

  const getConsumptionColor = (valeur) => {
    const val = parseFloat(valeur);
    if (val < 100) return 'text-green-600';
    if (val < 300) return 'text-yellow-600';
    return 'text-red-600';
  };

  const getPageTitle = () => {
    if (userInfo?.role === 'Admin') {
      return 'Consommation Électrique - Toutes les Directions';
    } else if (userInfo?.role === 'Direction') {
      return 'Consommation Électrique - Votre Direction';
    }
    return 'Consommation Électrique';
  };

  const getPageDescription = () => {
    if (userInfo?.role === 'Admin') {
      return 'Surveillance de la consommation électrique de toutes les directions';
    } else if (userInfo?.role === 'Direction') {
      return 'Surveillance de votre consommation électrique';
    }
    return 'Surveillance de la consommation électrique des PDU';
  };

  if (loading) {
    return (
      <div className="consommation">
        
      </div>
    );
  }

  return (
  <div className="consommation">

      {/* Section de statistiques globales */}
      <div className="consumption-stats-section">
        <h2>📈 Statistiques Globales de Consommation</h2>
        <p>Vue d'ensemble de la consommation électrique par rack et PDU</p>
      </div>

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      {racksData.length === 0 ? (
        <div className="no-data">
          <p>Aucun rack trouvé.</p>
        </div>
      ) : (
        <div className="consommation-content">
          {racksData.map((rack, index) => (
            <div key={index} className="rack-consumption-card">
              {/* Header du rack avec températures */}
              <div className="rack-header">
                <h2>{rack.rack_nom}</h2>
                <div className="rack-info">
                  <p><strong>📍 Localisation:</strong> {formatLocation(rack.localisation)}</p>
                  <p><strong>🏢 Direction:</strong> {rack.direction_nom || 'Non assignée'}</p>
                </div>
              </div>
              
              {/* Section des 2 PDUs */}
              <div className="pdus-grid">
                {/* PDU Gauche */}
                <div className="pdu-card">
                  <div className="pdu-header">
                    <h3>PDU Gauche</h3>
                    <div className="pdu-specs">
                      <p><strong>Marque:</strong> {rack.pdu_gauche_marque || 'N/A'}</p>
                      <p><strong>Modèle:</strong> {rack.pdu_gauche_modele || 'N/A'}</p>
                      <p><strong>Prises:</strong> {rack.pdu_gauche_prises || 'N/A'}</p>
                      <p><strong>Type:</strong> {rack.pdu_gauche_connexion || 'N/A'}</p>
                    </div>
                  </div>
                  
                  <div className="pdu-blocks">
                    <h4>Intensité des Blocs</h4>
                    <div className="blocks-grid">
                      <div className="block-item">
                        <span className="block-label">Bloc 1:</span>
                        <span className={`block-value ${getIntensityColor(rack.intensite_gauche_bloc1)}`}>
                          {formatIntensity(rack.intensite_gauche_bloc1, rack.unite_gauche_bloc1)}
                        </span>
                      </div>
                      <div className="block-item">
                        <span className="block-label">Bloc 2:</span>
                        <span className={`block-value ${getIntensityColor(rack.intensite_gauche_bloc2)}`}>
                          {formatIntensity(rack.intensite_gauche_bloc2, rack.unite_gauche_bloc2)}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* PDU Droite */}
                <div className="pdu-card">
                  <div className="pdu-header">
                    <h3>PDU Droite</h3>
                    <div className="pdu-specs">
                      <p><strong>Marque:</strong> {rack.pdu_droite_marque || 'N/A'}</p>
                      <p><strong>Modèle:</strong> {rack.pdu_droite_modele || 'N/A'}</p>
                      <p><strong>Prises:</strong> {rack.pdu_droite_prises || 'N/A'}</p>
                      <p><strong>Type:</strong> {rack.pdu_droite_connexion || 'N/A'}</p>
                    </div>
                  </div>
                  
                  <div className="pdu-blocks">
                    <h4>Intensité des Blocs</h4>
                    <div className="blocks-grid">
                      <div className="block-item">
                        <span className="block-label">Bloc 1:</span>
                        <span className={`block-value ${getIntensityColor(rack.intensite_droite_bloc1)}`}>
                          {formatIntensity(rack.intensite_droite_bloc1, rack.unite_droite_bloc1)}
                        </span>
                      </div>
                      <div className="block-item">
                        <span className="block-label">Bloc 2:</span>
                        <span className={`block-value ${getIntensityColor(rack.intensite_droite_bloc2)}`}>
                          {formatIntensity(rack.intensite_droite_bloc2, rack.unite_droite_bloc2)}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export default Consommation; 