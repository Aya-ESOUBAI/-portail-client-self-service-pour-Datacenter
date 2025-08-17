import React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';

function Navigation() {
  const location = useLocation();
  const navigate = useNavigate();
  
  const isActive = (path) => {
    return location.pathname === path;
  };
  
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    navigate('/login');
  };
  
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  
  return (
    <nav className="navigation">
      <div className="nav-brand">
        <div className="logo-container">
          <img src="/images/logo.svg" alt="DATA SERVE Logo" className="logo" />
        </div>
      </div>
      
      <div className="nav-links">
        <Link to="/dashboard" className={isActive('/dashboard') ? 'active' : ''}>
          Dashboard
        </Link>
<Link to="/alertes" className={isActive('/alertes') ? 'active' : ''}>
          Alertes
        </Link>
        <Link to="/demandes" className={isActive('/demandes') ? 'active' : ''}>
          Demandes
        </Link>
        {user.role === 'Direction' && (
          <>
            <Link to="/consommation" className={isActive('/consommation') ? 'active' : ''}>
              Consommation
            </Link>
            <Link to="/environnement" className={isActive('/environnement') ? 'active' : ''}>
              Environnement
            </Link>
            <Link to="/rapports" className={isActive('/rapports') ? 'active' : ''}>
              Rapports
            </Link>
          </>
        )}
        {user.role === 'Admin' && (
          <Link to="/ajouter-direction" className={isActive('/ajouter-direction') ? 'active' : ''}>
            Ajouter Direction
          </Link>
        )}
      </div>
      
      <div className="nav-user">
        <div className="user-info">
          <span className="user-name">{user.nom || 'Utilisateur'}</span>
          <span className="user-role">{user.role || 'Admin'}</span>
        </div>
        <div className="nav-actions">
          <Link to="/profile" className="profile-link">
            Profil
          </Link>
          <button onClick={handleLogout} className="logout-btn">
            Déconnexion
          </button>
        </div>
      </div>
    </nav>
  );
}

export default Navigation; 