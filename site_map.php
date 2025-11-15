<?php
/* Copyright (C) 2023-2024 Module Sites2
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       site_map.php
 *  \ingroup    sites2
 *  \brief      Carte générale de tous les sites clients
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

dol_include_once('/sites2/class/site.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("sites2@sites2", "other"));

// Security check
if (!$user->rights->sites2->site->read) accessforbidden();

// Output page
llxHeader('', $langs->trans("SitesMap"), '');

print load_fiche_titre($langs->trans("SitesMap"), '', 'object_map');

// Récupération de tous les sites avec des coordonnées
$sql = "SELECT s.rowid, s.ref, s.label, s.address, s.zip, s.town, s.phone, s.fk_soc, s.latitude, s.longitude, s.status, s.distance_km FROM ".MAIN_DB_PREFIX."sites2_site as s";
$sql.= " WHERE s.latitude IS NOT NULL AND s.longitude IS NOT NULL AND s.latitude != '' AND s.longitude != ''";
$sql.= " ORDER BY s.ref";

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Création du conteneur pour la carte
print '<div class="fichecenter">';

// Afficher la légende si la flotte électrique est activée
if ($electricFleetEnabled && $hasReferenceAgency && $electricFleetAutonomy > 0) {
    print '<div class="info-box" style="margin-bottom: 10px; padding: 10px; background-color: #f0f8ff; border: 1px solid #ddd; border-radius: 5px;">';
    print '<h4 style="margin: 0 0 10px 0;"><i class="fas fa-leaf" style="color: green;"></i> ' . $langs->trans("ElectricFleetLegend") . '</h4>';
    print '<div style="display: flex; align-items: center; margin-bottom: 5px;">';
    print '<div style="width: 20px; height: 20px; background-color: green; margin-right: 10px; border-radius: 50%;"></div>';
    print '<span>' . $langs->trans("ElectricRangeOK") . ' (' . $langs->trans("MaxDistance") . ': ' . round($electricFleetAutonomy/2) . ' km)</span>';
    print '</div>';
    print '<div style="display: flex; align-items: center;">';
    print '<div style="width: 20px; height: 20px; background-color: blue; margin-right: 10px; border-radius: 50%;"></div>';
    print '<span>' . $langs->trans("ElectricRangeKO") . '</span>';
    print '</div>';
    print '</div>';
}

print '<div style="width: 100%; margin-bottom: 20px;">';
print '<div style="height: 600px;" id="map"></div>';
print '</div>';
print '</div>';

// Charger les scripts et styles Leaflet
print '<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>';
print '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin="" />';

// Déterminer le fournisseur de cartes
$mapProvider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
$googleMapsApiKey = !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY) ? $conf->global->SITES2_GOOGLE_MAPS_API_KEY : '';

// Vérifier si l'agence de référence est configurée
$hasReferenceAgency = !empty($conf->global->SITES2_USE_REFERENCE_AGENCY) && 
    !empty($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) && 
    !empty($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE);

// Vérifier si la flotte électrique est activée
$electricFleetEnabled = !empty($conf->global->SITES2_ELECTRIC_FLEET_ENABLED);
$electricFleetAutonomy = !empty($conf->global->SITES2_ELECTRIC_FLEET_AUTONOMY) ? (float)$conf->global->SITES2_ELECTRIC_FLEET_AUTONOMY : 0;

// Préparer le tableau de données pour le JavaScript
echo '<script type="text/javascript">';
echo 'var sites = [';

$i = 0;
while ($obj = $db->fetch_object($resql)) {
    if ($i > 0) echo ',';
    
    // Déterminer si le site est dans la portée électrique en utilisant la distance réelle stockée
    $isInElectricRange = false;
    if ($electricFleetEnabled && $electricFleetAutonomy > 0 && !empty($obj->distance_km)) {
        // Vérifier si la distance réelle est inférieure ou égale à autonomie/2 (aller-retour)
        if ($obj->distance_km <= ($electricFleetAutonomy / 2)) {
            $isInElectricRange = true;
        }
    }
    
    // Préparer les infos du site pour le JavaScript
    $siteInfo = array(
        'id' => $obj->rowid,
        'ref' => $obj->ref,
        'label' => $obj->label,
        'address' => $obj->address . ', ' . $obj->zip . ' ' . $obj->town,
        'phone' => $obj->phone,
        'latitude' => $obj->latitude,
        'longitude' => $obj->longitude,
        'status' => $obj->status,
        'distance_km' => $obj->distance_km,
        'isInElectricRange' => $isInElectricRange
    );
    
    echo json_encode($siteInfo);
    $i++;
}

echo '];';

// Ajouter les données de l'agence de référence si configurée
if ($hasReferenceAgency) {
    echo 'var referenceAgency = {';
    echo '"name": "' . dol_escape_js($conf->global->SITES2_REFERENCE_AGENCY_NAME) . '",';
    echo '"latitude": ' . $conf->global->SITES2_REFERENCE_AGENCY_LATITUDE . ',';
    echo '"longitude": ' . $conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE;
    echo '};';
} else {
    echo 'var referenceAgency = null;';
}

// Script JavaScript pour initialiser la carte
echo '
function initMap() {
    // Initialiser la carte
    var map = L.map("map");
    
    // Ajouter le fond de carte';
    
if ($mapProvider == 'googlemaps' && !empty($googleMapsApiKey)) {
    // Utiliser Google Maps comme fond de carte avec Leaflet
    echo '
    L.tileLayer("https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}", {
        subdomains: ["mt0", "mt1", "mt2", "mt3"],
        attribution: "© Google Maps",
        minZoom: 1,
        maxZoom: 20
    }).addTo(map);';
} else {
    // Utiliser OpenStreetMap par défaut
    echo '
    L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
        attribution: \'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>\',
        minZoom: 1,
        maxZoom: 20
    }).addTo(map);';
}

echo '
    var bounds = new L.LatLngBounds();
    
    // Créer des icônes personnalisées pour les différents types de sites
    var blueIcon = new L.Icon({
        iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png",
        shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    var greenIcon = new L.Icon({
        iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png",
        shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Ajouter les marqueurs pour chaque site
    sites.forEach(function(site) {
        var popupContent = "<h3>" + site.label + "</h3>" +
            "<b>' . $langs->trans("Reference") . ':</b> " + site.ref + "<br>" +
            "<b>' . $langs->trans("Address") . ':</b> " + site.address + "<br>" +
            (site.phone ? "<b>' . $langs->trans("Phone") . ':</b> " + site.phone + "<br>" : "");
        
        // Ajouter l\'information sur la distance réelle si disponible
        if (site.distance_km) {
            popupContent += "<b>' . $langs->trans("Distance") . ':</b> " + site.distance_km + " km<br>";
        }
            
        // Ajouter l\'information sur la portée électrique si activée
        if (' . ($electricFleetEnabled ? 'true' : 'false') . ') {
            if (site.isInElectricRange) {
                popupContent += "<br><span style=\"color: green;\"><i class=\"fas fa-leaf\"></i> ' . dol_escape_js($langs->trans("ElectricRangeOK")) . '</span>";
            } else if (site.distance_km) {
                popupContent += "<br><span style=\"color: orange;\"><i class=\"fas fa-exclamation-triangle\"></i> ' . dol_escape_js($langs->trans("ElectricRangeKO")) . '</span>";
            }
        }
        
        popupContent += "<br><a href=\'' . dol_buildpath('/sites2/site_card.php', 1) . '?id=" + site.id + "\'>' . $langs->trans("ViewSite") . '</a>";
        
        // Choisir l\'icône selon la portée électrique
        var markerIcon = (site.isInElectricRange && ' . ($electricFleetEnabled ? 'true' : 'false') . ') ? greenIcon : blueIcon;
            
        var marker = L.marker([site.latitude, site.longitude], {icon: markerIcon})
            .addTo(map)
            .bindPopup(popupContent);
            
        bounds.extend([site.latitude, site.longitude]);
    });
    
    // Ajouter l\'agence de référence si configurée
    if (referenceAgency) {
        var refAgencyIcon = L.icon({
            iconUrl: "' . dol_buildpath('/sites2/img/ref_agency_marker.svg', 1) . '",
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        });
        
        var refAgencyMarker = L.marker([referenceAgency.latitude, referenceAgency.longitude], {
            icon: refAgencyIcon
        }).addTo(map);
        
        refAgencyMarker.bindPopup(referenceAgency.name + " (' . dol_escape_js($langs->trans("ReferenceAgency")) . ')");
        
        bounds.extend([referenceAgency.latitude, referenceAgency.longitude]);
    }
    
    // Ajuster la vue pour voir tous les marqueurs
    if (bounds.isValid()) {
        map.fitBounds(bounds, {
            padding: [50, 50]
        });
    } else {
        // Fallback au cas où il n\'y aurait pas de sites
        map.setView([46.2276, 2.2137], 6); // Centre de la France
    }
}

// Initialiser la carte au chargement de la page
window.onload = function() {
    initMap();
};
</script>';

// Ajouter des statistiques
print '<div class="fichecenter" style="margin-top: 20px;">';
print '<h3>' . $langs->trans("Statistics") . '</h3>';
print '<table class="border centpercent">';

// Nombre de sites avec coordonnées
print '<tr>';
print '<td width="30%">' . $langs->trans("SitesWithCoordinates") . '</td>';
print '<td>' . $num . '</td>';
print '</tr>';

// Statistique pour la flotte électrique si activée
if ($electricFleetEnabled && $electricFleetAutonomy > 0) {
    // Calculer le nombre de sites dans la portée électrique
    $sitesInElectricRange = 0;
    $totalSitesWithDistance = 0;
    
    // Requête pour récupérer tous les sites avec distance
    $sql_stats = "SELECT distance_km FROM ".MAIN_DB_PREFIX."sites2_site";
    $sql_stats.= " WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND latitude != '' AND longitude != ''";
    $sql_stats.= " AND distance_km IS NOT NULL AND distance_km != ''";
    
    $resql_stats = $db->query($sql_stats);
    if ($resql_stats) {
        while ($obj_stats = $db->fetch_object($resql_stats)) {
            $totalSitesWithDistance++;
            if ($obj_stats->distance_km <= ($electricFleetAutonomy / 2)) {
                $sitesInElectricRange++;
            }
        }
        $db->free($resql_stats);
    }
    
    print '<tr>';
    print '<td><span class="fas fa-leaf" style="color: green;"></span> ' . $langs->trans("SitesInElectricRange") . ' (' . $electricFleetAutonomy . 'km)</td>';
    print '<td>' . $sitesInElectricRange . ' / ' . $totalSitesWithDistance . '</td>';
    print '</tr>';
}

// Vérifier s'il y a des sites sans coordonnées
$sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."sites2_site";
$sql.= " WHERE latitude IS NULL OR longitude IS NULL OR latitude = '' OR longitude = ''";
$resql2 = $db->query($sql);
if ($resql2) {
    $obj = $db->fetch_object($resql2);
    if ($obj) {
        print '<tr>';
        print '<td>' . $langs->trans("SitesWithoutCoordinates") . '</td>';
        print '<td>';
        if ($obj->count > 0) {
            // Lien cliquable vers la liste des sites sans coordonnées
            print '<a href="' . dol_buildpath('/sites2/site_list.php', 1) . '?search_no_coordinates=1" class="classfortooltip" title="' . $langs->trans("ViewSitesWithoutCoordinates") . '">';
            print $obj->count;
            print '</a>';
        } else {
            print $obj->count;
        }
        print '</td>';
        print '</tr>';
    }
}

// Statut de l'agence de référence
print '<tr>';
print '<td>' . $langs->trans("ReferenceAgency") . '</td>';
print '<td>';
if ($hasReferenceAgency) {
    print $conf->global->SITES2_REFERENCE_AGENCY_NAME;
} else {
    print '<span class="warning">' . $langs->trans("NotConfigured") . '</span> ';
    print '<a href="' . dol_buildpath('/sites2/admin/reference_agency.php', 1) . '">' . $langs->trans("ConfigureHere") . '</a>';
}
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close(); 