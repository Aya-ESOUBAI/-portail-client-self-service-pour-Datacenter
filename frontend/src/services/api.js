import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost/3/backend/index.php',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response) {
      console.error('Erreur de réponse:', error.response.status, error.response.data);
    } else if (error.request) {
      console.error('Pas de réponse du serveur:', error.request);
    } else {
      console.error('Erreur de configuration:', error.message);
    }
    return Promise.reject(error);
  }
);


api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

export default api;