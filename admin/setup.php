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
 * \file    sites2/admin/setup.php
 * \ingroup sites2
 * \brief   Configuration page for Sites2 module
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

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/security.lib.php";
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
	'SITES2_MYPARAM1'=>array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'string'),
	'SITES2_MYPARAM2'=>array('css'=>'minwidth500', 'enabled'=>1, 'type'=>'string'),
    'SITES2_MAP_PROVIDER' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'select', 
        'options'=>array(
            'openstreetmap'=>'OpenStreetMap', 
            'googlemaps'=>'Google Maps'
        )
    ),
    'SITES2_GOOGLE_MAPS_API_KEY' => array('css'=>'minwidth300', 'enabled'=>1, 'type'=>'string'),
    'SITES2_OPENSTREETMAP_API_KEY' => array('css'=>'minwidth300', 'enabled'=>1, 'type'=>'string'),
    'SITES2_EXTERNAL_MAP_APP' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'select', 
        'options'=>array(
            'default' => $langs->trans("DefaultDeviceApp"),
            'googlemaps' => 'Google Maps',
            'openstreetmap' => 'OpenStreetMap',
            'waze' => 'Waze',
            'apple_maps' => 'Apple Plans',
            'bing_maps' => 'Bing Maps'
        )
    ),
    'SITES2_DISTANCE_CALCULATION_MODE' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'select', 
        'options' => array(
            'route' => 'DistanceCalculationRoute',
            'haversine' => 'DistanceCalculationHaversine'
        )
    ),
    'SITES2_ELECTRIC_FLEET_ENABLED' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'checkbox'),
    'SITES2_ELECTRIC_FLEET_AUTONOMY' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'string'),
    'SITES2_OPENWEATHERMAP_API_KEY' => array('css'=>'minwidth300', 'enabled'=>1, 'type'=>'string'),
    'SITES2_WEATHER_ENABLED' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'checkbox'),
    'SITES2_WEATHER_API_TYPE' => array('css'=>'minwidth200', 'enabled'=>1, 'type'=>'select', 
        'options'=>array(
            'free' => 'WeatherAPIFree',
            'paid' => 'WeatherAPIPaid'
        )
    )
);

/*
 * Actions
 */
if ((float) DOL_VERSION >= 6) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

// Gérer l'action update pour sauvegarder les paramètres
if ($action == 'update') {
    // Vérification du token CSRF (protection contre les attaques CSRF)
    $token = GETPOST('token', 'alpha');
    // Utiliser la fonction appropriée selon la version de Dolibarr
    if (function_exists('checkToken')) {
        $tokenValid = checkToken($token);
    } elseif (function_exists('dol_check_token')) {
        $tokenValid = dol_check_token($token);
    } else {
        // Fallback : vérification basique du token
        $tokenValid = (!empty($token) && isset($_SESSION['newtoken']) && $token === $_SESSION['newtoken']);
    }
    
    if (empty($token) || !$tokenValid) {
        setEventMessages($langs->trans("ErrorToken"), null, 'errors');
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    
    // Initialiser le compteur d'erreurs
    $error = 0;
    
    // Liste des paramètres à gérer
    $params_to_save = array(
        'SITES2_MAP_PROVIDER',
        'SITES2_GOOGLE_MAPS_API_KEY',
        'SITES2_OPENSTREETMAP_API_KEY',
        'SITES2_EXTERNAL_MAP_APP',
        'SITES2_DISTANCE_CALCULATION_MODE',
        'SITES2_ELECTRIC_FLEET_ENABLED',
        'SITES2_ELECTRIC_FLEET_AUTONOMY',
        'SITES2_OPENWEATHERMAP_API_KEY',
        'SITES2_WEATHER_ENABLED',
        'SITES2_WEATHER_API_TYPE'
    );

    // Enregistrer chaque paramètre
    foreach ($params_to_save as $param) {
        $value = GETPOST($param, 'alpha');
        if ($param == 'SITES2_GOOGLE_MAPS_API_KEY' || $param == 'SITES2_OPENSTREETMAP_API_KEY' || $param == 'SITES2_OPENWEATHERMAP_API_KEY') {
            // Sauvegarde des clés API avec validation
            if (!empty($value)) {
                // Valider le format de la clé API (alphanumérique, tirets, underscores)
                $value = trim($value);
                if (preg_match('/^[a-zA-Z0-9_-]+$/', $value) && strlen($value) >= 10) {
                    $res = dolibarr_set_const($db, $param, $value, 'chaine', 0, '', $conf->entity);
                } else {
                    setEventMessages($langs->trans("ErrorInvalidAPIKeyFormat"), null, 'errors');
                    $error++;
                }
            }
        } elseif ($param == 'SITES2_ELECTRIC_FLEET_ENABLED' || $param == 'SITES2_WEATHER_ENABLED') {
            // Sauvegarde du checkbox (0 ou 1)
            $res = dolibarr_set_const($db, $param, $value ? '1' : '0', 'chaine', 0, '', $conf->entity);
        } elseif ($param == 'SITES2_ELECTRIC_FLEET_AUTONOMY') {
            // Sauvegarde de l'autonomie (nombre)
            if (!empty($value) && is_numeric($value)) {
                $res = dolibarr_set_const($db, $param, $value, 'chaine', 0, '', $conf->entity);
            }
        } else {
            // Sauvegarde des autres paramètres
            $res = dolibarr_set_const($db, $param, $value, 'chaine', 0, '', $conf->entity);
        }
        
        if (!$res > 0) {
            $error++;
        }
    }

    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
    
    header("Location: ".$_SERVER["PHP_SELF"]);
    exit;
}

/*
 * View
 */

$page_name = "Sites2Setup";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans("Module641444Name"), -1, "sites2@sites2");

