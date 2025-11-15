<?php
/* Copyright (C) 2023-2024 D.A.R.Y.L.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    sites2/admin/reference_agency.php
 * \ingroup sites2
 * \brief   Configuration page for reference agency in Sites2 module
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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user, $db, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
dol_include_once('/sites2/lib/sites2.lib.php');

// Translations
$langs->loadLangs(array("admin", "sites2@sites2"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
    'SITES2_USE_REFERENCE_AGENCY' => array('css'=>'', 'enabled'=>1, 'type'=>'boolean'),
    'SITES2_REFERENCE_AGENCY_NAME' => array('css'=>'minwidth300', 'enabled'=>1, 'type'=>'string'),
    'SITES2_REFERENCE_AGENCY_ADDRESS' => array('css'=>'minwidth500', 'enabled'=>1, 'type'=>'string'),
    'SITES2_REFERENCE_AGENCY_ZIP' => array('css'=>'minwidth100', 'enabled'=>1, 'type'=>'string'),
    'SITES2_REFERENCE_AGENCY_TOWN' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'string'),
    'SITES2_REFERENCE_AGENCY_COUNTRY' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'string'),
    'SITES2_REFERENCE_AGENCY_LATITUDE' => array('css'=>'minwidth150', 'enabled'=>1, 'type'=>'string'),
    'SITES2_REFERENCE_AGENCY_LONGITUDE' => array('css'=>'minwidth150', 'enabled'=>1, 'type'=>'string')
);

/*
 * Actions
 */

if ($action == 'update') {
    foreach ($arrayofparameters as $key => $val) {
        if ($val['type'] == 'boolean') {
            // Cas particulier pour les cases à cocher
            $value = GETPOST($key, 'alpha') ? 1 : 0;
        } else {
            $value = GETPOST($key, 'alpha');
        }
        dolibarr_set_const($db, $key, $value, 'chaine', 0, '', $conf->entity);
    }
    
    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

if ($action == 'geocode') {
    // Récupérer l'adresse complète depuis la configuration actuelle
    $address = GETPOST('SITES2_REFERENCE_AGENCY_ADDRESS', 'alpha');
    $zip = GETPOST('SITES2_REFERENCE_AGENCY_ZIP', 'alpha');
    $town = GETPOST('SITES2_REFERENCE_AGENCY_TOWN', 'alpha');
    $country = GETPOST('SITES2_REFERENCE_AGENCY_COUNTRY', 'alpha');
    
    // Si les valeurs ne sont pas dans le POST, on essaie de les prendre directement de la configuration
    if (empty($address)) $address = $conf->global->SITES2_REFERENCE_AGENCY_ADDRESS;
    if (empty($zip)) $zip = $conf->global->SITES2_REFERENCE_AGENCY_ZIP;
    if (empty($town)) $town = $conf->global->SITES2_REFERENCE_AGENCY_TOWN;
    if (empty($country)) $country = $conf->global->SITES2_REFERENCE_AGENCY_COUNTRY;
    
    if (!empty($address) && !empty($zip) && !empty($town)) {
        $address_string = urlencode($address . ' ' . $zip . ' ' . $town . ' ' . $country);
        $provider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
        
        if ($provider == 'googlemaps' && !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY)) {
            // Utilisation de l'API Google Maps Geocoding
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address_string}&key=".$conf->global->SITES2_GOOGLE_MAPS_API_KEY;
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Dolibarr PHP Application\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                // Erreur lors de la connexion à l'API
                setEventMessages($langs->trans("ReferenceAgencyGeocodeErrorConnection", 'Google Maps'), null, 'errors');
            } else {
                $data = json_decode($response);

                if (!empty($data) && $data->status == 'OK' && !empty($data->results[0])) {
                    $latitude = $data->results[0]->geometry->location->lat;
                    $longitude = $data->results[0]->geometry->location->lng;
                    
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_LATITUDE', $latitude, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_LONGITUDE', $longitude, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_ADDRESS', $address, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_ZIP', $zip, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_TOWN', $town, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_COUNTRY', $country, 'chaine', 0, '', $conf->entity);
                    
                    setEventMessages($langs->trans("ReferenceAgencyGeocoded"), null, 'mesgs');
                } else {
                    $error_msg = $langs->trans("ReferenceAgencyGeocodeError");
                    if (!empty($data) && !empty($data->status) && $data->status != 'OK') {
                        $error_msg .= ' (Status: ' . $data->status . ')';
                        if (!empty($data->error_message)) {
                            $error_msg .= ' - ' . $data->error_message;
                        }
                    }
                    setEventMessages($error_msg, null, 'errors');
                }
            }
        } else {
            // Utilisation de l'API OpenStreetMap (Nominatim)
            $url = "https://nominatim.openstreetmap.org/search?q={$address_string}&format=jsonv2";
            
            // Ajouter la clé API si configurée
            if (!empty($conf->global->SITES2_OPENSTREETMAP_API_KEY)) {
                $url .= "&key=".$conf->global->SITES2_OPENSTREETMAP_API_KEY;
            }
            
            // Create a stream context to add User-Agent to the HTTP request
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Dolibarr PHP Application\r\n"
                ]
            ]);

            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                // Erreur lors de la connexion à l'API
                setEventMessages($langs->trans("ReferenceAgencyGeocodeErrorConnection", 'OpenStreetMap'), null, 'errors');
            } else {
                $data = json_decode($response);

                if (!empty($data) && count($data) > 0) {
                    $latitude = $data[0]->lat;
                    $longitude = $data[0]->lon;
                    
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_LATITUDE', $latitude, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_LONGITUDE', $longitude, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_ADDRESS', $address, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_ZIP', $zip, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_TOWN', $town, 'chaine', 0, '', $conf->entity);
                    dolibarr_set_const($db, 'SITES2_REFERENCE_AGENCY_COUNTRY', $country, 'chaine', 0, '', $conf->entity);
                    
                    setEventMessages($langs->trans("ReferenceAgencyGeocoded"), null, 'mesgs');
                } else {
                    // Pas de résultats trouvés
                    setEventMessages($langs->trans("ReferenceAgencyGeocodeErrorNoResults", $address, $zip, $town, $country), null, 'errors');
                }
            }
        }
    } else {
        // Message d'erreur plus informatif indiquant quels champs sont manquants
        $missing_fields = array();
        if (empty($address)) $missing_fields[] = $langs->trans("Address");
        if (empty($zip)) $missing_fields[] = $langs->trans("Zip");
        if (empty($town)) $missing_fields[] = $langs->trans("Town");
        
        if (!empty($missing_fields)) {
            setEventMessages($langs->trans("ReferenceAgencyGeocodeErrorMissingFields") . ' : ' . implode(', ', $missing_fields), null, 'errors');
        } else {
            setEventMessages($langs->trans("ReferenceAgencyGeocodeError"), null, 'errors');
        }
    }
    
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

