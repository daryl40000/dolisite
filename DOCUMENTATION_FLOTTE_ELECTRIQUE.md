# Documentation - Fonctionnalité Flotte de Véhicules Électriques

## 📋 Vue d'ensemble

Cette documentation décrit l'implémentation de la fonctionnalité de **flotte de véhicules électriques** dans le module Sites2 de Dolibarr. Cette fonctionnalité permet de visualiser sur une carte quels sites clients sont accessibles avec des véhicules électriques en fonction de leur autonomie.

## 🎯 Objectif

Permettre aux entreprises possédant une flotte de véhicules électriques de :
- Visualiser rapidement les sites accessibles avec leurs véhicules électriques
- Optimiser la planification des tournées en fonction de l'autonomie des véhicules
- Différencier visuellement les sites selon leur accessibilité électrique

## 🔧 Fichiers Modifiés

### 1. Configuration du module
- **`admin/setup.php`** : Ajout des options de configuration
- **`langs/fr_FR/sites2.lang`** : Traductions françaises
- **`langs/en_US/sites2.lang`** : Traductions anglaises

### 2. Carte interactive
- **`site_map.php`** : Logique d'affichage des marqueurs colorés

## ⚡ Nouvelles Fonctionnalités

### 1. Page de Configuration (`admin/setup.php`)

#### Section "Configuration de la flotte électrique"
- **Case à cocher "Activer la flotte de véhicules électriques"**
  - Paramètre : `SITES2_ELECTRIC_FLEET_ENABLED`
  - Active/désactive la fonctionnalité

- **Champ "Autonomie moyenne des véhicules électriques (km)"**
  - Paramètre : `SITES2_ELECTRIC_FLEET_AUTONOMY`
  - Valeur numérique en kilomètres
  - Exemple : 300 km

#### Validation des prérequis
- Vérification que l'agence de référence est configurée
- Affichage d'un avertissement si non configurée
- Lien direct vers la configuration de l'agence de référence

### 2. Carte Interactive (`site_map.php`)

#### Marqueurs colorés
- **🟢 Vert** : Sites dans la portée électrique (distance ≤ autonomie/2)
- **🔵 Bleu** : Sites hors portée électrique

#### Légende interactive
- Affichage automatique quand la flotte électrique est activée
- Indication de la distance maximale calculée (autonomie ÷ 2)
- Explication des couleurs des marqueurs

#### Popups enrichies
- Affichage de la distance réelle en kilomètres
- Indication du statut de portée électrique
- Icônes visuelles (🍃 pour accessible, ⚠️ pour hors portée)

#### Statistiques interactives
- **Nombre de sites dans la portée électrique** : Affichage du format "X / total" avec l'autonomie configurée
- **Lien cliquable vers les sites sans coordonnées** : Accès direct à la liste filtrée des sites nécessitant une correction
- **Titre adaptatif** : La page de liste s'adapte pour indiquer le filtre actif

## 🛠️ Détails Techniques

### Logique de Calcul

```php
// Condition pour déterminer si un site est dans la portée électrique
if ($electricFleetEnabled && $electricFleetAutonomy > 0 && !empty($obj->distance_km)) {
    if ($obj->distance_km <= ($electricFleetAutonomy / 2)) {
        $isInElectricRange = true;
    }
}
```

### Utilisation des Données Réelles
- **Source** : Champ `distance_km` de la table `llx_sites2_site`
- **Avantage** : Utilise les distances réelles calculées par l'API de routage
- **Performance** : Pas de calcul en temps réel, utilisation des données pré-calculées

### Paramètres de Configuration

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `SITES2_ELECTRIC_FLEET_ENABLED` | boolean | Active/désactive la fonctionnalité | 1 ou 0 |
| `SITES2_ELECTRIC_FLEET_AUTONOMY` | integer | Autonomie en kilomètres | 300 |

## 🌍 Traductions Ajoutées

