<?php
/* Copyright (C) 2025 MATER Stéphane
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
 * \file       statistiques.php
 * \ingroup    sites2
 * \brief      Page de statistiques pour le module Sites2
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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

// Chargement des traductions
$langs->loadLangs(array('sites2@sites2'));

// Sécurité : accès réservé aux utilisateurs ayant des droits sur les sites
if (!$user->rights->sites2->site->read) {
    accessforbidden();
}

// Fonctions utilitaires pour les calculs
function getSitesWithMostEquipments($db, $limit = 5) {
    $sql = "SELECT s.rowid, s.ref, s.label, s.address, s.town,
                   (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."equipement_alarm a WHERE a.fk_site = s.rowid AND a.status = 1) +
                   (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."equipement_video v WHERE v.fk_site = s.rowid AND v.status = 1) +
                   (SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."equipement_access ac WHERE ac.fk_site = s.rowid AND ac.status = 1) as total_equipments
            FROM ".MAIN_DB_PREFIX."sites2_site s
            WHERE s.status = 1
            HAVING total_equipments > 0
            ORDER BY total_equipments DESC
            LIMIT ".$limit;
    
    $resql = $db->query($sql);
    $sites = array();
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $sites[] = array(
                'rowid' => $obj->rowid,
                'ref' => $obj->ref,
                'label' => $obj->label,
                'address' => $obj->address,
                'town' => $obj->town,
                'total_equipments' => $obj->total_equipments
            );
        }
    }
    return $sites;
}

function getSitesWithOldestEquipments($db, $limit = 5) {
    $sql = "SELECT s.rowid, s.ref, s.label, s.address, s.town,
                   COALESCE(
                       (SELECT MIN(DATEDIFF(NOW(), a.date_installation) / 365.25) 
                        FROM ".MAIN_DB_PREFIX."equipement_alarm a 
                        WHERE a.fk_site = s.rowid AND a.status = 1 AND a.date_installation IS NOT NULL AND a.date_installation != '0000-00-00'),
                       999
                   ) as min_alarm_age,
                   COALESCE(
                       (SELECT MIN(DATEDIFF(NOW(), v.date_installation) / 365.25) 
                        FROM ".MAIN_DB_PREFIX."equipement_video v 
                        WHERE v.fk_site = s.rowid AND v.status = 1 AND v.date_installation IS NOT NULL AND v.date_installation != '0000-00-00'),
                       999
                   ) as min_video_age,
                   COALESCE(
                       (SELECT MIN(DATEDIFF(NOW(), ac.date_installation) / 365.25) 
                        FROM ".MAIN_DB_PREFIX."equipement_access ac 
                        WHERE ac.fk_site = s.rowid AND ac.status = 1 AND ac.date_installation IS NOT NULL AND ac.date_installation != '0000-00-00'),
                       999
                   ) as min_access_age
            FROM ".MAIN_DB_PREFIX."sites2_site s
            WHERE s.status = 1
            AND (
                EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."equipement_alarm a WHERE a.fk_site = s.rowid AND a.status = 1) OR
                EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."equipement_video v WHERE v.fk_site = s.rowid AND v.status = 1) OR
                EXISTS (SELECT 1 FROM ".MAIN_DB_PREFIX."equipement_access ac WHERE ac.fk_site = s.rowid AND ac.status = 1)
            )
            ORDER BY LEAST(min_alarm_age, min_video_age, min_access_age) DESC
            LIMIT ".$limit;
    
    $resql = $db->query($sql);
    $sites = array();
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $oldest_age = min($obj->min_alarm_age, $obj->min_video_age, $obj->min_access_age);
            if ($oldest_age < 999) {
                $sites[] = array(
                    'rowid' => $obj->rowid,
                    'ref' => $obj->ref,
                    'label' => $obj->label,
                    'address' => $obj->address,
                    'town' => $obj->town,
                    'oldest_equipment_age' => round($oldest_age, 1)
                );
            }
        }
    }
    return $sites;
}

function getGeneralStatistics($db) {
    $stats = array();
    
    // Nombre total de sites
    $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."sites2_site";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $stats['total_sites'] = $obj->count;
    }
    
    // Nombre de sites actifs
    $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."sites2_site WHERE status = 1";
    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql) > 0) {
        $obj = $db->fetch_object($resql);
        $stats['active_sites'] = $obj->count;
    }
    
    // Nombre de sites inactifs
    $stats['inactive_sites'] = $stats['total_sites'] - $stats['active_sites'];
    
    // Nombre total d'équipements actifs
    $total_equipments = 0;
    $tables = array('equipement_alarm', 'equipement_video', 'equipement_access');
    foreach ($tables as $table) {
        $sql = "SELECT COUNT(*) as count FROM ".MAIN_DB_PREFIX."$table WHERE status = 1";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            $total_equipments += $obj->count;
        }
    }
    $stats['total_equipments'] = $total_equipments;
    
    return $stats;
}

// Calcul des statistiques
$sitesWithMostEquipments = getSitesWithMostEquipments($db, 5);
$sitesWithOldestEquipments = getSitesWithOldestEquipments($db, 5);
$generalStats = getGeneralStatistics($db);

llxHeader('', $langs->trans('StatistiquesSites'));

print '<style>
.stats-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.stats-card {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    flex: 1;
    min-width: 300px;
}

.stats-card h3 {
    margin-top: 0;
    color: #495057;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.metric {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.metric:last-child {
    border-bottom: none;
}

.metric-label {
    font-weight: 500;
    color: #495057;
}

.metric-value {
    font-size: 1.2em;
    font-weight: bold;
    color: #007bff;
}

.sites-list-container {
    display: flex;
    gap: 30px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.sites-list {
    flex: 1;
    min-width: 400px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
}

.sites-list h3 {
    margin-top: 0;
    color: #495057;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
    text-align: center;
}

.site-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #e9ecef;
}

.site-item:last-child {
    border-bottom: none;
}

.site-info {
    flex: 1;
}

.site-name {
    font-weight: bold;
    color: #495057;
    margin-bottom: 5px;
}

.site-details {
    font-size: 0.9em;
    color: #6c757d;
}

.site-metric {
    text-align: right;
    min-width: 80px;
}

.site-metric-value {
    font-size: 1.1em;
    font-weight: bold;
    color: #28a745;
}

.site-metric-label {
    font-size: 0.8em;
    color: #6c757d;
}

.no-data {
    text-align: center;
    color: #6c757d;
    font-style: italic;
    padding: 40px;
}

.site-link {
    color: #495057;
    text-decoration: none;
}

.site-link:hover {
    color: #007bff;
    text-decoration: underline;
}
</style>';

print '<div class="fichecenter">';
print '<h1><i class="fa fa-chart-pie"></i> ' . $langs->trans('StatistiquesSites') . '</h1>';
print '<div class="underbanner clearboth"></div>';

// Section des métriques générales
print '<div class="stats-container">';

print '<div class="stats-card">';
print '<h3><i class="fa fa-warehouse"></i> Statistiques Générales</h3>';

print '<div class="metric">';
print '<span class="metric-label">' . $langs->trans('NombreTotalSites') . '</span>';
print '<span class="metric-value">' . $generalStats['total_sites'] . '</span>';
print '</div>';

print '<div class="metric">';
print '<span class="metric-label">' . $langs->trans('NombreSitesActifs') . '</span>';
print '<span class="metric-value">' . $generalStats['active_sites'] . '</span>';
print '</div>';

print '<div class="metric">';
print '<span class="metric-label">' . $langs->trans('NombreSitesInactifs') . '</span>';
print '<span class="metric-value">' . $generalStats['inactive_sites'] . '</span>';
print '</div>';

print '<div class="metric">';
print '<span class="metric-label">Total équipements actifs</span>';
print '<span class="metric-value">' . $generalStats['total_equipments'] . '</span>';
print '</div>';

print '</div>';

print '</div>'; // Fin stats-container

// Section des listes de sites
print '<div class="sites-list-container">';

// Liste des sites avec le plus d'équipements
print '<div class="sites-list">';
print '<h3><i class="fa fa-trophy"></i> ' . $langs->trans('SitesAvecLePlusEquipements') . '</h3>';

if (!empty($sitesWithMostEquipments)) {
    foreach ($sitesWithMostEquipments as $site) {
        print '<div class="site-item">';
        print '<div class="site-info">';
        print '<div class="site-name">';
        print '<a href="site_card.php?id=' . $site['rowid'] . '" class="site-link">';
        print dol_escape_htmltag($site['label']);
        print '</a>';
        print '</div>';
        print '<div class="site-details">';
        if (!empty($site['ref'])) {
            print 'Réf: ' . dol_escape_htmltag($site['ref']) . ' - ';
        }
        if (!empty($site['address'])) {
            print dol_escape_htmltag($site['address']);
            if (!empty($site['town'])) {
                print ', ' . dol_escape_htmltag($site['town']);
            }
        } elseif (!empty($site['town'])) {
            print dol_escape_htmltag($site['town']);
        }
        print '</div>';
        print '</div>';
        print '<div class="site-metric">';
        print '<div class="site-metric-value">' . $site['total_equipments'] . '</div>';
        print '<div class="site-metric-label">équipements</div>';
        print '</div>';
        print '</div>';
    }
} else {
    print '<div class="no-data">' . $langs->trans('AucunSiteTrouve') . '</div>';
}

print '</div>';

// Liste des sites avec les équipements les plus anciens
print '<div class="sites-list">';
print '<h3><i class="fa fa-clock-o"></i> ' . $langs->trans('SitesAvecEquipementsLesPlusAnciens') . '</h3>';

if (!empty($sitesWithOldestEquipments)) {
    foreach ($sitesWithOldestEquipments as $site) {
        print '<div class="site-item">';
        print '<div class="site-info">';
        print '<div class="site-name">';
        print '<a href="site_card.php?id=' . $site['rowid'] . '" class="site-link">';
        print dol_escape_htmltag($site['label']);
        print '</a>';
        print '</div>';
        print '<div class="site-details">';
        if (!empty($site['ref'])) {
            print 'Réf: ' . dol_escape_htmltag($site['ref']) . ' - ';
        }
        if (!empty($site['address'])) {
            print dol_escape_htmltag($site['address']);
            if (!empty($site['town'])) {
                print ', ' . dol_escape_htmltag($site['town']);
            }
        } elseif (!empty($site['town'])) {
            print dol_escape_htmltag($site['town']);
        }
        print '</div>';
        print '</div>';
        print '<div class="site-metric">';
        print '<div class="site-metric-value">' . $site['oldest_equipment_age'] . '</div>';
        print '<div class="site-metric-label">ans (plus ancien)</div>';
        print '</div>';
        print '</div>';
    }
} else {
    print '<div class="no-data">' . $langs->trans('AucunEquipementTrouve') . '</div>';
}

print '</div>';

print '</div>'; // Fin sites-list-container

print '</div>'; // Fin fichecenter

llxFooter(); 