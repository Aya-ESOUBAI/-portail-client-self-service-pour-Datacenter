import React, { createContext, useContext, useState, useEffect } from 'react';

const ThemeContext = createContext();

export const useTheme = () => {
  const context = useContext(ThemeContext);
  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
};

export const ThemeProvider = ({ children }) => {
  const [isDark, setIsDark] = useState(false);

  // Charger le thème depuis localStorage au démarrage
  useEffect(() => {
    const savedTheme = localStorage.getItem('theme');
    const initialTheme = savedTheme === 'dark';
    setIsDark(initialTheme);
    applyTheme(initialTheme);
  }, []);

  // Fonction pour appliquer le thème
  const applyTheme = (isDarkMode) => {
    console.log('=== APPLYING THEME GLOBALLY ===');
    console.log('Applying theme, isDark:', isDarkMode);
    
    // Appliquer directement les styles
    if (isDarkMode) {
      // Mode sombre
      document.body.style.background = 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)';
      document.body.style.color = '#ffffff';
      
      // Navigation
      const nav = document.querySelector('.navigation');
      if (nav) nav.style.background = '#2d2d2d';
      
      // Toutes les cartes (plus spécifique)
      const cards = document.querySelectorAll('.card, .rack-consumption-card, .rack-environment-card, .demande-card, .alerte-card, .stat-card, .chart-card, .direction-supervision-card, .rack-supervision-item, .threshold-card, .direction-card, .profile-card');
      cards.forEach(card => {
        card.style.background = '#333333';
        card.style.color = '#ffffff';
        card.style.borderColor = '#404040';
      });
      
      // Sections spécifiques
      const sections = document.querySelectorAll('.dashboard-content, .consommation-content, .environnement-content, .demandes-content, .alertes-content, .supervision-content, .profile-content');
      sections.forEach(section => {
        section.style.background = '#2d2d2d';
        section.style.color = '#ffffff';
      });
      
      // Headers
      const headers = document.querySelectorAll('.dashboard-header, .consommation-header, .environnement-header, .demandes-header, .alertes-header, .supervision-header, .profile-header');
      headers.forEach(header => {
        header.style.background = 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)';
        header.style.color = '#ffffff';
      });
      
      // Liens de navigation
      const navLinks = document.querySelectorAll('.nav-links a');
      navLinks.forEach(link => {
        link.style.color = '#b0b0b0';
      });
      
      // Liens actifs
      const activeLinks = document.querySelectorAll('.nav-links a.active');
      activeLinks.forEach(link => {
        link.style.color = '#ffffff';
        link.style.background = '#404040';
      });
      
      // Noms d'utilisateur
      const userNames = document.querySelectorAll('.user-name');
      userNames.forEach(name => {
        name.style.color = '#ffffff';
      });
      
      // Rôles d'utilisateur
      const userRoles = document.querySelectorAll('.user-role');
      userRoles.forEach(role => {
        role.style.color = '#b0b0b0';
      });
      
      // Boutons
      const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .btn-refresh, .btn-create-demande, .btn-edit-profile, .btn-save-profile, .btn-cancel-profile');
      buttons.forEach(button => {
        button.style.background = '#404040';
        button.style.color = '#ffffff';
        button.style.borderColor = '#555555';
      });
      
      // Formulaires
      const inputs = document.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.style.background = '#404040';
        input.style.color = '#ffffff';
        input.style.borderColor = '#555555';
      });
      
      // Textes secondaires
      const secondaryTexts = document.querySelectorAll('.text-secondary, .alert-message, .alert-time, .stat-label, .info-item span');
      secondaryTexts.forEach(text => {
        text.style.color = '#b0b0b0';
      });
      
      // Titres et sous-titres
      const titles = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
      titles.forEach(title => {
        title.style.color = '#ffffff';
      });
      
      // Paragraphes
      const paragraphs = document.querySelectorAll('p');
      paragraphs.forEach(p => {
        p.style.color = '#ffffff';
      });
      
      // Labels
      const labels = document.querySelectorAll('label');
      labels.forEach(label => {
        label.style.color = '#ffffff';
      });
      
      // Spans et divs avec texte
      const textElements = document.querySelectorAll('span, div');
      textElements.forEach(element => {
        if (element.textContent && element.textContent.trim() !== '') {
          element.style.color = '#ffffff';
        }
      });
      
      // Éléments spécifiques supplémentaires
      const specificElements = document.querySelectorAll('.rack-info, .pdu-info, .capteur-info, .demande-info, .alerte-info, .stat-info, .user-info, .direction-info');
      specificElements.forEach(element => {
        element.style.color = '#ffffff';
      });
      
      // Valeurs et données
      const values = document.querySelectorAll('.stat-value, .prise-value, .capteur-value, .rack-name, .pdu-name, .capteur-type');
      values.forEach(value => {
        value.style.color = '#ffffff';
      });
      
      // Descriptions et détails
      const descriptions = document.querySelectorAll('.description, .details, .info, .content');
      descriptions.forEach(desc => {
        desc.style.color = '#ffffff';
      });
      
      console.log('✅ Dark theme applied globally');
    } else {
      // Mode clair - Arrière-plan plus clair
      document.body.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
      document.body.style.color = '#1e293b';
      
      // Navigation
      const nav = document.querySelector('.navigation');
      if (nav) nav.style.background = '#ffffff';
      
      // Toutes les cartes
      const cards = document.querySelectorAll('.card, .rack-consumption-card, .rack-environment-card, .demande-card, .alerte-card, .stat-card, .chart-card, .direction-supervision-card, .rack-supervision-item, .threshold-card, .direction-card, .profile-card');
      cards.forEach(card => {
        card.style.background = '#ffffff';
        card.style.color = '#1e293b';
        card.style.borderColor = '#e2e8f0';
      });
      
      // Sections spécifiques
      const sections = document.querySelectorAll('.dashboard-content, .consommation-content, .environnement-content, .demandes-content, .alertes-content, .supervision-content, .profile-content');
      sections.forEach(section => {
        section.style.background = '#ffffff';
        section.style.color = '#1e293b';
      });
      
      // Headers - Gradient plus doux
      const headers = document.querySelectorAll('.dashboard-header, .consommation-header, .environnement-header, .demandes-header, .alertes-header, .supervision-header, .profile-header');
      headers.forEach(header => {
        header.style.background = 'linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)';
        header.style.color = '#1e293b';
      });
      
      // Liens de navigation
      const navLinks = document.querySelectorAll('.nav-links a');
      navLinks.forEach(link => {
        link.style.color = '#374151';
      });
      
      // Liens actifs
      const activeLinks = document.querySelectorAll('.nav-links a.active');
      activeLinks.forEach(link => {
        link.style.color = '#1e293b';
        link.style.background = '#f1f5f9';
      });
      
      // Noms d'utilisateur
      const userNames = document.querySelectorAll('.user-name');
      userNames.forEach(name => {
        name.style.color = '#1e293b';
      });
      
      // Rôles d'utilisateur
      const userRoles = document.querySelectorAll('.user-role');
      userRoles.forEach(role => {
        role.style.color = '#374151';
      });
      
      // Boutons
      const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .btn-refresh, .btn-create-demande, .btn-edit-profile, .btn-save-profile, .btn-cancel-profile');
      buttons.forEach(button => {
        button.style.background = '';
        button.style.color = '#1e293b';
        button.style.borderColor = '';
      });
      
      // Formulaires
      const inputs = document.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.style.background = '#ffffff';
        input.style.color = '#1e293b';
        input.style.borderColor = '#d1d5db';
      });
      
      // Textes secondaires
      const secondaryTexts = document.querySelectorAll('.text-secondary, .alert-message, .alert-time, .stat-label, .info-item span');
      secondaryTexts.forEach(text => {
        text.style.color = '#374151';
      });
      
      // Titres et sous-titres
      const titles = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
      titles.forEach(title => {
        title.style.color = '#1e293b';
      });
      
      // Paragraphes
      const paragraphs = document.querySelectorAll('p');
      paragraphs.forEach(p => {
        p.style.color = '#1e293b';
      });
      
      // Labels
      const labels = document.querySelectorAll('label');
      labels.forEach(label => {
        label.style.color = '#1e293b';
      });
      
      // Spans et divs avec texte
      const textElements = document.querySelectorAll('span, div');
      textElements.forEach(element => {
        if (element.textContent && element.textContent.trim() !== '') {
          element.style.color = '#1e293b';
        }
      });
      
      // Éléments spécifiques supplémentaires
      const specificElements = document.querySelectorAll('.rack-info, .pdu-info, .capteur-info, .demande-info, .alerte-info, .stat-info, .user-info, .direction-info');
      specificElements.forEach(element => {
        element.style.color = '#1e293b';
      });
      
      // Valeurs et données
      const values = document.querySelectorAll('.stat-value, .prise-value, .capteur-value, .rack-name, .pdu-name, .capteur-type');
      values.forEach(value => {
        value.style.color = '#1e293b';
      });
      
      // Descriptions et détails
      const descriptions = document.querySelectorAll('.description, .details, .info, .content');
      descriptions.forEach(desc => {
        desc.style.color = '#1e293b';
      });
      
      console.log('✅ Light theme applied globally');
    }
  };

  const toggleTheme = () => {
    console.log('=== TOGGLE THEME GLOBALLY ===');
    console.log('Toggling theme, current isDark:', isDark);
    
    const newIsDark = !isDark;
    setIsDark(newIsDark);
    
    // Sauvegarder le thème dans localStorage
    localStorage.setItem('theme', newIsDark ? 'dark' : 'light');
    
    // Appliquer le thème
    applyTheme(newIsDark);
  };

  const value = {
    isDark,
    toggleTheme,
    applyTheme
  };

  return (
    <ThemeContext.Provider value={value}>
      {children}
    </ThemeContext.Provider>
  );
}; 