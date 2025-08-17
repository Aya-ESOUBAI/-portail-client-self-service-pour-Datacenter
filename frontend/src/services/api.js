import axios from 'axios';

const API_URL = 'http://localhost/3/backend/index.php';

// Ajoute automatiquement le token à chaque requête
const api = axios.create({
  baseURL: API_URL
});

api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers['Authorization'] = 'Bearer ' + token;
  }
  return config;
});

export default api;
