<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024 Module Sites2
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
 *   	\file       site_equipement.php
 *		\ingroup    sites2
 *		\brief      Page des équipements liés à un site
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
// Solution de secours - chemin absolu courant
if (!$res && file_exists(dirname(dirname(__DIR__))."/main.inc.php")) {
	$res = @include dirname(dirname(__DIR__))."/main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Définir GETPOST s'il n'est pas disponible
if (!function_exists('GETPOST')) {
    function GETPOST($paramname, $check = 'alphanohtml', $method = 0, $filter = 0, $options = array())
    {
        if (empty($method)) $method = 1; // Request
        $val = null;
        
        // Récupération des valeurs GET, POST ou autres
        if ($method & 1) {
            if (isset($_GET[$paramname])) $val = $_GET[$paramname];
        }
        if ($method & 2) {
            if (isset($_POST[$paramname])) $val = $_POST[$paramname];
        }
        
        return $val;
    }
}

// Définir dol_buildpath si elle n'existe pas
if (!function_exists('dol_buildpath')) {
    function dol_buildpath($path, $type = 0)
    {
        // Si c'est un chemin absolu ou une URL, on retourne tel quel
        if (preg_match('/^(http|https):\/\//i', $path) || preg_match('/^[\\/\\\\]/', $path)) {
            return $path;
        }
        
        if ($type == 1) {
            // Pour type 1, on retourne un chemin relatif depuis la racine web
            return '/custom/sites2/' . $path;
        } else {
            // Pour type 0, on retourne un chemin système
            return __DIR__ . '/' . $path;
        }
    }
}

// Définir dol_include_once si elle n'existe pas
if (!function_exists('dol_include_once')) {
    function dol_include_once($path, $mode = 0) 
    {
        $fullpath = dol_buildpath($path, 0);
        if (file_exists($fullpath)) {
            include_once $fullpath;
            return true;
        }
        return false;
    }
}

dol_include_once('/sites2/class/site.class.php');
dol_include_once('/sites2/lib/sites2_site.lib.php');

// Inclure les classes du module équipement si elles existent
if (isModEnabled('equipement')) {
    dol_include_once('/equipement/class/camera.class.php');
    dol_include_once('/equipement/class/video.class.php');
    dol_include_once('/equipement/class/alarm.class.php');
    dol_include_once('/equipement/class/access.class.php');
}

// Load translation files required by the page
$langs->loadLangs(array("sites2@sites2", "other"));
if (isModEnabled('equipement')) {
    $langs->load("equipement@equipement");
}

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'siteequipement';
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object = new Site($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('siteequipement', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread = $user->rights->sites2->site->read;
if (isModEnabled('equipement')) {
    $permissiontocreate = $user->rights->equipement->camera->write;
}

// Security check - Protection if external user
if (!$permissiontoread) accessforbidden();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

/*
 * View
 */

$title = $langs->trans("Site") . ' - ' . $langs->trans("Equipements");
$help_url = '';
llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
	$head = sitePrepareHead($object);
	print dol_get_fiche_head($head, 'equipement', $langs->trans("Site"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/sites2/site_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref client
	$morehtmlref .= $form->editfieldkey("RefClient", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefClient", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	if (is_object($object->thirdparty)) {
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'customer');
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	if (!isModEnabled('equipement')) {
		print '<div class="warning">'.$langs->trans("ModuleEquipementNotEnabled").'</div>';
	} else {
		// Afficher les enregistreurs vidéo liés à ce site
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		
		print '<tr class="liste_titre">';
		print '<td colspan="5">';
		print '<i class="fa fa-video-camera"></i> '.$langs->trans("VideoRecorders");
		
		// Bouton d'ajout
		if ($permissiontocreate) {
			print '<a class="editfielda paddingleft" href="'.dol_buildpath('/equipement/video_card.php', 1).'?action=create&fk_site='.$object->id.'">';
			print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddVideoRecorder").'"></span>';
			print '</a>';
		}
		
		print '</td></tr>';
		
		// Requête pour récupérer les enregistreurs vidéo liés à ce site
		$sql = "SELECT v.rowid, v.ref, v.server_ip, v.status";
		$sql .= " FROM ".MAIN_DB_PREFIX."equipement_video as v";
		$sql .= " WHERE v.fk_site = ".$object->id;
		$sql .= " ORDER BY v.ref ASC";
		
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			
			if ($num > 0) {
				print '<tr class="liste_titre">';
				print '<th>'.$langs->trans("Ref").'</th>';
				print '<th>'.$langs->trans("ServerIP").'</th>';
				print '<th class="center">'.$langs->trans("Status").'</th>';
				print '</tr>';
				
				if (class_exists('Video')) {
					$video = new Video($db);
				}
				
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					
					print '<tr class="oddeven">';
					
					// Référence
					print '<td>';
					if (isset($video)) {
						$video->id = $obj->rowid;
						$video->ref = $obj->ref;
						print $video->getNomUrl(1);
					} else {
						print $obj->ref;
					}
					print '</td>';
					
					// Adresse IP du serveur
					print '<td>'.$obj->server_ip.'</td>';
					
					// Statut
					print '<td class="center">';
					if (isset($video)) {
						print $video->LibStatut($obj->status, 5);
					} else {
						print $obj->status;
					}
					print '</td>';
					
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans("NoEquipementFound").'</td></tr>';
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
		
		// Lien vers la liste complète des enregistreurs vidéo
		if ($num > 0) {
			print '<tr><td colspan="5" class="right">';
			print '<a href="'.dol_buildpath('/equipement/video_list.php', 1).'">';
			print '<i class="fa fa-list"></i> '.$langs->trans("SeeAllVideoRecorders");
			print '</a>';
			print '</td></tr>';
		}
		
		print '</table>';
		print '</div>';
		
		print '<br>';
		
		// Afficher les alarmes liées à ce site
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		
		print '<tr class="liste_titre">';
		print '<td colspan="5">';
		print '<i class="fa fa-bell"></i> '.$langs->trans("Alarms");
		
		// Bouton d'ajout
		if ($permissiontocreate) {
			print '<a class="editfielda paddingleft" href="'.dol_buildpath('/equipement/alarm_card.php', 1).'?action=create&fk_site='.$object->id.'">';
			print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddAlarm").'"></span>';
			print '</a>';
		}
		
		print '</td></tr>';
		
		// Requête pour récupérer les alarmes liées à ce site
		$sql = "SELECT a.rowid, a.ref, a.status, a.model";
		$sql .= " FROM ".MAIN_DB_PREFIX."equipement_alarm as a";
		$sql .= " WHERE a.fk_site = ".$object->id;
		$sql .= " ORDER BY a.ref ASC";
		
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			
			if ($num > 0) {
				print '<tr class="liste_titre">';
				print '<th>'.$langs->trans("Ref").'</th>';
				print '<th>'.$langs->trans("Model").'</th>';
				print '<th class="center">'.$langs->trans("Status").'</th>';
				print '</tr>';
				
				if (class_exists('Alarm')) {
					$alarm = new Alarm($db);
				}
				
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					
					print '<tr class="oddeven">';
					
					// Référence
					print '<td>';
					if (isset($alarm)) {
						$alarm->id = $obj->rowid;
						$alarm->ref = $obj->ref;
						print $alarm->getNomUrl(1);
					} else {
						print $obj->ref;
					}
					print '</td>';
					
					// Modèle
					print '<td>'.$obj->model.'</td>';
					
					// Statut
					print '<td class="center">';
					if (isset($alarm)) {
						print $alarm->LibStatut($obj->status, 5);
					} else {
						print $obj->status;
					}
					print '</td>';
					
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans("NoEquipementFound").'</td></tr>';
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
		
		// Lien vers la liste complète des alarmes
		if ($num > 0) {
			print '<tr><td colspan="5" class="right">';
			print '<a href="'.dol_buildpath('/equipement/alarm_list.php', 1).'">';
			print '<i class="fa fa-list"></i> '.$langs->trans("SeeAllAlarms");
			print '</a>';
			print '</td></tr>';
		}
		
		print '</table>';
		print '</div>';

		print '<br>';

		// Afficher les contrôles d'accès liés à ce site
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<td colspan="5">';
		print '<i class="fa fa-key"></i> '.$langs->trans("AccessControls");

		// Bouton d'ajout
		if ($permissiontocreate) {
			print '<a class="editfielda paddingleft" href="'.dol_buildpath('/equipement/access_card.php', 1).'?action=create&fk_site='.$object->id.'">';
			print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddAccessControl").'"></span>';
			print '</a>';
		}

		print '</td></tr>';

		// Requête pour récupérer les contrôles d'accès liés à ce site
		$sql = "SELECT a.rowid, a.ref, a.status";
		$sql .= " FROM ".MAIN_DB_PREFIX."equipement_access as a";
		$sql .= " WHERE a.fk_site = ".$object->id;
		$sql .= " ORDER BY a.ref ASC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			
			if ($num > 0) {
				print '<tr class="liste_titre">';
				print '<th>'.$langs->trans("Ref").'</th>';
				print '<th class="center">'.$langs->trans("Status").'</th>';
				print '</tr>';
				
				if (class_exists('Access')) {
					$access = new Access($db);
				}
				
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					
					print '<tr class="oddeven">';
					
					// Référence
					print '<td>';
					if (isset($access)) {
						$access->id = $obj->rowid;
						$access->ref = $obj->ref;
						print $access->getNomUrl(1);
					} else {
						print $obj->ref;
					}
					print '</td>';
					
					// Statut
					print '<td class="center">';
					if (isset($access)) {
						print $access->LibStatut($obj->status, 5);
					} else {
						print $obj->status;
					}
					print '</td>';
					
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans("NoEquipementFound").'</td></tr>';
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		// Lien vers la liste complète des contrôles d'accès
		if ($num > 0) {
			print '<tr><td colspan="5" class="right">';
			print '<a href="'.dol_buildpath('/equipement/access_list.php', 1).'">';
			print '<i class="fa fa-list"></i> '.$langs->trans("SeeAllAccessControls");
			print '</a>';
			print '</td></tr>';
		}

		print '</table>';
		print '</div>';

		print '<br>';

		// Afficher la section réseau si le module réseau est activé
		if (!empty($conf->global->ENABLE_NETWORK)) {
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="3">';
			print '<i class="fa fa-network-wired"></i> '.$langs->trans("Network");
			print '</td></tr>';

			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("NetworkConfiguration").'</td>';
			print '<td class="center">';
			print '<a href="'.dol_buildpath('/equipement/network_card.php', 1).'?site_id='.$object->id.'" class="button">';
			print '<i class="fa fa-eye"></i> '.$langs->trans("ViewNetworkCard");
			print '</a>';
			print '</td>';
			print '</tr>';

			print '</table>';
			print '</div>';
		}
	}
	
	print '</div>'; // End fichecenter
	
	print dol_get_fiche_end();
}

// Ajouter les traductions pour cette page
$langs->load("equipement@equipement");
if (empty($langs->tab_translate["VideoRecorders"])) $langs->tab_translate["VideoRecorders"] = "Vidéosurveillance";
if (empty($langs->tab_translate["Alarms"])) $langs->tab_translate["Alarms"] = "Alarmes";
if (empty($langs->tab_translate["AccessControls"])) $langs->tab_translate["AccessControls"] = "Contrôle d'accès";
if (empty($langs->tab_translate["NoEquipementFound"])) $langs->tab_translate["NoEquipementFound"] = "Aucun équipement trouvé";
if (empty($langs->tab_translate["SeeAllVideoRecorders"])) $langs->tab_translate["SeeAllVideoRecorders"] = "Voir toute la vidéosurveillance";
if (empty($langs->tab_translate["SeeAllAlarms"])) $langs->tab_translate["SeeAllAlarms"] = "Voir toutes les alarmes";
if (empty($langs->tab_translate["SeeAllAccessControls"])) $langs->tab_translate["SeeAllAccessControls"] = "Voir tous les contrôles d'accès";
if (empty($langs->tab_translate["ModuleEquipementNotEnabled"])) $langs->tab_translate["ModuleEquipementNotEnabled"] = "Le module Équipement n'est pas activé";
if (empty($langs->tab_translate["Equipements"])) $langs->tab_translate["Equipements"] = "Équipements";
if (empty($langs->tab_translate["AddVideoRecorder"])) $langs->tab_translate["AddVideoRecorder"] = "Ajouter un système de vidéosurveillance";
if (empty($langs->tab_translate["AddAlarm"])) $langs->tab_translate["AddAlarm"] = "Ajouter une alarme";
if (empty($langs->tab_translate["AddAccessControl"])) $langs->tab_translate["AddAccessControl"] = "Ajouter un contrôle d'accès";
if (empty($langs->tab_translate["Network"])) $langs->tab_translate["Network"] = "Réseau";
if (empty($langs->tab_translate["NetworkConfiguration"])) $langs->tab_translate["NetworkConfiguration"] = "Configuration réseau";
if (empty($langs->tab_translate["ViewNetworkCard"])) $langs->tab_translate["ViewNetworkCard"] = "Voir la fiche réseau";

// End of page
llxFooter();
$db->close(); 