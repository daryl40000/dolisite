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
 * \file    sites2/admin/contact_roles.php
 * \ingroup sites2
 * \brief   Page to configure contact roles for Sites2 module
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
$langs->loadLangs(array("admin", "sites2@sites2", "companies"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$rowid = GETPOST('rowid', 'int');
$code = GETPOST('code', 'alpha');
$libelle = GETPOST('libelle', 'alpha');
$module = 'sites2';
$element = 'site';

/*
 * Actions
 */

// Add or update role
if ($action == 'add' || $action == 'update') {
    $code = GETPOST('code', 'alpha');
    $libelle = GETPOST('libelle', 'alpha');
    $position = GETPOST('position', 'int');
    $module = 'sites2';
    $element = 'site';
    $active = GETPOST('active', 'int') ? 1 : 0;
    $source = GETPOST('source', 'alpha') ? GETPOST('source', 'alpha') : 'external';
    
    $error = 0;
    
    if (empty($code)) {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Code")), null, 'errors');
        $error++;
    }
    
    if (empty($libelle)) {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
        $error++;
    }
    
    if (!$error) {
        if ($action == 'add') {
            // Vérifier si le code existe déjà
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact ";
            $sql .= "WHERE element = '".$db->escape($element)."' AND code = '".$db->escape($code)."'";
            $resql = $db->query($sql);
            
            if ($resql && $db->num_rows($resql) > 0) {
                setEventMessages($langs->trans("ErrorCodeAlreadyExists", $code), null, 'errors');
                $error++;
            }
            $db->free($resql);
        }
    }
    
    if (!$error) {
        if ($action == 'add') {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_type_contact ";
            $sql .= "(element, source, code, libelle, position, active) ";
            $sql .= "VALUES ('".$db->escape($element)."', '".$db->escape($source)."', ";
            $sql .= "'".$db->escape($code)."', '".$db->escape($libelle)."', ";
            $sql .= $position.", ".$active.")";
            
            $resql = $db->query($sql);
            if (!$resql) {
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                setEventMessages($langs->trans("RoleAdded"), null);
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            }
        } elseif ($action == 'update') {
            $rowid = GETPOST('rowid', 'int');
            
            $sql = "UPDATE ".MAIN_DB_PREFIX."c_type_contact SET ";
            $sql .= "code = '".$db->escape($code)."', ";
            $sql .= "libelle = '".$db->escape($libelle)."', ";
            $sql .= "position = ".$position.", ";
            $sql .= "active = ".$active." ";
            $sql .= "WHERE rowid = ".$rowid;
            
            $resql = $db->query($sql);
            if (!$resql) {
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                setEventMessages($langs->trans("RoleUpdated"), null);
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

// Delete role
if ($action == 'delete') {
    $rowid = GETPOST('rowid', 'int');
    
    // Vérifier s'il y a des enregistrements liés à ce type de contact
    $sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."element_contact ";
    $sql .= "WHERE fk_c_type_contact = ".$rowid;
    $resql = $db->query($sql);
    
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj->nb > 0) {
            setEventMessages($langs->trans("ErrorRoleInUse"), null, 'errors');
        } else {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."c_type_contact ";
            $sql .= "WHERE rowid = ".$rowid;
            $resql = $db->query($sql);
            
            if (!$resql) {
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                setEventMessages($langs->trans("RoleDeleted"), null);
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            }
        }
    } else {
        setEventMessages($db->lasterror(), null, 'errors');
    }
}

/*
 * View
 */

$page_name = "ContactRolesSetup";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'contact_roles', $langs->trans("Module641444Name"), -1, "sites2@sites2");

// Introduction
print '<div class="info">';
print '<p>' . $langs->trans("ContactRolesDescription") . '</p>';
print '</div>';

// List of existing roles
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Code") . '</td>';
print '<td>' . $langs->trans("Label") . '</td>';
print '<td>' . $langs->trans("Position") . '</td>';
print '<td class="center">' . $langs->trans("Active") . '</td>';
print '<td class="right">' . $langs->trans("Actions") . '</td>';
print '</tr>';

$sql = "SELECT rowid, code, libelle, position, active ";
$sql .= "FROM ".MAIN_DB_PREFIX."c_type_contact ";
$sql .= "WHERE element = 'site' ";
$sql .= "ORDER BY position";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;
    $var = true;
    
    if ($num > 0) {
        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            
            print '<tr class="oddeven">';
            print '<td>' . $obj->code . '</td>';
            print '<td>' . $obj->libelle . '</td>';
            print '<td class="center">' . $obj->position . '</td>';
            print '<td class="center">' . yn($obj->active) . '</td>';
            print '<td class="right">';
            print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=edit&rowid='.$obj->rowid.'">'.img_edit().'</a> &nbsp; ';
            print '<a class="paddingleft" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&rowid='.$obj->rowid.'">'.img_delete().'</a>';
            print '</td>';
            print '</tr>';
            $i++;
        }
    } else {
        print '<tr><td colspan="5" class="opacitymedium">' . $langs->trans("NoRolesDefined") . '</td></tr>';
    }
    $db->free($resql);
} else {
    dol_print_error($db);
}

print '</table>';
print '</div>';

// Form to add/edit a role
print '<br>';
print load_fiche_titre($action == 'edit' ? $langs->trans("EditContactRole") : $langs->trans("NewContactRole"), '', '');

if ($action == 'edit') {
    // Get role data
    $sql = "SELECT code, libelle, position, active, source ";
    $sql .= "FROM ".MAIN_DB_PREFIX."c_type_contact ";
    $sql .= "WHERE rowid = ".$rowid;
    
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $code = $obj->code;
        $libelle = $obj->libelle;
        $position = $obj->position;
        $active = $obj->active;
        $source = $obj->source;
    }
    $db->free($resql);
}

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.($action == 'edit' ? 'update' : 'add').'">';
if ($action == 'edit') {
    print '<input type="hidden" name="rowid" value="'.$rowid.'">';
}

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . ($action == 'edit' ? $langs->trans("ModifyContactRole") : $langs->trans("AddContactRole")) . '</td>';
print '</tr>';

// Code
print '<tr class="oddeven">';
print '<td class="fieldrequired">' . $langs->trans("Code") . '</td>';
print '<td><input type="text" name="code" value="' . $code . '" size="20" maxlength="32"></td>';
print '</tr>';

// Label
print '<tr class="oddeven">';
print '<td class="fieldrequired">' . $langs->trans("Label") . '</td>';
print '<td><input type="text" name="libelle" value="' . $libelle . '" size="40" maxlength="64"></td>';
print '</tr>';

// Position
print '<tr class="oddeven">';
print '<td>' . $langs->trans("Position") . '</td>';
print '<td><input type="text" name="position" value="' . ($position ? $position : '0') . '" size="5" maxlength="10"></td>';
print '</tr>';

// Active
print '<tr class="oddeven">';
print '<td>' . $langs->trans("Active") . '</td>';
print '<td><input type="checkbox" name="active" value="1" ' . ($active == 1 ? 'checked' : '') . '></td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="' . ($action == 'edit' ? $langs->trans("Save") : $langs->trans("Add")) . '">';
print '</div>';

print '</form>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close(); 