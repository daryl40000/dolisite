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
 *   	\file       site_chantier.php
 *		\ingroup    sites2
 *		\brief      Page des chantiers programmés liés à un site
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

// Load translation files required by the page
$langs->loadLangs(array("sites2@sites2", "other", "propal"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sitechantier';
$backtopage = GETPOST('backtopage', 'alpha');
$chantier_id = GETPOST('chantier_id', 'int');

// Initialize technical objects
$object = new Site($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('sitechantier', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread = $user->rights->sites2->site->read;
$permissiontoadd = $user->rights->sites2->site->write;
$permissiontodelete = $user->rights->sites2->site->delete;

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

// Action pour ajouter un chantier programmé
if ($action == 'add_chantier' && $permissiontoadd) {
	$fk_propal = GETPOST('fk_propal', 'int');
	$date_debut = GETPOST('date_debut', 'alpha');
	$date_fin = GETPOST('date_fin', 'alpha');
	$location_type = GETPOST('location_type', 'int');
	$note_public = GETPOST('note_public', 'restricthtml');
	$note_private = GETPOST('note_private', 'restricthtml');
	
	// Valeur par défaut pour location_type si non fournie
	if ($location_type === '' || $location_type === null) {
		$location_type = 1; // Extérieur par défaut
	}
	
	// Validation des données
	if (empty($fk_propal) || $fk_propal <= 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Proposal")), null, 'errors');
	} else {
		// Les dates sont maintenant optionnelles
		// Vérifier que le devis appartient bien au tiers du site
		if (!empty($object->fk_soc)) {
			$sql = "SELECT rowid, fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".((int)$fk_propal)." AND fk_soc = ".((int)$object->fk_soc);
			$resql = $db->query($sql);
			if (!$resql || $db->num_rows($resql) == 0) {
				setEventMessages($langs->trans("ErrorProposalNotBelongToThirdParty"), null, 'errors');
			} else {
				// Vérifier que le devis est signé
				$obj = $db->fetch_object($resql);
				$sql_status = "SELECT fk_statut FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".((int)$fk_propal);
				$resql_status = $db->query($sql_status);
				if ($resql_status) {
					$obj_status = $db->fetch_object($resql_status);
					// Statut 2 = signé (seulement les devis signés sont acceptés)
					if ($obj_status->fk_statut != 2) {
						setEventMessages($langs->trans("ErrorProposalNotSigned"), null, 'errors');
					} else {
						// Insérer le chantier
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."sites2_chantier (fk_site, fk_propal, date_debut, date_fin, location_type, note_public, note_private, date_creation, fk_user_creat, entity)";
						$sql .= " VALUES (".((int)$object->id).", ".((int)$fk_propal).", ";
						$sql .= (!empty($date_debut) ? "'".$db->idate($date_debut)."'" : "NULL").", ";
						$sql .= (!empty($date_fin) ? "'".$db->idate($date_fin)."'" : "NULL").", ";
						$sql .= ((int)$location_type).", ";
						$sql .= (!empty($note_public) ? "'".$db->escape($note_public)."'" : "NULL").", ";
						$sql .= (!empty($note_private) ? "'".$db->escape($note_private)."'" : "NULL").", ";
						$sql .= "NOW(), ".((int)$user->id).", ".((int)$conf->entity).")";
						
						$resql = $db->query($sql);
						if ($resql) {
							setEventMessages($langs->trans("ScheduledWorkSiteAdded"), null, 'mesgs');
							$action = '';
						} else {
							setEventMessages($langs->trans("Error").' '.$db->lasterror(), null, 'errors');
						}
					}
					$db->free($resql_status);
				}
			}
			if ($resql) {
				$db->free($resql);
			}
		} else {
			setEventMessages($langs->trans("ErrorNoThirdPartyLinkedToSite"), null, 'errors');
		}
	}
}

// Action pour modifier un chantier programmé
if ($action == 'update_chantier' && $permissiontoadd && $chantier_id > 0) {
	$fk_propal = GETPOST('fk_propal', 'int');
	$date_debut = GETPOST('date_debut', 'alpha');
	$date_fin = GETPOST('date_fin', 'alpha');
	$location_type = GETPOST('location_type', 'int');
	$note_public = GETPOST('note_public', 'restricthtml');
	$note_private = GETPOST('note_private', 'restricthtml');
	
	// Valeur par défaut pour location_type si non fournie
	if ($location_type === '' || $location_type === null) {
		$location_type = 1; // Extérieur par défaut
	}
	
	// Validation des données
	if (empty($fk_propal) || $fk_propal <= 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Proposal")), null, 'errors');
		$action = 'edit_chantier'; // Revenir au formulaire d'édition
	} else {
		// Les dates sont maintenant optionnelles
		// Vérifier que le chantier appartient bien au site
		$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."sites2_chantier WHERE rowid = ".((int)$chantier_id)." AND fk_site = ".((int)$object->id);
		$resql_check = $db->query($sql_check);
		if (!$resql_check || $db->num_rows($resql_check) == 0) {
			setEventMessages($langs->trans("Error").' '.$langs->trans("ScheduledWorkSiteNotFound"), null, 'errors');
		} else {
			// Vérifier que le devis appartient bien au tiers du site
			if (!empty($object->fk_soc)) {
				$sql = "SELECT rowid, fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".((int)$fk_propal)." AND fk_soc = ".((int)$object->fk_soc);
				$resql = $db->query($sql);
				if (!$resql || $db->num_rows($resql) == 0) {
					setEventMessages($langs->trans("ErrorProposalNotBelongToThirdParty"), null, 'errors');
					$action = 'edit_chantier';
				} else {
					// Vérifier que le devis est signé
					$obj = $db->fetch_object($resql);
					$sql_status = "SELECT fk_statut FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".((int)$fk_propal);
					$resql_status = $db->query($sql_status);
					if ($resql_status) {
						$obj_status = $db->fetch_object($resql_status);
						// Statut 2 = signé (seulement les devis signés sont acceptés)
						if ($obj_status->fk_statut != 2) {
							setEventMessages($langs->trans("ErrorProposalNotSigned"), null, 'errors');
							$action = 'edit_chantier';
						} else {
							// Mettre à jour le chantier
							$sql = "UPDATE ".MAIN_DB_PREFIX."sites2_chantier SET";
							$sql .= " fk_propal = ".((int)$fk_propal).",";
							$sql .= " date_debut = ".(!empty($date_debut) ? "'".$db->idate($date_debut)."'" : "NULL").",";
							$sql .= " date_fin = ".(!empty($date_fin) ? "'".$db->idate($date_fin)."'" : "NULL").",";
							$sql .= " location_type = ".((int)$location_type).",";
							$sql .= " note_public = ".(!empty($note_public) ? "'".$db->escape($note_public)."'" : "NULL").",";
							$sql .= " note_private = ".(!empty($note_private) ? "'".$db->escape($note_private)."'" : "NULL").",";
							$sql .= " fk_user_modif = ".((int)$user->id);
							$sql .= " WHERE rowid = ".((int)$chantier_id)." AND fk_site = ".((int)$object->id);
							
							$resql = $db->query($sql);
							if ($resql) {
								setEventMessages($langs->trans("ScheduledWorkSiteUpdated"), null, 'mesgs');
								$action = '';
							} else {
								setEventMessages($langs->trans("Error").' '.$db->lasterror(), null, 'errors');
								$action = 'edit_chantier';
							}
						}
						$db->free($resql_status);
					}
				}
				if ($resql) {
					$db->free($resql);
				}
			} else {
				setEventMessages($langs->trans("ErrorNoThirdPartyLinkedToSite"), null, 'errors');
				$action = 'edit_chantier';
			}
		}
		$db->free($resql_check);
	}
}

// Action pour supprimer un chantier
if ($action == 'delete_chantier' && $permissiontodelete && $chantier_id > 0) {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."sites2_chantier WHERE rowid = ".((int)$chantier_id)." AND fk_site = ".((int)$object->id);
	$resql = $db->query($sql);
	if ($resql) {
		setEventMessages($langs->trans("ScheduledWorkSiteDeleted"), null, 'mesgs');
		$action = '';
	} else {
		setEventMessages($langs->trans("Error").' '.$db->lasterror(), null, 'errors');
	}
}

/*
 * View
 */

$title = $langs->trans("Site") . ' - ' . $langs->trans("ScheduledWorkSite");
$help_url = '';
llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
	$head = sitePrepareHead($object);
	print dol_get_fiche_head($head, 'chantier', $langs->trans("Site"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/sites2/site_list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	// Thirdparty
	if (is_object($object->thirdparty)) {
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'customer');
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	// Vérifier qu'un tiers est associé au site
	if (empty($object->fk_soc)) {
		print '<div class="warning">'.$langs->trans("ErrorNoThirdPartyLinkedToSite").'</div>';
	} else {
		// Afficher les chantiers programmés existants
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		
		print '<tr class="liste_titre">';
		print '<td colspan="6">';
		print '<i class="fa fa-calendar-alt"></i> '.$langs->trans("ScheduledWorkSites");
		
		// Bouton d'ajout
		if ($permissiontoadd) {
			if ($action != 'add_chantier' && $action != 'edit_chantier') {
				print '<a class="editfielda paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=add_chantier&token='.newToken().'">';
				print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddScheduledWorkSite").'"></span>';
				print '</a>';
			}
		}
		
		print '</td></tr>';
		
		// Formulaire d'ajout ou d'édition de chantier
		$is_edit_mode = ($action == 'edit_chantier' && $chantier_id > 0);
		$chantier_data = null;
		
		// Charger les données du chantier si on est en mode édition
		if ($is_edit_mode) {
			$sql_chantier = "SELECT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public, c.note_private";
			$sql_chantier .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
			$sql_chantier .= " WHERE c.rowid = ".((int)$chantier_id)." AND c.fk_site = ".((int)$object->id);
			$resql_chantier = $db->query($sql_chantier);
			if ($resql_chantier && $db->num_rows($resql_chantier) > 0) {
				$chantier_data = $db->fetch_object($resql_chantier);
			} else {
				setEventMessages($langs->trans("ScheduledWorkSiteNotFound"), null, 'errors');
				$is_edit_mode = false;
				$action = '';
			}
			$db->free($resql_chantier);
		}
		
		if (($action == 'add_chantier' || $is_edit_mode) && $permissiontoadd) {
			print '<tr class="oddeven">';
			print '<td colspan="7">';
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="'.($is_edit_mode ? 'update_chantier' : 'add_chantier').'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			if ($is_edit_mode) {
				print '<input type="hidden" name="chantier_id" value="'.$chantier_id.'">';
			}
			
			print '<table class="border centpercent">';
			
			// Titre du formulaire
			if ($is_edit_mode) {
				print '<tr><td colspan="2"><h3>'.$langs->trans("EditScheduledWorkSite").'</h3></td></tr>';
			}
			
			// Sélection du devis
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("Proposal").' <span class="fieldrequired">*</span></td>';
			print '<td>';
			
			// Récupérer les devis signés du tiers
			$sql = "SELECT p.rowid, p.ref, p.fk_statut, p.datep as date_propal, p.total_ht";
			$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
			$sql .= " WHERE p.fk_soc = ".((int)$object->fk_soc);
			$sql .= " AND p.fk_statut = 2"; // Seulement signé (2), pas validé (1)
			$sql .= " ORDER BY p.datep DESC, p.ref DESC";
			
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				$selected_propal = $is_edit_mode && $chantier_data ? $chantier_data->fk_propal : (GETPOST('fk_propal', 'int') ? GETPOST('fk_propal', 'int') : '');
				
				print '<select name="fk_propal" class="flat minwidth200" required>';
				print '<option value="">-- '.$langs->trans("SelectProposal").' --</option>';
				
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					$status_label = '';
					if ($obj->fk_statut == 2) {
						$status_label = ' ('.$langs->trans("Signed").')';
					} elseif ($obj->fk_statut == 1) {
						$status_label = ' ('.$langs->trans("Validated").')';
					}
					
					$selected = ($obj->rowid == $selected_propal) ? ' selected' : '';
					print '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->ref.$status_label.' - '.dol_print_date($db->jdate($obj->date_propal), 'day').'</option>';
					$i++;
				}
				print '</select>';
				$db->free($resql);
			} else {
				print '<span class="opacitymedium">'.$langs->trans("NoSignedProposal").'</span>';
			}
			print '</td>';
			print '</tr>';
			
			// Date de début (optionnelle)
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("StartDate").'</td>';
			print '<td>';
			$date_debut_value = '';
			if ($is_edit_mode && $chantier_data && !empty($chantier_data->date_debut)) {
				$date_debut_value = date('Y-m-d', $db->jdate($chantier_data->date_debut));
			} elseif (GETPOST('date_debut', 'alpha')) {
				$date_debut_value = GETPOST('date_debut', 'alpha');
			}
			print '<input type="date" name="date_debut" class="flat" value="'.$date_debut_value.'">';
			print '<span class="opacitymedium"> ('.$langs->trans("OptionalDateNotDecided").')</span>';
			print '</td>';
			print '</tr>';
			
			// Date de fin (optionnelle)
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("EndDate").'</td>';
			print '<td>';
			$date_fin_value = '';
			if ($is_edit_mode && $chantier_data && !empty($chantier_data->date_fin)) {
				$date_fin_value = date('Y-m-d', $db->jdate($chantier_data->date_fin));
			} elseif (GETPOST('date_fin', 'alpha')) {
				$date_fin_value = GETPOST('date_fin', 'alpha');
			}
			print '<input type="date" name="date_fin" class="flat" value="'.$date_fin_value.'">';
			print '<span class="opacitymedium"> ('.$langs->trans("OptionalForMultiDayWork").')</span>';
			print '</td>';
			print '</tr>';
			
			// Type de localisation (intérieur/extérieur)
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("WorkLocationType").'</td>';
			print '<td>';
			$location_type_value = 1; // Par défaut extérieur
			if ($is_edit_mode && $chantier_data && isset($chantier_data->location_type)) {
				$location_type_value = (int)$chantier_data->location_type;
			} elseif (GETPOST('location_type', 'int') !== '') {
				$location_type_value = GETPOST('location_type', 'int');
			}
			print '<input type="radio" name="location_type" value="0" id="location_interior" '.($location_type_value == 0 ? 'checked' : '').'> ';
			print '<label for="location_interior">'.$langs->trans("Interior").'</label> ';
			print '<input type="radio" name="location_type" value="1" id="location_exterior" '.($location_type_value == 1 ? 'checked' : '').'> ';
			print '<label for="location_exterior">'.$langs->trans("Exterior").'</label>';
			print '<br><span class="opacitymedium">'.$langs->trans("WorkLocationTypeHelp").'</span>';
			print '</td>';
			print '</tr>';
			
			// Note publique
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("PublicNote").'</td>';
			print '<td>';
			$note_public_value = '';
			if ($is_edit_mode && $chantier_data) {
				$note_public_value = $chantier_data->note_public;
			} elseif (GETPOST('note_public', 'restricthtml')) {
				$note_public_value = GETPOST('note_public', 'restricthtml');
			}
			print '<textarea name="note_public" class="flat" rows="3" cols="80">'.htmlspecialchars($note_public_value).'</textarea>';
			print '</td>';
			print '</tr>';
			
			// Note privée
			print '<tr>';
			print '<td class="titlefieldcreate">'.$langs->trans("PrivateNote").'</td>';
			print '<td>';
			$note_private_value = '';
			if ($is_edit_mode && $chantier_data) {
				$note_private_value = $chantier_data->note_private;
			} elseif (GETPOST('note_private', 'restricthtml')) {
				$note_private_value = GETPOST('note_private', 'restricthtml');
			}
			print '<textarea name="note_private" class="flat" rows="3" cols="80">'.htmlspecialchars($note_private_value).'</textarea>';
			print '</td>';
			print '</tr>';
			
			print '</table>';
			
			print '<div class="center" style="margin-top: 10px;">';
			print '<input type="submit" class="button" value="'.($is_edit_mode ? $langs->trans("Save") : $langs->trans("Add")).'">';
			print ' &nbsp; ';
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" class="button button-cancel">'.$langs->trans("Cancel").'</a>';
			print '</div>';
			
			print '</form>';
			print '</td>';
			print '</tr>';
		}
		
		// En-têtes du tableau
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Proposal").'</th>';
		print '<th>'.$langs->trans("StartDate").'</th>';
		print '<th>'.$langs->trans("EndDate").'</th>';
		print '<th>'.$langs->trans("WorkLocationType").'</th>';
		print '<th>'.$langs->trans("Duration").'</th>';
		print '<th>'.$langs->trans("Note").'</th>';
		print '<th class="right">'.$langs->trans("Actions").'</th>';
		print '</tr>';
		
		// Récupérer les chantiers programmés
		$sql = "SELECT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public, c.note_private,";
		$sql .= " p.ref as propal_ref, p.fk_statut as propal_status";
		$sql .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
		$sql .= " WHERE c.fk_site = ".((int)$object->id);
		$sql .= " ORDER BY c.date_debut DESC, c.date_creation DESC";
		
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			
			if ($num > 0) {
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					
					print '<tr class="oddeven">';
					
					// Devis
					print '<td>';
					if (!empty($obj->propal_ref)) {
						// Créer un lien vers le devis
						$propal_url = DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->fk_propal;
						print '<a href="'.$propal_url.'" target="_blank">'.$obj->propal_ref.'</a>';
						if ($obj->propal_status == 2) {
							print ' <span class="badge badge-status4">'.$langs->trans("Signed").'</span>';
						} elseif ($obj->propal_status == 1) {
							print ' <span class="badge badge-status1">'.$langs->trans("Validated").'</span>';
						}
					} else {
						print '<span class="opacitymedium">-</span>';
					}
					print '</td>';
					
					// Date de début
					print '<td>';
					if (!empty($obj->date_debut)) {
						print dol_print_date($db->jdate($obj->date_debut), 'day');
					} else {
						print '<span class="opacitymedium">'.$langs->trans("DateNotDecided").'</span>';
					}
					print '</td>';
					
					// Date de fin
					print '<td>';
					if (!empty($obj->date_fin)) {
						print dol_print_date($db->jdate($obj->date_fin), 'day');
					} else {
						if (!empty($obj->date_debut)) {
							print '<span class="opacitymedium">-</span>';
						} else {
							print '<span class="opacitymedium">'.$langs->trans("DateNotDecided").'</span>';
						}
					}
					print '</td>';
					
					// Type de localisation
					print '<td>';
					$location_type = isset($obj->location_type) ? (int)$obj->location_type : 1; // Par défaut extérieur
					if ($location_type == 0) {
						print '<span class="badge badge-status0"><i class="fa fa-home"></i> '.$langs->trans("Interior").'</span>';
					} else {
						print '<span class="badge badge-status1"><i class="fa fa-sun"></i> '.$langs->trans("Exterior").'</span>';
					}
					print '</td>';
					
					// Durée
					print '<td>';
					if (!empty($obj->date_debut)) {
						if (!empty($obj->date_fin)) {
							$date_debut_ts = $db->jdate($obj->date_debut);
							$date_fin_ts = $db->jdate($obj->date_fin);
							$diff = $date_fin_ts - $date_debut_ts;
							$days = floor($diff / 86400) + 1; // +1 pour inclure le jour de début
							if ($days == 1) {
								print $langs->trans("OneDay");
							} else {
								print $days.' '.$langs->trans("Days");
							}
						} else {
							print $langs->trans("OneDay");
						}
					} else {
						print '<span class="opacitymedium">'.$langs->trans("NotApplicable").'</span>';
					}
					print '</td>';
					
					// Note
					print '<td>';
					if (!empty($obj->note_public)) {
						print dol_trunc($obj->note_public, 50);
					} else {
						print '<span class="opacitymedium">-</span>';
					}
					print '</td>';
					
					// Actions
					print '<td class="right">';
					if ($permissiontoadd) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_chantier&chantier_id='.$obj->rowid.'&token='.newToken().'">';
						print '<span class="fa fa-edit paddingleft" title="'.$langs->trans("Modify").'"></span>';
						print '</a>';
					}
					if ($permissiontodelete) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete_chantier&chantier_id='.$obj->rowid.'&token='.newToken().'"';
						print ' onclick="return confirm(\''.dol_escape_js($langs->trans("ConfirmDeleteScheduledWorkSite")).'\');">';
						print '<span class="fa fa-trash paddingleft" title="'.$langs->trans("Delete").'"></span>';
						print '</a>';
					}
					print '</td>';
					
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td colspan="6" class="opacitymedium center">'.$langs->trans("NoScheduledWorkSite").'</td></tr>';
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
		
		print '</table>';
		print '</div>';
	}
	
	print '</div>'; // End fichecenter
	
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();

