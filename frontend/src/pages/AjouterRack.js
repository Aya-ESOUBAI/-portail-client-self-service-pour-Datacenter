import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

// Simple notification function as fallback
const notify = {
  success: (message) => window.alert(`Succès: ${message}`),
  error: (message) => window.alert(`Erreur: ${message}`)
};

function AjouterRack() {
  const [directions, setDirections] = useState([]);
  const [formData, setFormData] = useState({
    nom: '',
    localisation: '',
    direction_id: '',
    pdus: [
      { marque: '', modele: '', ip_address: '', snmp_community: 'public', nombre_prises: 16, type_connexion: 'C13/C14' },
      { marque: '', modele: '', ip_address: '', snmp_community: 'public', nombre_prises: 16, type_connexion: 'C13/C14' }
    ],
    climatisation: { marque: '', modele: '' }
  });
  const [isLoading, setIsLoading] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchDirections = async () => {
      try {
        const response = await api.get('/directions');
        setDirections(response.data);
        if (response.data.length > 0) {
          setFormData(prev => ({ ...prev, direction_id: response.data[0].id }));
        }
      } catch (error) {
        console.error('Erreur lors du chargement des directions:', error);
        notify.error('Erreur lors du chargement des directions');
      }
    };

    fetchDirections();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handlePduChange = (index, e) => {
    const { name, value } = e.target;
    const newPdus = [...formData.pdus];
    newPdus[index] = { ...newPdus[index], [name]: value };
    setFormData(prev => ({
      ...prev,
      pdus: newPdus
    }));
  };

  const handleClimatisationChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      climatisation: {
        ...prev.climatisation,
        [name]: value
      }
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    
    try {
      // 1. Créer le rack
      const rackResponse = await api.post('/racks', {
        nom: formData.nom,
        localisation: formData.localisation,
        direction_id: formData.direction_id
      });

      const rackId = rackResponse.data.id;

      // 2. Créer les PDU
      await Promise.all(
        formData.pdus.map(pdu => 
          api.post('/pdus', { ...pdu, rak_id: rackId })
        )
      );

      // 3. Créer l'armoire de climatisation
      if (formData.climatisation.marque && formData.climatisation.modele) {
        await api.post('/clim', {
          ...formData.climatisation,
          rak_id: rackId
        });
      }

      notify.success('Rack ajouté avec succès');
      navigate('/dashboard');
    } catch (error) {
      console.error('Erreur lors de l\'ajout du rack:', error);
      notify.error('Erreur lors de l\'ajout du rack');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="container mx-auto px-4 py-6 max-w-5xl">
      <div className="bg-white rounded-lg shadow-md p-6 mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Ajouter un nouveau rack</h1>
        <p className="text-gray-600 mb-6">Remplissez les informations ci-dessous pour ajouter un nouveau rack au système.</p>
        
        <form onSubmit={handleSubmit} className="space-y-8">
          {/* Section Informations du rack */}
          <div className="bg-gray-50 p-6 rounded-lg border border-gray-200">
            <div className="mb-6">
              <h2 className="text-xl font-semibold text-gray-800">Informations du rack</h2>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="form-group">
                <label className="form-label">Nom du rack <span className="text-red-500">*</span></label>
                <input
                  type="text"
                  name="nom"
                  value={formData.nom}
                  onChange={handleChange}
                  className="form-control"
                  placeholder="Ex: Rack A1"
                  required
                />
                <small className="text-xs text-gray-500">Identifiant unique du rack</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Localisation <span className="text-red-500">*</span></label>
                <input
                  type="text"
                  name="localisation"
                  value={formData.localisation}
                  onChange={handleChange}
                  className="form-control"
                  placeholder="Ex: Salle serveur 1, Étage 2"
                  required
                />
                <small className="text-xs text-gray-500">Emplacement physique du rack</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Direction <span className="text-red-500">*</span></label>
                <select
                  name="direction_id"
                  value={formData.direction_id}
                  onChange={handleChange}
                  className="form-control"
                  required
                >
                  <option value="">Sélectionnez une direction</option>
                  {directions.map(dir => (
                    <option key={dir.id} value={dir.id}>
                      {dir.nom}
                    </option>
                  ))}
                </select>
                <small className="text-xs text-gray-500">Direction responsable du rack</small>
              </div>
            </div>
          </div>

          {/* Section PDU 1 */}
          <div className="bg-gray-50 p-6 rounded-lg border border-gray-200">
            <div className="mb-4">
              <h2 className="text-xl font-semibold text-gray-800">PDU 1</h2>
              <span className="ml-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Obligatoire</span>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="form-group">
                <label className="form-label">Marque <span className="text-red-500">*</span></label>
                <input
                  type="text"
                  name="marque"
                  value={formData.pdus[0].marque}
                  onChange={(e) => handlePduChange(0, e)}
                  className="form-control"
                  placeholder="Ex: APC, Eaton, etc."
                  required
                />
                <small className="text-xs text-gray-500">Marque du PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Modèle <span className="text-red-500">*</span></label>
                <input
                  type="text"
                  name="modele"
                  value={formData.pdus[0].modele}
                  onChange={(e) => handlePduChange(0, e)}
                  className="form-control"
                  placeholder="Ex: AP8959, ePDU G3, etc."
                  required
                />
                <small className="text-xs text-gray-500">Modèle du PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Adresse IP <span className="text-red-500">*</span></label>
                <div className="relative">
                  <input
                    type="text"
                    name="ip_address"
                    value={formData.pdus[0].ip_address}
                    onChange={(e) => handlePduChange(0, e)}
                    className="form-control pr-10"
                    placeholder="Ex: 192.168.1.100"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => {
                      // Fonction pour tester la connexion SNMP
                      console.log('Tester la connexion SNMP');
                    }}
                    className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-600 transition-colors"
                    title="Tester la connexion SNMP"
                  >

                  </button>
                </div>
                <small className="text-xs text-gray-500">Adresse IP du PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Communauté SNMP <span className="text-red-500">*</span></label>
                <input
                  type="text"
                  name="snmp_community"
                  value={formData.pdus[0].snmp_community}
                  onChange={(e) => handlePduChange(0, e)}
                  className="form-control"
                  placeholder="Ex: public"
                  required
                />
                <small className="text-xs text-gray-500">Communauté SNMP (par défaut: public)</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Nombre de prises <span className="text-red-500">*</span></label>
                <input
                  type="number"
                  name="nombre_prises"
                  value={formData.pdus[0].nombre_prises}
                  onChange={(e) => handlePduChange(0, e)}
                  className="form-control"
                  min="1"
                  max="48"
                  required
                />
                <small className="text-xs text-gray-500">Nombre total de prises sur le PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Type de connexion <span className="text-red-500">*</span></label>
                <select
                  name="type_connexion"
                  value={formData.pdus[0].type_connexion}
                  onChange={(e) => handlePduChange(0, e)}
                  className="form-control"
                  required
                >
                  <option value="C13/C14">C13/C14 (10A/16A)</option>
                  <option value="C19/C20">C19/C20 (16A/20A)</option>
                  <option value="IEC 60320">IEC 60320 (standard)</option>
                  <option value="Schuko">Schuko (prise européenne)</option>
                  <option value="NEMA 5-15">NEMA 5-15 (prise US)</option>
                  <option value="NEMA 5-20">NEMA 5-20 (prise US 20A)</option>
                  <option value="NEMA L5-20">NEMA L5-20 (prise verrouillable)</option>
                  <option value="NEMA L6-20">NEMA L6-20 (prise 250V)</option>
                  <option value="NEMA L6-30">NEMA L6-30 (prise 30A)</option>
                  <option value="Autre">Autre (préciser en commentaires)</option>
                </select>
                <small className="text-xs text-gray-500">Type de connecteur des prises</small>
              </div>
            </div>
          </div>

          {/* Section PDU 2 (Optionnel) */}
          <div className="bg-gray-50 p-6 rounded-lg border border-gray-200">
            <div className="mb-4">
              <h2 className="text-xl font-semibold text-gray-800">PDU 2</h2>
              <span className="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Optionnel</span>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="form-group">
                <label className="form-label">Marque</label>
                <input
                  type="text"
                  name="marque"
                  value={formData.pdus[1].marque}
                  onChange={(e) => handlePduChange(1, e)}
                  className="form-control"
                  placeholder="Ex: APC, Eaton, etc."
                />
                <small className="text-xs text-gray-500">Marque du PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Modèle</label>
                <input
                  type="text"
                  name="modele"
                  value={formData.pdus[1].modele}
                  onChange={(e) => handlePduChange(1, e)}
                  className="form-control"
                  placeholder="Ex: AP8959, ePDU G3, etc."
                />
                <small className="text-xs text-gray-500">Modèle du PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Adresse IP</label>
                <div className="relative">
                  <input
                    type="text"
                    name="ip_address"
                    value={formData.pdus[1].ip_address}
                    onChange={(e) => handlePduChange(1, e)}
                    className="form-control pr-10"
                    placeholder="Ex: 192.168.1.101"
                  />
                  <button
                    type="button"
                    onClick={() => {
                      // Fonction pour tester la connexion SNMP
                      console.log('Tester la connexion SNMP');
                    }}
                    className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-600 transition-colors"
                    title="Tester la connexion SNMP"
                  >

                  </button>
                </div>
                <small className="text-xs text-gray-500">Adresse IP du PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Communauté SNMP</label>
                <input
                  type="text"
                  name="snmp_community"
                  value={formData.pdus[1].snmp_community}
                  onChange={(e) => handlePduChange(1, e)}
                  className="form-control"
                  placeholder="Ex: public"
                />
                <small className="text-xs text-gray-500">Communauté SNMP (par défaut: public)</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Nombre de prises</label>
                <input
                  type="number"
                  name="nombre_prises"
                  value={formData.pdus[1].nombre_prises}
                  onChange={(e) => handlePduChange(1, e)}
                  className="form-control"
                  min="1"
                  max="48"
                />
                <small className="text-xs text-gray-500">Nombre total de prises sur le PDU</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Type de connexion</label>
                <select
                  name="type_connexion"
                  value={formData.pdus[1].type_connexion}
                  onChange={(e) => handlePduChange(1, e)}
                  className="form-control"
                >
                  <option value="C13/C14">C13/C14 (10A/16A)</option>
                  <option value="C19/C20">C19/C20 (16A/20A)</option>
                  <option value="IEC 60320">IEC 60320 (standard)</option>
                  <option value="Schuko">Schuko (prise européenne)</option>
                  <option value="NEMA 5-15">NEMA 5-15 (prise US)</option>
                  <option value="NEMA 5-20">NEMA 5-20 (prise US 20A)</option>
                  <option value="NEMA L5-20">NEMA L5-20 (prise verrouillable)</option>
                  <option value="NEMA L6-20">NEMA L6-20 (prise 250V)</option>
                  <option value="NEMA L6-30">NEMA L6-30 (prise 30A)</option>
                  <option value="Autre">Autre (préciser en commentaires)</option>
                </select>
                <small className="text-xs text-gray-500">Type de connecteur des prises</small>
              </div>
            </div>
          </div>

          {/* Section Armoire de climatisation */}
          <div className="bg-gray-50 p-6 rounded-lg border border-gray-200">
            <div className="mb-4">
              <h2 className="text-xl font-semibold text-gray-800">Armoire de climatisation</h2>
              <span className="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Optionnel</span>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="form-group">
                <label className="form-label">Marque</label>
                <input
                  type="text"
                  name="marque"
                  value={formData.climatisation.marque}
                  onChange={handleClimatisationChange}
                  className="form-control"
                  placeholder="Ex: Stulz, Liebert, etc."
                />
                <small className="text-xs text-gray-500">Marque de l'armoire de climatisation</small>
              </div>
              
              <div className="form-group">
                <label className="form-label">Modèle</label>
                <input
                  type="text"
                  name="modele"
                  value={formData.climatisation.modele}
                  onChange={handleClimatisationChange}
                  className="form-control"
                  placeholder="Ex: CW 100, DSE 107, etc."
                />
                <small className="text-xs text-gray-500">Modèle de l'armoire de climatisation</small>
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-4">
            <button
              type="button"
              onClick={() => navigate('/dashboard')}
              className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              disabled={isLoading}
            >
              Annuler
            </button>
            <button
              type="submit"
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              disabled={isLoading}
            >
              {isLoading ? 'Enregistrement...' : 'Enregistrer le rack'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

export default AjouterRack;