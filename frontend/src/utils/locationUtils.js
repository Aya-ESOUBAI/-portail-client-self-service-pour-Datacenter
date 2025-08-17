// Fonction pour extraire seulement la zone de la localisation
export const extractZone = (localisation) => {
  if (!localisation) return '';
  
  // Si la localisation contient une virgule, prendre seulement la première partie
  if (localisation.includes(',')) {
    return localisation.split(',')[0].trim();
  }
  
  // Si la localisation contient "Zone", la garder telle quelle
  if (localisation.includes('Zone')) {
    return localisation;
  }
  
  // Si c'est une adresse complète, essayer d'extraire la zone
  // Par exemple: "123 Rue de Test, 75001 Paris" -> "Zone 75001"
  const match = localisation.match(/(\d{5})\s+(\w+)/);
  if (match) {
    return `Zone ${match[1]}`;
  }
  
  // Si la localisation contient "Salle", remplacer par "Zone"
  if (localisation.includes('Salle')) {
    return localisation.replace('Salle', 'Zone');
  }
  
  // Si la localisation contient "Étage", remplacer par "Zone"
  if (localisation.includes('Étage')) {
    return localisation.replace('Étage', 'Zone');
  }
  
  // Si c'est juste un nom simple, ajouter "Zone" devant
  if (localisation.trim() && !localisation.includes('Zone')) {
    return `Zone ${localisation.trim()}`;
  }
  
  // Sinon, retourner la localisation telle quelle
  return localisation;
};

// Fonction pour formater l'affichage de la localisation
export const formatLocation = (localisation) => {
  const zone = extractZone(localisation);
  return `📍 ${zone}`;
}; 