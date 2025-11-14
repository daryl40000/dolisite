# Changelog - Fonctionnalité Flotte de Véhicules Électriques

## Version 1.0.0 - Décembre 2024

### ✨ Nouvelles fonctionnalités

#### Configuration du module
- **[admin/setup.php]** Ajout d'une nouvelle section "Configuration de la flotte électrique"
- **[admin/setup.php]** Ajout de la case à cocher "Activer la flotte de véhicules électriques" (`SITES2_ELECTRIC_FLEET_ENABLED`)
- **[admin/setup.php]** Ajout du champ "Autonomie moyenne des véhicules électriques" (`SITES2_ELECTRIC_FLEET_AUTONOMY`)
- **[admin/setup.php]** Validation des prérequis : vérification de la configuration de l'agence de référence
- **[admin/setup.php]** Affichage d'avertissements avec liens directs si l'agence de référence n'est pas configurée

#### Carte interactive
- **[site_map.php]** Système de marqueurs colorés :
  - 🟢 **Vert** : Sites dans la portée électrique (distance ≤ autonomie/2)
  - 🔵 **Bleu** : Sites hors portée électrique
- **[site_map.php]** Légende automatique avec code couleur et distance maximale
- **[site_map.php]** Popups enrichies avec distance réelle et statut de portée électrique
- **[site_map.php]** Utilisation des distances réelles stockées en base (champ `distance_km`)

#### Statistiques interactives
- **[site_map.php]** Statistique "Sites dans la portée électrique" avec autonomie affichée : format "(300km)"
- **[site_map.php]** Lien cliquable sur le nombre de sites sans coordonnées
- **[site_list.php]** Nouveau filtre `search_no_coordinates` pour afficher uniquement les sites sans coordonnées
- **[site_list.php]** Titre adaptatif : "Liste des sites sans coordonnées" quand le filtre est actif
- **[site_list.php]** Préservation du filtre lors des actions sur la page

### 🔧 Modifications techniques

#### Base de données
- **[site_map.php]** Modification de la requête SQL pour inclure le champ `distance_km`
- **[site_map.php]** Utilisation des distances pré-calculées au lieu du calcul à vol d'oiseau

#### Logique métier
- **[site_map.php]** Suppression de la fonction `calculateDistance()` (calcul Haversine)
- **[site_map.php]** Implémentation de la logique de portée basée sur les données réelles
- **[admin/setup.php]** Ajout des nouveaux paramètres dans `$params_to_save`
- **[admin/setup.php]** Gestion spéciale des types de données (checkbox, numérique)

#### Interface utilisateur
- **[site_map.php]** Ajout d'icônes Leaflet colorées (vert/bleu)
- **[site_map.php]** Styling de la légende avec CSS inline
- **[admin/setup.php]** Messages d'avertissement avec icônes Font Awesome
- **[site_map.php]** Icônes dans les popups (🍃 vert, ⚠️ orange)
- **[site_map.php]** Liens cliquables avec tooltip sur les statistiques
- **[site_list.php]** Système de filtrage par absence de coordonnées
- **[site_list.php]** Paramètres cachés pour préserver les filtres lors des actions

### 🌍 Traductions ajoutées

#### Français (langs/fr_FR/sites2.lang)
```diff
+ SITES2_ELECTRIC_FLEET_ENABLED = Activer la flotte de véhicules électriques
+ SITES2_ELECTRIC_FLEET_ENABLEDTooltip = Active l'affichage différencié des sites selon l'autonomie des véhicules électriques
+ SITES2_ELECTRIC_FLEET_AUTONOMY = Autonomie moyenne des véhicules électriques (km)
+ SITES2_ELECTRIC_FLEET_AUTONOMYTooltip = Autonomie moyenne de vos véhicules électriques en kilomètres (utilisée pour calculer l'aller-retour)
+ ElectricFleetConfiguration = Configuration de la flotte électrique
+ ElectricRangeOK = Dans la portée électrique (aller-retour possible)
+ ElectricRangeKO = Hors portée électrique
+ ElectricFleetLegend = Légende - Flotte électrique
+ MaxDistance = Distance maximale
+ Distance = Distance
+ ElectricFleetRequiresReferenceAgency = La fonctionnalité de flotte électrique nécessite la configuration d'une agence de référence.
+ ConfigureReferenceAgency = Configurer l'agence de référence
+ SitesInElectricRange = Sites dans la portée électrique
+ ViewSitesWithoutCoordinates = Voir la liste des sites sans coordonnées
+ ListOfSitesWithoutCoordinates = Liste des sites sans coordonnées
```

