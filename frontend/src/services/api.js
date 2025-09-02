import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/3/backend/index.php',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Gestion des erreurs globale
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response) {
      // Le serveur a répondu avec un statut hors 2xx
      console.error('Erreur de réponse:', error.response.status, error.response.data);
    } else if (error.request) {
      // La requête a été faite mais aucune réponse n'a été reçue
      console.error('Pas de réponse du serveur:', error.request);
    } else {
      // Erreur lors de la configuration de la requête
      console.error('Erreur de configuration:', error.message);
    }
    return Promise.reject(error);
  }
);

// Intercepteur pour le token
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

export default api;