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
 *   	\file       ajax_get_societe_address.php
 *		\ingroup    sites2
 *		\brief      Endpoint AJAX pour récupérer l'adresse d'un tiers
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

// Security check
if (!$user->rights->sites2->site->write) {
	http_response_code(403);
	echo json_encode(array('error' => 'Access denied'));
	exit;
}

// Récupérer l'ID du tiers depuis la requête
$fk_soc = GETPOST('fk_soc', 'int');

if (empty($fk_soc)) {
	http_response_code(400);
	echo json_encode(array('error' => 'Missing fk_soc parameter'));
	exit;
}

// Charger la classe Societe
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$societe = new Societe($db);
$result = $societe->fetch($fk_soc);

if ($result <= 0) {
	http_response_code(404);
	echo json_encode(array('error' => 'Societe not found'));
	exit;
}

// Retourner les informations d'adresse
$response = array(
	'success' => true,
	'address' => $societe->address ? $societe->address : '',
	'zip' => $societe->zip ? $societe->zip : '',
	'town' => $societe->town ? $societe->town : ''
);

// Définir le type de contenu JSON
header('Content-Type: application/json');
echo json_encode($response);

