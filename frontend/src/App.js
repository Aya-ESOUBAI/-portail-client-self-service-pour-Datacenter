import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import Dashboard from './pages/Dashboard';
import Alertes from './pages/Alertes';
import Demandes from './pages/Demandes';
import AjouterDirection from './pages/AjouterDirection';
import AjouterRack from './pages/AjouterRack';
import Consommation from './pages/Consommation';
import Environnement from './pages/Environnement';
import Rapports from './pages/Rapports';
import Profile from './pages/Profile';
import Layout from './components/Layout';
import ProtectedRoute from './components/ProtectedRoute';
import DirectionRoute from './components/DirectionRoute';
import AdminRoute from './components/AdminRoute';
import { ThemeProvider } from './components/ThemeProvider';

function App() {
  return (
    <ThemeProvider>
      <Router>
        <div className="App">
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          
          <Route path="/dashboard" element={
            <ProtectedRoute>
              <Layout>
                <Dashboard />
              </Layout>
            </ProtectedRoute>
          } />
          
<Route path="/alertes" element={
            <ProtectedRoute>
              <Layout>
                <Alertes />
              </Layout>
            </ProtectedRoute>
          } />
          
          <Route path="/demandes" element={
            <ProtectedRoute>
              <Layout>
                <Demandes />
              </Layout>
            </ProtectedRoute>
          } />
          
          <Route path="/ajouter-direction" element={
            <AdminRoute>
              <Layout>
                <AjouterDirection />
              </Layout>
            </AdminRoute>
          } />
          
          <Route path="/ajouter-rack" element={
            <AdminRoute>
              <Layout>
                <AjouterRack />
              </Layout>
            </AdminRoute>
          } />
          
          <Route path="/consommation" element={
            <DirectionRoute>
              <Layout>
                <Consommation />
              </Layout>
            </DirectionRoute>
          } />
          
          <Route path="/environnement" element={
            <DirectionRoute>
              <Layout>
                <Environnement />
              </Layout>
            </DirectionRoute>
          } />
          
          <Route path="/rapports" element={
            <DirectionRoute>
              <Layout>
                <Rapports />
              </Layout>
            </DirectionRoute>
          } />
          
          <Route path="/profile" element={
            <ProtectedRoute>
              <Layout>
                <Profile />
              </Layout>
            </ProtectedRoute>
          } />
        </Routes>
      </div>
      </Router>
    </ThemeProvider>
  );
}

export default App;
