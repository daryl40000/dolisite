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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    sites2/admin/export.php
 * \ingroup sites2
 * \brief   Page to export sites
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
dol_include_once('/sites2/lib/sites2.lib.php');
dol_include_once('/sites2/class/site.class.php');

// Translations
$langs->loadLangs(array("admin", "sites2@sites2"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$format = GETPOST('format', 'alpha');

/*
 * Actions
 */

if ($action == 'export' && !empty($format)) {
	$sql = "SELECT s.rowid, s.ref, s.label, s.description, s.address, s.zip, s.town, s.phone, s.status, s.latitude, s.longitude, 
	        s.distance_km, s.travel_time, s.fk_soc, soc.nom as socname, s.date_creation, s.tms as date_modification, u.login
			FROM ".MAIN_DB_PREFIX."sites2_site as s
			LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON s.fk_soc = soc.rowid
			LEFT JOIN ".MAIN_DB_PREFIX."user as u ON s.fk_user_creat = u.rowid
			ORDER BY s.label ASC";
			
	$resql = $db->query($sql);
	
	if ($resql) {
		$num = $db->num_rows($resql);
		
		// Définir l'en-tête du fichier
		$headers = array(
			'Id' => $langs->trans('Id'),
			'Ref' => $langs->trans('Ref'),
			'Label' => $langs->trans('Label'),
			'Description' => $langs->trans('Description'),
			'Address' => $langs->trans('Address'),
			'Zip' => $langs->trans('Zip'),
			'Town' => $langs->trans('Town'),
			'Phone' => $langs->trans('Phone'),
			'Status' => $langs->trans('Status'),
			'Latitude' => $langs->trans('Latitude'),
			'Longitude' => $langs->trans('Longitude'),
			'DistanceKm' => $langs->trans('DistanceKm'),
			'TravelTime' => $langs->trans('TravelTime'),
			'ThirdPartyId' => $langs->trans('ThirdPartyId'),
			'ThirdPartyName' => $langs->trans('ThirdPartyName'),
			'DateCreation' => $langs->trans('DateCreation'),
			'DateModification' => $langs->trans('DateModification'),
			'CreatedBy' => $langs->trans('CreatedBy')
		);
		
		$data = array();
		while ($obj = $db->fetch_object($resql)) {
			$status = '';
			if ($obj->status == 0) {
				$status = $langs->trans('SiteStatusDraft');
			} elseif ($obj->status == 1) {
				$status = $langs->trans('SiteStatusValidated');
			} elseif ($obj->status == 9) {
				$status = $langs->trans('SiteStatusClosed');
			}
			
			$row = array(
				'Id' => $obj->rowid,
				'Ref' => $obj->ref,
				'Label' => $obj->label,
				'Description' => $obj->description,
				'Address' => $obj->address,
				'Zip' => $obj->zip,
				'Town' => $obj->town,
				'Phone' => $obj->phone,
				'Status' => $status,
				'Latitude' => $obj->latitude,
				'Longitude' => $obj->longitude,
				'DistanceKm' => $obj->distance_km,
				'TravelTime' => $obj->travel_time,
				'ThirdPartyId' => $obj->fk_soc,
				'ThirdPartyName' => $obj->socname,
				'DateCreation' => dol_print_date($db->jdate($obj->date_creation), 'dayhour'),
				'DateModification' => dol_print_date($db->jdate($obj->date_modification), 'dayhour'),
				'CreatedBy' => $obj->login
			);
			
			$data[] = $row;
		}
		
		// Générer le fichier d'export au format CSV
		if ($format == 'csv') {
			$outputFile = DOL_DATA_ROOT.'/sites2/temp/export_sites_'.dol_print_date(dol_now(), 'dayhourlog').'.csv';
			$fp = fopen($outputFile, 'w');
			
			// Écrire l'en-tête
			fputcsv($fp, array_values($headers), ';');
			
			// Écrire les données
			foreach ($data as $row) {
				fputcsv($fp, array_values($row), ';');
			}
			
			fclose($fp);
			
			// Télécharger le fichier
			$filename = 'export_sites_'.dol_print_date(dol_now(), 'dayhourlog').'.csv';
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment; filename='.$filename);
			header('Pragma: no-cache');
			readfile($outputFile);
			
			exit;
		} 
		// Générer le fichier d'export au format Excel (CSV avec entêtes différentes)
		elseif ($format == 'excel') {
			$outputFile = DOL_DATA_ROOT.'/sites2/temp/export_sites_'.dol_print_date(dol_now(), 'dayhourlog').'.csv';
			$fp = fopen($outputFile, 'w');
			
			// BOM UTF-8 pour Excel
			fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
			
			// Écrire l'en-tête
			fputcsv($fp, array_values($headers), ';');
			
			// Écrire les données
			foreach ($data as $row) {
				fputcsv($fp, array_values($row), ';');
			}
			
			fclose($fp);
			
			// Télécharger le fichier
			$filename = 'export_sites_'.dol_print_date(dol_now(), 'dayhourlog').'.csv';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename='.$filename);
			header('Pragma: no-cache');
			readfile($outputFile);
			
			exit;
		}
	} else {
		setEventMessages($db->lasterror(), null, 'errors');
	}
}

/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans("ExportSites"), $help_url);

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("ExportSites"), $linkback, 'title_setup');

// Configuration header
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'export', $langs->trans("ModuleSites2Name"), -1, "sites2@sites2");

// Explications sur l'export
print '<div class="opacitymedium">';
print $langs->trans("ExportSitesDescription");
print '</div><br>';

// Formulaire d'export
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="export">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Format d'export
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ExportFormat").'</td>';
print '<td>';
print '<select name="format" class="flat minwidth200">';
print '<option value="csv">'.$langs->trans("CSV").'</option>';
print '<option value="excel">'.$langs->trans("Excel").'</option>';
print '</select>';
print '</td>';
print '</tr>';

print '</table>';

print '<br>';
print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("ExportSitesList").'">';
print '</div>';

print '</form>';

// Fin de page
print dol_get_fiche_end();
llxFooter();
$db->close(); 