#### Anglais (langs/en_US/sites2.lang)
```diff
+ SITES2_ELECTRIC_FLEET_ENABLED = Enable Electric Vehicle Fleet
+ SITES2_ELECTRIC_FLEET_ENABLEDTooltip = Enables differentiated display of sites according to electric vehicle range
+ SITES2_ELECTRIC_FLEET_AUTONOMY = Average Electric Vehicle Range (km)
+ SITES2_ELECTRIC_FLEET_AUTONOMYTooltip = Average range of your electric vehicles in kilometers (used to calculate round trip)
+ ElectricFleetConfiguration = Electric Fleet Configuration
+ ElectricRangeOK = Within electric range (round trip possible)
+ ElectricRangeKO = Out of electric range
+ ElectricFleetLegend = Legend - Electric Fleet
+ MaxDistance = Maximum distance
+ Distance = Distance
+ ElectricFleetRequiresReferenceAgency = Electric fleet functionality requires a reference agency to be configured.
+ ConfigureReferenceAgency = Configure reference agency
+ SitesInElectricRange = Sites within electric range
+ ViewSitesWithoutCoordinates = View list of sites without coordinates
+ ListOfSitesWithoutCoordinates = List of sites without coordinates
```

## 📝 Détail des modifications par fichier

### admin/setup.php
```diff
+ // Ajout des nouveaux paramètres
+ 'SITES2_ELECTRIC_FLEET_ENABLED',
+ 'SITES2_ELECTRIC_FLEET_AUTONOMY'

+ // Gestion spéciale des paramètres de flotte électrique
+ } elseif ($param == 'SITES2_ELECTRIC_FLEET_ENABLED') {
+     $res = dolibarr_set_const($db, $param, $value ? '1' : '0', 'chaine', 0, '', $conf->entity);
+ } elseif ($param == 'SITES2_ELECTRIC_FLEET_AUTONOMY') {
+     if (!empty($value) && is_numeric($value)) {
+         $res = dolibarr_set_const($db, $param, $value, 'chaine', 0, '', $conf->entity);
+     }

+ // Nouvelle section pour la flotte électrique
+ print '<table class="noborder centpercent">';
+ print '<tr class="liste_titre">';
+ print '<td><span class="fas fa-leaf"></span> '.$langs->trans("ElectricFleetConfiguration").'</td>';

+ // Validation des prérequis
+ $hasReferenceAgency = !empty($conf->global->SITES2_USE_REFERENCE_AGENCY) && 
+     !empty($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) && 
+     !empty($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE);
```

