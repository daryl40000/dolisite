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
 *   	\file       site_card.php
 *		\ingroup    sites2
 *		\brief      Page to create/edit/view site
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
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

// Définir setEventMessages si elle n'existe pas
if (!function_exists('setEventMessages')) {
    function setEventMessages($mesg, $megs_array = array(), $style = 'mesgs')
    {
        if (empty($mesg)) return;
        
        if ($style == 'errors') {
            echo '<div class="error">' . $mesg . '</div>';
        } else {
            echo '<div class="info">' . $mesg . '</div>';
        }
    }
}

// dol_buildpath est déjà défini par Dolibarr, pas besoin de redéfinir

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
dol_include_once('/sites2/lib/sites2.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("sites2@sites2", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sitecard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Site($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->sites2->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('sitecard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$permissiontoread = $user->rights->sites2->site->read;
$permissiontoadd = $user->rights->sites2->site->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->sites2->site->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->sites2->site->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->sites2->site->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->sites2->multidir_output[isset($object->entity) ? $object->entity : 1].'/site';

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/sites2/site_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = dol_buildpath('/sites2/site_card.php', 1).'?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	$triggermodname = 'SITES2_SITE_MODIFY'; // Name of trigger action code to execute when we modify record

	// S'assurer que le calcul des distances est fait avant l'update
	if ($action == 'update' && !empty($id)) {
		$object->fetch($id);
		if (!empty($object->latitude) && !empty($object->longitude)) {
			// Forcer le calcul des distances
			dol_syslog("Calcul automatique des distances lors de l'update du site ID=$id", LOG_INFO);
			$result_calc = $object->calculateDistanceFromHQ();
			
			if (!$result_calc) {
				dol_syslog("Erreur lors du calcul automatique des distances pour le site ID=$id", LOG_WARNING);
				// Si le calcul échoue, on continue malgré tout avec l'update
			} else {
				dol_syslog("Calcul des distances réussi: ".$object->distance_km." km, ".$object->travel_time, LOG_DEBUG);
			}
		} else {
			dol_syslog("Pas de coordonnées disponibles pour le calcul des distances du site ID=$id", LOG_DEBUG);
		}
	}

	// Gestion du message après suppression réussie via $_GET
	if (GETPOST('delsiteok', 'alpha')) {
		setEventMessages($langs->trans("SiteDeleted"), null, 'mesgs');
	}

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	// Intercepter l'action de suppression pour ajouter notre redirection personnalisée
	if ($action == 'confirm_delete' && $confirm == 'yes') {
		if ($permissiontodelete) {
			$result = $object->delete($user);
			if ($result > 0) {
				// Rediriger vers la liste avec un message de succès
				header("Location: ".dol_buildpath('/sites2/site_list.php', 1)."?delsiteok=1");
				exit;
			} else {
				// En cas d'erreur, afficher un message d'erreur
				$error_msg = $langs->trans("ErrorDeletingSite");
				if (!empty($object->error)) {
					$error_msg .= ": " . $object->error;
				}
				
				// Si l'erreur contient 'extrafields', proposer une solution
				if (strpos($object->error, 'extrafields') !== false) {
					$error_msg .= '<br>' . $langs->trans("MissingExtrafieldsTable");
					$error_msg .= '<br>' . $langs->trans("ContactModuleAdmin");
				}
				
				setEventMessages($error_msg, $object->errors, 'errors');
				$action = '';
			}
		} else {
			// Pas de permission pour supprimer
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
			$action = '';
		}
	} else {
		// Inclure le fichier standard si ce n'est pas une action de suppression
		include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';
		
	}

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'SITES2_SITE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SITE_TO';
	$trackid = 'site'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

// Placer le traitement de l'action 'calculate_distances' AVANT la partie "View"
if ($action == 'calculate_distances' && !empty($id) && $permissiontoadd) {
	// Journalisation détaillée
	dol_syslog("Début de l'action calculate_distances pour le site ID=$id", LOG_INFO);
	
	// Charger l'objet
	$result_fetch = $object->fetch($id);
	dol_syslog("Chargement de l'objet: ".($result_fetch > 0 ? "Succès" : "Échec"), LOG_DEBUG);
	
	if ($result_fetch <= 0) {
		setEventMessages("Erreur lors du chargement du site", null, 'errors');
		$action = '';
	} else {
		// Vérifier que le site a des coordonnées
		if (empty($object->latitude) || empty($object->longitude)) {
			setEventMessages($langs->trans("NoCoordinatesAvailable"), null, 'errors');
			dol_syslog("Pas de coordonnées disponibles pour le site ID=$id", LOG_WARNING);
			$action = '';
		} else {
			// Calculer la distance et le temps de trajet
			$result_calc = $object->calculateDistanceFromHQ();
			
			if ($result_calc) {
				// Mettre à jour la base de données
				$result_update = $object->update($user);
				if ($result_update > 0) {
					setEventMessages($langs->trans("DistanceAndTravelTimeUpdated"), null);
					dol_syslog("Distances et temps de trajet mis à jour avec succès pour le site ID=$id", LOG_INFO);
				} else {
					setEventMessages($langs->trans("Error").' '.$object->error, null, 'errors');
					dol_syslog("Erreur lors de la mise à jour du site ID=$id: ".$object->error, LOG_ERR);
				}
			} else {
				setEventMessages($langs->trans("CalculationError"), null, 'errors');
				dol_syslog("Erreur lors du calcul de distance pour le site ID=$id", LOG_ERR);
			}
		}
	}
	
	// Réinitialiser l'action pour afficher la vue normale
	$action = '';
}

// Gestion de l'action 'update_position' pour mettre à jour les coordonnées du site
if ($action == 'update_position' && !empty($id) && $permissiontoadd) {
	dol_syslog("Début de l'action update_position pour le site ID=$id", LOG_INFO);
	
	// Récupérer les nouvelles coordonnées
	$new_latitude = GETPOST('new_latitude', 'alpha');
	$new_longitude = GETPOST('new_longitude', 'alpha');
	
	// Vérifier que les coordonnées sont valides
	if (empty($new_latitude) || empty($new_longitude)) {
		setEventMessages($langs->trans("NoCoordinatesProvided"), null, 'errors');
		dol_syslog("Erreur: Coordonnées non fournies", LOG_WARNING);
		$action = '';
	} else {
		// Charger l'objet
		$result_fetch = $object->fetch($id);
		
		if ($result_fetch <= 0) {
			setEventMessages($langs->trans("ErrorFetchingSite"), null, 'errors');
			dol_syslog("Erreur lors du chargement du site ID=$id", LOG_WARNING);
			$action = '';
		} else {
			// Mettre à jour les coordonnées
			$object->latitude = $new_latitude;
			$object->longitude = $new_longitude;
			
			// Recalculer les distances
			$result_calc = $object->calculateDistanceFromHQ();
			
			// Mettre à jour en base de données
			$result_update = $object->update($user);
			
			if ($result_update > 0) {
				setEventMessages($langs->trans("PositionUpdatedSuccessfully"), null, 'mesgs');
				dol_syslog("Position du site ID=$id mise à jour: lat=$new_latitude, lng=$new_longitude", LOG_INFO);
			} else {
				setEventMessages($langs->trans("Error").' '.$object->error, null, 'errors');
				dol_syslog("Erreur lors de la mise à jour du site ID=$id: ".$object->error, LOG_ERR);
			}
		}
	}
	
	// Réinitialiser l'action pour afficher la vue normale
	$action = '';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Site");
$help_url = '';
llxHeader('', $title, $help_url);


// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Site")), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print '&nbsp; ';
	print '<input type="'.($backtopage ? "submit" : "button").'" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage ? '' : ' onclick="javascript:history.go(-1)"').'>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Site"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Masquer les champs distance_km et travel_time qui sont calculés automatiquement
	print '<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", function() {
			// Masquer les champs distance_km et travel_time dans le formulaire d\'édition
			var distanceField = document.querySelector("tr[data-field=\'distance_km\']");
			var travelTimeField = document.querySelector("tr[data-field=\'travel_time\']");
			
			if (distanceField) distanceField.style.display = "none";
			if (travelTimeField) travelTimeField.style.display = "none";
			
			// Ajouter un message explicatif
			var noteElem = document.createElement("tr");
			noteElem.innerHTML = "<td colspan=\'2\'><div class=\'info\'>" + 
				"<span class=\'fa fa-info-circle\'></span> " + 
				"' . $langs->trans("DistanceAndTravelTimeCalculatedAutomatically") . '" + 
				"</div></td>";
			
			// Insérer après le champ longitude s\'il existe
			var longitudeRow = document.querySelector("tr[data-field=\'longitude\']");
			if (longitudeRow) {
				longitudeRow.parentNode.insertBefore(noteElem, longitudeRow.nextSibling);
			}
		});
	</script>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = sitePrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("Workstation"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteSite'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/sites2/site_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	
	$morehtmlref = '<div class="refidno">';
	if (!empty($object->address) && !empty($object->zip) && !empty($object->town)) {
		$morehtmlref .= '<span class="fa fa-map-marker-alt"></span> ' . htmlspecialchars($object->address . ", " . $object->zip . ", " . $object->town);
	}
	if (!empty($object->phone)) {
		$morehtmlref .= '<br/><span class="fa fa-phone"></span> ' . htmlspecialchars($object->phone);
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'label', $morehtmlref);

	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
	$soc = new Societe($db);
	$soc->fetch($object->fk_soc);

	print '<div class="fichecenter">';
	print '    <div class="fichehalfleft">';
	print '        <h3>Informations générales</h3>';
	print '        <div class="underbanner clearboth"></div>';
	print '        <table class="border centpercent tableforfield">'."\n";
	print '            <tr><td>' . $langs->trans("Description") . '</td><td><div style="white-space: pre-wrap;">' . htmlspecialchars($object->description) . '</div></td></tr>';
	print '            <tr><td>' . $langs->trans("ThirdParty") . '</td><td>' . ($object->fk_soc ? $soc->getNomUrl(1) : '') . '</td></tr>';
	
	// Affichage de la distance et du temps de trajet
	// Toujours utiliser l'agence de référence comme point de calcul
	$distanceLabel = $langs->trans("DistanceFromReferenceAgency");
	if (!empty($object->distance_km)) {
		// Ajouter une indication sur le type de calcul utilisé
		$distanceType = '';
		if (!empty($conf->global->SITES2_DISTANCE_CALCULATION_MODE) && $conf->global->SITES2_DISTANCE_CALCULATION_MODE === 'haversine') {
			$distanceType = ' <span class="badge badge-info" title="' . $langs->trans("DistanceCalculationHaversine") . '"><i class="fas fa-crow"></i> ' . $langs->trans("StraightLineDistance") . '</span>';
		} else {
			$distanceType = ' <span class="badge badge-success" title="' . $langs->trans("DistanceCalculationRoute") . '"><i class="fas fa-road"></i> ' . $langs->trans("RoadDistance") . '</span>';
		}
		
		// Ajouter un bouton pour recalculer la distance
		$recalculateButton = ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=calculate_distances&token='.newToken().'" class="buttonaction paddingleftimp"><i class="fas fa-sync"></i> ' . $langs->trans("RecalculateDistance") . '</a>';
		
		print '            <tr><td>' . $distanceLabel . '</td><td>' . price($object->distance_km, 0, '', 1, 0) . ' km' . $distanceType . $recalculateButton . '</td></tr>';
	} else {
		// Si latitude/longitude existent mais distance non calculée
		if (!empty($object->latitude) && !empty($object->longitude)) {
			print '            <tr><td>' . $distanceLabel . '</td><td><span class="opacitymedium">' . $langs->trans("NotCalculated") . '</span> - <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">' . $langs->trans("UpdateData") . '</a></td></tr>';
		} else {
			print '            <tr><td>' . $distanceLabel . '</td><td><span class="opacitymedium">' . $langs->trans("NotAvailable") . '</span> - <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">' . $langs->trans("AddAddressCoordinates") . '</a></td></tr>';
		}
	}
	
	$travelTimeLabel = $langs->trans("TravelTimeFromReferenceAgency");
	if (!empty($object->travel_time)) {
		print '            <tr><td>' . $travelTimeLabel . '</td><td>' . $object->travel_time . '</td></tr>';
	} else {
		// Si latitude/longitude existent mais temps non calculé
		if (!empty($object->latitude) && !empty($object->longitude)) {
			print '            <tr><td>' . $travelTimeLabel . '</td><td><span class="opacitymedium">' . $langs->trans("NotCalculated") . '</span> - <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">' . $langs->trans("UpdateData") . '</a></td></tr>';
		} else {
			print '            <tr><td>' . $travelTimeLabel . '</td><td><span class="opacitymedium">' . $langs->trans("NotAvailable") . '</span> - <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">' . $langs->trans("AddAddressCoordinates") . '</a></td></tr>';
		}
	}
	
	print '        </table>';
	
	// Ajout du tableau récapitulatif des contacts sous les informations générales (à gauche)
	if (!empty($object->fk_soc)) {
		print '<h3>' . $langs->trans("ContactSummary") . '</h3>';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent noborder tableforfield">';
		
		// Récupérer les contacts liés à ce site avec leurs numéros de téléphone
		$sql = "SELECT ec.rowid, ec.fk_socpeople, ec.fk_c_type_contact, ";
		$sql .= " tc.libelle as type_contact, ";
		$sql .= " sp.rowid as contactid, sp.lastname, sp.firstname, sp.phone_mobile ";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON tc.rowid = ec.fk_c_type_contact";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.rowid = ec.fk_socpeople";
		$sql .= " WHERE ec.element_id = ".$object->id;
		$sql .= " AND tc.element = 'site'";
		$sql .= " ORDER BY sp.lastname, sp.firstname";
		
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num > 0) {
				print '<tr class="liste_titre">';
				print '<td>' . $langs->trans("Name") . '</td>';
				print '<td>' . $langs->trans("ContactType") . '</td>';
				print '<td>' . $langs->trans("Mobile") . '</td>';
				print '</tr>';
				
				$contactstatic = new Contact($db);
				$i = 0;
				while ($i < $num && $i < 5) { // Limiter à 5 contacts pour garder le tableau compact
					$obj = $db->fetch_object($resql);
					
					print '<tr class="oddeven">';
					
					// Nom du contact
					print '<td>';
					$contactstatic->id = $obj->contactid;
					$contactstatic->lastname = $obj->lastname;
					$contactstatic->firstname = $obj->firstname;
					print $contactstatic->getNomUrl(1);
					print '</td>';
					
					// Type de contact
					print '<td>' . $obj->type_contact . '</td>';
					
					// Téléphone mobile - cliquable sur mobile
					print '<td>';
					if (!empty($obj->phone_mobile)) {
						print '<a href="tel:' . $obj->phone_mobile . '" class="tel-link">';
						print $obj->phone_mobile;
						print '</a>';
					} else {
						print '<span class="opacitymedium">-</span>';
					}
					print '</td>';
					
					print '</tr>';
					$i++;
				}
				
				if ($num > 5) {
					print '<tr><td colspan="3" class="center">';
					print '<a href="' . dol_buildpath('/sites2/site_contact.php', 1) . '?id=' . $object->id . '">';
					print '<em>' . $langs->trans("SeeAllContacts") . ' (' . $num . ')</em>';
					print '</a>';
					print '</td></tr>';
				}
			} else {
				print '<tr><td colspan="3" class="opacitymedium center">' . $langs->trans("NoContacts") . '</td></tr>';
			}
			
			// Lien vers la page des contacts
			print '<tr><td colspan="3" class="center" style="padding-top: 10px;">';
			print '<a href="' . dol_buildpath('/sites2/site_contact.php', 1) . '?id=' . $object->id . '">';
			print '<i class="fa fa-users"></i> ' . $langs->trans("ManageContacts");
			print '</a>';
			print '</td></tr>';
			
			$db->free($resql);
		} else {
			print '<tr><td class="opacitymedium">' . $langs->trans("ErrorFetchingContacts") . '</td></tr>';
		}
		
		print '</table>';

		// Ajout du CSS pour les liens téléphoniques
		print '<style>
			.tel-link {
				text-decoration: none;
				color: #428bca;
			}
			@media (min-width: 768px) {
				.tel-link {
					cursor: default;
					color: inherit;
					pointer-events: none;
				}
			}
			@media (max-width: 767px) {
				.tel-link {
					text-decoration: underline;
				}
			}
		</style>';
	}
	
	// Ajout de la section des chantiers programmés
	print '<h3>' . $langs->trans("ScheduledWorkSites") . '</h3>';
	print '<div class="underbanner clearboth"></div>';
	
	// Récupérer les chantiers programmés pour ce site
	$sql = "SELECT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public,";
	$sql .= " p.ref as propal_ref, p.fk_statut as propal_status";
	$sql .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
	$sql .= " WHERE c.fk_site = ".((int)$object->id);
	$sql .= " ORDER BY c.date_debut ASC, c.date_creation DESC";
	
	// Initialiser les variables météo avant la boucle
	$weatherEnabled = !empty($conf->global->SITES2_WEATHER_ENABLED);
	$weatherData = null;
	
	// Récupérer les données météo si activé (pour afficher la météo des jours de chantier et les jours favorables)
	// On les récupère avant la boucle pour pouvoir les réutiliser
	if ($weatherEnabled && !empty($conf->global->SITES2_OPENWEATHERMAP_API_KEY) && !empty($object->latitude) && !empty($object->longitude)) {
		$weatherData = sites2GetWeatherData($object->latitude, $object->longitude, $conf->global->SITES2_OPENWEATHERMAP_API_KEY);
	}
	
	$resql_chantier = $db->query($sql);
	if ($resql_chantier && $db->num_rows($resql_chantier) > 0) {
		
		$num_chantiers = $db->num_rows($resql_chantier);
		$i_chantier = 0;
		
		while ($i_chantier < $num_chantiers) {
			$obj_chantier = $db->fetch_object($resql_chantier);
			
			print '<div style="margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px;">';
			
			// Devis
			print '<div style="margin-bottom: 8px;">';
			print '<strong><i class="fa fa-file-invoice"></i> ' . $langs->trans("Proposal") . ' : </strong>';
			if (!empty($obj_chantier->propal_ref)) {
				$propal_url = DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj_chantier->fk_propal;
				print '<a href="'.$propal_url.'" target="_blank">'.$obj_chantier->propal_ref.'</a>';
				if ($obj_chantier->propal_status == 2) {
					print ' <span class="badge badge-status4">'.$langs->trans("Signed").'</span>';
				} elseif ($obj_chantier->propal_status == 1) {
					print ' <span class="badge badge-status1">'.$langs->trans("Validated").'</span>';
				}
			} else {
				print '<span class="opacitymedium">-</span>';
			}
			print '</div>';
			
			// Dates
			print '<div style="margin-bottom: 8px;">';
			print '<strong><i class="fa fa-calendar-alt"></i> ' . $langs->trans("Dates") . ' : </strong>';
			if (!empty($obj_chantier->date_debut)) {
				$date_debut_formatted = dol_print_date($db->jdate($obj_chantier->date_debut), 'day');
				print $date_debut_formatted;
				
				if (!empty($obj_chantier->date_fin)) {
					$date_fin_formatted = dol_print_date($db->jdate($obj_chantier->date_fin), 'day');
					if ($obj_chantier->date_debut != $obj_chantier->date_fin) {
						print ' - ' . $date_fin_formatted;
						
						// Calculer le nombre de jours
						$date_debut_ts = $db->jdate($obj_chantier->date_debut);
						$date_fin_ts = $db->jdate($obj_chantier->date_fin);
						$diff = $date_fin_ts - $date_debut_ts;
						$days = floor($diff / 86400) + 1;
						print ' <span class="opacitymedium">(' . $days . ' ' . $langs->trans("Days") . ')</span>';
					}
				} else {
					print ' <span class="opacitymedium">(' . $langs->trans("OneDay") . ')</span>';
				}
			} else {
				print '<span class="opacitymedium">' . $langs->trans("DateNotDecided") . '</span>';
			}
			print '</div>';
			
			// Type de localisation
			$location_type = isset($obj_chantier->location_type) ? (int)$obj_chantier->location_type : 1; // Par défaut extérieur
			print '<div style="margin-bottom: 8px;">';
			print '<strong><i class="fa fa-map-marker-alt"></i> ' . $langs->trans("WorkLocationType") . ' : </strong>';
			if ($location_type == 0) {
				print '<span class="badge badge-status0"><i class="fa fa-home"></i> ' . $langs->trans("Interior") . '</span>';
			} else {
				print '<span class="badge badge-status1"><i class="fa fa-sun"></i> ' . $langs->trans("Exterior") . '</span>';
			}
			print '</div>';
			
			// Météo pour les jours de chantier (uniquement si extérieur)
			if ($location_type == 1 && $weatherEnabled && !empty($conf->global->SITES2_OPENWEATHERMAP_API_KEY) && !empty($object->latitude) && !empty($object->longitude)) {
				// Si une date est définie, afficher la météo pour cette date
				if (!empty($obj_chantier->date_debut)) {
					if (!empty($weatherData) && !empty($weatherData['forecast'])) {
						print '<div style="margin-top: 10px;">';
						print '<strong><i class="fas fa-cloud-sun"></i> ' . $langs->trans("WeatherForWorkDays") . ' : </strong>';
						print '<div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">';
						
						// Déterminer les dates à afficher
						$date_debut_ts = $db->jdate($obj_chantier->date_debut);
						$date_fin_ts = !empty($obj_chantier->date_fin) ? $db->jdate($obj_chantier->date_fin) : $date_debut_ts;
						
						// Convertir les timestamps en dates au format Y-m-d pour la comparaison
						$date_debut_str = date('Y-m-d', $date_debut_ts);
						$date_fin_str = date('Y-m-d', $date_fin_ts);
						
						// Parcourir les prévisions météo et afficher celles qui correspondent aux dates de chantier
						foreach ($weatherData['forecast'] as $day) {
							$forecast_date = $day['date']; // Format Y-m-d
							
							// Vérifier si cette date est dans la plage du chantier
							if ($forecast_date >= $date_debut_str && $forecast_date <= $date_fin_str) {
								// Afficher la météo pour ce jour
								$forecast_timestamp = strtotime($forecast_date);
								$date_label = dol_print_date($forecast_timestamp, 'day');
								if ($forecast_date == date('Y-m-d')) {
									$date_label = $langs->trans("Today");
								} elseif ($forecast_date == date('Y-m-d', strtotime('+1 day'))) {
									$date_label = $langs->trans("Tomorrow");
								}
								
								print '<div style="flex: 0 0 auto; min-width: 80px; padding: 5px; background-color: white; border-radius: 3px; text-align: center; border: 1px solid #ccc;">';
								print '<div style="font-weight: bold; font-size: 0.75em; margin-bottom: 3px;">' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '</div>';
								print '<div style="margin-bottom: 3px;">';
								
								// Valider et nettoyer l'icône avant affichage
								$icon = isset($day['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $day['icon']) : '';
								$description = isset($day['description']) ? htmlspecialchars($day['description'], ENT_QUOTES, 'UTF-8') : '';
								
								if (!empty($icon)) {
									print '<img src="https://openweathermap.org/img/wn/' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '.png" alt="' . $description . '" style="width: 35px; height: 35px;">';
								}
								print '</div>';
								
								if (!empty($description)) {
									print '<div style="font-size: 0.7em; color: #666; margin-bottom: 2px; line-height: 1.2;">' . $description . '</div>';
								}
								
								// Température
								$temp = isset($day['temp']) ? intval($day['temp']) : 0;
								print '<div style="font-weight: bold; font-size: 0.9em;">' . htmlspecialchars((string)$temp, ENT_QUOTES, 'UTF-8') . '°C</div>';
								
								if (isset($day['temp_min']) && isset($day['temp_max'])) {
									$tempMin = intval($day['temp_min']);
									$tempMax = intval($day['temp_max']);
									print '<div style="font-size: 0.65em; color: #999;">' . htmlspecialchars((string)$tempMin, ENT_QUOTES, 'UTF-8') . '° / ' . htmlspecialchars((string)$tempMax, ENT_QUOTES, 'UTF-8') . '°</div>';
								}
								
								print '</div>';
							}
						}
						
						print '</div>';
						print '</div>';
					} else {
						print '<div style="margin-top: 5px; font-size: 0.9em; color: #999;">';
						print '<i class="fas fa-info-circle"></i> ' . $langs->trans("WeatherDataNotAvailable");
						print '</div>';
					}
				} else {
					// Pas de date définie : afficher les jours favorables (15 jours avec Open-Meteo, 8 jours max avec API payante OpenWeatherMap, 5 jours avec API gratuite)
					// Réutiliser les données météo déjà récupérées si disponibles, sinon faire un nouvel appel
					// Pour les chantiers, on recherche sur 15 jours si Open-Meteo est utilisé
					$weatherProvider = !empty($conf->global->SITES2_WEATHER_PROVIDER) ? $conf->global->SITES2_WEATHER_PROVIDER : 'openweathermap';
					$forecastDaysForChantiers = ($weatherProvider === 'openmeteo') ? 15 : 8;
					
					if (!empty($weatherData) && !empty($weatherData['forecast'])) {
						// Utiliser les données déjà récupérées pour éviter les incohérences
						// Si Open-Meteo et que les données ne couvrent que 8 jours, refaire un appel pour 15 jours
						if ($weatherProvider === 'openmeteo' && count($weatherData['forecast']) <= 8) {
							$favorableDays = sites2GetFavorableWeatherDays($object->latitude, $object->longitude, $conf->global->SITES2_OPENWEATHERMAP_API_KEY, null, $weatherProvider, $forecastDaysForChantiers);
						} else {
							$favorableDays = sites2GetFavorableWeatherDaysFromData($weatherData['forecast']);
						}
					} else {
						// Faire un nouvel appel API si les données ne sont pas disponibles
						$favorableDays = sites2GetFavorableWeatherDays($object->latitude, $object->longitude, $conf->global->SITES2_OPENWEATHERMAP_API_KEY, null, $weatherProvider, $forecastDaysForChantiers);
					}
					
					if (!empty($favorableDays) && count($favorableDays) > 0) {
						print '<div style="margin-top: 10px;">';
						// Afficher le nombre de jours dynamiquement
						$daysText = ($weatherProvider === 'openmeteo' && $forecastDaysForChantiers == 15) ? '15' : '8';
						// Remplacer le nombre de jours dans la traduction si nécessaire
						$favorableText = $langs->trans("FavorableWeatherDays");
						$favorableText = preg_replace('/\d+\s+prochains\s+jours/', $daysText . ' prochains jours', $favorableText);
						print '<strong><i class="fas fa-sun"></i> ' . $favorableText . ' : </strong>';
						print '<div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px;">';
						
						foreach ($favorableDays as $day) {
							$forecast_date = $day['date'];
							$forecast_timestamp = strtotime($forecast_date);
							$date_label = dol_print_date($forecast_timestamp, 'day');
							if ($forecast_date == date('Y-m-d')) {
								$date_label = $langs->trans("Today");
							} elseif ($forecast_date == date('Y-m-d', strtotime('+1 day'))) {
								$date_label = $langs->trans("Tomorrow");
							}
							
							// Déterminer la couleur selon si c'est de la bruine légère
							$isLightDrizzle = isset($day['is_light_drizzle']) && $day['is_light_drizzle'];
							$bgColor = $isLightDrizzle ? '#fff9c4' : '#e8f5e9'; // Jaune clair pour bruine légère, vert clair pour favorable
							$borderColor = $isLightDrizzle ? '#f9a825' : '#4caf50'; // Jaune/orange pour bruine légère, vert pour favorable
							
							print '<div style="flex: 0 0 auto; min-width: 80px; padding: 5px; background-color: ' . $bgColor . '; border-radius: 3px; text-align: center; border: 1px solid ' . $borderColor . ';">';
							print '<div style="font-weight: bold; font-size: 0.75em; margin-bottom: 3px;">' . htmlspecialchars($date_label, ENT_QUOTES, 'UTF-8') . '</div>';
							print '<div style="margin-bottom: 3px;">';
							
							// Valider et nettoyer l'icône avant affichage
							$icon = isset($day['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $day['icon']) : '';
							$description = isset($day['description']) ? htmlspecialchars($day['description'], ENT_QUOTES, 'UTF-8') : '';
							
							if (!empty($icon)) {
								print '<img src="https://openweathermap.org/img/wn/' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '.png" alt="' . $description . '" style="width: 35px; height: 35px;">';
							}
							print '</div>';
							
							if (!empty($description)) {
								print '<div style="font-size: 0.7em; color: #666; margin-bottom: 2px; line-height: 1.2;">' . $description . '</div>';
							}
							
							// Température
							$temp = isset($day['temp']) ? intval($day['temp']) : 0;
							print '<div style="font-weight: bold; font-size: 0.9em;">' . htmlspecialchars((string)$temp, ENT_QUOTES, 'UTF-8') . '°C</div>';
							
							if (isset($day['temp_min']) && isset($day['temp_max'])) {
								$tempMin = intval($day['temp_min']);
								$tempMax = intval($day['temp_max']);
								print '<div style="font-size: 0.65em; color: #999;">' . htmlspecialchars((string)$tempMin, ENT_QUOTES, 'UTF-8') . '° / ' . htmlspecialchars((string)$tempMax, ENT_QUOTES, 'UTF-8') . '°</div>';
							}
							
							print '</div>';
						}
						
						print '</div>';
						print '</div>';
					} else {
						print '<div style="margin-top: 5px; font-size: 0.9em; color: #999;">';
						// Afficher le nombre de jours dynamiquement
						$daysText = ($weatherProvider === 'openmeteo' && $forecastDaysForChantiers == 15) ? '15' : '8';
						// Remplacer le nombre de jours dans la traduction
						$noFavorableText = $langs->trans("NoFavorableWeatherDays");
						$noFavorableText = preg_replace('/\d+/', $daysText, $noFavorableText);
						print '<i class="fas fa-info-circle"></i> ' . $noFavorableText;
						print '</div>';
					}
				}
			} elseif ($location_type == 0) {
				print '<div style="margin-top: 5px; font-size: 0.9em; color: #999;">';
				print '<i class="fas fa-info-circle"></i> ' . $langs->trans("WeatherNotRelevantForInteriorWork");
				print '</div>';
			}
			
			// Note publique si elle existe
			if (!empty($obj_chantier->note_public)) {
				print '<div style="margin-top: 8px; padding: 5px; background-color: #fff; border-left: 3px solid #428bca; font-size: 0.9em;">';
				print '<strong>' . $langs->trans("Note") . ' : </strong>' . nl2br(htmlspecialchars($obj_chantier->note_public));
				print '</div>';
			}
			
			print '</div>';
			
			$i_chantier++;
		}
		
		// Lien vers l'onglet chantier
		print '<div style="text-align: center; margin-top: 10px;">';
		print '<a href="' . dol_buildpath('/sites2/site_chantier.php', 1) . '?id=' . $object->id . '" class="button">';
		print '<i class="fa fa-calendar-alt"></i> ' . $langs->trans("ManageScheduledWorkSites");
		print '</a>';
		print '</div>';
		
		$db->free($resql_chantier);
	} else {
		print '<div class="opacitymedium center" style="padding: 10px;">';
		print $langs->trans("NoScheduledWorkSite");
		print '<br><a href="' . dol_buildpath('/sites2/site_chantier.php', 1) . '?id=' . $object->id . '">';
		print '<i class="fa fa-plus-circle"></i> ' . $langs->trans("AddScheduledWorkSite");
		print '</a>';
		print '</div>';
	}
	
	print '    </div>';

	// Récupérer les données météo si activé (avant l'affichage de la carte) - pour la carte
	// Si les données météo n'ont pas été récupérées pour les chantiers, les récupérer maintenant
	if (!isset($weatherData) && $weatherEnabled && !empty($conf->global->SITES2_OPENWEATHERMAP_API_KEY) && !empty($object->latitude) && !empty($object->longitude)) {
		$weatherData = sites2GetWeatherData($object->latitude, $object->longitude, $conf->global->SITES2_OPENWEATHERMAP_API_KEY);
	}

	if (!empty($object->latitude) && !empty($object->longitude)) {
		print '<div class="fichehalfright">';
		print '    <h3>Carte';
		// Afficher l'icône météo si activée
		if ($weatherEnabled && !empty($conf->global->SITES2_OPENWEATHERMAP_API_KEY)) {
			print ' <span class="fas fa-cloud-sun" title="' . $langs->trans("WeatherEnabled") . '"></span>';
		}
		print '</h3>';
		print '    <div class="underbanner clearboth"></div>';
		
		// Afficher la carte en premier
		print '    <table class="border centpercent tableforfield">'."\n";
		print '        <div style="height:300px"; id="map"></div>';
		print '    </table>';
		
		// Afficher le panneau météo en dessous si activé et données disponibles
		if ($weatherEnabled && !empty($weatherData) && !empty($weatherData['forecast'])) {
			print '<div style="margin-top: 10px; padding: 8px; background-color: #f0f8ff; border: 1px solid #ddd; border-radius: 5px;">';
			print '<h4 style="margin: 0 0 8px 0; font-size: 0.95em;"><i class="fas fa-cloud-sun"></i> ' . $langs->trans("WeatherForecast") . '</h4>';
			print '<div style="display: flex; flex-wrap: wrap; gap: 5px;">';
			
			// Déterminer le nombre de jours à afficher selon le type d'API
			$apiType = !empty($conf->global->SITES2_WEATHER_API_TYPE) ? $conf->global->SITES2_WEATHER_API_TYPE : 'free';
			$daysToDisplay = ($apiType == 'paid') ? 8 : 6; // 8 jours pour API payante, 6 jours pour API gratuite
			
			// Afficher les jours selon le type d'API
			$daysToShow = array_slice($weatherData['forecast'], 0, $daysToDisplay);
			foreach ($daysToShow as $day) {
				print '<div style="flex: 1; min-width: 70px; padding: 5px; background-color: white; border-radius: 3px; text-align: center; border: 1px solid #ccc;">';
				print '<div style="font-weight: bold; font-size: 0.75em; margin-bottom: 3px;">' . htmlspecialchars($day['date_label'], ENT_QUOTES, 'UTF-8') . '</div>';
				print '<div style="margin-bottom: 3px;">';
				// Valider et nettoyer l'icône avant affichage
				$icon = isset($day['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $day['icon']) : '';
				$description = isset($day['description']) ? htmlspecialchars($day['description'], ENT_QUOTES, 'UTF-8') : '';
				print '<img src="https://openweathermap.org/img/wn/' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '.png" alt="' . $description . '" style="width: 35px; height: 35px;">';
				print '</div>';
				print '<div style="font-size: 0.7em; color: #666; margin-bottom: 2px; line-height: 1.2;">' . $description . '</div>';
				// Valider les températures avant affichage
				$temp = isset($day['temp']) ? intval($day['temp']) : 0;
				print '<div style="font-weight: bold; font-size: 0.9em;">' . htmlspecialchars((string)$temp, ENT_QUOTES, 'UTF-8') . '°C</div>';
				if (isset($day['temp_min']) && isset($day['temp_max'])) {
					$tempMin = intval($day['temp_min']);
					$tempMax = intval($day['temp_max']);
					print '<div style="font-size: 0.65em; color: #999;">' . htmlspecialchars((string)$tempMin, ENT_QUOTES, 'UTF-8') . '° / ' . htmlspecialchars((string)$tempMax, ENT_QUOTES, 'UTF-8') . '°</div>';
				}
				print '</div>';
			}
			print '</div>';
			print '</div>';
		}
		
		print '</div>';
	}
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Variable pour savoir si on est en mode d'ajustement de position
	$isPositionEditMode = ($action == 'adjust_position');

	// Afficher la carte uniquement si des coordonnées sont disponibles
	if (!(empty($object->latitude)) && !(empty($object->longitude))) {
		// Déterminer le fournisseur de cartes
		$mapProvider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
		$googleMapsApiKey = !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY) ? $conf->global->SITES2_GOOGLE_MAPS_API_KEY : '';
		
		// Charger les scripts et styles Leaflet
		print '<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>';
		print '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin="" />';

		$address = htmlspecialchars($object->address . ' ' . $object->zip . ' ' . $object->town);
		if (empty(str_replace(' ', '', $address))) {
			$address = 'Non renseignée';
		}
		
		// Si mode d'ajustement de position, afficher des instructions et le formulaire
		if ($isPositionEditMode) {
			print '<div class="titre">' . $langs->trans("AdjustSitePosition") . '</div>';
			print '<div class="info">';
			print '<p><strong>' . $langs->trans("PositionAdjustmentInstructions") . '</strong></p>';
			print '<p>' . $langs->trans("DragMarkerToAdjustPosition") . '</p>';
			print '</div>';
			
			print '<div id="current-coordinates" style="margin: 10px 0; font-weight: bold;"></div>';
		}
		
		echo '
		<script type="text/javascript">
			var lat = ' . $object->latitude . ';' . '
			var lon = '  . $object->longitude . ';' . '
			var ref = "' . $object->label . '";' . '
			var address = "' . $address . '";' . '
			var macarte = null;
			var marker = null;
			var isPositionEditMode = ' . ($isPositionEditMode ? 'true' : 'false') . ';
			
			function initMap() {
				macarte = L.map("map").setView([lat, lon], 15);';
				
		if ($mapProvider == 'googlemaps' && !empty($googleMapsApiKey)) {
			// Utiliser Google Maps comme fond de carte avec Leaflet
			echo '
				// Utilisation de Google Maps comme fond de carte
				L.tileLayer("https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}", {
					subdomains: ["mt0", "mt1", "mt2", "mt3"],
					attribution: "© Google Maps",
					minZoom: 1,
					maxZoom: 20
				}).addTo(macarte);';
		} else {
			// Utiliser OpenStreetMap par défaut
			echo '
				// Utilisation d\'OpenStreetMap comme fond de carte
				L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
					attribution: \'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>\',
					minZoom: 1,
					maxZoom: 20
				}).addTo(macarte);';
		}
		
		echo '
				// Déterminer l\'URL pour ouvrir la carte dans le navigateur
				let mapsUrl = "";';
		
		if ($mapProvider == 'googlemaps') {
			echo '
				mapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lon}`;';
		} else {
			echo '
				mapsUrl = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}&zoom=15`;';
		}
		
		echo '
				// Créer le contenu de la popup
				let popupContent = `<h3>${ref}</h3><b>Adresse: </b>${address}<br><a href="${mapsUrl}" target="_blank">Ouvrir dans le navigateur</a>`;
				
				// Créer le marqueur
				if (isPositionEditMode) {
					// En mode ajustement de position, créer un marqueur déplaçable avec une icône personnalisée
					var draggableIcon = L.icon({
						iconUrl: "' . dol_buildpath('/sites2/img/draggable_marker.svg', 1) . '",
						iconSize: [25, 41],
						iconAnchor: [12, 41],
						popupAnchor: [1, -34]
					});
					
					// Créer un marqueur déplaçable
					marker = L.marker([lat, lon], {
						draggable: true,
						icon: draggableIcon
					}).addTo(macarte);
					
					marker.bindPopup(`<h3>${ref}</h3><b>Adresse: </b>${address}<br><b>Position actuelle:</b><br>Latitude: <span id="current-lat">${lat}</span><br>Longitude: <span id="current-lng">${lon}</span>`);
					
					// Ajouter un événement de mise à jour des coordonnées lors du déplacement
					marker.on("dragend", function(e) {
						var newPos = marker.getLatLng();
						// Mettre à jour les champs cachés du formulaire
						document.getElementById("new-latitude").value = newPos.lat.toFixed(6);
						document.getElementById("new-longitude").value = newPos.lng.toFixed(6);
						
						// Afficher les nouvelles coordonnées
						document.getElementById("current-coordinates").innerHTML = "' . $langs->trans("NewCoordinates") . ': " + 
							newPos.lat.toFixed(6) + ", " + newPos.lng.toFixed(6);
						
						// Mettre à jour le contenu de la popup
						marker.setPopupContent(`<h3>${ref}</h3><b>Adresse: </b>${address}<br><b>Position actuelle:</b><br>Latitude: ${newPos.lat.toFixed(6)}<br>Longitude: ${newPos.lng.toFixed(6)}`);
						
						// Ouvrir la popup
						marker.openPopup();
					});
					
					// Afficher les coordonnées actuelles
					if (document.getElementById("current-coordinates")) {
						document.getElementById("current-coordinates").innerHTML = "' . $langs->trans("CurrentCoordinates") . ': " + 
							lat.toFixed(6) + ", " + lon.toFixed(6);
					}
					
					// Ouvrir la popup automatiquement
					setTimeout(function() {
						marker.openPopup();
					}, 500);
				} else {
					// En mode normal, créer un marqueur standard
					marker = L.marker([lat, lon]).addTo(macarte);
					marker.bindPopup(popupContent);
				}';

		// Si l'agence de référence est configurée et activée, et qu'on n'est pas en mode d'ajustement
		if (!empty($conf->global->SITES2_USE_REFERENCE_AGENCY) && !$isPositionEditMode) {
			echo '
				// Afficher l\'agence de référence sur la carte
				var refAgencyLat = ' . $conf->global->SITES2_REFERENCE_AGENCY_LATITUDE . ';
				var refAgencyLng = ' . $conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE . ';
				var refAgencyName = "' . dol_escape_js($conf->global->SITES2_REFERENCE_AGENCY_NAME) . '";
				
				// Créer un marqueur pour l\'agence de référence avec une icône personnalisée
				var refAgencyIcon = L.icon({
					iconUrl: "' . dol_buildpath('/sites2/img/ref_agency_marker.svg', 1) . '",
					iconSize: [25, 41],
					iconAnchor: [12, 41],
					popupAnchor: [1, -34]
				});
				
				// Créer un marqueur pour l\'agence de référence
				var refAgencyMarker = L.marker([refAgencyLat, refAgencyLng], {
					icon: refAgencyIcon
				}).addTo(macarte);
				
				refAgencyMarker.bindPopup(refAgencyName + " (' . dol_escape_js($langs->trans("ReferenceAgency")) . ')");
				
				// Ajuster la vue pour inclure les deux marqueurs
				var bounds = L.latLngBounds([
					[refAgencyLat, refAgencyLng],
					[lat, lon]
				]);
				macarte.fitBounds(bounds, {padding: [50, 50]});';
		}

		echo '
			}
			
			window.onload = function() {
				initMap();
			};
		</script>
		';
		
		// Si on est en mode d'ajustement de position, afficher le formulaire pour enregistrer la nouvelle position
		if ($isPositionEditMode) {
			// Formulaire pour enregistrer la nouvelle position
			print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" id="position-form" style="text-align: center; margin: 20px 0;">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print '<input type="hidden" name="action" value="update_position">';
			print '<input type="hidden" name="id" value="' . $object->id . '">';
			print '<input type="hidden" name="new_latitude" id="new-latitude" value="' . $object->latitude . '">';
			print '<input type="hidden" name="new_longitude" id="new-longitude" value="' . $object->longitude . '">';
			print '<input type="submit" class="button" value="' . $langs->trans("SaveNewPosition") . '"> ';
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" class="button button-cancel">' . $langs->trans("Cancel") . '</a>';
			print '</form>';
		}
	}

	// Buttons for actions
	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Open Maps in browser
			if (!(empty($object->latitude)) && !(empty($object->longitude))) {
				$mapProvider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
				$externalMapApp = !empty($conf->global->SITES2_EXTERNAL_MAP_APP) ? $conf->global->SITES2_EXTERNAL_MAP_APP : 'default';
				$lat = $object->latitude;
				$lng = $object->longitude;
				$address = urlencode($object->address . ' ' . $object->zip . ' ' . $object->town);
				$site_name = urlencode($object->label);
				
				// Générer l'URL en fonction de l'application cartographique sélectionnée
				switch ($externalMapApp) {
					case 'googlemaps':
						$mapsUrl = "https://www.google.com/maps/search/?api=1&query=$lat,$lng";
						$buttonLabel = sprintf($langs->trans("OpenWithExternalApp"), "Google Maps");
						break;
					case 'openstreetmap':
						$mapsUrl = "https://www.openstreetmap.org/?mlat=$lat&mlon=$lng&zoom=15";
						$buttonLabel = sprintf($langs->trans("OpenWithExternalApp"), "OpenStreetMap");
						break;
					case 'waze':
						$mapsUrl = "https://waze.com/ul?ll=$lat,$lng&navigate=yes&z=15";
						$buttonLabel = sprintf($langs->trans("OpenWithExternalApp"), "Waze");
						break;
					case 'apple_maps':
						// Format pour Apple Plans: http://maps.apple.com/?ll=latitude,longitude&q=label
						$mapsUrl = "http://maps.apple.com/?ll=$lat,$lng&q=$site_name";
						$buttonLabel = sprintf($langs->trans("OpenWithExternalApp"), "Apple Plans");
						break;
					case 'bing_maps':
						$mapsUrl = "https://www.bing.com/maps?cp=$lat~$lng&lvl=15&sp=point.$lat"."_"."$lng"."_"."$site_name";
						$buttonLabel = sprintf($langs->trans("OpenWithExternalApp"), "Bing Maps");
						break;
					case 'default':
					default:
						// Sur mobile, utiliser un format d'URL universellement compatible qui déclenche l'app par défaut
						// geo:latitude,longitude fonctionne bien pour Android
						// Sur les autres plateformes, on utilise une fallback web (Google Maps)
						$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
						$isMobile = strpos($ua, 'Android') !== false || strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false;
						
						if ($isMobile && strpos($ua, 'Android') !== false) {
							// Format Android: utiliser uniquement les coordonnées si disponibles
							// Si les coordonnées sont présentes, on les utilise directement (sans paramètre q)
							// pour forcer l'application à utiliser les coordonnées plutôt que l'adresse
							if (!empty($lat) && !empty($lng)) {
								// Utiliser uniquement les coordonnées pour forcer leur utilisation
								$mapsUrl = "geo:$lat,$lng";
							} else {
								// Fallback: utiliser l'adresse si les coordonnées ne sont pas disponibles
								$mapsUrl = "geo:0,0?q=$address";
							}
						} else if ($isMobile && (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false)) {
							// Format iOS: http://maps.apple.com/?ll=latitude,longitude&q=label
							$mapsUrl = "http://maps.apple.com/?ll=$lat,$lng&q=$site_name";
						} else {
							// Fallback vers Google Maps pour les navigateurs desktop
							$mapsUrl = "https://www.google.com/maps/search/?api=1&query=$lat,$lng";
						}
						$buttonLabel = $langs->trans("OpenInMaps");
						break;
				}
				
				print dolGetButtonAction($buttonLabel, '', 'default', $mapsUrl, '_blank', $permissiontoadd);
				
				// Bouton pour recalculer les distances et temps de trajet sans passer par le formulaire d'édition
				print dolGetButtonAction($langs->trans('RecalculateDistances'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=calculate_distances&token='.newToken(), '', $permissiontoadd);
				
				// Bouton pour ajuster manuellement la position sur la carte
				print dolGetButtonAction($langs->trans('AdjustPositionManually'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=adjust_position&token='.newToken(), '', $permissiontoadd);
			}

			// Create intervention
			if (($conf->ficheinter->enabled) && !(empty($object->fk_soc))) {
				print dolGetButtonAction($langs->trans('Intervention'), '', 'default', DOL_URL_ROOT.'/fichinter/card.php?action=create&leftmenu=ficheinter&socid='.$object->fk_soc, '', $permissiontoadd);
			}

			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction($langs->trans('SendMail'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init&token='.newToken().'#formmailbeforetitle');
			}

			print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken(), '', $permissiontoadd);
			print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&socid='.$object->socid.'&action=clone&token='.newToken(), '', $permissiontoadd);
			print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}

	// Presend form
	$modelmail = 'site';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->sites2->dir_output;
	$trackid = 'site'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close(); 