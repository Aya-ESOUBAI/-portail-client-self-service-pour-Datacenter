import React from 'react';
import { Navigate } from 'react-router-dom';

function DirectionRoute({ children }) {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  
  // Vérifier si l'utilisateur est connecté et est de type Direction
  if (!user.id || user.role !== 'Direction') {
    return <Navigate to="/dashboard" replace />;
  }
  
  return children;
}

export default DirectionRoute; 