### Français (`langs/fr_FR/sites2.lang`)
```
SITES2_ELECTRIC_FLEET_ENABLED = Activer la flotte de véhicules électriques
SITES2_ELECTRIC_FLEET_ENABLEDTooltip = Active l'affichage différencié des sites selon l'autonomie des véhicules électriques
SITES2_ELECTRIC_FLEET_AUTONOMY = Autonomie moyenne des véhicules électriques (km)
SITES2_ELECTRIC_FLEET_AUTONOMYTooltip = Autonomie moyenne de vos véhicules électriques en kilomètres (utilisée pour calculer l'aller-retour)
ElectricFleetConfiguration = Configuration de la flotte électrique
ElectricRangeOK = Dans la portée électrique (aller-retour possible)
ElectricRangeKO = Hors portée électrique
ElectricFleetLegend = Légende - Flotte électrique
MaxDistance = Distance maximale
Distance = Distance
ElectricFleetRequiresReferenceAgency = La fonctionnalité de flotte électrique nécessite la configuration d'une agence de référence.
ConfigureReferenceAgency = Configurer l'agence de référence
SitesInElectricRange = Sites dans la portée électrique
ViewSitesWithoutCoordinates = Voir la liste des sites sans coordonnées
ListOfSitesWithoutCoordinates = Liste des sites sans coordonnées
```

### Anglais (`langs/en_US/sites2.lang`)
```
SITES2_ELECTRIC_FLEET_ENABLED = Enable Electric Vehicle Fleet
SITES2_ELECTRIC_FLEET_ENABLEDTooltip = Enables differentiated display of sites according to electric vehicle range
SITES2_ELECTRIC_FLEET_AUTONOMY = Average Electric Vehicle Range (km)
SITES2_ELECTRIC_FLEET_AUTONOMYTooltip = Average range of your electric vehicles in kilometers (used to calculate round trip)
ElectricFleetConfiguration = Electric Fleet Configuration
ElectricRangeOK = Within electric range (round trip possible)
ElectricRangeKO = Out of electric range
ElectricFleetLegend = Legend - Electric Fleet
MaxDistance = Maximum distance
Distance = Distance
ElectricFleetRequiresReferenceAgency = Electric fleet functionality requires a reference agency to be configured.
ConfigureReferenceAgency = Configure reference agency
SitesInElectricRange = Sites within electric range
ViewSitesWithoutCoordinates = View list of sites without coordinates
ListOfSitesWithoutCoordinates = List of sites without coordinates
```

## 📖 Guide d'Utilisation

### 1. Configuration Initiale

1. **Configurer l'agence de référence** (prérequis)
   - Aller dans `Administration > Sites2 > Agence de référence`
   - Activer l'utilisation de l'agence de référence
   - Saisir le nom, latitude et longitude de l'agence

2. **Activer la flotte électrique**
   - Aller dans `Administration > Sites2 > Configuration`
   - Cocher "Activer la flotte de véhicules électriques"
   - Saisir l'autonomie moyenne (ex: 300 km)
   - Cliquer sur "Enregistrer"

### 2. Visualisation sur la Carte

1. **Accéder à la carte**
   - Menu `Sites2 > Carte des sites`

2. **Interpréter les marqueurs**
   - **🟢 Marqueurs verts** : Sites accessibles en aller-retour
   - **🔵 Marqueurs bleus** : Sites hors portée électrique

3. **Consulter les détails**
   - Cliquer sur un marqueur pour voir la popup
   - Distance réelle affichée
   - Statut de portée électrique indiqué

4. **Utiliser les statistiques interactives**
   - **Portée électrique** : Voir le nombre de sites accessibles au format "X / total (autonomie)"
   - **Sites sans coordonnées** : Cliquer sur le chiffre pour accéder à la liste filtrée
   - **Correction en lot** : Utiliser les actions massives sur la page de liste

### 3. Gestion des Sites sans Coordonnées

1. **Accès depuis la carte**
   - Consulter les statistiques en bas de la carte
   - Cliquer sur le chiffre des "Sites sans coordonnées"

2. **Page de liste filtrée**
   - Titre adaptatif : "Liste des sites sans coordonnées"
   - Seuls les sites sans latitude/longitude sont affichés
   - Actions massives disponibles pour correction en lot

3. **Correction des données**
   - Accéder aux fiches individuelles depuis la liste
   - Ajouter les coordonnées manquantes
   - Utiliser l'import CSV pour les corrections en lot

### 4. Exemple Pratique

**Scénario** : Véhicules avec 300 km d'autonomie
- **Distance maximale** : 150 km (aller-retour)
- **Sites à 120 km** : Marqueur vert ✅
- **Sites à 180 km** : Marqueur bleu ❌

## 🔍 Structure du Code

