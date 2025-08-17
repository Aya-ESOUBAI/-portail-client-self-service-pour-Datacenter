import React, { useState } from 'react';
import api from '../services/api';

function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    
    try {
      console.log('Tentative de connexion avec:', { email, password });
      
      const res = await api.post('/login', {
        email: email,
        mot_de_passe: password
      });
      
      console.log('Réponse du serveur:', res.data);
      
      if (res.data.token) {
        localStorage.setItem('token', res.data.token);
        localStorage.setItem('user', JSON.stringify(res.data.user));
        window.location.href = '/dashboard';
      } else {
        setError('Identifiants invalides');
      }
    } catch (err) {
      console.error('Erreur de connexion:', err);
      console.error('Détails de l\'erreur:', err.response?.data);
      
      if (err.response?.data?.error) {
        setError(err.response.data.error);
      } else if (err.response?.status === 401) {
        setError('Email ou mot de passe incorrect');
      } else if (err.response?.status === 404) {
        setError('Service de connexion indisponible');
      } else {
        setError('Erreur de connexion au serveur');
      }
    }
  };

  return (
    <div className="login-page">
      <div className="login-container">
        <h2>Connexion</h2>
        <form onSubmit={handleSubmit}>
          <input 
            type="email" 
            placeholder="Email" 
            value={email} 
            onChange={e => setEmail(e.target.value)} 
            required 
          />
          <input 
            type="password" 
            placeholder="Mot de passe" 
            value={password} 
            onChange={e => setPassword(e.target.value)} 
            required 
          />
          <button type="submit">Se connecter</button>
        </form>
        {error && <div className="error-message">{error}</div>}
      </div>
    </div>
  );
}

export default LoginPage;
