# CHANGELOG SITES2 MODULE

## 2.3 - 2024-12-XX
### Ajouts majeurs
- **Gestion des chantiers programmés** : Nouvelle fonctionnalité complète de planification des chantiers
  - Nouvel onglet "Chantier programmé" sur la fiche site (entre les onglets Contact et Équipement)
  - Association d'un devis signé à un site pour créer un chantier programmé
  - Sélection de dates théoriques de début et fin de chantier (optionnelles)
  - Choix du type de chantier : intérieur ou extérieur
  - Affichage des informations du chantier sur l'onglet principal de la fiche site
  - Modification des chantiers programmés via un bouton dédié

- **Prévisions météorologiques pour chantiers extérieurs** :
  - Affichage automatique de la météo pour les jours d'intervention des chantiers extérieurs avec dates définies
  - Affichage des jours favorables (météo clémente) sur 15 jours pour les chantiers extérieurs sans date définie
  - Filtrage des jours favorables : uniquement les jours sans pluie, orage ou neige
  - Inclusion des jours nuageux comme météo clémente
  - Affichage uniquement des jours travaillés (lundi à vendredi) pour les prévisions sans date
  - Cohérence des données météo entre les différentes sections (réutilisation des données API)

- **Page de gestion des chantiers** : Nouvelle page accessible via le menu "Chantiers à venir"
  - Liste des chantiers programmés (avec dates) affichée sous forme de tuiles calendrier
  - Affichage de la météo prévue pour chaque jour d'intervention sur les tuiles
  - Tri automatique des tuiles par date de début (du plus proche au plus lointain)
  - Liste séparée des chantiers affectés (sans date) sous forme de tableau
  - Liste des devis signés non encore liés à un site
  - Possibilité d'affecter directement un site à un devis signé depuis la liste
  - Affichage des notes publiques des devis dans toutes les listes
  - Nettoyage automatique des balises HTML dans les notes publiques

### Améliorations
- **Filtrage des devis** : Seuls les devis signés (statut = 2) sont affichés et utilisables
  - Dans l'onglet "Chantier programmé" : uniquement les devis signés dans la liste déroulante
  - Dans la page "Chantiers à venir" : uniquement les devis signés non liés
  - Validation lors de la création/modification pour garantir que seul un devis signé peut être associé

- **Interface utilisateur** :
  - Tuiles calendrier avec design moderne et responsive
  - Badges visuels pour distinguer les chantiers intérieurs et extérieurs
  - Affichage optimisé des informations météorologiques avec icônes
  - Messages informatifs lorsque aucune donnée n'est disponible
  - Suppression des colonnes redondantes (statut des devis)

- **Gestion des données** :
  - Utilisation de `DISTINCT` dans les requêtes SQL pour éviter les doublons
  - Correction de l'affichage des noms de sites (suppression des doublons)
  - Gestion correcte des entités multi-entreprises
  - Nettoyage et troncature intelligente des notes publiques avec tooltips

### Corrections
- Correction de l'affichage des dates : message "Date non encore décidée" lorsque les dates sont vides
- Correction de la cohérence des données météo entre les différentes sections
- Correction des requêtes SQL pour éviter les erreurs de colonnes inexistantes
- Correction de la gestion des entités dans les requêtes de sites
- Amélioration de la gestion des erreurs SQL avec messages explicites

### Base de données
- Création de la table `llx_sites2_chantier` pour stocker les chantiers programmés
  - Champs : `fk_propal`, `fk_site`, `date_debut`, `date_fin`, `location_type`, `note_public`, `entity`
  - Dates optionnelles (peuvent être NULL)
  - Type de localisation : 0 = intérieur, 1 = extérieur

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