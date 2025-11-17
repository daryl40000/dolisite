<?php
/* Copyright (C) 2023-2024 Module Sites2
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
 * \file    sites2/admin/about.php
 * \ingroup sites2
 * \brief   About page of module Sites2
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
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

/*
 * View
 */

$form = new Form($db);

$page_name = "Sites2About";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans("Module641444Name"), -1, "sites2@sites2");

// About page goes here
print '<div style="float: left; margin-right: 30px;">';
print '<img src="'.dol_buildpath('/sites2/img/sites2.png', 1).'" alt="Logo Sites2" style="height:180px;">';
print '</div>';

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<div class="fichehalfleft">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="titlefield" width="25%">'.$langs->trans("Info").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Module
print '<tr>';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>Sites clients</td>';
print '</tr>';

// Version
print '<tr>';
print '<td>'.$langs->trans("Version").'</td>';
print '<td>2.3.4</td>';
print '</tr>';

// Author
print '<tr>';
print '<td>'.$langs->trans("Author").'</td>';
print '<td>MATER Stéphane</td>';
print '</tr>';

print '</table>';
print '</div>';

print '</div>';
print '<div class="fichehalfright">';

// Description
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield" width="25%">'.$langs->trans("Description").'</td>';
print '<td></td>';
print '</tr>';

print '<tr>';
print '<td colspan="2">';
print $langs->trans("Sites2Description");
print '<br><br>';
print $langs->trans("Sites2Features");
print '<ul>';
print '<li>'.$langs->trans("Sites2Feature1").'</li>';
print '<li>'.$langs->trans("Sites2Feature2").'</li>';
print '<li>'.$langs->trans("Sites2Feature3").'</li>';
print '</ul>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '</div>';
print '</div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close(); 