// Nouvelle action pour recalculer toutes les distances
if ($action == 'recalculate_all_distances') {
    dol_include_once('/sites2/class/site.class.php');
    
    $site = new Site($db);
    $sites = $site->fetchAll();
    
    if (is_array($sites) && count($sites) > 0) {
        $count_updated = 0;
        $count_errors = 0;
        
        foreach ($sites as $site_obj) {
            // Recalculer la distance seulement pour les sites avec des coordonnées valides
            if (!empty($site_obj->latitude) && !empty($site_obj->longitude)) {
                // Recalcul de la distance
                $result = $site_obj->calculateDistanceFromHQ();
                
                if ($result) {
                    // Mise à jour en base
                    $update_result = $site_obj->update($user);
                    if ($update_result > 0) {
                        $count_updated++;
                    } else {
                        $count_errors++;
                    }
                } else {
                    $count_errors++;
                }
            }
        }
        
        if ($count_updated > 0) {
            setEventMessages($langs->trans("DistancesRecalculatedSuccessfully", $count_updated), null, 'mesgs');
        }
        
        if ($count_errors > 0) {
            setEventMessages($langs->trans("ErrorsRecalculatingDistances", $count_errors), null, 'warnings');
        }
    } else {
        setEventMessages($langs->trans("NoSitesToUpdate"), null, 'warnings');
    }
    
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

/*
 * View
 */

$page_name = "ReferenceAgencySetup";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'reference_agency', $langs->trans("Module641444Name"), -1, "sites2@sites2");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("ReferenceAgencyDescription").'</span><br><br>';

echo '<div class="info">';
echo '<p><strong>À quoi sert cette configuration ?</strong></p>';
echo '<p>Cette page vous permet de définir une agence de référence (adresse principale) qui sera utilisée comme point de départ pour calculer les distances et les temps de trajet vers tous vos sites.</p>';
echo '<p>Une fois configurée et activée, les fiches des sites afficheront automatiquement la distance et le temps de trajet estimé depuis cette agence de référence.</p>';
echo '<p>Si vous ne configurez pas d\'agence de référence ou si vous désactivez cette option, le système utilisera l\'adresse du siège social de chaque tiers associé au site comme point de départ pour les calculs.</p>';
echo '</div><br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="reference_agency_form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// Affichage des champs
foreach ($arrayofparameters as $key => $val) {
    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
    print '</td><td>';
    
    if ($val['type'] == 'boolean') {
        $value = isset($conf->global->$key) ? $conf->global->$key : 0;
        print $form->selectyesno($key, $value, 1);
    } else {
        $value = isset($conf->global->$key) ? $conf->global->$key : '';
        print '<input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.$value.'">';
    }
    
    print '</td></tr>';
}

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button buttonforaok" name="save" value="'.$langs->trans("Save").'">';
print '</div>';
print '</form>';

// Ajout du bouton pour recalculer toutes les distances
print '<br>';
print '<hr>';
print '<h3>' . $langs->trans("DistanceCalculation") . '</h3>';
print '<p>' . $langs->trans("RecalculateAllDistancesDescription") . '</p>';

print '<div class="center">';
print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?action=recalculate_all_distances&token=' . newToken() . '">';
print $langs->trans("RecalculateAllDistances");
print '</a>';
print '</div>';

// Geocoding form
print '<br>';
print '<hr>';
print '<h3>' . $langs->trans("GeocodeReferenceAgency") . '</h3>';
print '<p>' . $langs->trans("GeocodeReferenceAgencyDescription") . '</p>';

// Plutôt que d'avoir un formulaire séparé avec des champs cachés qui sont mis à jour par JavaScript,
// nous utilisons un bouton qui soumet directement le formulaire principal avec une action différente
print '<div class="center">';
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display:inline-block;">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="geocode">';

// Nous ajoutons un message d'information pour indiquer que les valeurs des champs seront utilisées
print '<div class="info" style="max-width:600px;margin:0 auto 15px auto;">';
print '<i class="fas fa-info-circle"></i> ' . $langs->trans("GeocodeUsingCurrentValues") . ' ';
print '</div>';

print '<input type="submit" class="button buttonforaok" name="geocode" value="'.$langs->trans("GeocodeReferenceAgency").'">';
print '</form>';
print '</div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close(); 