// Setup page content
print '<div class="setupcontent">';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td><span class="fas fa-map-marked-alt"></span> '.$langs->trans("MapConfiguration").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Choix du fournisseur de cartes
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_MAP_PROVIDER");
print '<br><small>' . $langs->trans("SITES2_MAP_PROVIDERTooltip") . '</small>';
print '</td><td>';
$map_provider_options = array(
    'openstreetmap' => 'OpenStreetMap',
    'googlemaps' => 'Google Maps'
);
$current_map_provider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
print $form->selectarray('SITES2_MAP_PROVIDER', $map_provider_options, $current_map_provider);
print '</td></tr>';

// Clé API Google Maps
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_GOOGLE_MAPS_API_KEY");
print '<br><small>' . $langs->trans("SITES2_GOOGLE_MAPS_API_KEYTooltip") . '</small>';
print '</td><td>';
print '<input type="text" name="SITES2_GOOGLE_MAPS_API_KEY" value="' . getDolGlobalString('SITES2_GOOGLE_MAPS_API_KEY') . '" class="flat minwidth300" >';
print '</td></tr>';

// Clé API OpenStreetMap
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_OPENSTREETMAP_API_KEY");
print '<br><small>' . $langs->trans("SITES2_OPENSTREETMAP_API_KEYTooltip") . '</small>';
print '</td><td>';
print '<input type="text" name="SITES2_OPENSTREETMAP_API_KEY" value="' . getDolGlobalString('SITES2_OPENSTREETMAP_API_KEY') . '" class="flat minwidth300" >';
print '</td></tr>';

// Nouvelle option: Application externe à ouvrir
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_EXTERNAL_MAP_APP");
print '<br><small>' . $langs->trans("SITES2_EXTERNAL_MAP_APPTooltip") . '</small>';
print '</td><td>';
$external_map_options = array(
    'default' => $langs->trans("DefaultDeviceApp"),
    'googlemaps' => 'Google Maps',
    'openstreetmap' => 'OpenStreetMap',
    'waze' => 'Waze',
    'apple_maps' => 'Apple Plans',
    'bing_maps' => 'Bing Maps'
);
$current_external_map = !empty($conf->global->SITES2_EXTERNAL_MAP_APP) ? $conf->global->SITES2_EXTERNAL_MAP_APP : 'default';
print $form->selectarray('SITES2_EXTERNAL_MAP_APP', $external_map_options, $current_external_map);
print '</td></tr>';

// Ajout du nouveau paramètre pour le mode de calcul des distances
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_DISTANCE_CALCULATION_MODE");
print '<br><small>' . $langs->trans("SITES2_DISTANCE_CALCULATION_MODETooltip") . '</small>';
print '</td><td>';
$distance_calculation_options = array(
    'route' => 'DistanceCalculationRoute',
    'haversine' => 'DistanceCalculationHaversine'
);
$current_distance_calculation = !empty($conf->global->SITES2_DISTANCE_CALCULATION_MODE) ? $conf->global->SITES2_DISTANCE_CALCULATION_MODE : 'route';
print $form->selectarray('SITES2_DISTANCE_CALCULATION_MODE', $distance_calculation_options, $current_distance_calculation);
print '</td></tr>';

print '</table>';

// Nouvelle section pour la météo
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td><span class="fas fa-cloud-sun"></span> '.$langs->trans("WeatherConfiguration").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Activation de l'affichage météo
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_WEATHER_ENABLED");
print '<br><small>' . $langs->trans("SITES2_WEATHER_ENABLEDTooltip") . '</small>';
print '</td><td>';
print '<input type="checkbox" name="SITES2_WEATHER_ENABLED" value="1" ' . (!empty($conf->global->SITES2_WEATHER_ENABLED) ? 'checked' : '') . '>';
print '</td></tr>';

