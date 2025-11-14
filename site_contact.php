<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       site_contact.php
 *  \ingroup    sites2
 *  \brief      Tab for contacts linked to Site
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

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

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
$langs->loadLangs(array("sites2@sites2", "companies", "other", "mails"));

$id     = (GETPOST('id') ?GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref    = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$socid  = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Site($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->sites2->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('sitecontact', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

$permission = $user->rights->sites2->site->write;


/*
 * Add a new contact
 */

if ($action == 'addcontact' && $permission) {
	$contactid = (GETPOST('userid') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
	$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
	$source = GETPOST('source', 'aZ09');
	$force_external = GETPOST('force_external', 'int');
	if (empty($source)) $source = 'external';
	
	// Journalisation pour diagnostic
	dol_syslog("addcontact: contactid=$contactid, typeid=$typeid, source=$source, force_external=$force_external", LOG_DEBUG);
	
	// Validation des paramètres avant l'appel
	if (empty($contactid) || $contactid <= 0) {
		setEventMessages($langs->trans("ErrorBadValueForContactId"), null, 'errors');
		dol_syslog("addcontact: Contact ID invalide ou manquant", LOG_WARNING);
	} elseif (empty($typeid) || $typeid <= 0) {
		setEventMessages($langs->trans("ErrorBadValueForContactType"), null, 'errors');
		dol_syslog("addcontact: Type de contact invalide ou manquant", LOG_WARNING);
	} else {
		// Appel à add_contact uniquement si les paramètres sont valides
		$result = $object->add_contact($contactid, $typeid, $source, 0, $force_external);

		if ($result >= 0) {
			// Succès - redirection sans message d'erreur
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			// Gestion des erreurs réelles
			dol_syslog("Error in add_contact: ".$object->error, LOG_ERR);
			if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorContactAlreadyExists"), null, 'errors');
			} elseif ($object->error == 'ContactNotBelongToThirdParty') {
				setEventMessages($langs->trans("ErrorThisContactDoesNotBelongToLinkedThirdParty"), null, 'errors');
			} elseif ($object->error == 'ErrorBadValueForContactId') {
				// Ne pas afficher cette erreur car elle a déjà été gérée ci-dessus
				// ou elle peut provenir d'une autre validation dans add_contact
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
} elseif ($action == 'swapstatut' && $permission) {
	// Toggle the status of a contact
	$result = $object->swapContactStatus(GETPOST('ligne', 'int'));
} elseif ($action == 'deletecontact' && $permission) {
	// Deletes a contact
	$result = $object->delete_contact($lineid);

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$title = $langs->trans('Site')." - ".$langs->trans('ContactsAddresses');
$help_url = '';
llxHeader('', $title, $help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* View and edit mode                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($object->id) {
	/*
	 * Show tabs
	 */
	$head = sitePrepareHead($object);

	print dol_get_fiche_head($head, 'contact', $langs->trans("Contact"), -1, $object->picto);

	$linkback = '<a href="'.dol_buildpath('/sites2/site_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'label', $morehtmlref, '', 0, '', '', 1);

	print dol_get_fiche_end();

	print '<br>';

	// Contacts liés au tiers associé au site
	if (!empty($object->fk_soc)) {
		// Créer un rôle par défaut s'il n'existe aucun type de contact pour les sites
		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."c_type_contact WHERE element = 'site' AND active = 1";
		$resql = $db->query($sql);
		if ($resql && $db->fetch_object($resql)->nb == 0) {
			// Aucun rôle défini, créons-en un par défaut
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_type_contact (element, source, code, libelle, position, active) VALUES ";
			$sql .= "('site', 'external', 'CONTACT', '".$langs->trans("Contact")."', 10, 1)";
			$db->query($sql); // On ne vérifie pas le résultat, ce n'est pas critique si ça échoue
		}
		if ($resql) {
			$db->free($resql);
		}

		// Affichage directement des contacts avec numéros de téléphone
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Name").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>'; // Type de contact
		print '<td>'.$langs->trans("Phone").'</td>'; // Téléphone fixe
		print '<td>'.$langs->trans("Mobile").'</td>'; // Téléphone portable
		print '<td class="right">'.$langs->trans("Actions").'</td>';
		print '</tr>';
		
		// Récupérer les contacts liés à ce site avec leurs numéros de téléphone
		$sql = "SELECT ec.rowid, ec.statut, ec.fk_socpeople, ec.fk_c_type_contact, ";
		$sql .= " tc.code, tc.libelle, ";
		$sql .= " s.rowid as socid, s.nom as name, ";
		$sql .= " sp.rowid as contactid, sp.lastname, sp.firstname, sp.email, sp.phone, sp.phone_mobile ";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON tc.rowid = ec.fk_c_type_contact";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.rowid = ec.fk_socpeople";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = sp.fk_soc";
		$sql .= " WHERE ec.element_id = ".$object->id;
		$sql .= " AND tc.element = 'site'";
		
		$resql_contact = $db->query($sql);
		if ($resql_contact) {
			$num = $db->num_rows($resql_contact);
			if ($num > 0) {
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql_contact);
					
					print '<tr class="oddeven">';
					
					// Nom du contact
					print '<td>';
					$contactstatic->id = $obj->contactid;
					$contactstatic->lastname = $obj->lastname;
					$contactstatic->firstname = $obj->firstname;
					print $contactstatic->getNomUrl(1);
					print '</td>';
					
					// Type de contact
					print '<td>';
					print $obj->libelle;
					print '</td>';
					
					// Téléphone fixe - rendons le cliquable sur mobile
					print '<td>';
					if (!empty($obj->phone)) {
						print '<a href="tel:'.$obj->phone.'" class="tel-link">';
						print $obj->phone;
						print '</a>';
					} else {
						print '<span class="opacitymedium">-</span>';
					}
					print '</td>';
					
					// Téléphone mobile - rendons le cliquable sur mobile
					print '<td>';
					if (!empty($obj->phone_mobile)) {
						print '<a href="tel:'.$obj->phone_mobile.'" class="tel-link">';
						print $obj->phone_mobile;
						print '</a>';
					} else {
						print '<span class="opacitymedium">-</span>';
					}
					print '</td>';
					
					// Actions
					print '<td class="right">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deletecontact&token='.urlencode(newToken()).'&lineid='.$obj->rowid.'">';
					print img_delete();
					print '</a>';
					print '</td>';
					
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td class="opacitymedium" colspan="5">'.$langs->trans("NoContacts").'</td></tr>';
			}
			// Libérer le résultat de la requête de contacts
			$db->free($resql_contact);
		} else {
			dol_print_error($db);
		}
		
		print '</table>';
		print '</div>';
		
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
		
		// Bouton pour afficher le formulaire d'ajout
		if ($action != 'addcontact' && $permission) {
			print '<div class="tabsAction">';
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addcontact&token='.urlencode(newToken()).'">'.$langs->trans("AddContact").'</a>';
			print '</div>';
		}
		
		// Formulaire d'ajout de contact
		if ($action == 'addcontact' && $permission) {
			print '<br>';
			print load_fiche_titre($langs->trans("AddContact"), '', '');
			
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="contactform">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="addcontact">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="source" value="external">';
			
			// Option pour forcer l'ajout d'un contact externe
			$use_external = GETPOST('use_external', 'int');
			print '<input type="hidden" name="force_external" value="'.($use_external ? 1 : 0).'">';
			
			print '<table class="border centpercent">';
			
			// Option pour choisir entre contacts internes et tous les contacts
			print '<tr><td class="titlefieldcreate">'.$langs->trans("ContactSource").'</td><td>';
			print '<input type="radio" name="use_external" value="0" '.(!$use_external ? 'checked' : '').' onclick="submit();"> ';
			print '<label for="use_external_0">'.$langs->trans("LinkedThirdPartyOnly").'</label> ';
			print '<input type="radio" name="use_external" value="1" '.($use_external ? 'checked' : '').' onclick="submit();"> ';
			print '<label for="use_external_1">'.$langs->trans("AllThirdParties").'</label>';
			print '</td></tr>';
			
			// Contact
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Contact").' <span class="fieldrequired">*</span></td><td>';
			
			if (!$use_external) {
				// Liste des contacts du tiers
				if (!empty($object->fk_soc)) {
					// Récupérer la liste des contacts disponibles pour ce tiers
					$sql = "SELECT sp.rowid, sp.lastname, sp.firstname FROM " . MAIN_DB_PREFIX . "socpeople as sp";
					$sql .= " WHERE sp.fk_soc = " . $object->fk_soc;
					$sql .= " ORDER BY sp.lastname, sp.firstname";
					
					$resql_available = $db->query($sql);
					if ($resql_available && $db->num_rows($resql_available) > 0) {
						print '<select class="flat minwidth300" id="contactid" name="contactid">';
						print '<option value="0">&nbsp;</option>';  // Utiliser "0" au lieu de "" pour éviter les problèmes de validation
						
						while ($contact = $db->fetch_object($resql_available)) {
							print '<option value="' . $contact->rowid . '">';
							print $contact->lastname . ' ' . $contact->firstname;
							print '</option>';
						}
						
						print '</select>';
						$db->free($resql_available);
					} else {
						print '<span class="opacitymedium">' . $langs->trans("NoContactForCompany") . '</span>';
						print ' <a href="' . DOL_URL_ROOT . '/contact/card.php?socid=' . $object->fk_soc . '&action=create&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?id=' . $object->id) . '">';
						print img_picto('', 'add', 'class="paddingleft"') . ' ' . $langs->trans("AddContact");
						print '</a>';
					}
				} else {
					print '<span class="opacitymedium">' . $langs->trans("NoThirdPartyAssociatedToSite") . '</span>';
				}
			} else {
				// Liste de tous les contacts, avec le nom du tiers
				$sql = "SELECT sp.rowid, sp.lastname, sp.firstname, s.nom as socname 
					FROM " . MAIN_DB_PREFIX . "socpeople as sp
					LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = sp.fk_soc 
					WHERE s.status = 1 
					ORDER BY s.nom, sp.lastname, sp.firstname";
				
				$resql_all_contacts = $db->query($sql);
				if ($resql_all_contacts && $db->num_rows($resql_all_contacts) > 0) {
					print '<select class="flat minwidth400" id="contactid" name="contactid">';
					print '<option value="0">&nbsp;</option>';
					
					$current_soc = '';
					while ($contact = $db->fetch_object($resql_all_contacts)) {
						// Ajouter un optgroup pour chaque société
						if ($current_soc != $contact->socname) {
							if ($current_soc != '') print '</optgroup>';
							print '<optgroup label="' . $contact->socname . '">';
							$current_soc = $contact->socname;
						}
						
						print '<option value="' . $contact->rowid . '">';
						print $contact->lastname . ' ' . $contact->firstname;
						print '</option>';
					}
					
					if ($current_soc != '') print '</optgroup>';
					print '</select>';
					$db->free($resql_all_contacts);
				} else {
					print '<span class="opacitymedium">' . $langs->trans("NoContactFound") . '</span>';
				}
			}
			print '</td></tr>';
			
			// Type de contact
			print '<tr><td>'.$langs->trans("ContactType").' <span class="fieldrequired">*</span></td><td>';
			// Récupérer la liste des types de contacts pour les sites
			$sql = "SELECT tc.rowid, tc.code, tc.libelle FROM " . MAIN_DB_PREFIX . "c_type_contact as tc";
			$sql .= " WHERE tc.element = 'site' AND tc.active = 1";
			$sql .= " ORDER BY tc.position";
			
			$resql_types = $db->query($sql);
			if ($resql_types && $db->num_rows($resql_types) > 0) {
				print '<select class="flat" id="type" name="type">';
				print '<option value="0">&nbsp;</option>';  // Utiliser "0" au lieu de "" pour éviter les problèmes de validation
				
				while ($type = $db->fetch_object($resql_types)) {
					print '<option value="' . $type->rowid . '">';
					print $type->libelle;
					print '</option>';
				}
				
				print '</select>';
				$db->free($resql_types);
			} else {
				print '<span class="opacitymedium">' . $langs->trans("NoContactRolesDefinedForSites") . '</span>';
			}
			print '</td></tr>';
			
			print '</table>';
			
			print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
			print '</div>';
			
			print '</form>';
		}
	} else {
		print '<div class="warning">'.$langs->trans("NoThirdPartyAssociatedToSite").'</div>';
	}
}

// End of page
llxFooter();
$db->close(); 