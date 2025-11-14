# CHANGELOG SITES2 MODULE

## 2.2 - 2024-12-14
### Corrections
- Correction du bug d'affichage de l'erreur "ErrorBadValueForContactId" lors de l'ajout d'un contact
  - Validation des paramètres (contactid et typeid) avant l'appel à la fonction add_contact()
  - Affichage de messages d'erreur clairs si un contact ou un type de contact n'est pas sélectionné
  - L'erreur ne s'affiche plus lorsque l'opération réussit correctement
  - Ajout des traductions pour les messages d'erreur de validation

## 2.1 - 2024-12-14
### Améliorations
- Réorganisation de l'affichage : la carte s'affiche maintenant en premier, suivie du panneau météo en dessous
- Réduction de la taille du tableau météo pour un affichage plus compact
  - Tailles de police réduites
  - Icônes météo plus petites
  - Espacements optimisés
  - Interface plus sobre et moins encombrante

## 2.0 - 2024-12-14
### Ajouts majeurs
- **Nouvelle fonctionnalité météo** : Affichage des prévisions météorologiques sur la carte du site
  - Prévisions pour aujourd'hui et les 5 jours à venir
  - Utilisation de l'API OpenWeatherMap
  - Configuration de la clé API dans les paramètres du module
  - Affichage des températures, descriptions météo et icônes
  - Panneau météo intégré au-dessus de la carte du site

### Corrections
- Correction du comportement sur Android pour l'ouverture dans une carte externe : utilisation prioritaire des coordonnées GPS plutôt que de l'adresse
- Les applications cartographiques (comme OsmAnd) utilisent maintenant les coordonnées précises lorsque disponibles
- L'adresse n'est utilisée qu'en cas d'absence des coordonnées GPS

## 1.10 - 2024-03-18
### Ajouts
- Ajout d'un tableau récapitulatif des contacts sur la page principale du site
- Optimisation de l'affichage des contacts dans l'onglet Contact
- Amélioration de l'interface utilisateur mobile pour les numéros de téléphone cliquables
- Ajout d'une fonctionnalité d'export des sites aux formats CSV et Excel
- Intégration dans la recherche globale de Dolibarr pour retrouver facilement les sites

### Corrections
- Correction de la gestion des rôles de contact
- Correction du problème d'affichage de la carte
- Correction des erreurs lors de l'ajout de nouveaux contacts
- Optimisation de la gestion des colonnes dans l'interface contact

## 1.00 - 2024-02-15
### Version initiale
- Gestion des sites avec adresses complètes
- Géocodage automatique des adresses (latitude/longitude)
- Affichage des sites sur une carte interactive
- Calcul automatique de la distance en kilomètres depuis le siège social ou l'agence de référence
- Estimation du temps de trajet
- Gestion des contacts associés au site 