// Clé API OpenWeatherMap
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_OPENWEATHERMAP_API_KEY");
print '<br><small>' . $langs->trans("SITES2_OPENWEATHERMAP_API_KEYTooltip") . '</small>';
print '</td><td>';
print '<input type="text" name="SITES2_OPENWEATHERMAP_API_KEY" value="' . getDolGlobalString('SITES2_OPENWEATHERMAP_API_KEY') . '" class="flat minwidth300" placeholder="Votre clé API OpenWeatherMap" >';
print '<br><small><a href="https://openweathermap.org/api" target="_blank">' . $langs->trans("GetOpenWeatherMapAPIKey") . '</a></small>';
print '</td></tr>';

// Type d'API OpenWeatherMap (gratuite ou payante)
print '<tr class="oddeven"><td>';
print $langs->trans("SITES2_WEATHER_API_TYPE");
print '<br><small>' . $langs->trans("SITES2_WEATHER_API_TYPETooltip") . '</small>';
print '</td><td>';
$weather_api_type_options = array(
    'free' => $langs->trans("WeatherAPIFree"),
    'paid' => $langs->trans("WeatherAPIPaid")
);
$current_weather_api_type = !empty($conf->global->SITES2_WEATHER_API_TYPE) ? $conf->global->SITES2_WEATHER_API_TYPE : 'free';
print $form->selectarray('SITES2_WEATHER_API_TYPE', $weather_api_type_options, $current_weather_api_type);
print '<br><small>' . $langs->trans("WeatherAPITypeHelp") . '</small>';
print '</td></tr>';

print '</table>';

// Nouvelle section pour la flotte électrique
print '<br>';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td><span class="fas fa-leaf"></span> '.$langs->trans("ElectricFleetConfiguration").'</td>'."\n";
print '<td>'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Vérifier si l'agence de référence est configurée
$hasReferenceAgency = !empty($conf->global->SITES2_USE_REFERENCE_AGENCY) && 
    !empty($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) && 
    !empty($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE);

if (!$hasReferenceAgency) {
    print '<tr class="oddeven"><td colspan="2">';
    print '<div class="warning">';
    print '<span class="fas fa-exclamation-triangle"></span> ';
    print $langs->trans("ElectricFleetRequiresReferenceAgency");
    print ' <a href="' . dol_buildpath('/sites2/admin/reference_agency.php', 1) . '">' . $langs->trans("ConfigureReferenceAgency") . '</a>';
    print '</div>';
    print '</td></tr>';
} else {
    // Activation de la flotte électrique
    print '<tr class="oddeven"><td>';
    print $langs->trans("SITES2_ELECTRIC_FLEET_ENABLED");
    print '<br><small>' . $langs->trans("SITES2_ELECTRIC_FLEET_ENABLEDTooltip") . '</small>';
    print '</td><td>';
    print '<input type="checkbox" name="SITES2_ELECTRIC_FLEET_ENABLED" value="1" ' . (!empty($conf->global->SITES2_ELECTRIC_FLEET_ENABLED) ? 'checked' : '') . '>';
    print '</td></tr>';

    // Autonomie moyenne de la flotte électrique
    print '<tr class="oddeven"><td>';
    print $langs->trans("SITES2_ELECTRIC_FLEET_AUTONOMY");
    print '<br><small>' . $langs->trans("SITES2_ELECTRIC_FLEET_AUTONOMYTooltip") . '</small>';
    print '</td><td>';
    print '<input type="text" name="SITES2_ELECTRIC_FLEET_AUTONOMY" value="' . getDolGlobalString('SITES2_ELECTRIC_FLEET_AUTONOMY') . '" class="flat minwidth200" placeholder="300" >';
    print '</td></tr>';
}

print '</table>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';
print '</div>';

// Documentation sur les applications cartographiques
print '<br>';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2"><span class="fas fa-info-circle"></span> ' . $langs->trans("ExternalMapAppsInfo") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td colspan="2">' . $langs->trans("ExternalMapAppsInfoDesc") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td width="25%"><strong>Default</strong></td>';
print '<td>' . $langs->trans("DefaultAppExplanation") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>Google Maps</strong></td>';
print '<td>' . $langs->trans("GoogleMapsExplanation") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>OpenStreetMap</strong></td>';
print '<td>' . $langs->trans("OpenStreetMapExplanation") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>Waze</strong></td>';
print '<td>' . $langs->trans("WazeExplanation") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>Apple Plans</strong></td>';
print '<td>' . $langs->trans("AppleMapsExplanation") . '</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td><strong>Bing Maps</strong></td>';
print '<td>' . $langs->trans("BingMapsExplanation") . '</td>';
print '</tr>';
print '</table>';
print '</div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close(); 