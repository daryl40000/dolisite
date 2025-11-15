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
 *  \file       chantier_list.php
 *  \ingroup    sites2
 *  \brief      Liste des chantiers programmés et devis signés non liés
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
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
dol_include_once('/sites2/lib/sites2.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("sites2@sites2", "propal", "other"));

$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'chantierlist';

// Action pour affecter un site à un devis
if ($action == 'assign_site' && $user->rights->sites2->site->write) {
	$fk_propal = GETPOST('fk_propal', 'int');
	$fk_site = GETPOST('fk_site', 'int');
	
	if ($fk_propal > 0 && $fk_site > 0) {
		// Vérifier que le devis est signé
		$sql_propal = "SELECT fk_statut, fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE rowid = ".((int)$fk_propal);
		$resql_propal = $db->query($sql_propal);
		if ($resql_propal) {
			$obj_propal = $db->fetch_object($resql_propal);
			if ($obj_propal && $obj_propal->fk_statut == 2) {
				// Vérifier que le site appartient au même tiers que le devis
				$sql_site = "SELECT fk_soc FROM ".MAIN_DB_PREFIX."sites2_site WHERE rowid = ".((int)$fk_site);
				$resql_site = $db->query($sql_site);
				if ($resql_site) {
					$obj_site = $db->fetch_object($resql_site);
					if ($obj_site) {
						// Vérifier que le devis n'est pas déjà lié à un chantier
						$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."sites2_chantier WHERE fk_propal = ".((int)$fk_propal);
						$resql_check = $db->query($sql_check);
						if ($resql_check && $db->num_rows($resql_check) == 0) {
							// Créer le chantier programmé (on accepte même si le site n'appartient pas au même tiers)
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."sites2_chantier (fk_site, fk_propal, date_debut, date_fin, location_type, date_creation, fk_user_creat, entity)";
							$sql .= " VALUES (".((int)$fk_site).", ".((int)$fk_propal).", NULL, NULL, 1, NOW(), ".((int)$user->id).", ".((int)$conf->entity).")";
							
							$resql = $db->query($sql);
							if ($resql) {
								setEventMessages($langs->trans("ScheduledWorkSiteAdded"), null, 'mesgs');
								header("Location: ".$_SERVER["PHP_SELF"]);
								exit;
							} else {
								setEventMessages("Erreur lors de la création du chantier: ".$db->lasterror(), null, 'errors');
							}
						} else {
							setEventMessages($langs->trans("ErrorProposalAlreadyLinked"), null, 'errors');
						}
					}
				}
			} else {
				setEventMessages($langs->trans("ErrorProposalNotSigned"), null, 'errors');
			}
		}
	}
}

// Security check
if (!$user->rights->sites2->site->read) accessforbidden();

// Paramètres de pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma') ? GETPOST('sortfield', 'aZ09comma') : 'date_debut';
$sortorder = GETPOST('sortorder', 'aZ09comma') ? GETPOST('sortorder', 'aZ09comma') : 'DESC';
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
    $page = 0;
}
$offset = $limit * $page;

