# CHANGELOG SITES2 MODULE

## 2.3.6 - 2025-11-18
### Corrections
- **Correction de l'affichage de la carte des chantiers** : Correction du problème où la carte des chantiers programmés s'affichait en gris
  - Ajout d'un centre et d'un zoom par défaut lors de l'initialisation de la carte
  - Vérification du chargement de Leaflet avant l'initialisation
  - Gestion d'erreur si Leaflet n'est pas chargé
  - La carte s'affiche maintenant correctement même sans marqueurs

- **Correction de la logique de séparation des chantiers** : Correction du problème où les chantiers avec dates apparaissaient dans les deux catégories
  - Les chantiers avec une date de début (même sans date de fin) sont maintenant uniquement dans "chantiers à venir"
  - Les chantiers sans date de début sont uniquement dans "chantiers affectés"
  - Condition SQL corrigée : `date_debut IS NULL` pour les chantiers affectés au lieu de `(date_debut IS NULL OR date_fin IS NULL)`

- **Filtrage de la météo par dates du chantier** : La météo n'est maintenant affichée que pour les jours prévus du chantier
  - Si le chantier a une date de début et une date de fin : météo uniquement pour les jours entre ces deux dates (inclus)
  - Si le chantier a seulement une date de début : météo uniquement pour ce jour
  - Les jours météo en dehors de la période du chantier ne sont plus affichés

### Améliorations
- **Agence de référence sur la carte des chantiers** : Ajout de l'agence de référence sur la carte des chantiers programmés
  - Marqueur vert pour l'agence de référence avec popup affichant le nom
  - Ajustement automatique du zoom pour inclure tous les chantiers et l'agence de référence
  - Ajout de l'agence de référence dans la légende de la carte
  - La carte s'affiche même s'il n'y a pas de chantiers, si l'agence de référence est configurée

