<?php
/* Copyright (C) 2023-2024 Module Sites2
 *
 * This program is free software; you can redistribute it and/or modify
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

// Purge automatique : Supprimer les chantiers dont le devis n'est plus signé (statut != 2)
// Cette purge s'exécute à chaque chargement de la page pour maintenir la cohérence
$sql_purge = "DELETE c FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
$sql_purge .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
$sql_purge .= " WHERE c.entity = ".((int)$conf->entity);
$sql_purge .= " AND c.fk_propal IS NOT NULL"; // Uniquement les chantiers liés à un devis
$sql_purge .= " AND (p.rowid IS NULL OR p.fk_statut != 2)"; // Supprimer si le devis n'existe plus ou n'est plus signé

$resql_purge = $db->query($sql_purge);
if ($resql_purge) {
	$num_purged = $db->affected_rows($resql_purge);
	if ($num_purged > 0 && !empty($conf->global->MAIN_FEATURES_LEVEL) && $conf->global->MAIN_FEATURES_LEVEL >= 2) {
		dol_syslog("chantier_list.php - Purged ".$num_purged." worksites with non-signed proposals", LOG_DEBUG);
	}
} else {
	dol_syslog("chantier_list.php - Error during purge: ".$db->lasterror(), LOG_WARNING);
}

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

// Récupérer tous les sites existants UNE SEULE FOIS avant la boucle
// Note: La table sites2_site n'a pas de colonne entity, donc pas de filtre d'entité
$sql_sites_all = "SELECT s.rowid, s.ref, s.label, s.fk_soc, soc.nom as soc_nom";
$sql_sites_all .= " FROM ".MAIN_DB_PREFIX."sites2_site as s";
$sql_sites_all .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql_sites_all .= " WHERE 1 = 1";
$sql_sites_all .= " ORDER BY soc.nom, s.label";

$resql_sites_all = $db->query($sql_sites_all);
$sites_all = array();
if ($resql_sites_all) {
	while ($obj_site = $db->fetch_object($resql_sites_all)) {
		$sites_all[$obj_site->rowid] = $obj_site;
	}
}

// Requêtes SQL pour les chantiers programmés (avec dates) et les devis signés
$sql_chantiers = "SELECT DISTINCT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public,";
$sql_chantiers .= " p.ref as propal_ref, p.fk_statut as propal_status, p.datep as date_propal, p.note_public as propal_note_public,";
$sql_chantiers .= " s.rowid as site_id, s.label as site_label, s.ref as site_ref, s.latitude, s.longitude,";
$sql_chantiers .= " soc.rowid as soc_id, soc.nom as soc_nom";
$sql_chantiers .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
$sql_chantiers .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
$sql_chantiers .= " LEFT JOIN ".MAIN_DB_PREFIX."sites2_site as s ON s.rowid = c.fk_site";
$sql_chantiers .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql_chantiers .= " WHERE c.entity = ".((int)$conf->entity);
$sql_chantiers .= " AND c.fk_propal IS NOT NULL";
$sql_chantiers .= " AND p.fk_statut = 2"; // Uniquement les devis signés
$sql_chantiers .= " AND c.date_debut IS NOT NULL"; // Uniquement les chantiers avec dates

// Requête pour les chantiers affectés (sans dates)
$sql_chantiers_affectes = "SELECT DISTINCT c.rowid, c.fk_propal, c.date_debut, c.date_fin, c.location_type, c.note_public,";
$sql_chantiers_affectes .= " p.ref as propal_ref, p.fk_statut as propal_status, p.datep as date_propal, p.note_public as propal_note_public,";
$sql_chantiers_affectes .= " s.rowid as site_id, s.label as site_label, s.ref as site_ref, s.latitude, s.longitude,";
$sql_chantiers_affectes .= " soc.rowid as soc_id, soc.nom as soc_nom";
$sql_chantiers_affectes .= " FROM ".MAIN_DB_PREFIX."sites2_chantier as c";
$sql_chantiers_affectes .= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON p.rowid = c.fk_propal";
$sql_chantiers_affectes .= " LEFT JOIN ".MAIN_DB_PREFIX."sites2_site as s ON s.rowid = c.fk_site";
$sql_chantiers_affectes .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql_chantiers_affectes .= " WHERE c.entity = ".((int)$conf->entity);
$sql_chantiers_affectes .= " AND c.fk_propal IS NOT NULL";
$sql_chantiers_affectes .= " AND p.fk_statut = 2"; // Uniquement les devis signés
$sql_chantiers_affectes .= " AND c.date_debut IS NULL"; // Chantiers sans date de début (donc sans dates)

// Requête pour les devis signés non liés
$sql_propals = "SELECT p.rowid, p.ref, p.fk_statut, p.datep as date_propal, p.total_ht, p.total_ttc, p.note_public,";
$sql_propals .= " soc.rowid as soc_id, soc.nom as soc_nom";
$sql_propals .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql_propals .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = p.fk_soc";
$sql_propals .= " WHERE p.entity IN (".getEntity("propal").")";
$sql_propals .= " AND p.fk_statut = 2"; // Uniquement les devis signés
$sql_propals .= " AND p.rowid NOT IN (SELECT DISTINCT fk_propal FROM ".MAIN_DB_PREFIX."sites2_chantier WHERE fk_propal IS NOT NULL AND entity = ".((int)$conf->entity).")"; // Exclure les devis déjà liés

// Appliquer les filtres de recherche
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

// Appliquer les filtres de recherche aux chantiers affectés
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

// Trier les chantiers par date de début
$sql_chantiers .= " ORDER BY c.date_debut ASC, c.date_creation DESC";
$sql_chantiers_affectes .= " ORDER BY c.date_creation DESC";
$sql_propals .= " ORDER BY p.datep DESC";

// Exécuter les requêtes
$resql_chantiers = $db->query($sql_chantiers);
if (!$resql_chantiers) {
	setEventMessages("Erreur SQL pour les chantiers programmés: ".$db->lasterror(), null, 'errors');
	$num_chantiers = 0;
} else {
	$num_chantiers = $db->num_rows($resql_chantiers);
}

$resql_chantiers_affectes = $db->query($sql_chantiers_affectes);
if (!$resql_chantiers_affectes) {
	setEventMessages("Erreur SQL pour les chantiers affectés: ".$db->lasterror(), null, 'errors');
	$num_chantiers_affectes = 0;
} else {
	$num_chantiers_affectes = $db->num_rows($resql_chantiers_affectes);
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

// Récupérer les données depuis les résultats SQL
$chantiers_data = array();
if ($resql_chantiers) {
	while ($obj = $db->fetch_object($resql_chantiers)) {
		$chantiers_data[] = $obj;
		if (!empty($obj->latitude) && !empty($obj->longitude)) {
			$date_debut_ts = !empty($obj->date_debut) ? $db->jdate($obj->date_debut) : null;
			$date_fin_ts = !empty($obj->date_fin) ? $db->jdate($obj->date_fin) : null;
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

$chantiers_affectes_data = array();
if ($resql_chantiers_affectes) {
	while ($obj = $db->fetch_object($resql_chantiers_affectes)) {
		$chantiers_affectes_data[] = $obj;
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

// Vérifier si l'agence de référence est configurée
$refAgencyLat = !empty($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) ? floatval($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) : null;
$refAgencyLng = !empty($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE) ? floatval($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE) : null;
$refAgencyName = !empty($conf->global->SITES2_REFERENCE_AGENCY_NAME) ? $conf->global->SITES2_REFERENCE_AGENCY_NAME : '';

// Afficher la carte si au moins un chantier a des coordonnées ou si l'agence de référence est configurée
if (count($map_chantiers) > 0 || count($map_chantiers_affectes) > 0 || ($refAgencyLat !== null && $refAgencyLng !== null)) {
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
		// Vérifier que Leaflet est chargé
		if (typeof L === "undefined") {
			console.error("Leaflet n\'est pas chargé. Vérifiez votre connexion internet.");
			document.getElementById("map_chantiers").innerHTML = "<div style=\'padding: 20px; text-align: center; color: #666;\'><i class=\'fas fa-exclamation-triangle\'></i> Impossible de charger la carte. Vérifiez votre connexion internet.</div>";
		} else {
			// Initialiser la carte avec un centre et un zoom par défaut (France)
			var map_chantiers = L.map("map_chantiers", {
				center: [46.6034, 1.8883], // Centre de la France
				zoom: 6
			});
			var markers = [];
			var bounds = null;
			
			// Ajouter le fond de carte';
	
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
	
	// Ajouter l'agence de référence si configurée';
	
	if ($refAgencyLat !== null && $refAgencyLng !== null) {
		print '
			
			// Ajouter l\'agence de référence
			var refAgencyLat = ' . $refAgencyLat . ';
			var refAgencyLng = ' . $refAgencyLng . ';
			var refAgencyName = ' . json_encode($refAgencyName) . ';
			
			// Créer un marqueur pour l\'agence de référence avec une icône personnalisée (vert)
			var refAgencyMarker = L.marker([refAgencyLat, refAgencyLng], {
				icon: L.icon({
					iconUrl: "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png",
					shadowUrl: "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png",
					iconSize: [25, 41],
					iconAnchor: [12, 41],
					popupAnchor: [1, -34],
					shadowSize: [41, 41]
				})
			}).addTo(map_chantiers);
			refAgencyMarker.bindPopup(refAgencyName + " (' . dol_escape_js($langs->trans("ReferenceAgency")) . ')");
			markers.push(refAgencyMarker);
			
			// Ajouter l\'agence de référence aux bounds
			if (bounds === null) {
				bounds = L.latLngBounds([refAgencyLat, refAgencyLng]);
			} else {
				bounds.extend([refAgencyLat, refAgencyLng]);
			}';
	}
	
	// Ajuster la vue pour inclure tous les marqueurs
	print '
			
			// Ajuster la vue pour inclure tous les marqueurs (chantiers + agence de référence)
			if (bounds !== null && markers.length > 0) {
				map_chantiers.fitBounds(bounds, {padding: [50, 50]});
			} else if (markers.length === 1) {
				map_chantiers.setView([markers[0].getLatLng().lat, markers[0].getLatLng().lng], 13);
			}
			// Si aucun marqueur, la carte reste centrée sur la France (vue par défaut)
		}
	</script>';
	
	// Légende
	print '<div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">';
	print '<strong>'.$langs->trans("Legend").':</strong> ';
	print '<span style="display: inline-block; margin-left: 15px;"><img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png" style="width: 20px; height: 32px; vertical-align: middle;"> '.$langs->trans("ScheduledWorkSites").'</span>';
	print '<span style="display: inline-block; margin-left: 15px;"><img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png" style="width: 20px; height: 32px; vertical-align: middle;"> '.$langs->trans("AssignedWorkSites").'</span>';
	if ($refAgencyLat !== null && $refAgencyLng !== null) {
		print '<span style="display: inline-block; margin-left: 15px;"><img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png" style="width: 20px; height: 32px; vertical-align: middle;"> '.$langs->trans("ReferenceAgency").'</span>';
	}
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
	padding-bottom: 8px;
	margin-bottom: 10px;
}
.chantier-tile-date {
	font-weight: bold;
	font-size: 1.1em;
	color: #2c3e50;
}
.chantier-tile-proposal {
	margin: 8px 0;
	font-size: 0.95em;
}
.chantier-tile-site {
	margin: 8px 0;
	font-size: 0.95em;
}
.chantier-tile-thirdparty {
	margin: 8px 0;
	font-size: 0.9em;
	color: #666;
}
.chantier-tile-location {
	margin: 8px 0;
}
.chantier-tile-note {
	margin: 8px 0;
	font-size: 0.85em;
	color: #666;
	max-height: 60px;
	overflow: hidden;
	text-overflow: ellipsis;
}
.chantier-tile-weather {
	margin-top: 10px;
	padding-top: 10px;
	border-top: 1px solid #e0e0e0;
}
.chantier-tile-weather-title {
	font-weight: bold;
	font-size: 0.9em;
	margin-bottom: 8px;
}
.chantier-tile-weather-days {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}
.chantier-weather-day {
	background: #f8f9fa;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 6px;
	min-width: 70px;
	text-align: center;
	font-size: 0.8em;
}
.chantier-weather-day-date {
	font-weight: bold;
	margin-bottom: 4px;
}
.chantier-weather-day-icon {
	margin: 4px 0;
}
.chantier-weather-day-icon img {
	width: 30px;
	height: 30px;
}
.chantier-weather-day-desc {
	color: #666;
	font-size: 0.75em;
	margin: 4px 0;
}
.chantier-weather-day-temp {
	font-weight: bold;
	font-size: 1em;
}
.chantier-tile-actions {
	margin-top: 10px;
	padding-top: 10px;
	border-top: 1px solid #e0e0e0;
	text-align: right;
}
</style>';

if ($num_chantiers > 0) {
	print '<div class="chantier-calendar-grid">';
	
	// Réinitialiser le pointeur de la requête
	if ($resql_chantiers) {
		$db->free($resql_chantiers);
		$resql_chantiers = $db->query($sql_chantiers);
	}
	
	while ($obj = $db->fetch_object($resql_chantiers)) {
		$date_debut_ts = !empty($obj->date_debut) ? $db->jdate($obj->date_debut) : null;
		$date_fin_ts = !empty($obj->date_fin) ? $db->jdate($obj->date_fin) : null;
		$date_debut_str = $date_debut_ts ? date('Y-m-d', $date_debut_ts) : '';
		$date_fin_str = $date_fin_ts ? date('Y-m-d', $date_fin_ts) : '';
		$date_debut_display = $date_debut_ts ? dol_print_date($date_debut_ts, 'day') : '';
		$date_fin_display = $date_fin_ts ? dol_print_date($date_fin_ts, 'day') : '';
		$location_type = isset($obj->location_type) ? (int)$obj->location_type : 1;
		
		// Récupérer les données météo pour les chantiers extérieurs avec coordonnées
		$weatherData = null;
		if ($location_type == 1 && !empty($obj->latitude) && !empty($obj->longitude)) {
			$weatherProvider = !empty($conf->global->SITES2_WEATHER_PROVIDER) ? $conf->global->SITES2_WEATHER_PROVIDER : 'openweathermap';
			$needsApiKey = ($weatherProvider === 'openweathermap');
			$hasApiKey = !empty($conf->global->SITES2_OPENWEATHERMAP_API_KEY);
			
			if (!$needsApiKey || $hasApiKey) {
				$weatherData = sites2GetWeatherData($obj->latitude, $obj->longitude, $conf->global->SITES2_OPENWEATHERMAP_API_KEY);
			}
		}
		
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
		if (!empty($obj->note_public)) {
			$note_cleaned = strip_tags($obj->note_public);
			$note_display = dol_trunc($note_cleaned, 80);
			
			print '<div class="chantier-tile-note" title="'.dol_escape_htmltag($note_cleaned).'">';
			print '<i class="fa fa-sticky-note"></i> '.dol_escape_htmltag($note_display);
			print '</div>';
		}
		
		// Météo pour les chantiers extérieurs avec dates
		if ($location_type == 1 && $weatherData && !empty($weatherData['forecast']) && $date_debut_str) {
			print '<div class="chantier-tile-weather">';
			print '<div class="chantier-tile-weather-title"><i class="fas fa-cloud-sun"></i> '.$langs->trans("WeatherForWorkDays").'</div>';
			print '<div class="chantier-tile-weather-days">';
			
			// Déterminer la date de fin : si pas de date de fin, utiliser uniquement la date de début
			$date_fin_effective = !empty($date_fin_str) ? $date_fin_str : $date_debut_str;
			
			foreach ($weatherData['forecast'] as $day) {
				$day_date = isset($day['date']) ? $day['date'] : '';
				// Afficher uniquement les jours entre date_debut et date_fin (inclus)
				if (!empty($day_date) && $day_date >= $date_debut_str && $day_date <= $date_fin_effective) {
					$date_label = dol_print_date(dol_stringtotime($day_date), '%d/%m');
					$description = isset($day['description']) ? $day['description'] : '';
					$icon = isset($day['icon']) ? $day['icon'] : '';
					
					print '<div class="chantier-weather-day">';
					print '<div class="chantier-weather-day-date">'.dol_escape_htmltag($date_label).'</div>';
					
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
	// Réinitialiser le pointeur de la requête
	if ($resql_chantiers_affectes) {
		$db->free($resql_chantiers_affectes);
		$resql_chantiers_affectes = $db->query($sql_chantiers_affectes);
	}
	
	while ($obj = $db->fetch_object($resql_chantiers_affectes)) {
		$location_type = isset($obj->location_type) ? (int)$obj->location_type : 1;
		
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
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->soc_id.'">'.dol_escape_htmltag($obj->soc_nom).'</a>';
		}
		print '</td>';
		
		// Site
		print '<td>';
		if (!empty($obj->site_id)) {
			print '<a href="site_card.php?id='.$obj->site_id.'">'.dol_escape_htmltag($obj->site_label).'</a>';
		}
		print '</td>';
		
		// Location type
		print '<td>';
		if ($location_type == 0) {
			print '<span class="badge badge-status0"><i class="fa fa-home"></i> '.$langs->trans("Interior").'</span>';
		} else {
			print '<span class="badge badge-status1"><i class="fa fa-sun"></i> '.$langs->trans("Exterior").'</span>';
		}
		print '</td>';
		
		// Public note
		print '<td>';
		if (!empty($obj->note_public)) {
			$note_cleaned = strip_tags($obj->note_public);
			$note_display = dol_trunc($note_cleaned, 100);
			print '<span title="'.dol_escape_htmltag($note_cleaned).'">'.dol_escape_htmltag($note_display).'</span>';
		}
		print '</td>';
		
		// Actions
		print '<td class="right">';
		if (!empty($obj->site_id)) {
			print '<a href="site_chantier.php?id='.$obj->site_id.'" class="button" title="'.$langs->trans("ManageScheduledWorkSites").'">';
			print '<i class="fa fa-edit"></i> '.$langs->trans("Edit");
			print '</a>';
		}
		print '</td>';
		
		print '</tr>';
	}
} else {
	print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("NoAssignedWorkSite").'</td></tr>';
}

print '</table>';
print '</div>';

// Section 3: Signed proposals not linked
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
print '<th>'.$langs->trans("PublicNote").'</th>';
print '<th>'.$langs->trans("Site").'</th>';
print '<th class="right">'.$langs->trans("Actions").'</th>';
print '</tr>';

if ($num_propals > 0) {
	$i = 0;
	while ($obj = $db->fetch_object($resql_propals)) {
		$date_propal_ts = !empty($obj->date_propal) ? $db->jdate($obj->date_propal) : null;
		$date_propal_display = $date_propal_ts ? dol_print_date($date_propal_ts, 'day') : '';
		
		print '<tr class="oddeven">';
		
		// Proposal
		print '<td>';
		if (!empty($obj->ref)) {
			print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->rowid.'">'.dol_escape_htmltag($obj->ref).'</a>';
		}
		print '</td>';
		
		// Third party
		print '<td>';
		if (!empty($obj->soc_nom)) {
			print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->soc_id.'">'.dol_escape_htmltag($obj->soc_nom).'</a>';
		}
		print '</td>';
		
		// Date
		print '<td>'.dol_escape_htmltag($date_propal_display).'</td>';
		
		// Amount
		print '<td class="right">';
		if (!empty($obj->total_ht)) {
			print price($obj->total_ht);
		}
		print '</td>';
		
		// Public note
		print '<td>';
		if (!empty($obj->note_public)) {
			$note_cleaned = strip_tags($obj->note_public);
			$note_display = dol_trunc($note_cleaned, 100);
			print '<span title="'.dol_escape_htmltag($note_cleaned).'">'.dol_escape_htmltag($note_display).'</span>';
		}
		print '</td>';
		
		// Site selection
		print '<td>';
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" style="display: inline;">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="assign_site">';
		print '<input type="hidden" name="fk_propal" value="'.$obj->rowid.'">';
		
		// Récupérer les sites du tiers
		$sites_du_tiers = array();
		if (!empty($obj->soc_id)) {
			foreach ($sites_all as $site_id => $site_obj) {
				if ($site_obj->fk_soc == $obj->soc_id) {
					$sites_du_tiers[$site_id] = $site_obj;
				}
			}
		}
		
		if (count($sites_du_tiers) > 0) {
			print '<select name="fk_site" class="flat minwidth200">';
			print '<option value="">'.$langs->trans("SelectSite").'</option>';
			foreach ($sites_du_tiers as $site_id => $site_obj) {
				$site_label_display = trim($site_obj->label);
				if (empty($site_label_display)) {
					$site_label_display = $site_obj->ref;
				}
				print '<option value="'.$site_id.'">'.dol_escape_htmltag($site_label_display).'</option>';
			}
			print '</select>';
			print ' <input type="submit" class="button" value="'.$langs->trans("Assign").'">';
		} else {
			print '<span class="opacitymedium">'.$langs->trans("NoSiteForThirdParty").'</span>';
		}
		
		print '</form>';
		print '</td>';
		
		// Actions
		print '<td class="right">';
		print '<a href="'.DOL_URL_ROOT.'/comm/propal/card.php?id='.$obj->rowid.'" class="button" title="'.$langs->trans("ViewProposal").'">';
		print '<i class="fa fa-eye"></i>';
		print '</a>';
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
