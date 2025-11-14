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
 * \file    sites2/admin/optimize.php
 * \ingroup sites2
 * \brief   Page pour optimiser les performances du module
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

global $langs, $user, $conf, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
dol_include_once('/sites2/lib/sites2.lib.php');
dol_include_once('/sites2/class/site.class.php');

// Traductions
$langs->loadLangs(array("admin", "sites2@sites2"));

// Contrôle d'accès - Réservé aux administrateurs
if (!$user->admin) {
	accessforbidden();
}

// Paramètres
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

if ($action == 'update_indexes') {
    // Mise à jour des index de la base de données
    
    $indexes = array(
        "ALTER TABLE ".MAIN_DB_PREFIX."sites2_site ADD INDEX idx_sites2_site_rowid (rowid)",
        "ALTER TABLE ".MAIN_DB_PREFIX."sites2_site ADD INDEX idx_sites2_site_ref (ref)",
        "ALTER TABLE ".MAIN_DB_PREFIX."sites2_site ADD INDEX idx_sites2_site_label (label)",
        "ALTER TABLE ".MAIN_DB_PREFIX."sites2_site ADD INDEX idx_sites2_site_fk_soc (fk_soc)",
        "ALTER TABLE ".MAIN_DB_PREFIX."sites2_site ADD INDEX idx_sites2_site_status (status)",
        "ALTER TABLE ".MAIN_DB_PREFIX."sites2_site ADD INDEX idx_sites2_site_town (town)"
    );
    
    $success = true;
    $error_messages = array();
    
    foreach ($indexes as $sql) {
        $result = $db->query($sql);
        if (!$result) {
            // Vérifier si l'erreur est due au fait que l'index existe déjà
            if (strpos($db->lasterror(), 'Duplicate key name') !== false || 
                strpos($db->lasterror(), 'already exists') !== false) {
                // C'est normal, on continue
            } else {
                $success = false;
                $error_messages[] = $db->lasterror();
            }
        }
    }
    
    if ($success) {
        setEventMessages($langs->trans("IndexesUpdated"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("ErrorUpdatingIndexes"), $error_messages, 'errors');
    }
}

if ($action == 'optimize_table') {
    // Optimisation de la table
    $sql = "OPTIMIZE TABLE ".MAIN_DB_PREFIX."sites2_site";
    $result = $db->query($sql);
    
    if ($result) {
        setEventMessages($langs->trans("TableOptimized"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("ErrorOptimizingTable"), array($db->lasterror()), 'errors');
    }
}

/*
 * View
 */

$page_name = "PerformanceOptimization";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

// Sous-en-tête
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// En-tête de configuration
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'optimize', $langs->trans("ModuleSites2Name"), -1, 'sites2@sites2');

// Introduction
print '<div class="opacitymedium">';
print $langs->trans("PerformanceOptimizationDescription");
print '</div><br>';

// Optimisation des index
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("IndexOptimization").'</td>';
print '<td>'.$langs->trans("Action").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("UpdateDatabaseIndexes").'<br>';
print '<span class="opacitymedium">'.$langs->trans("UpdateDatabaseIndexesDescription").'</span>';
print '</td>';
print '<td>';
print '<a class="button" href="'.$_SERVER["PHP_SELF"].'?action=update_indexes&token='.newToken().'">'.$langs->trans("UpdateIndexes").'</a>';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("OptimizeTable").'<br>';
print '<span class="opacitymedium">'.$langs->trans("OptimizeTableDescription").'</span>';
print '</td>';
print '<td>';
print '<a class="button" href="'.$_SERVER["PHP_SELF"].'?action=optimize_table&token='.newToken().'">'.$langs->trans("OptimizeTable").'</a>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

// Fin de page
print dol_get_fiche_end();
llxFooter();
$db->close(); 