### Modifications dans `admin/setup.php`
```php
// Ajout des nouveaux paramètres
$params_to_save = array(
    // ... autres paramètres
    'SITES2_ELECTRIC_FLEET_ENABLED',
    'SITES2_ELECTRIC_FLEET_AUTONOMY'
);

// Validation des prérequis
$hasReferenceAgency = !empty($conf->global->SITES2_USE_REFERENCE_AGENCY) && 
    !empty($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) && 
    !empty($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE);
```

### Modifications dans `site_map.php`
```php
// Récupération de la distance réelle
$sql = "SELECT s.rowid, s.ref, s.label, s.address, s.zip, s.town, s.phone, s.fk_soc, s.latitude, s.longitude, s.status, s.distance_km FROM ".MAIN_DB_PREFIX."sites2_site as s";

// Logique de détermination de la portée
if ($electricFleetEnabled && $electricFleetAutonomy > 0 && !empty($obj->distance_km)) {
    if ($obj->distance_km <= ($electricFleetAutonomy / 2)) {
        $isInElectricRange = true;
    }
}

// Marqueurs colorés JavaScript
var markerIcon = (site.isInElectricRange && electricFleetEnabled) ? greenIcon : blueIcon;

// Statistiques avec lien cliquable
if ($obj->count > 0) {
    print '<a href="' . DOL_URL_ROOT . '/custom/sites2/site_list.php?search_no_coordinates=1">';
    print $obj->count;
    print '</a>';
}
```

### Modifications dans `site_list.php`
```php
// Nouveau paramètre de filtrage
$search_no_coordinates = GETPOST('search_no_coordinates', 'int');

// Condition SQL pour filtrer les sites sans coordonnées
if (!empty($search_no_coordinates)) {
    $sql .= " AND (s.latitude IS NULL OR s.longitude IS NULL OR s.latitude = '' OR s.longitude = '')";
}

// Titre adaptatif selon le filtre
$title = $langs->trans("ListOfSites");
if (!empty($search_no_coordinates)) {
    $title = $langs->trans("ListOfSitesWithoutCoordinates");
}
```

## 📊 Base de Données

### Champ Utilisé
- **Table** : `llx_sites2_site`
- **Champ** : `distance_km` (type: double)
- **Description** : Distance réelle en kilomètres depuis l'agence de référence

### Nouvelles Constantes
- `SITES2_ELECTRIC_FLEET_ENABLED` : Activation de la fonctionnalité
- `SITES2_ELECTRIC_FLEET_AUTONOMY` : Autonomie des véhicules

## 🎨 Interface Utilisateur

### Éléments Visuels Ajoutés
1. **Section de configuration** avec icône 🍃
2. **Légende sur la carte** avec code couleur
3. **Marqueurs colorés** (vert/bleu)
4. **Popups enrichies** avec distance et statut
5. **Messages d'avertissement** pour les prérequis

### CSS/Styling
- Légende avec fond bleu clair (`#f0f8ff`)
- Bordure et bordures arrondies
- Icônes Font Awesome pour les statuts
- Couleurs sémantiques (vert = OK, orange = attention)

## 🚀 Améliorations Apportées

### Performance
- ✅ Utilisation des distances pré-calculées (pas de calcul en temps réel)
- ✅ Requête SQL optimisée avec jointure
- ✅ Pas de calculs JavaScript complexes

### Précision
- ✅ Distances réelles par la route (vs à vol d'oiseau)
- ✅ Basé sur les données API de routage existantes
- ✅ Calcul d'aller-retour (autonomie ÷ 2)

### Expérience Utilisateur
- ✅ Interface intuitive avec code couleur
- ✅ Légende explicative automatique
- ✅ Validation des prérequis avec liens directs
- ✅ Popups informatives enrichies

## 🔧 Maintenance

### Points d'Attention
1. **Agence de référence** : Doit être configurée avant utilisation
2. **Distances calculées** : Les sites doivent avoir leurs distances calculées
3. **Performance** : Utilise les données existantes, pas de surcharge

### Évolutions Possibles
- Gestion de différents types de véhicules
- Prise en compte de la charge restante
- Intégration avec les planning de tournées
- Export des sites accessibles

---

**Date de création** : Décembre 2024  
**Version du module** : Sites2 v1.x  
**Compatibilité** : Dolibarr 13+ 