### site_map.php
```diff
+ // Modification de la requête SQL
- $sql = "SELECT s.rowid, s.ref, s.label, s.address, s.zip, s.town, s.phone, s.fk_soc, s.latitude, s.longitude, s.status FROM ".MAIN_DB_PREFIX."sites2_site as s";
+ $sql = "SELECT s.rowid, s.ref, s.label, s.address, s.zip, s.town, s.phone, s.fk_soc, s.latitude, s.longitude, s.status, s.distance_km FROM ".MAIN_DB_PREFIX."sites2_site as s";

+ // Logique de portée électrique basée sur les données réelles
+ if ($electricFleetEnabled && $electricFleetAutonomy > 0 && !empty($obj->distance_km)) {
+     if ($obj->distance_km <= ($electricFleetAutonomy / 2)) {
+         $isInElectricRange = true;
+     }
+ }

+ // Ajout des données de distance dans les données JavaScript
+ 'distance_km' => $obj->distance_km,
+ 'isInElectricRange' => $isInElectricRange

+ // Marqueurs colorés
+ var blueIcon = new L.Icon({
+     iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png",
+ var greenIcon = new L.Icon({
+     iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png",

+ // Popups enrichies
+ if (site.distance_km) {
+     popupContent += "<b>Distance:</b> " + site.distance_km + " km<br>";
+ }

+ // Légende interactive
+ if ($electricFleetEnabled && $hasReferenceAgency && $electricFleetAutonomy > 0) {
+     print '<div class="info-box" style="margin-bottom: 10px; padding: 10px; background-color: #f0f8ff;">';
+ 
+ // Statistiques avec autonomie et lien cliquable
+ print '<td><span class="fas fa-leaf" style="color: green;"></span> ' . $langs->trans("SitesInElectricRange") . ' (' . $electricFleetAutonomy . 'km)</td>';
+ if ($obj->count > 0) {
+     print '<a href="' . DOL_URL_ROOT . '/custom/sites2/site_list.php?search_no_coordinates=1" class="classfortooltip" title="' . $langs->trans("ViewSitesWithoutCoordinates") . '">';
+     print $obj->count;
+     print '</a>';
+ }
```

### site_list.php
```diff
+ // Nouveau paramètre de recherche
+ $search_no_coordinates = GETPOST('search_no_coordinates', 'int');

+ // Filtrage SQL pour sites sans coordonnées
+ if (!empty($search_no_coordinates)) {
+     $sql .= " AND (s.latitude IS NULL OR s.longitude IS NULL OR s.latitude = '' OR s.longitude = '')";
+ }

+ // Titre adaptatif
+ $title = $langs->trans("ListOfSites");
+ if (!empty($search_no_coordinates)) {
+     $title = $langs->trans("ListOfSitesWithoutCoordinates");
+ }

+ // Préservation du paramètre dans les formulaires
+ if (!empty($search_no_coordinates)) {
+     print '<input type="hidden" name="search_no_coordinates" value="'.$search_no_coordinates.'">';
+ }
```

## 🎯 Impact et bénéfices

### Performance
- ✅ **Pas de calcul en temps réel** : Utilisation des distances pré-calculées
- ✅ **Requête optimisée** : Un seul appel SQL avec toutes les données nécessaires
- ✅ **Interface réactive** : Affichage instantané des marqueurs colorés

### Précision
- ✅ **Distances réelles** : Utilisation des données d'API de routage
- ✅ **Calcul d'aller-retour** : Autonomie divisée par 2 pour tenir compte du retour
- ✅ **Données fiables** : Basé sur les distances stockées et vérifiées

### Expérience utilisateur
- ✅ **Visualisation intuitive** : Code couleur universellement compris (vert=OK, bleu=standard)
- ✅ **Information contextuelle** : Popups avec distance exacte et statut
- ✅ **Configuration guidée** : Validation des prérequis avec liens directs
- ✅ **Légende claire** : Explication automatique des couleurs et distances
- ✅ **Navigation intelligente** : Liens cliquables vers les données problématiques
- ✅ **Gestion facilitée** : Accès direct aux sites nécessitant une correction
- ✅ **Workflow optimisé** : Filtrage automatique pour la maintenance des données

## 🔮 Évolutions futures possibles

1. **Multi-véhicules** : Gestion de différents types de véhicules avec autonomies variables
2. **Charge dynamique** : Prise en compte du niveau de charge actuel
3. **Optimisation de tournées** : Suggestion d'itinéraires optimisés
4. **Alertes** : Notifications pour sites hors portée
5. **Statistiques** : Tableau de bord avec métriques de couverture électrique
6. **Export** : Liste des sites accessibles/non accessibles
7. **API** : Endpoint pour intégration avec systèmes externes

---

**Auteur** : Assistant IA  
**Date** : Décembre 2024  
**Version du module** : Sites2  
**Compatibilité** : Dolibarr 13+ 