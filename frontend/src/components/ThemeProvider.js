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

  useEffect(() => {
    const savedTheme = localStorage.getItem('theme');
    const initialTheme = savedTheme === 'dark';
    setIsDark(initialTheme);
    applyTheme(initialTheme);
  }, []);

  const applyTheme = (isDarkMode) => {
    console.log('=== APPLYING THEME GLOBALLY ===');
    console.log('Applying theme, isDark:', isDarkMode);
    
    if (isDarkMode) {
      document.body.style.background = 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)';
      document.body.style.color = '#ffffff';
      
      const nav = document.querySelector('.navigation');
      if (nav) nav.style.background = '#2d2d2d';
      
      const cards = document.querySelectorAll('.card, .rack-consumption-card, .rack-environment-card, .demande-card, .alerte-card, .stat-card, .chart-card, .direction-supervision-card, .rack-supervision-item, .threshold-card, .direction-card, .profile-card');
      cards.forEach(card => {
        card.style.background = '#333333';
        card.style.color = '#ffffff';
        card.style.borderColor = '#404040';
      });
      
      const sections = document.querySelectorAll('.dashboard-content, .consommation-content, .environnement-content, .demandes-content, .alertes-content, .supervision-content, .profile-content');
      sections.forEach(section => {
        section.style.background = '#2d2d2d';
        section.style.color = '#ffffff';
      });
      
      const headers = document.querySelectorAll('.dashboard-header, .consommation-header, .environnement-header, .demandes-header, .alertes-header, .supervision-header, .profile-header');
      headers.forEach(header => {
        header.style.background = 'linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%)';
        header.style.color = '#ffffff';
      });
      
      const navLinks = document.querySelectorAll('.nav-links a');
      navLinks.forEach(link => {
        link.style.color = '#b0b0b0';
      });
      
      const activeLinks = document.querySelectorAll('.nav-links a.active');
      activeLinks.forEach(link => {
        link.style.color = '#ffffff';
        link.style.background = '#404040';
      });
      
      const userNames = document.querySelectorAll('.user-name');
      userNames.forEach(name => {
        name.style.color = '#ffffff';
      });
      
      const userRoles = document.querySelectorAll('.user-role');
      userRoles.forEach(role => {
        role.style.color = '#b0b0b0';
      });
      
      const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .btn-refresh, .btn-create-demande, .btn-edit-profile, .btn-save-profile, .btn-cancel-profile');
      buttons.forEach(button => {
        button.style.background = '#404040';
        button.style.color = '#ffffff';
        button.style.borderColor = '#555555';
      });
      
      const inputs = document.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.style.background = '#404040';
        input.style.color = '#ffffff';
        input.style.borderColor = '#555555';
      });
      
      const secondaryTexts = document.querySelectorAll('.text-secondary, .alert-message, .alert-time, .stat-label, .info-item span');
      secondaryTexts.forEach(text => {
        text.style.color = '#b0b0b0';
      });
      
      const titles = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
      titles.forEach(title => {
        title.style.color = '#ffffff';
      });
      
      
      const paragraphs = document.querySelectorAll('p');
      paragraphs.forEach(p => {
        p.style.color = '#ffffff';
      });
      
      const labels = document.querySelectorAll('label');
      labels.forEach(label => {
        label.style.color = '#ffffff';
      });
      
      const textElements = document.querySelectorAll('span, div');
      textElements.forEach(element => {
        if (element.textContent && element.textContent.trim() !== '') {
          element.style.color = '#ffffff';
        }
      });
      
      const specificElements = document.querySelectorAll('.rack-info, .pdu-info, .capteur-info, .demande-info, .alerte-info, .stat-info, .user-info, .direction-info');
      specificElements.forEach(element => {
        element.style.color = '#ffffff';
      });
      
      const values = document.querySelectorAll('.stat-value, .prise-value, .capteur-value, .rack-name, .pdu-name, .capteur-type');
      values.forEach(value => {
        value.style.color = '#ffffff';
      });
      
      const descriptions = document.querySelectorAll('.description, .details, .info, .content');
      descriptions.forEach(desc => {
        desc.style.color = '#ffffff';
      });
      
      console.log('✅ Dark theme applied globally');
    } else {
      document.body.style.background = 'linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%)';
      document.body.style.color = '#1e293b';
      
      const nav = document.querySelector('.navigation');
      if (nav) nav.style.background = '#ffffff';
      
      const cards = document.querySelectorAll('.card, .rack-consumption-card, .rack-environment-card, .demande-card, .alerte-card, .stat-card, .chart-card, .direction-supervision-card, .rack-supervision-item, .threshold-card, .direction-card, .profile-card');
      cards.forEach(card => {
        card.style.background = '#ffffff';
        card.style.color = '#1e293b';
        card.style.borderColor = '#e2e8f0';
      });
      
      const sections = document.querySelectorAll('.dashboard-content, .consommation-content, .environnement-content, .demandes-content, .alertes-content, .supervision-content, .profile-content');
      sections.forEach(section => {
        section.style.background = '#ffffff';
        section.style.color = '#1e293b';
      });
      
      const headers = document.querySelectorAll('.dashboard-header, .consommation-header, .environnement-header, .demandes-header, .alertes-header, .supervision-header, .profile-header');
      headers.forEach(header => {
        header.style.background = 'linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%)';
        header.style.color = '#1e293b';
      });
      
      const navLinks = document.querySelectorAll('.nav-links a');
      navLinks.forEach(link => {
        link.style.color = '#374151';
      });
      
      const activeLinks = document.querySelectorAll('.nav-links a.active');
      activeLinks.forEach(link => {
        link.style.color = '#1e293b';
        link.style.background = '#f1f5f9';
      });
      
      const userNames = document.querySelectorAll('.user-name');
      userNames.forEach(name => {
        name.style.color = '#1e293b';
      });
      
      const userRoles = document.querySelectorAll('.user-role');
      userRoles.forEach(role => {
        role.style.color = '#374151';
      });
      
      const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .btn-refresh, .btn-create-demande, .btn-edit-profile, .btn-save-profile, .btn-cancel-profile');
      buttons.forEach(button => {
        button.style.background = '';
        button.style.color = '#1e293b';
        button.style.borderColor = '';
      });
      
      const inputs = document.querySelectorAll('input, textarea, select');
      inputs.forEach(input => {
        input.style.background = '#ffffff';
        input.style.color = '#1e293b';
        input.style.borderColor = '#d1d5db';
      });
      
      const secondaryTexts = document.querySelectorAll('.text-secondary, .alert-message, .alert-time, .stat-label, .info-item span');
      secondaryTexts.forEach(text => {
        text.style.color = '#374151';
      });
      
      const titles = document.querySelectorAll('h1, h2, h3, h4, h5, h6');
      titles.forEach(title => {
        title.style.color = '#1e293b';
      });
      
      const paragraphs = document.querySelectorAll('p');
      paragraphs.forEach(p => {
        p.style.color = '#1e293b';
      });
      
      const labels = document.querySelectorAll('label');
      labels.forEach(label => {
        label.style.color = '#1e293b';
      });
      
      const textElements = document.querySelectorAll('span, div');
      textElements.forEach(element => {
        if (element.textContent && element.textContent.trim() !== '') {
          element.style.color = '#1e293b';
        }
      });
      
      const specificElements = document.querySelectorAll('.rack-info, .pdu-info, .capteur-info, .demande-info, .alerte-info, .stat-info, .user-info, .direction-info');
      specificElements.forEach(element => {
        element.style.color = '#1e293b';
      });
      
      const values = document.querySelectorAll('.stat-value, .prise-value, .capteur-value, .rack-name, .pdu-name, .capteur-type');
      values.forEach(value => {
        value.style.color = '#1e293b';
      });
      
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
    
    localStorage.setItem('theme', newIsDark ? 'dark' : 'light');
    
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