// Initialize search variables
$search_all = GETPOST('search_all', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');
$search_propal = GETPOST('search_propal', 'alpha');
$search_site = GETPOST('search_site', 'alpha');

// Initialize technical objects
$object = new Site($db);
$form = new Form($db);
$propal = new Propal($db);

// Build SQL for scheduled worksites (with dates)
$sql_chantiers = "SELECT DISTINCT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public,";
$sql_chantiers .= " p.ref as propal_ref, p.fk_statut as propal_status, p.datep as date_propal, p.note_public as propal_note_public,";
$sql_chantiers .= " s.rowid as site_id, s.label as site_label, s.ref as site_ref, s.latitude, s.longitude,";
$sql_chantiers .= " soc.rowid as soc_id, soc.nom as soc_nom";
$sql_chantiers .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
$sql_chantiers .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
$sql_chantiers .= " LEFT JOIN ".MAIN_DB_PREFIX."sites2_site as s ON s.rowid = c.fk_site";
$sql_chantiers .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
// Filter by entity - use conf->entity directly for simplicity
$entity_filter = $conf->entity;
$sql_chantiers .= " WHERE c.entity = ".((int)$entity_filter);
$sql_chantiers .= " AND c.date_debut IS NOT NULL"; // Only worksites with dates

// Build SQL for assigned worksites (without dates)
$sql_chantiers_affectes = "SELECT DISTINCT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public,";
$sql_chantiers_affectes .= " p.ref as propal_ref, p.fk_statut as propal_status, p.datep as date_propal, p.note_public as propal_note_public,";
$sql_chantiers_affectes .= " s.rowid as site_id, s.label as site_label, s.ref as site_ref, s.latitude, s.longitude,";
$sql_chantiers_affectes .= " soc.rowid as soc_id, soc.nom as soc_nom";
$sql_chantiers_affectes .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
$sql_chantiers_affectes .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
$sql_chantiers_affectes .= " LEFT JOIN ".MAIN_DB_PREFIX."sites2_site as s ON s.rowid = c.fk_site";
$sql_chantiers_affectes .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql_chantiers_affectes .= " WHERE c.entity = ".((int)$entity_filter);
$sql_chantiers_affectes .= " AND c.date_debut IS NULL"; // Only worksites without dates

// Build SQL for signed proposals not linked to a site
$sql_propals = "SELECT p.rowid, p.ref, p.fk_statut, p.datep as date_propal, p.total_ht, p.total_ttc, p.note_public,";
$sql_propals .= " soc.rowid as soc_id, soc.nom as soc_nom";
$sql_propals .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql_propals .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = p.fk_soc";
// Filter by entity for proposals - use conf->entity directly for simplicity
$entity_filter_propal = $conf->entity;
$sql_propals .= " LEFT JOIN ".MAIN_DB_PREFIX."sites2_chantier as c ON c.fk_propal = p.rowid AND c.entity = ".((int)$entity_filter_propal);
$sql_propals .= " WHERE p.fk_statut = 2"; // Only Signed (2), not Validated (1)
$sql_propals .= " AND p.entity = ".((int)$entity_filter_propal);
$sql_propals .= " AND c.rowid IS NULL"; // Not linked to a worksite

// Récupérer tous les sites existants UNE SEULE FOIS avant la boucle
// Note: La table sites2_site n'a pas de colonne entity, donc pas de filtre d'entité
$sql_sites_all = "SELECT s.rowid, s.ref, s.label, s.fk_soc, soc.nom as soc_nom";
$sql_sites_all .= " FROM ".MAIN_DB_PREFIX."sites2_site as s";
$sql_sites_all .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql_sites_all .= " WHERE 1 = 1";
$sql_sites_all .= " ORDER BY soc.nom, s.label";
$res_sites_all = $db->query($sql_sites_all);

// Stocker tous les sites dans un tableau pour réutilisation
$all_sites = array();
if ($res_sites_all) {
	if ($db->num_rows($res_sites_all) > 0) {
		$num_sites_all = $db->num_rows($res_sites_all);
		$j = 0;
		while ($j < $num_sites_all) {
			$obj_site = $db->fetch_object($res_sites_all);
			$all_sites[] = $obj_site;
			$j++;
		}
	} else {
		dol_syslog(__METHOD__.": Aucun site trouvé dans la base. Requête: ".$sql_sites_all, LOG_WARNING);
	}
} else {
	dol_syslog(__METHOD__.": Erreur SQL pour récupérer les sites: ".$db->lasterror(), LOG_ERR);
	dol_syslog(__METHOD__.": Requête: ".$sql_sites_all, LOG_ERR);
}

// Apply search filters
if (!empty($search_all)) {
	$sql_chantiers .= " AND (p.ref LIKE '%".$db->escape($search_all)."%'";
	$sql_chantiers .= " OR s.label LIKE '%".$db->escape($search_all)."%'";
	$sql_chantiers .= " OR s.ref LIKE '%".$db->escape($search_all)."%'";
	$sql_chantiers .= " OR soc.nom LIKE '%".$db->escape($search_all)."%')";
	
	$sql_propals .= " AND (p.ref LIKE '%".$db->escape($search_all)."%'";
	$sql_propals .= " OR soc.nom LIKE '%".$db->escape($search_all)."%')";
}

if (!empty($search_societe)) {
	$sql_chantiers .= " AND soc.nom LIKE '%".$db->escape($search_societe)."%'";
	$sql_propals .= " AND soc.nom LIKE '%".$db->escape($search_societe)."%'";
}

if (!empty($search_propal)) {
	$sql_chantiers .= " AND p.ref LIKE '%".$db->escape($search_propal)."%'";
	$sql_propals .= " AND p.ref LIKE '%".$db->escape($search_propal)."%'";
}

if (!empty($search_site)) {
	$sql_chantiers .= " AND (s.label LIKE '%".$db->escape($search_site)."%' OR s.ref LIKE '%".$db->escape($search_site)."%')";
	$sql_chantiers_affectes .= " AND (s.label LIKE '%".$db->escape($search_site)."%' OR s.ref LIKE '%".$db->escape($search_site)."%')";
}

// Apply search filters to assigned worksites
if (!empty($search_all)) {
	$sql_chantiers_affectes .= " AND (p.ref LIKE '%".$db->escape($search_all)."%'";
	$sql_chantiers_affectes .= " OR s.label LIKE '%".$db->escape($search_all)."%'";
	$sql_chantiers_affectes .= " OR s.ref LIKE '%".$db->escape($search_all)."%'";
	$sql_chantiers_affectes .= " OR soc.nom LIKE '%".$db->escape($search_all)."%')";
}

if (!empty($search_societe)) {
	$sql_chantiers_affectes .= " AND soc.nom LIKE '%".$db->escape($search_societe)."%'";
}

if (!empty($search_propal)) {
	$sql_chantiers_affectes .= " AND p.ref LIKE '%".$db->escape($search_propal)."%'";
}

// Add sorting - Trier par date de début (les plus proches en premier)
$sql_chantiers .= " ORDER BY c.date_debut ASC, c.date_creation DESC";
$sql_chantiers_affectes .= " ORDER BY c.date_creation DESC";
$sql_propals .= " ORDER BY p.datep DESC";

// Execute queries
$resql_chantiers = $db->query($sql_chantiers);
if (!$resql_chantiers) {
	setEventMessages("Erreur SQL pour les chantiers programmés: ".$db->lasterror(), null, 'errors');
	$num_chantiers = 0;
	$chantiers_data = array();
} else {
	$num_chantiers = $db->num_rows($resql_chantiers);
	// Stocker les résultats dans un tableau pour réutilisation
	$chantiers_data = array();
	$i = 0;
	while ($i < $num_chantiers) {
		$obj = $db->fetch_object($resql_chantiers);
		$chantiers_data[] = $obj;
		$i++;
	}
}

$resql_chantiers_affectes = $db->query($sql_chantiers_affectes);
if (!$resql_chantiers_affectes) {
	setEventMessages("Erreur SQL pour les chantiers affectés: ".$db->lasterror(), null, 'errors');
	$num_chantiers_affectes = 0;
	$chantiers_affectes_data = array();
} else {
	$num_chantiers_affectes = $db->num_rows($resql_chantiers_affectes);
	// Stocker les résultats dans un tableau pour réutilisation
	$chantiers_affectes_data = array();
	$i = 0;
	while ($i < $num_chantiers_affectes) {
		$obj = $db->fetch_object($resql_chantiers_affectes);
		$chantiers_affectes_data[] = $obj;
		$i++;
	}
}

$resql_propals = $db->query($sql_propals);
if (!$resql_propals) {
	setEventMessages("Erreur SQL pour les devis signés: ".$db->lasterror(), null, 'errors');
	$num_propals = 0;
} else {
	$num_propals = $db->num_rows($resql_propals);
}

if (!empty($conf->global->MAIN_FEATURES_LEVEL) && $conf->global->MAIN_FEATURES_LEVEL >= 2) {
	dol_syslog("chantier_list.php - Query chantiers: ".$sql_chantiers, LOG_DEBUG);
	dol_syslog("chantier_list.php - Query propals: ".$sql_propals, LOG_DEBUG);
	dol_syslog("chantier_list.php - Found ".$num_chantiers." scheduled worksites and ".$num_propals." signed proposals", LOG_DEBUG);
}

// Page title
$title = $langs->trans("ScheduledWorkSites");
llxHeader('', $title);

// Search form
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="search">';

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">';
print $langs->trans('QuickSearch') . ': <input class="flat" type="text" name="search_all" value="'.dol_escape_htmltag($search_all).'" size="18" placeholder="'.$langs->trans("Search").'...">';
print '</div>';
print '<div class="divsearchfield">';
print $langs->trans('ThirdParty') . ': <input class="flat" type="text" name="search_societe" value="'.dol_escape_htmltag($search_societe).'" size="15">';
print '</div>';
print '<div class="divsearchfield">';
print $langs->trans('Proposal') . ': <input class="flat" type="text" name="search_propal" value="'.dol_escape_htmltag($search_propal).'" size="15">';
print '</div>';
print '<div class="divsearchfield">';
print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
print ' <a href="'.$_SERVER["PHP_SELF"].'" class="button">'.$langs->trans("Clear").'</a>';
print '</div>';
print '</div>';
print '</form>';

// Section 0: Carte des chantiers
// Récupérer les données pour la carte (chantiers avec coordonnées)
$map_chantiers = array();
$map_chantiers_affectes = array();

// Récupérer les chantiers à venir avec coordonnées
if (!empty($chantiers_data)) {
	foreach ($chantiers_data as $obj) {
		if (!empty($obj->latitude) && !empty($obj->longitude)) {
			$date_debut_ts = !empty($obj->date_debut) ? $db->jdate($obj->date_debut) : null;
			$date_fin_ts = !empty($obj->date_fin) ? $db->jdate($obj->date_fin) : null;
			$date_debut_str = $date_debut_ts ? date('Y-m-d', $date_debut_ts) : '';
			$date_fin_str = $date_fin_ts ? date('Y-m-d', $date_fin_ts) : '';
			$date_debut_display = $date_debut_ts ? dol_print_date($date_debut_ts, 'day') : '';
			$date_fin_display = $date_fin_ts ? dol_print_date($date_fin_ts, 'day') : '';
			
			$map_chantiers[] = array(
				'latitude' => floatval($obj->latitude),
				'longitude' => floatval($obj->longitude),
				'site_label' => $obj->site_label,
				'site_id' => $obj->site_id,
				'propal_ref' => $obj->propal_ref,
				'propal_id' => $obj->fk_propal,
				'soc_nom' => $obj->soc_nom,
				'soc_id' => $obj->soc_id,
				'date_debut' => $date_debut_display,
				'date_fin' => $date_fin_display,
				'location_type' => isset($obj->location_type) ? (int)$obj->location_type : 1
			);
		}
	}
}

// Récupérer les chantiers affectés avec coordonnées
if (!empty($chantiers_affectes_data)) {
	foreach ($chantiers_affectes_data as $obj) {
		if (!empty($obj->latitude) && !empty($obj->longitude)) {
			$map_chantiers_affectes[] = array(
				'latitude' => floatval($obj->latitude),
				'longitude' => floatval($obj->longitude),
				'site_label' => $obj->site_label,
				'site_id' => $obj->site_id,
				'propal_ref' => $obj->propal_ref,
				'propal_id' => $obj->fk_propal,
				'soc_nom' => $obj->soc_nom,
				'soc_id' => $obj->soc_id,
				'location_type' => isset($obj->location_type) ? (int)$obj->location_type : 1
			);
		}
	}
}

// Afficher la carte si au moins un chantier a des coordonnées
if (count($map_chantiers) > 0 || count($map_chantiers_affectes) > 0) {
	print '<div class="div-table-responsive" style="margin-bottom: 30px;">';
	print '<h3>'.$langs->trans("MapOfScheduledWorkSites").'</h3>';
	
	// Déterminer le fournisseur de cartes
	$mapProvider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
	$googleMapsApiKey = !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY) ? $conf->global->SITES2_GOOGLE_MAPS_API_KEY : '';
	
	// Charger les scripts et styles Leaflet
	print '<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>';
	print '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin="" />';
	
	print '<div id="map_chantiers" style="height: 350px; width: 100%; border: 1px solid #ddd; border-radius: 5px;"></div>';
	
	print '<script type="text/javascript">
		var map_chantiers = L.map("map_chantiers");
		var markers = [];
		var bounds = null;';
	
	// Ajouter le fond de carte
	if ($mapProvider == 'googlemaps' && !empty($googleMapsApiKey)) {
		print '
		L.tileLayer("https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}", {
			subdomains: ["mt0", "mt1", "mt2", "mt3"],
			attribution: "© Google Maps",
			minZoom: 1,
			maxZoom: 20
		}).addTo(map_chantiers);';
	} else {
		print '
		L.tileLayer("https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png", {
			attribution: \'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu <a href="//openstreetmap.fr">OSM France</a>\',
			minZoom: 1,
			maxZoom: 20
		}).addTo(map_chantiers);';
	}
	
	// Ajouter les marqueurs pour les chantiers à venir
	if (count($map_chantiers) > 0) {
		print '
		// Marqueurs pour les chantiers à venir (avec dates)
		var chantiersData = ' . json_encode($map_chantiers) . ';
		chantiersData.forEach(function(chantier) {
			var popupContent = "<h4>" + ' . json_encode($langs->trans("ScheduledWorkSite")) . ' + "</h4>";
			popupContent += "<strong>" + ' . json_encode($langs->trans("Site")) . ' + ":</strong> <a href=\\"site_card.php?id=" + chantier.site_id + "\\">" + chantier.site_label + "</a><br>";
			if (chantier.propal_ref) {
				popupContent += "<strong>" + ' . json_encode($langs->trans("Proposal")) . ' + ":</strong> <a href=\\"' . DOL_URL_ROOT . '/comm/propal/card.php?id=" + chantier.propal_id + "\\">" + chantier.propal_ref + "</a><br>";
			}
			if (chantier.soc_nom) {
				popupContent += "<strong>" + ' . json_encode($langs->trans("ThirdParty")) . ' + ":</strong> <a href=\\"' . DOL_URL_ROOT . '/societe/card.php?socid=" + chantier.soc_id + "\\">" + chantier.soc_nom + "</a><br>";
			}
			if (chantier.date_debut) {
				popupContent += "<strong>" + ' . json_encode($langs->trans("Date")) . ' + ":</strong> " + chantier.date_debut;
				if (chantier.date_fin && chantier.date_fin != chantier.date_debut) {
					popupContent += " - " + chantier.date_fin;
				}
				popupContent += "<br>";
			}
			popupContent += "<strong>" + ' . json_encode($langs->trans("WorkLocationType")) . ' + ":</strong> " + (chantier.location_type == 0 ? ' . json_encode($langs->trans("Interior")) . ' : ' . json_encode($langs->trans("Exterior")) . ');
			
			var marker = L.marker([chantier.latitude, chantier.longitude], {
				icon: L.icon({
					iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png",
					shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
					iconSize: [25, 41],
					iconAnchor: [12, 41],
					popupAnchor: [1, -34],
					shadowSize: [41, 41]
				})
			}).addTo(map_chantiers);
			marker.bindPopup(popupContent);
			markers.push(marker);
			
			if (bounds === null) {
				bounds = L.latLngBounds([chantier.latitude, chantier.longitude]);
			} else {
				bounds.extend([chantier.latitude, chantier.longitude]);
			}
		});';
	}
	
	// Ajouter les marqueurs pour les chantiers affectés
	if (count($map_chantiers_affectes) > 0) {
		print '
		// Marqueurs pour les chantiers affectés (sans dates)
		var chantiersAffectesData = ' . json_encode($map_chantiers_affectes) . ';
		chantiersAffectesData.forEach(function(chantier) {
			var popupContent = "<h4>" + ' . json_encode($langs->trans("AssignedWorkSite")) . ' + "</h4>";
			popupContent += "<strong>" + ' . json_encode($langs->trans("Site")) . ' + ":</strong> <a href=\\"site_card.php?id=" + chantier.site_id + "\\">" + chantier.site_label + "</a><br>";
			if (chantier.propal_ref) {
				popupContent += "<strong>" + ' . json_encode($langs->trans("Proposal")) . ' + ":</strong> <a href=\\"' . DOL_URL_ROOT . '/comm/propal/card.php?id=" + chantier.propal_id + "\\">" + chantier.propal_ref + "</a><br>";
			}
			if (chantier.soc_nom) {
				popupContent += "<strong>" + ' . json_encode($langs->trans("ThirdParty")) . ' + ":</strong> <a href=\\"' . DOL_URL_ROOT . '/societe/card.php?socid=" + chantier.soc_id + "\\">" + chantier.soc_nom + "</a><br>";
			}
			popupContent += "<strong>" + ' . json_encode($langs->trans("WorkLocationType")) . ' + ":</strong> " + (chantier.location_type == 0 ? ' . json_encode($langs->trans("Interior")) . ' : ' . json_encode($langs->trans("Exterior")) . ');
			
			var marker = L.marker([chantier.latitude, chantier.longitude], {
				icon: L.icon({
					iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png",
					shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
					iconSize: [25, 41],
					iconAnchor: [12, 41],
					popupAnchor: [1, -34],
					shadowSize: [41, 41]
				})
			}).addTo(map_chantiers);
			marker.bindPopup(popupContent);
			markers.push(marker);
			
			if (bounds === null) {
				bounds = L.latLngBounds([chantier.latitude, chantier.longitude]);
			} else {
				bounds.extend([chantier.latitude, chantier.longitude]);
			}
		});';
	}
	
	// Ajuster la vue pour inclure tous les marqueurs
	print '
		if (bounds !== null && markers.length > 0) {
			map_chantiers.fitBounds(bounds, {padding: [50, 50]});
		} else if (markers.length === 1) {
			map_chantiers.setView([markers[0].getLatLng().lat, markers[0].getLatLng().lng], 13);
		}
	</script>';
	
	// Légende
	print '<div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">';
	print '<strong>'.$langs->trans("Legend").':</strong> ';
	print '<span style="display: inline-block; margin-left: 15px;"><img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png" style="width: 20px; height: 32px; vertical-align: middle;"> '.$langs->trans("ScheduledWorkSites").'</span>';
	print '<span style="display: inline-block; margin-left: 15px;"><img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png" style="width: 20px; height: 32px; vertical-align: middle;"> '.$langs->trans("AssignedWorkSites").'</span>';
	print '</div>';
	
	print '</div>';
}