## 2.3.5 - 2025-11-18
### Corrections
- **Géocodage forcé sur OpenStreetMap** : Le géocodage utilise maintenant toujours OpenStreetMap, indépendamment du fournisseur de cartes configuré
  - Le choix Google Maps / OpenStreetMap reste disponible uniquement pour l'affichage des cartes
  - Amélioration de la fiabilité du géocodage (OpenStreetMap fonctionne sans clé API et sans restrictions)
  - Correction des problèmes de géocodage avec Google Maps (erreurs d'autorisation API)

- **Correction du bug Open-Meteo** : Correction du problème où Open-Meteo ne fonctionnait pas lors de la première utilisation sur un nouveau système
  - Vérification du fournisseur météo avant de vérifier la présence de la clé API OpenWeatherMap
  - Open-Meteo fonctionne maintenant dès la première utilisation sans nécessiter de passer par OpenWeatherMap

- **Correction des coordonnées GPS vides** : Conversion automatique des chaînes vides en NULL pour les colonnes DECIMAL
  - Évite l'erreur SQL "Incorrect decimal value: '' for column latitude"
  - Les coordonnées vides sont maintenant correctement gérées lors de la création et de la mise à jour

- **Création de la table chantier** : Ajout de la création automatique de la table `llx_sites2_chantier` lors de l'installation du module
  - La table est maintenant créée automatiquement lors de l'activation du module
  - Plus besoin d'exécuter manuellement le script SQL

### Améliorations
- **Récupération automatique de l'adresse du tiers** : Ajout d'un bouton pour récupérer automatiquement l'adresse, le code postal et la ville du tiers sélectionné
  - Bouton "Récupérer l'adresse du tiers" à côté du champ "Tiers" dans les formulaires de création et d'édition
  - Remplissage automatique des champs adresse, code postal et ville depuis les informations du tiers
  - Gain de temps et réduction des erreurs de saisie
  - Endpoint AJAX dédié pour la récupération des informations

- **Gestion d'erreurs améliorée pour Google Maps** : Amélioration de la gestion des erreurs de l'API Google Maps
  - Gestion complète de tous les codes de statut de l'API (OK, ZERO_RESULTS, OVER_QUERY_LIMIT, REQUEST_DENIED, INVALID_REQUEST)
  - Messages d'erreur détaillés dans les logs pour faciliter le diagnostic
  - Timeout de 10 secondes pour éviter les blocages

- **Purge automatique des chantiers** : Suppression automatique des chantiers dont le devis n'est plus signé
  - Purge automatique à chaque chargement de la page "Chantiers à venir"
  - Filtrage des requêtes pour n'afficher que les chantiers avec devis signés
  - Maintien de la cohérence des données

## 2.3.4 - 2025-11-17
### Améliorations
- **Affichage dynamique du nombre de jours** : Le texte affiche maintenant correctement "15 prochains jours" lorsque Open-Meteo est utilisé avec 15 jours pour les chantiers
  - Correction du double affichage du nombre de jours dans le texte
  - Remplacement automatique du nombre de jours dans les traductions selon le fournisseur et le contexte

- **Bruine légère acceptée comme favorable** : Les jours avec bruine légère sont maintenant considérés comme favorables mais avec un indicateur visuel
  - Affichage en jaune (fond jaune clair, bordure jaune/orange) pour les jours avec bruine légère
  - Détection précise via le code météo WMO (code 51)
  - La bruine modérée et dense reste défavorable
  - Stockage du code météo dans les données pour permettre la détection précise

## 2.3.3 - 2025-11-17
### Ajouts
- **Support de l'API Open-Meteo** : Ajout de la possibilité d'utiliser l'API Open-Meteo comme alternative à OpenWeatherMap
  - Nouveau paramètre de configuration `SITES2_WEATHER_PROVIDER` pour choisir entre OpenWeatherMap et Open-Meteo
  - Open-Meteo est gratuit et ne nécessite pas de clé API
  - Prévisions météorologiques jusqu'à 8 jours avec Open-Meteo (affichage standard)
  - **Prévisions étendues pour les chantiers** : Recherche de jours favorables sur 15 jours pour les chantiers à venir lorsque Open-Meteo est utilisé
  - Conversion automatique des codes météo WMO (World Meteorological Organization) en descriptions et icônes
  - Interface de configuration mise à jour avec sélection du fournisseur
  - Les champs de configuration OpenWeatherMap sont masqués automatiquement lorsque Open-Meteo est sélectionné

### Corrections
- **Précision des coordonnées GPS** : Correction du problème d'arrondi des coordonnées latitude et longitude
  - Migration des colonnes `latitude` et `longitude` de `DOUBLE` vers `DECIMAL(10,8)` et `DECIMAL(11,8)` pour garantir une précision exacte de 8 décimales
  - Modification du type de champ dans la définition Dolibarr de `double` vers `varchar(20)` pour éviter le formatage automatique
  - Les coordonnées GPS sont maintenant stockées et affichées exactement telles qu'elles sont saisies, sans arrondi ni formatage
  - Préservation du signe négatif pour les coordonnées
  - Les valeurs saisies manuellement sont conservées avec leur précision complète

### Base de données
- Migration des colonnes `latitude` et `longitude` vers `DECIMAL(10,8)` et `DECIMAL(11,8)` pour une précision exacte
- Script de migration disponible pour les installations existantes

## 2.3.2 - 2025-11-15
### Corrections
- **Migration vers One Call API 3.0** : Correction de l'utilisation de l'API payante OpenWeatherMap
  - Remplacement de l'endpoint `/forecast/daily` (déprécié) par `/data/3.0/onecall` (One Call API 3.0)
  - Adaptation du traitement des données pour la nouvelle structure de réponse (`current` et `daily` au lieu de `list`)
  - Correction de toutes les références à 15/16 jours vers 8 jours (limite réelle de l'API One Call 3.0)
  - Mise à jour des commentaires, documentation et fichiers de langue pour refléter 8 jours au lieu de 15/16 jours
  - Affichage de 8 jours de prévisions météo avec l'API payante (au lieu de 6 jours avec l'API gratuite)

### Améliorations
- **Carte des chantiers programmés** : Ajout d'une carte interactive sur la page "Chantiers à venir"
  - Affichage de tous les chantiers à venir (avec dates) et chantiers affectés (sans dates) sur une carte
  - Marqueurs bleus pour les chantiers à venir, marqueurs orange pour les chantiers affectés
  - Popups informatives avec détails du chantier (site, devis, tiers, dates, type de localisation)
  - Légende pour distinguer les deux types de chantiers
  - Ajustement automatique de la vue pour afficher tous les marqueurs
  - Support des fournisseurs de cartes (OpenStreetMap par défaut, Google Maps si configuré)
  - Optimisation : stockage des résultats SQL dans des tableaux pour éviter les requêtes multiples

- **Interface utilisateur** : Optimisation de l'affichage
  - Réduction de la hauteur de la carte de 500px à 350px
  - Réduction de la taille minimale des tuiles calendrier de 320px à 260px
  - Réduction de l'espacement entre les tuiles de 20px à 15px
  - Réduction du padding des tuiles de 15px à 12px
  - Interface plus compacte tout en conservant la lisibilité

### Traductions
- Ajout des traductions manquantes pour la carte des chantiers :
  - `MapOfScheduledWorkSites` (FR/EN)
  - `AssignedWorkSite` (FR/EN)
  - `Legend` (FR/EN)
- Mise à jour des textes pour refléter 8 jours au lieu de 15 jours dans les fichiers de langue

## 2.3.1 - 2025-11-15
### Corrections
- **Correction de l'erreur `checkToken()`** : Ajout de la bibliothèque `security.lib.php` et implémentation d'une vérification conditionnelle du token CSRF compatible avec différentes versions de Dolibarr
  - Support de `checkToken()`, `dol_check_token()` ou vérification basique via session
  - Correction de l'initialisation de la variable `$error` dans le traitement des paramètres

- **Correction de l'affichage de la météo** : Résolution du problème empêchant l'affichage des prévisions météorologiques
  - Correction de la récupération du type d'API depuis la configuration (gratuite/payante)
  - Amélioration de la vérification des codes de réponse de l'API OpenWeatherMap (gestion des codes string et integer)
  - Support de l'API payante qui peut ne pas retourner de champ `cod` dans la réponse
  - Ajout de vérifications pour s'assurer que les données de prévisions sont présentes avant traitement
  - Application des mêmes corrections à `sites2GetWeatherData` et `sites2GetFavorableWeatherDays`

### Améliorations
- **Gestion des erreurs API** : Messages d'erreur plus détaillés incluant le code d'erreur retourné par l'API OpenWeatherMap
- **Robustesse** : Vérifications supplémentaires pour éviter les erreurs lorsque les données API sont incomplètes

## 2.3 - 2025-11-15
### Ajouts majeurs
- **Support de l'API payante OpenWeatherMap** : Possibilité d'utiliser l'API payante One Call API 3.0 pour obtenir jusqu'à 8 jours de prévisions météorologiques
  - Nouveau paramètre de configuration `SITES2_WEATHER_API_TYPE` pour choisir entre API gratuite (5 jours) et API payante (8 jours)
  - Support automatique des deux endpoints : `/forecast` (gratuit) et `/data/3.0/onecall` (payant - One Call API 3.0)
  - Adaptation automatique du traitement des données selon le type d'API utilisé
  - Limite automatique : 6 jours pour l'API gratuite, 8 jours pour l'API payante
  - Interface de configuration avec explications sur les différences entre les deux types d'API

- **Gestion des chantiers programmés** : Nouvelle fonctionnalité complète de planification des chantiers
  - Nouvel onglet "Chantier programmé" sur la fiche site (entre les onglets Contact et Équipement)
  - Association d'un devis signé à un site pour créer un chantier programmé
  - Sélection de dates théoriques de début et fin de chantier (optionnelles)
  - Choix du type de chantier : intérieur ou extérieur
  - Affichage des informations du chantier sur l'onglet principal de la fiche site
  - Modification des chantiers programmés via un bouton dédié

- **Prévisions météorologiques pour chantiers extérieurs** :
  - Affichage automatique de la météo pour les jours d'intervention des chantiers extérieurs avec dates définies
  - Affichage des jours favorables (météo clémente) sur 8 jours pour les chantiers extérieurs sans date définie (5 jours avec API gratuite)
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

## 2.2 - 2025-11-14
### Corrections
- Correction du bug d'affichage de l'erreur "ErrorBadValueForContactId" lors de l'ajout d'un contact
  - Validation des paramètres (contactid et typeid) avant l'appel à la fonction add_contact()
  - Affichage de messages d'erreur clairs si un contact ou un type de contact n'est pas sélectionné
  - L'erreur ne s'affiche plus lorsque l'opération réussit correctement
  - Ajout des traductions pour les messages d'erreur de validation

## 2.1 - 2025-11-14
### Améliorations
- Réorganisation de l'affichage : la carte s'affiche maintenant en premier, suivie du panneau météo en dessous
- Réduction de la taille du tableau météo pour un affichage plus compact
  - Tailles de police réduites
  - Icônes météo plus petites
  - Espacements optimisés
  - Interface plus sobre et moins encombrante

## 2.0 - 2025-11-14
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

## 1.10 - 2025-03-18
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

## 1.00 - 2025-02-15
### Version initiale
- Gestion des sites avec adresses complètes
- Géocodage automatique des adresses (latitude/longitude)
- Affichage des sites sur une carte interactive
- Calcul automatique de la distance en kilomètres depuis le siège social ou l'agence de référence
- Estimation du temps de trajet
- Gestion des contacts associés au site 