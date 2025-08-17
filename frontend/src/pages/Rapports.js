import React, { useEffect, useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, BarChart, Bar, PieChart, Pie, Cell } from 'recharts';
import api from '../services/api';
import './Rapports.css';

function Rapports() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [userInfo, setUserInfo] = useState(null);
  
  // États pour les données des graphiques
  const [temperatureData, setTemperatureData] = useState([]);
  const [consumptionData, setConsumptionData] = useState([]);
  const [accessData, setAccessData] = useState([]);
  const [rackList, setRackList] = useState([]);
  
  // États pour les filtres
  const [selectedRack, setSelectedRack] = useState('all');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [dataType, setDataType] = useState('temperature'); // temperature, consumption, access
  const [showPowerChart, setShowPowerChart] = useState(false);

  useEffect(() => {
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}');
    setUserInfo(currentUser);
    
    // Définir la période par défaut (7 derniers jours)
    const end = new Date();
    const start = new Date();
    start.setDate(start.getDate() - 7);
    
    setEndDate(end.toISOString().split('T')[0]);
    setStartDate(start.toISOString().split('T')[0]);
    
    loadRackList(currentUser.id);
  }, []);

  useEffect(() => {
    if (startDate && endDate) {
      loadData();
    }
  }, [startDate, endDate, selectedRack, dataType]);

  const loadRackList = async (userId) => {
    try {
      const response = await api.get(`/raks-by-user-direction/${userId}`);
      setRackList(response.data);
    } catch (err) {
      console.error('Erreur chargement racks:', err);
    }
  };

  const loadData = async () => {
    try {
      setLoading(true);
      
      if (dataType === 'temperature') {
        await loadTemperatureData();
      } else if (dataType === 'consumption') {
        await loadConsumptionData();
      } else if (dataType === 'access') {
        await loadAccessData();
      }
      
    } catch (err) {
      setError('Erreur lors du chargement des données');
      console.error('Erreur données:', err);
    } finally {
      setLoading(false);
    }
  };

  const loadTemperatureData = async () => {
    try {
      const params = {
        start_date: startDate,
        end_date: endDate,
        rack_id: selectedRack !== 'all' ? selectedRack : null
      };
      
      const response = await api.get('/temperature-stats-by-rack', { params });
      setTemperatureData(response.data);
    } catch (err) {
      console.error('Erreur température:', err);
      setTemperatureData([]);
    }
  };

  const loadConsumptionData = async () => {
    try {
      const params = {
        start_date: startDate,
        end_date: endDate,
        rack_id: selectedRack !== 'all' ? selectedRack : null
      };
      
      const response = await api.get('/energy-stats-by-rack', { params });
      setConsumptionData(response.data);
    } catch (err) {
      console.error('Erreur consommation:', err);
      setConsumptionData([]);
    }
  };

  const loadAccessData = async () => {
    try {
      const params = {
        start_date: startDate,
        end_date: endDate,
        type: 'ACCES'
      };
      
      const response = await api.get('/demandes-by-direction', { params });
      setAccessData(response.data);
    } catch (err) {
      console.error('Erreur accès:', err);
      setAccessData([]);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const colors = {
    grid: '#E5E7EB',
    text: '#374151',
    line: '#2563EB',
    area: '#DBEAFE'
  };

  const renderTemperatureChart = () => {
    // Largeur dynamique : 100% si <=10 points, sinon 1200px
    const tempWidth = temperatureData.length > 10 ? 1200 : '100%';
    return (
      <div className="chart-container">
        <h3>Évolution de la Température</h3>
        <ResponsiveContainer width={tempWidth} height={400}>
          <LineChart data={temperatureData}>
            <CartesianGrid strokeDasharray="3 3" stroke={colors.grid} />
            <XAxis 
              dataKey="date_mesure" 
              tick={{ fill: colors.text }}
              tickFormatter={formatDate}
            />
            <YAxis 
              tick={{ fill: colors.text }}
              label={{ value: 'Température (°C)', angle: -90, position: 'insideLeft', fill: colors.text }}
            />
            <Tooltip 
              contentStyle={{ backgroundColor: '#FFFFFF', border: `1px solid ${colors.grid}` }}
              labelFormatter={formatDate}
            />
            <Legend />
            {/* Air entrant */}
            <Line 
              type="monotone" 
              dataKey="air_entrant" 
              name="Air Entrant"
              stroke="#2563EB" 
              strokeWidth={2}
              dot={{ fill: '#2563EB', strokeWidth: 2, r: 4 }}
              activeDot={{ r: 6, stroke: '#2563EB', strokeWidth: 2, fill: '#DBEAFE' }}
            />
            {/* Air sortant */}
            <Line 
              type="monotone" 
              dataKey="air_sortant" 
              name="Air Sortant"
              stroke="#F59E0B" 
              strokeWidth={2}
              dot={{ fill: '#F59E0B', strokeWidth: 2, r: 4 }}
              activeDot={{ r: 6, stroke: '#F59E0B', strokeWidth: 2, fill: '#FEF3C7' }}
            />
          </LineChart>
        </ResponsiveContainer>
      </div>
    );
  };

  const renderConsumptionChart = () => (
    <div className="chart-container">
      <h3>⚡ Évolution de la Consommation Électrique</h3>
      
      {/* Bouton pour basculer entre intensité et puissance */}
      <div className="chart-toggle">
        <button 
          className={`toggle-btn ${!showPowerChart ? 'active' : ''}`}
          onClick={() => setShowPowerChart(false)}
        >
          Intensité (A)
        </button>
        <button 
          className={`toggle-btn ${showPowerChart ? 'active' : ''}`}
          onClick={() => setShowPowerChart(true)}
        >
          Puissance (kW)
        </button>
      </div>
      
      {/* Largeur dynamique pour consommation */}
      <ResponsiveContainer width={consumptionData.length > 10 ? 1200 : '100%'} height={400}>
        <LineChart data={consumptionData}>
          <CartesianGrid strokeDasharray="3 3" stroke={colors.grid} />
          <XAxis 
            dataKey="date_mesure" 
            tick={{ fill: colors.text }}
            tickFormatter={formatDate}
          />
          <YAxis 
            tick={{ fill: colors.text }}
            label={{ 
              value: showPowerChart ? 'Puissance (kW)' : 'Intensité Moyenne (A)', 
              angle: -90, 
              position: 'insideLeft', 
              fill: colors.text 
            }}
          />
          <Tooltip 
            contentStyle={{ backgroundColor: '#FFFFFF', border: `1px solid ${colors.grid}` }}
            labelFormatter={formatDate}
            formatter={(value, name, props) => {
              if (showPowerChart) {
                return [`${value} kW`, 'Puissance Rack'];
              } else {
                return [`${value} A (moyenne des 4 blocks)`, 'Intensité Rack'];
              }
            }}
          />
          <Legend />
          <Line 
            type="monotone" 
            dataKey={showPowerChart ? "puissance_kw" : "valeur"} 
            stroke={showPowerChart ? "#F59E0B" : "#10B981"} 
            strokeWidth={2}
            dot={{ fill: showPowerChart ? "#F59E0B" : "#10B981", strokeWidth: 2, r: 4 }}
            activeDot={{ r: 6, stroke: showPowerChart ? "#F59E0B" : "#10B981", strokeWidth: 2, fill: showPowerChart ? "#FEF3C7" : "#D1FAE5" }}
          />
        </LineChart>
      </ResponsiveContainer>
      

    </div>
  );

  const renderAccessChart = () => {
    // Grouper les accès par utilisateur
    // Filtrer uniquement les accès physiques validés
    const validatedAccess = accessData.filter(a => a.statut === 'VALIDEE');
    const accessByUser = validatedAccess.reduce((acc, access) => {
      const userName = access.utilisateur_nom || 'Utilisateur inconnu';
      acc[userName] = (acc[userName] || 0) + 1;
      return acc;
    }, {});

    const pieData = Object.entries(accessByUser).map(([name, count]) => ({
      name,
      value: count
    }));

    const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884D8'];

    return (
      <div className="chart-container">
        <h3>🚪 Accès Physiques Validés par Utilisateur</h3>
        <ResponsiveContainer width={pieData.length > 10 ? 1200 : '100%'} height={400}>
          <PieChart>
            <Pie
              data={pieData}
              cx="50%"
              cy="50%"
              labelLine={false}
              label={({ name, percent }) => `${name} (${(percent * 100).toFixed(0)}%)`}
              outerRadius={120}
              fill="#8884d8"
              dataKey="value"
            >
              {pieData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Tooltip 
              contentStyle={{ backgroundColor: '#FFFFFF', border: `1px solid ${colors.grid}` }}
            />
          </PieChart>
        </ResponsiveContainer>
        
        {/* Tableau des accès détaillés */}
        <div className="access-table-container">
          <h4>📋 Détail des Accès Validés</h4>
          <div className="access-table">
            <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Utilisateur</th>
                  <th>Direction</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                {validatedAccess.map((access, index) => (
                  <tr key={index}>
                    <td>{formatDate(access.date_soumission)}</td>
                    <td>{access.utilisateur_nom || 'N/A'}</td>
                    <td>{access.direction_nom || 'N/A'}</td>
                    <td>
                      <span className={`status-badge status-${access.statut.toLowerCase()}`}>
                        {access.statut}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
  );
  };

  if (loading && !temperatureData.length && !consumptionData.length && !accessData.length) {
  return (
      <div className="rapports">
      </div>
    );
  }

  return (
  <div className="rapports">
          
      {/* Filtres */}
      <div className="filters-section">
        <h2>🔍 Filtres et Paramètres</h2>
        <div className="filters-grid">
          <div className="filter-group">
            <label>Type de Données:</label>
            <select 
              value={dataType} 
              onChange={(e) => setDataType(e.target.value)}
              className="filter-select"
            >
              <option value="temperature">🌡️ Température</option>
              <option value="consumption">⚡ Consommation Électrique</option>
              <option value="access">🚪 Accès Physiques</option>
            </select>
          </div>
          
          <div className="filter-group">
            <label>Rack:</label>
            <select
              value={selectedRack} 
              onChange={(e) => setSelectedRack(e.target.value)}
              className="filter-select"
            >
              <option value="all">Tous les racks</option>
              {rackList.map((rack) => (
                <option key={rack.id} value={rack.id}>
                  {rack.nom}
                </option>
              ))}
            </select>
          </div>
          
          <div className="filter-group">
            <label>Date de début:</label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="filter-input"
            />
          </div>

          <div className="filter-group">
            <label>Date de fin:</label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="filter-input"
            />
          </div>
        </div>
        
        <button onClick={loadData} className="refresh-btn">
          🔄 Actualiser les Données
        </button>
      </div>

      {error && (
        <div className="error-message">
          {error}
        </div>
      )}

      {/* Graphiques */}
      <div className="charts-section">
        {dataType === 'temperature' && renderTemperatureChart()}
        {dataType === 'consumption' && renderConsumptionChart()}
        {dataType === 'access' && renderAccessChart()}
      </div>

      {/* Statistiques résumées */}
      <div className="stats-summary">
        <h2>📈 Résumé des Données</h2>
        <div className="stats-grid">
          <div className="stat-card">
            <h3>Période Analysée</h3>
            <p>Du {startDate} au {endDate}</p>
          </div>
          <div className="stat-card">
            <h3>Type de Données</h3>
            <p>
              {dataType === 'temperature' && '🌡️ Température'}
              {dataType === 'consumption' && '⚡ Consommation Électrique'}
              {dataType === 'access' && '🚪 Accès Physiques'}
            </p>
          </div>
          <div className="stat-card">
            <h3>Rack Sélectionné</h3>
            <p>
              {selectedRack === 'all' 
                ? 'Tous les racks' 
                : rackList.find(r => r.id == selectedRack)?.nom || 'N/A'
              }
            </p>
          </div>
          <div className="stat-card">
            <h3>Nombre de Données</h3>
            <p>
              {dataType === 'temperature' && temperatureData.length}
              {dataType === 'consumption' && consumptionData.length}
              {dataType === 'access' && accessData.length}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Rapports;
