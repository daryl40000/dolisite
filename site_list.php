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
 *  \file       site_list.php
 *  \ingroup    sites2
 *  \brief      Liste des sites clients
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("sites2@sites2", "other"));

$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sitelist';

// Security check
if (!$user->rights->sites2->site->read) accessforbidden();

// Paramètres de pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma') ? GETPOST('sortfield', 'aZ09comma') : 'ref';
$sortorder = GETPOST('sortorder', 'aZ09comma') ? GETPOST('sortorder', 'aZ09comma') : 'ASC';
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $page = 0;
}
$offset = $limit * $page;

// Initialize search variables
$search_all = GETPOST('search_all', 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_address = GETPOST('search_address', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_phone = GETPOST('search_phone', 'alpha');
$search_status = GETPOST('search_status', 'int');
$search_societe = GETPOST('search_societe', 'alpha');
$search_no_coordinates = GETPOST('search_no_coordinates', 'int');

// Initialize technical objects
$object = new Site($db);
$extrafields = new ExtraFields($db);
$form = new Form($db);

// Traitement des actions massives
if ($massaction == 'setstatus' && $user->rights->sites2->site->write) {
    $status = GETPOST('status', 'int');
    $nb = 0;
    foreach ($toselect as $siteid) {
        $result = $object->fetch($siteid);
        if ($result > 0) {
            $object->status = $status;
            $result = $object->update($user);
            if ($result <= 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                $nb++;
            }
        }
    }
    if ($nb > 0) {
        setEventMessages($langs->trans("StatusChangedForNSites", $nb), null, 'mesgs');
    }
}

if ($massaction == 'delete' && $user->rights->sites2->site->delete) {
    $nb = 0;
    foreach ($toselect as $siteid) {
        $result = $object->fetch($siteid);
        if ($result > 0) {
            $result = $object->delete($user);
            if ($result <= 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                $nb++;
            }
        }
    }
    if ($nb > 0) {
        setEventMessages($langs->trans("NSitesDeleted", $nb), null, 'mesgs');
    }
}

//  Load list of sites
$sql = "SELECT s.rowid, s.ref, s.label, s.address, s.zip, s.town, s.phone, s.fk_soc, s.status, soc.nom as societe_nom";
$sql.= " FROM ".MAIN_DB_PREFIX."sites2_site as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON s.fk_soc = soc.rowid";
$sql.= " WHERE 1 = 1";

// Recherche unifiée
if (!empty($search_all)) {
    $sql .= natural_search(array('s.ref', 's.label', 's.address', 's.town', 's.zip', 's.phone', 'soc.nom'), $search_all);
}
if (!empty($search_ref)) $sql .= natural_search('s.ref', $search_ref);
if (!empty($search_label)) $sql .= natural_search('s.label', $search_label);
if (!empty($search_address)) $sql .= natural_search('s.address', $search_address);
if (!empty($search_town)) $sql .= natural_search('s.town', $search_town);
if (!empty($search_zip)) $sql .= natural_search('s.zip', $search_zip);
if (!empty($search_phone)) $sql .= natural_search('s.phone', $search_phone);
if (!empty($search_societe)) $sql .= natural_search('soc.nom', $search_societe);
if (isset($search_status) && $search_status != '' && $search_status >= 0) $sql .= " AND s.status = ".$db->escape($search_status);
if (!empty($search_no_coordinates)) $sql .= " AND (s.latitude IS NULL OR s.longitude IS NULL OR s.latitude = '' OR s.longitude = '')";

$sql.= $db->order($sortfield, $sortorder);

// Count total number of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Actions à afficher suivant les droits
$arrayofmassactions = array();
if ($user->rights->sites2->site->write) {
    $arrayofmassactions['setstatus'] = img_picto('', 'edit', 'class="pictofixedwidth"').$langs->trans("SetStatus");
}
if ($user->rights->sites2->site->delete) {
    $arrayofmassactions['delete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// Output page
llxHeader('', $langs->trans("Sites"), '');

// Modifier le titre si on affiche les sites sans coordonnées
$title = $langs->trans("ListOfSites");
if (!empty($search_no_coordinates)) {
    $title = $langs->trans("ListOfSitesWithoutCoordinates");
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_'.$object->picto, 0, '', '', $limit);

// Formulaire de sélection de statut pour l'action massive
if ($massaction == 'setstatus') {
    $formquestion = array(
        array('type' => 'select', 'name' => 'status', 'label' => $langs->trans("Status"), 'values' => array(
            0 => $langs->trans("SiteStatusDraft"),
            1 => $langs->trans("SiteStatusValidated"),
            9 => $langs->trans("SiteStatusClosed")
        ))
    );
    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassStatusChange"), $langs->trans("ConfirmMassStatusChangeQuestion", count($toselect)), 'confirm_setstatus', $formquestion, 1, 0, 200, 500, 1);
}

// Confirmation de suppression en masse
if ($massaction == 'delete') {
    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), 'confirm_delete', null, '', 1);
}

// Les champs de recherche sont dans un formulaire
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="massaction" value="">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
if (!empty($search_no_coordinates)) {
    print '<input type="hidden" name="search_no_coordinates" value="'.$search_no_coordinates.'">';
}

// Add more filter
$moreforfilter = 0;

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">';
print $langs->trans('QuickSearch') . ': <input class="flat" type="text" name="search_all" value="'.$search_all.'" size="18" placeholder="'.$langs->trans("Search").'...">';
print '</div><div class="divsearchfield">';
print $langs->trans('Status') . ': ';
print $object->selectLibStatut($search_status, 1);
print '</div>';
// Boutons de recherche et de réinitialisation des filtres
print '<div class="divsearchfield">';
print '<input type="image" class="valignmiddle" src="'.img_picto('', 'search', '', false, 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" class="valignmiddle" src="'.img_picto('', 'searchclear', '', false, 1).'" value="'.dol_escape_htmltag($langs->trans("Clear")).'" title="'.dol_escape_htmltag($langs->trans("Clear")).'" name="button_removefilter">';
print '</div>';
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">';

// Header Lines 
print '<tr class="liste_titre_filter">';
print_liste_field_titre($langs->trans("Name"), $_SERVER["PHP_SELF"], "s.label", "", "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("ThirdParty"), $_SERVER["PHP_SELF"], "s.fk_soc", "", "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Address"), $_SERVER["PHP_SELF"], "s.address", "", "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Zip"), $_SERVER["PHP_SELF"], "s.zip", "", "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Town"), $_SERVER["PHP_SELF"], "s.town", "", "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Phone"), $_SERVER["PHP_SELF"], "s.phone", "", "", "", $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "s.status", "", "", 'align="center"', $sortfield, $sortorder);
print_liste_field_titre('');
print '<td class="liste_titre">';
print '<input type="checkbox" onclick="$(\'.checkforselect\').prop(\'checked\', $(this).prop(\'checked\')); toggleGlobalCheckbox();" />';
print '</td>';
print '</tr>';

if ($num > 0) {
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);
        
        print '<tr class="oddeven">';
        
        // Nom avec lien vers la fiche
        print '<td>';
        print '<a href="site_card.php?id='.$obj->rowid.'">'.$obj->label.'</a>';
        print '</td>';
        
        // Tiers
        print '<td>';
        if ($obj->fk_soc > 0) {
            print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->fk_soc.'">'.$obj->societe_nom.'</a>';
        }
        print '</td>';
        
        // Adresse
        print '<td>'.dol_trunc($obj->address, 30).'</td>';
        
        // Code postal
        print '<td>'.$obj->zip.'</td>';
        
        // Ville
        print '<td>'.$obj->town.'</td>';
        
        // Téléphone
        print '<td>'.$obj->phone.'</td>';
        
        // Statut
        print '<td class="center nowrap">';
        $site_static = new Site($db);
        $site_static->id = $obj->rowid;
        $site_static->status = $obj->status;
        print $site_static->getLibStatut(5);
        print '</td>';
        
        // Actions
        print '<td class="nowrap center">';
        print '<a href="site_card.php?id='.$obj->rowid.'&action=edit">'.img_edit().'</a> ';
        print '<a href="site_card.php?id='.$obj->rowid.'&action=delete&token='.newToken().'">'.img_delete().'</a>';
        print '</td>';
        
        // Checkbox pour sélection multiple
        print '<td><input id="cb'.$obj->rowid.'" class="checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"></td>';
        
        print '</tr>';
        
        $i++;
    }
} else {
    print '<tr><td colspan="9" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</table>';
print '</div>';

// Bouton d'action massive en bas de la liste
if ($massactionbutton) {
    print '<div class="tabsAction">';
    print $massactionbutton;
    print '</div>';
}

print '</form>';

// Bouton pour créer un nouveau site
print '<div class="tabsAction">';
if ($user->rights->sites2->site->write) {
    print '<a class="butAction" href="'.dol_buildpath('/sites2/site_card.php', 1).'?action=create">'.$langs->trans("NewSite").'</a>';
}
print '</div>';

// Script JavaScript pour gérer la case à cocher globale
print '<script type="text/javascript">
function toggleGlobalCheckbox() {
    var allChecked = true;
    $(".checkforselect").each(function() {
        if (!$(this).prop("checked")) {
            allChecked = false;
            return false;
        }
    });
    $("input[type=checkbox]:first").prop("checked", allChecked);
}
$(document).ready(function() {
    $(".checkforselect").click(function() {
        toggleGlobalCheckbox();
    });
});
</script>';

// End of page
llxFooter();
$db->close(); 