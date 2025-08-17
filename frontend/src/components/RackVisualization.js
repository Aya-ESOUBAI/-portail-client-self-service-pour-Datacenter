import React from 'react';
import { useTheme } from '../hooks/useTheme';
import './RackVisualization.css';

const RackVisualization = ({ racksData, loading, error }) => {
  const { isDark } = useTheme();

  if (loading) {
    return (
      <div className="rack-visualization-loading">
        <div className="loading-spinner"></div>
        <p>Chargement des données des racks...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="rack-visualization-error">
        <p>Erreur lors du chargement des données: {error}</p>
      </div>
    );
  }

  if (!racksData || racksData.length === 0) {
    return (
      <div className="rack-visualization-empty">
        <p>Aucun rack trouvé.</p>
      </div>
    );
  }

  const formatValue = (value, unit) => {
    if (value === null || value === undefined) return 'N/A';
    return `${value} ${unit || ''}`;
  };

  const getIntensityColor = (value, unit) => {
    if (!value) return '#ccc';
    const numValue = parseFloat(value);
    if (unit === 'A') {
      if (numValue > 15) return '#ff4444'; // Rouge pour intensité élevée
      if (numValue > 10) return '#ffaa00'; // Orange pour intensité moyenne
      return '#44ff44'; // Vert pour intensité normale
    }
    return '#666';
  };

  const getTemperatureColor = (value, unit) => {
    if (!value) return '#ccc';
    const numValue = parseFloat(value);
    if (unit === '°C') {
      if (numValue > 30) return '#ff4444'; // Rouge pour température élevée
      if (numValue > 25) return '#ffaa00'; // Orange pour température moyenne
      return '#44ff44'; // Vert pour température normale
    }
    return '#666';
  };



  return (
    <div className={`rack-visualization ${isDark ? 'dark' : 'light'}`}>
      <div className="racks-grid">
        {racksData.map((rack) => (
          <div key={rack.rack_id} className="rack-container">
            {/* En-tête du rack avec température */}
            <div className="rack-header">
              <div className="rack-name">{rack.rack_nom}</div>
              <div className="rack-location">{rack.localisation}</div>
              <div className="temperature-display">
                <div className="temp-item">
                  <span className="temp-label">Air Entrant:</span>
                  <span 
                    className="temp-value"
                    style={{ color: getTemperatureColor(rack.temperature_entrant, rack.unite_temp_entrant) }}
                  >
                    {formatValue(rack.temperature_entrant, rack.unite_temp_entrant)}
                  </span>
                </div>
                <div className="temp-item">
                  <span className="temp-label">Air Sortant:</span>
                  <span 
                    className="temp-value"
                    style={{ color: getTemperatureColor(rack.temperature_sortant, rack.unite_temp_sortant) }}
                  >
                    {formatValue(rack.temperature_sortant, rack.unite_temp_sortant)}
                  </span>
                </div>
              </div>
            </div>

            {/* Corps du rack avec PDU gauche et droite */}
            <div className="rack-body">
              {/* PDU Gauche */}
              <div className="pdu-section pdu-left">
                <div className="pdu-header">
                  <div className="pdu-title">PDU Gauche</div>
                  <div className="pdu-info">
                    {rack.pdu_gauche_marque} {rack.pdu_gauche_modele}
                  </div>
                </div>
                <div className="pdu-blocks">
                  {/* Bloc 1 */}
                  <div className="block-section">
                    <div className="block-label">Bloc 1</div>
                    <div 
                      className="intensity-display"
                      style={{ 
                        backgroundColor: getIntensityColor(rack.intensite_gauche_bloc1, rack.unite_gauche_bloc1),
                        color: '#fff'
                      }}
                    >
                      {formatValue(rack.intensite_gauche_bloc1, rack.unite_gauche_bloc1)}
                    </div>
                  </div>
                  {/* Bloc 2 */}
                  <div className="block-section">
                    <div className="block-label">Bloc 2</div>
                    <div 
                      className="intensity-display"
                      style={{ 
                        backgroundColor: getIntensityColor(rack.intensite_gauche_bloc2, rack.unite_gauche_bloc2),
                        color: '#fff'
                      }}
                    >
                      {formatValue(rack.intensite_gauche_bloc2, rack.unite_gauche_bloc2)}
                    </div>
                  </div>
                </div>
              </div>

              {/* Espace central */}
              <div className="rack-center">
                <div className="rack-vertical-line"></div>
              </div>

              {/* PDU Droite */}
              <div className="pdu-section pdu-right">
                <div className="pdu-header">
                  <div className="pdu-title">PDU Droite</div>
                  <div className="pdu-info">
                    {rack.pdu_droite_marque} {rack.pdu_droite_modele}
                  </div>
                </div>
                <div className="pdu-blocks">
                  {/* Bloc 1 */}
                  <div className="block-section">
                    <div className="block-label">Bloc 1</div>
                    <div 
                      className="intensity-display"
                      style={{ 
                        backgroundColor: getIntensityColor(rack.intensite_droite_bloc1, rack.unite_droite_bloc1),
                        color: '#fff'
                      }}
                    >
                      {formatValue(rack.intensite_droite_bloc1, rack.unite_droite_bloc1)}
                    </div>
                  </div>
                  {/* Bloc 2 */}
                  <div className="block-section">
                    <div className="block-label">Bloc 2</div>
                    <div 
                      className="intensity-display"
                      style={{ 
                        backgroundColor: getIntensityColor(rack.intensite_droite_bloc2, rack.unite_droite_bloc2),
                        color: '#fff'
                      }}
                    >
                      {formatValue(rack.intensite_droite_bloc2, rack.unite_droite_bloc2)}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Pied du rack */}
            <div className="rack-footer">
              <div className="rack-direction">
                Direction: {rack.direction_nom || 'Non assignée'}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default RackVisualization;