// Section 1: Scheduled worksites - Affichage en tuiles type calendrier
print '<div class="div-table-responsive">';
print '<h3>'.$langs->trans("ScheduledWorkSites").' ('.$num_chantiers.')</h3>';

if ($num_chantiers == 0) {
	print '<div class="info">';
	print '<i class="fas fa-info-circle"></i> '.$langs->trans("NoScheduledWorkSite").'<br>';
	print '<small>'.$langs->trans("InfoNoScheduledWorkSite").'</small>';
	print '</div>';
}

// CSS pour les tuiles calendrier
print '<style>
.chantier-calendar-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
	gap: 15px;
	margin-top: 20px;
}
.chantier-tile {
	background: white;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 12px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: box-shadow 0.3s;
}
.chantier-tile:hover {
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.chantier-tile-header {
	border-bottom: 2px solid #e0e0e0;
	padding-bottom: 10px;
	margin-bottom: 10px;
}
.chantier-tile-date {
	font-size: 1.3em;
	font-weight: bold;
	color: #2c3e50;
	margin-bottom: 5px;
}
.chantier-tile-proposal {
	font-size: 0.9em;
	color: #7f8c8d;
	margin-bottom: 5px;
}
.chantier-tile-proposal a {
	color: #3498db;
	text-decoration: none;
}
.chantier-tile-proposal a:hover {
	text-decoration: underline;
}
.chantier-tile-site {
	font-size: 1em;
	font-weight: 600;
	color: #34495e;
	margin-bottom: 8px;
}
.chantier-tile-site a {
	color: #2c3e50;
	text-decoration: none;
}
.chantier-tile-site a:hover {
	text-decoration: underline;
}
.chantier-tile-thirdparty {
	font-size: 0.85em;
	color: #7f8c8d;
	margin-bottom: 10px;
}
.chantier-tile-thirdparty a {
	color: #3498db;
	text-decoration: none;
}
.chantier-tile-location {
	margin: 8px 0;
}
.chantier-tile-note {
	font-size: 0.85em;
	color: #555;
	margin: 10px 0;
	padding: 8px;
	background: #f8f9fa;
	border-radius: 4px;
	max-height: 60px;
	overflow: hidden;
}
.chantier-tile-weather {
	margin-top: 12px;
	padding-top: 12px;
	border-top: 1px solid #e0e0e0;
}
.chantier-tile-weather-title {
	font-size: 0.9em;
	font-weight: bold;
	color: #2c3e50;
	margin-bottom: 8px;
}
.chantier-tile-weather-days {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}
.chantier-weather-day {
	flex: 0 0 auto;
	min-width: 70px;
	padding: 6px;
	background: #f0f8ff;
	border-radius: 4px;
	text-align: center;
	border: 1px solid #b0d4f1;
}
.chantier-weather-day-date {
	font-size: 0.7em;
	font-weight: bold;
	margin-bottom: 3px;
	color: #2c3e50;
}
.chantier-weather-day-icon {
	margin: 3px 0;
}
.chantier-weather-day-icon img {
	width: 30px;
	height: 30px;
}
.chantier-weather-day-desc {
	font-size: 0.65em;
	color: #666;
	margin-bottom: 2px;
	line-height: 1.2;
}
.chantier-weather-day-temp {
	font-weight: bold;
	font-size: 0.85em;
	color: #2c3e50;
}
.chantier-tile-actions {
	margin-top: 12px;
	padding-top: 12px;
	border-top: 1px solid #e0e0e0;
	text-align: right;
}
</style>';

if ($num_chantiers > 0) {
	print '<div class="chantier-calendar-grid">';
	
	foreach ($chantiers_data as $obj) {
		
		// Récupérer les données météo si le chantier est extérieur et que le site a des coordonnées
		$weatherData = null;
		$weatherEnabled = !empty($conf->global->SITES2_WEATHER_ENABLED);
		$location_type = isset($obj->location_type) ? (int)$obj->location_type : 1;
		
		if ($location_type == 1 && $weatherEnabled && !empty($conf->global->SITES2_OPENWEATHERMAP_API_KEY) && !empty($obj->latitude) && !empty($obj->longitude)) {
			$weatherData = sites2GetWeatherData($obj->latitude, $obj->longitude, $conf->global->SITES2_OPENWEATHERMAP_API_KEY);
		}
		
		// Dates du chantier
		$date_debut_ts = !empty($obj->date_debut) ? $db->jdate($obj->date_debut) : null;
		$date_fin_ts = !empty($obj->date_fin) ? $db->jdate($obj->date_fin) : null;
		$date_debut_str = $date_debut_ts ? date('Y-m-d', $date_debut_ts) : '';
		$date_fin_str = $date_fin_ts ? date('Y-m-d', $date_fin_ts) : '';
		
		// Formatage des dates pour affichage
		$date_debut_display = $date_debut_ts ? dol_print_date($date_debut_ts, 'day') : '';
		$date_fin_display = $date_fin_ts ? dol_print_date($date_fin_ts, 'day') : '';
		
		print '<div class="chantier-tile">';
		
		// En-tête avec dates
		print '<div class="chantier-tile-header">';
		print '<div class="chantier-tile-date">';
		if ($date_debut_display) {
			print dol_escape_htmltag($date_debut_display);
			if ($date_fin_display && $date_fin_str != $date_debut_str) {
				print ' - '.dol_escape_htmltag($date_fin_display);
			}
		}
		print '</div>';
		print '</div>';
		
		// Devis
		print '<div class="chantier-tile-proposal">';
		if (!empty($obj->propal_ref)) {
			print '<i class="fa fa-file-invoice"></i> ';
			print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->fk_propal.'">'.dol_escape_htmltag($obj->propal_ref).'</a>';
		}
		print '</div>';
		
		// Site
		print '<div class="chantier-tile-site">';
		if (!empty($obj->site_id)) {
			print '<i class="fa fa-map-marker-alt"></i> ';
			print '<a href="site_card.php?id='.$obj->site_id.'">';
			$site_display = trim($obj->site_label);
			print dol_escape_htmltag($site_display);
			print '</a>';
		} else {
			print '<span class="opacitymedium">-</span>';
		}
		print '</div>';
		
		// Tiers
		print '<div class="chantier-tile-thirdparty">';
		if (!empty($obj->soc_nom)) {
			print '<i class="fa fa-building"></i> ';
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->soc_id.'">'.dol_escape_htmltag($obj->soc_nom).'</a>';
		}
		print '</div>';
		
		// Type de localisation
		print '<div class="chantier-tile-location">';
		if ($location_type == 0) {
			print '<span class="badge badge-status0"><i class="fa fa-home"></i> '.$langs->trans("Interior").'</span>';
		} else {
			print '<span class="badge badge-status1"><i class="fa fa-sun"></i> '.$langs->trans("Exterior").'</span>';
		}
		print '</div>';
		
		// Note publique
		if (!empty($obj->propal_note_public)) {
			$note_cleaned = strip_tags($obj->propal_note_public);
			$note_cleaned = str_replace(array("\r\n", "\r", "\n"), ' ', $note_cleaned);
			$note_cleaned = preg_replace('/\s+/', ' ', $note_cleaned);
			$note_cleaned = trim($note_cleaned);
			$note_display = dol_trunc($note_cleaned, 80);
			
			print '<div class="chantier-tile-note" title="'.dol_escape_htmltag($note_cleaned).'">';
			print '<i class="fa fa-sticky-note"></i> '.dol_escape_htmltag($note_display);
			print '</div>';
		}
		
		// Météo pour les jours d'intervention (uniquement si extérieur)
		if ($location_type == 1 && $weatherData && !empty($weatherData['forecast']) && $date_debut_str) {
			print '<div class="chantier-tile-weather">';
			print '<div class="chantier-tile-weather-title"><i class="fas fa-cloud-sun"></i> '.$langs->trans("WeatherForWorkDays").'</div>';
			print '<div class="chantier-tile-weather-days">';
			
			// Parcourir les prévisions et afficher celles qui correspondent aux dates de chantier
			foreach ($weatherData['forecast'] as $day) {
				$forecast_date = isset($day['date']) ? $day['date'] : ''; // Format Y-m-d
				
				// Vérifier si cette date est dans la plage du chantier
				if (!empty($forecast_date) && $forecast_date >= $date_debut_str && ($date_fin_str ? $forecast_date <= $date_fin_str : $forecast_date == $date_debut_str)) {
					$forecast_timestamp = strtotime($forecast_date);
					$date_label = isset($day['date_label']) ? $day['date_label'] : dol_print_date($forecast_timestamp, 'day');
					if ($forecast_date == date('Y-m-d')) {
						$date_label = $langs->trans("Today");
					} elseif ($forecast_date == date('Y-m-d', strtotime('+1 day'))) {
						$date_label = $langs->trans("Tomorrow");
					}
					
					print '<div class="chantier-weather-day">';
					print '<div class="chantier-weather-day-date">'.dol_escape_htmltag($date_label).'</div>';
					
					$icon = isset($day['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $day['icon']) : '';
					$description = isset($day['description']) ? htmlspecialchars($day['description'], ENT_QUOTES, 'UTF-8') : '';
					
					if (!empty($icon)) {
						print '<div class="chantier-weather-day-icon">';
						print '<img src="https://openweathermap.org/img/wn/'.htmlspecialchars($icon, ENT_QUOTES, 'UTF-8').'.png" alt="'.$description.'">';
						print '</div>';
					}
					
					if (!empty($description)) {
						print '<div class="chantier-weather-day-desc">'.dol_escape_htmltag($description).'</div>';
					}
					
					$temp = isset($day['temp']) ? intval($day['temp']) : 0;
					print '<div class="chantier-weather-day-temp">'.$temp.'°C</div>';
					
					if (isset($day['temp_min']) && isset($day['temp_max'])) {
						$tempMin = intval($day['temp_min']);
						$tempMax = intval($day['temp_max']);
						print '<div style="font-size: 0.6em; color: #999;">'.$tempMin.'° / '.$tempMax.'°</div>';
					}
					
					print '</div>';
				}
			}
			
			print '</div>';
			print '</div>';
		} elseif ($location_type == 0) {
			print '<div class="chantier-tile-weather">';
			print '<div style="font-size: 0.85em; color: #999;"><i class="fas fa-info-circle"></i> '.$langs->trans("WeatherNotRelevantForInteriorWork").'</div>';
			print '</div>';
		}
		
		// Actions
		print '<div class="chantier-tile-actions">';
		if (!empty($obj->site_id)) {
			print '<a href="site_chantier.php?id='.$obj->site_id.'" class="button" title="'.$langs->trans("ManageScheduledWorkSites").'">';
			print '<i class="fa fa-edit"></i> '.$langs->trans("Edit");
			print '</a>';
		}
		print '</div>';
		
		print '</div>'; // Fin de la tuile
	}
	
	print '</div>'; // Fin de la grille
} else {
	print '<div class="opacitymedium">'.$langs->trans("NoScheduledWorkSite").'</div>';
}

print '</div>';

// Section 2: Assigned worksites (without dates)
print '<div class="div-table-responsive" style="margin-top: 30px;">';
print '<h3>'.$langs->trans("AssignedWorkSites").' ('.$num_chantiers_affectes.')</h3>';

if ($num_chantiers_affectes == 0) {
	print '<div class="info">';
	print '<i class="fas fa-info-circle"></i> '.$langs->trans("NoAssignedWorkSite").'<br>';
	print '<small>'.$langs->trans("InfoNoAssignedWorkSite").'</small>';
	print '</div>';
}

print '<table class="tagtable liste">';

// Headers
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Proposal").'</th>';
print '<th>'.$langs->trans("ThirdParty").'</th>';
print '<th>'.$langs->trans("Site").'</th>';
print '<th>'.$langs->trans("WorkLocationType").'</th>';
print '<th>'.$langs->trans("PublicNote").'</th>';
print '<th class="right">'.$langs->trans("Actions").'</th>';
print '</tr>';

if ($num_chantiers_affectes > 0) {
	foreach ($chantiers_affectes_data as $obj) {
		
		print '<tr class="oddeven">';
		
		// Proposal
		print '<td>';
		if (!empty($obj->propal_ref)) {
			print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->fk_propal.'">'.$obj->propal_ref.'</a>';
		}
		print '</td>';
		
		// Third party
		print '<td>';
		if (!empty($obj->soc_nom)) {
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->soc_id.'">'.$obj->soc_nom.'</a>';
		}
		print '</td>';
		
		// Site
		print '<td>';
		if (!empty($obj->site_id)) {
			print '<a href="site_card.php?id='.$obj->site_id.'">';
			$site_display = trim($obj->site_label);
			print dol_escape_htmltag($site_display);
			print '</a>';
		} else {
			print '<span class="opacitymedium">-</span>';
		}
		print '</td>';
		
		// Location type
		print '<td>';
		$location_type = isset($obj->location_type) ? (int)$obj->location_type : 1;
		if ($location_type == 0) {
			print '<span class="badge badge-status0"><i class="fa fa-home"></i> '.$langs->trans("Interior").'</span>';
		} else {
			print '<span class="badge badge-status1"><i class="fa fa-sun"></i> '.$langs->trans("Exterior").'</span>';
		}
		print '</td>';
		
		// Note publique
		print '<td>';
		if (!empty($obj->propal_note_public)) {
			// Nettoyer le texte : supprimer les balises HTML et convertir les retours à la ligne
			$note_cleaned = strip_tags($obj->propal_note_public);
			$note_cleaned = str_replace(array("\r\n", "\r", "\n"), ' ', $note_cleaned);
			$note_cleaned = preg_replace('/\s+/', ' ', $note_cleaned); // Remplacer les espaces multiples par un seul
			$note_cleaned = trim($note_cleaned);
			
			// Limiter l'affichage à 100 caractères avec "..." si plus long
			$note_display = dol_trunc($note_cleaned, 100);
			print '<span title="'.dol_escape_htmltag($note_cleaned).'">'.dol_escape_htmltag($note_display).'</span>';
		} else {
			print '<span class="opacitymedium">-</span>';
		}
		print '</td>';
		
		// Actions
		print '<td class="right nowrap">';
		if (!empty($obj->site_id)) {
			print '<a href="site_chantier.php?id='.$obj->site_id.'" title="'.$langs->trans("ManageScheduledWorkSites").'">'.img_edit().'</a>';
		}
		print '</td>';
		
		print '</tr>';
	}
} else {
	print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("NoAssignedWorkSite").'</td></tr>';
}

print '</table>';
print '</div>';

// Section 3: Signed proposals not linked to a site
print '<div class="div-table-responsive" style="margin-top: 30px;">';
print '<h3>'.$langs->trans("SignedProposalsNotLinked").' ('.$num_propals.')</h3>';

if ($num_propals == 0) {
	print '<div class="info">';
	print '<i class="fas fa-info-circle"></i> '.$langs->trans("NoSignedProposalNotLinked").'<br>';
	print '<small>'.$langs->trans("InfoNoSignedProposalNotLinked").'</small>';
	print '</div>';
}

print '<table class="tagtable liste">';

// Headers
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Proposal").'</th>';
print '<th>'.$langs->trans("ThirdParty").'</th>';
print '<th>'.$langs->trans("Date").'</th>';
print '<th>'.$langs->trans("AmountHT").'</th>';
print '<th>'.$langs->trans("AmountTTC").'</th>';
print '<th>'.$langs->trans("PublicNote").'</th>';
print '<th>'.$langs->trans("Site").'</th>';
print '<th class="right">'.$langs->trans("Actions").'</th>';
print '</tr>';

if ($num_propals > 0) {
	$i = 0;
	while ($i < $num_propals) {
		$obj = $db->fetch_object($resql_propals);
		
		print '<tr class="oddeven">';
		
		// Proposal
		print '<td>';
		print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->rowid.'">'.$obj->ref.'</a>';
		print '</td>';
		
		// Third party
		print '<td>';
		if (!empty($obj->soc_nom)) {
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->soc_id.'">'.$obj->soc_nom.'</a>';
		}
		print '</td>';
		
		// Date
		print '<td>';
		if (!empty($obj->date_propal)) {
			print dol_print_date($db->jdate($obj->date_propal), 'day');
		}
		print '</td>';
		
		// Amount HT
		print '<td class="right">';
		if (!empty($obj->total_ht)) {
			print price($obj->total_ht);
		}
		print '</td>';
		
		// Amount TTC
		print '<td class="right">';
		if (!empty($obj->total_ttc)) {
			print price($obj->total_ttc);
		}
		print '</td>';
		
		// Note publique
		print '<td>';
		if (!empty($obj->note_public)) {
			// Nettoyer le texte : supprimer les balises HTML et convertir les retours à la ligne
			$note_cleaned = strip_tags($obj->note_public);
			$note_cleaned = str_replace(array("\r\n", "\r", "\n"), ' ', $note_cleaned);
			$note_cleaned = preg_replace('/\s+/', ' ', $note_cleaned); // Remplacer les espaces multiples par un seul
			$note_cleaned = trim($note_cleaned);
			
			// Limiter l'affichage à 100 caractères avec "..." si plus long
			$note_display = dol_trunc($note_cleaned, 100);
			print '<span title="'.dol_escape_htmltag($note_cleaned).'">'.dol_escape_htmltag($note_display).'</span>';
		} else {
			print '<span class="opacitymedium">-</span>';
		}
		print '</td>';
		
		// Site - Liste déroulante pour affecter un site
		print '<td>';
		// Utiliser les sites déjà récupérés avant la boucle
		// Vérifier que $all_sites est défini et contient des sites
		if (isset($all_sites) && !empty($all_sites) && count($all_sites) > 0) {
			$select_id = 'select_site_'.$obj->rowid;
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" id="form_'.$select_id.'" style="display: inline;">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="assign_site">';
			print '<input type="hidden" name="fk_propal" value="'.$obj->rowid.'">';
			print '<select name="fk_site" id="'.$select_id.'" class="flat minwidth200" ';
			print 'onchange="this.form.submit();">';
			print '<option value="">-- '.$langs->trans("SelectSite").' --</option>';
			
			$current_soc = '';
			foreach ($all_sites as $j => $obj_site) {
				// Grouper par tiers pour faciliter la sélection
				if ($current_soc != $obj_site->soc_nom) {
					if ($j > 0) {
						print '</optgroup>';
					}
					$current_soc = $obj_site->soc_nom;
					$soc_label = dol_escape_htmltag($current_soc ? $current_soc : $langs->trans("NoThirdParty"));
					print '<optgroup label="'.$soc_label.'">';
				}
				
				$site_label = (!empty($obj_site->ref) ? $obj_site->ref.' - ' : '').$obj_site->label;
				// Marquer les sites du même tiers que le devis
				$selected_attr = ($obj_site->fk_soc == $obj->soc_id) ? ' style="font-weight: bold;"' : '';
				print '<option value="'.$obj_site->rowid.'"'.$selected_attr.'>';
				print dol_escape_htmltag($site_label);
				print '</option>';
			}
			if (count($all_sites) > 0) {
				print '</optgroup>';
			}
			print '</select>';
			print '</form>';
		} else {
			// Afficher un message d'erreur si aucun site n'est trouvé
			print '<span class="opacitymedium">'.$langs->trans("NoSiteAvailable").'</span>';
			if (isset($res_sites_all) && !$res_sites_all) {
				print '<br><small class="error">Erreur SQL: '.$db->lasterror().'</small>';
			} elseif (!isset($all_sites) || empty($all_sites)) {
				print '<br><small class="opacitymedium">(Aucun site dans la base de données)</small>';
			}
		}
		print '</td>';
		
		// Actions
		print '<td class="right nowrap">';
		// Lien vers la liste des sites
		print '<a href="site_list.php" title="'.$langs->trans("ViewAllSites").'">'.img_object($langs->trans("ViewAllSites"), 'site@sites2').'</a>';
		print '</td>';
		
		print '</tr>';
		
		$i++;
	}
} else {
	print '<tr><td colspan="8" class="opacitymedium">'.$langs->trans("NoSignedProposalNotLinked").'</td></tr>';
}

print '</table>';
print '</div>';

llxFooter();

