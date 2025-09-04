import React, { useEffect, useState } from 'react';
import api from '../services/api';
import { formatLocation } from '../utils/locationUtils';

function Environnement() {
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

  const getTemperatureColor = (temperature) => {
    if (!temperature || temperature === 0) return 'text-gray-500';
    const value = parseFloat(temperature);
    if (value < 20) return 'text-blue-600';
    if (value < 25) return 'text-green-600';
    if (value < 30) return 'text-yellow-600';
    return 'text-red-600';
  };

  const formatTemperature = (temperature) => {
    if (!temperature || temperature === 0) return 'N/A';
    return `${parseFloat(temperature).toFixed(1)} °C`;
  };



  const getPageTitle = () => {
    if (userInfo?.role === 'Admin') {
      return 'Environnement - Toutes les Directions';
    } else if (userInfo?.role === 'Direction') {
      return 'Environnement - Votre Direction';
    }
    return 'Environnement';
  };

  if (loading) {
    return (
      <div className="environnement">
      </div>
    );
  }

  return (
  <div className="environnement">

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
        <div className="environnement-content">
          {racksData.map((rack, index) => (
            <div key={index} className="rack-environment-card">
              <div className="rack-header">
                <h2>{rack.rack_nom}</h2>
                <div className="rack-info">
                  <p><strong>📍 Localisation:</strong> {formatLocation(rack.localisation)}</p>
                  <p><strong>🏢 Direction:</strong> {rack.direction_nom || 'Non assignée'}</p>
                </div>
              </div>
              
              <div className="environment-stats">
                <div className="temperature-section">
                  <h3>🌡️ Températures Air</h3>
                  <div className="stats-grid">
                    <div className="stat-item">
                      <span className="stat-label">Air Entrant:</span>
                      <span className={`stat-value ${getTemperatureColor(rack.temperature_entrant)}`}>
                        {formatTemperature(rack.temperature_entrant)}
                      </span>
                    </div>
                    <div className="stat-item">
                      <span className="stat-label">Air Sortant:</span>
                      <span className={`stat-value ${getTemperatureColor(rack.temperature_sortant)}`}>
                        {formatTemperature(rack.temperature_sortant)}
                      </span>
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

export default Environnement; 