import React from 'react';
import { Navigate } from 'react-router-dom';

function AdminRoute({ children }) {
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  
  // Vérifier si l'utilisateur est connecté et est de type Admin
  if (!user.id || user.role !== 'Admin') {
    return <Navigate to="/dashboard" replace />;
  }
  
  return children;
}

export default AdminRoute; 