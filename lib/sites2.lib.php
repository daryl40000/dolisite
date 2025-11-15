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
 * \file    lib/sites2.lib.php
 * \ingroup sites2
 * \brief   Library files with common functions for Sites2
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function sites2AdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("sites2@sites2");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/sites2/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	// Agence de référence
	$head[$h][0] = dol_buildpath("/sites2/admin/reference_agency.php", 1);
	$head[$h][1] = $langs->trans("ReferenceAgency");
	$head[$h][2] = 'reference_agency';
	$h++;

	// Rôles de contacts
	$head[$h][0] = dol_buildpath("/sites2/admin/contact_roles.php", 1);
	$head[$h][1] = $langs->trans("ContactRoles");
	$head[$h][2] = 'contact_roles';
	$h++;

	// Importation de données
	$head[$h][0] = dol_buildpath("/sites2/admin/import.php", 1);
	$head[$h][1] = $langs->trans("ImportExport");
	$head[$h][2] = 'import';
	$h++;

	$head[$h][0] = dol_buildpath("/sites2/admin/export.php", 1);
	$head[$h][1] = $langs->trans("ExportSites");
	$head[$h][2] = 'export';
	$h++;

	// About page
	$head[$h][0] = dol_buildpath("/sites2/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	$head[$h][0] = dol_buildpath("/sites2/admin/optimize.php", 1);
	$head[$h][1] = $langs->trans("PerformanceOptimization");
	$head[$h][2] = 'optimize';
	$h++;

	// Show more tabs from modules
	// Entries for tab for external module
	$parameters = array();
	$reshook = $GLOBALS['hookmanager']->executeHooks('adminSites2PrepareHead', $parameters); // Note that $action and $object may have been modified by hook
	if ($reshook > 0) {
		$head = $GLOBALS['hookmanager']->resPrint;
	}

	return $head;
}

/**
 * Récupère les données météo pour un site donné via l'API OpenWeatherMap
 *
 * @param float $latitude Latitude du site
 * @param float $longitude Longitude du site
 * @param string $apiKey Clé API OpenWeatherMap
 * @param string $apiType Type d'API : 'free' (gratuite, 5 jours) ou 'paid' (payante, 8 jours). Par défaut 'free'
 * @return array|false Tableau avec les données météo ou false en cas d'erreur
 */
function sites2GetWeatherData($latitude, $longitude, $apiKey, $apiType = null)
{
	global $langs, $conf;
	
	// Récupérer le type d'API depuis la configuration si non spécifié
	if ($apiType === null || $apiType === 'free') {
		if (isset($conf->global->SITES2_WEATHER_API_TYPE) && !empty($conf->global->SITES2_WEATHER_API_TYPE)) {
			$apiType = $conf->global->SITES2_WEATHER_API_TYPE;
		} else {
			$apiType = 'free'; // Par défaut, API gratuite
		}
	}
	
	// Validation des paramètres d'entrée
	if (empty($apiKey) || empty($latitude) || empty($longitude)) {
		dol_syslog(__METHOD__.": Paramètres manquants pour récupérer la météo", LOG_WARNING);
		return false;
	}
	
	// Valider que latitude et longitude sont des nombres
	if (!is_numeric($latitude) || !is_numeric($longitude)) {
		dol_syslog(__METHOD__.": Coordonnées invalides (latitude ou longitude non numérique)", LOG_WARNING);
		return false;
	}
	
	// Valider les plages de coordonnées (latitude: -90 à 90, longitude: -180 à 180)
	$latitude = floatval($latitude);
	$longitude = floatval($longitude);
	if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
		dol_syslog(__METHOD__.": Coordonnées hors limites", LOG_WARNING);
		return false;
	}
	
	// Valider la clé API (format basique : alphanumérique, tirets, underscores, longueur minimale)
	$apiKey = trim($apiKey);
	if (empty($apiKey) || strlen($apiKey) < 10 || !preg_match('/^[a-zA-Z0-9_-]+$/', $apiKey)) {
		dol_syslog(__METHOD__.": Format de clé API invalide", LOG_WARNING);
		return false;
	}
	
	// Déterminer l'endpoint selon le type d'API
	$usePaidAPI = ($apiType === 'paid');
	
	if ($usePaidAPI) {
		// API payante : One Call API 3.0 (8 jours de prévisions quotidiennes)
		// Exclure minutely et hourly pour optimiser la réponse
		$url = "https://api.openweathermap.org/data/3.0/onecall?lat=" . urlencode((string)$latitude) . 
		       "&lon=" . urlencode((string)$longitude) . 
		       "&appid=" . urlencode($apiKey) . 
		       "&units=metric&lang=fr" .
		       "&exclude=minutely,hourly,alerts"; // Exclure les données non nécessaires
		dol_syslog(__METHOD__.": Utilisation de l'API payante One Call API 3.0 pour récupérer les données météo", LOG_DEBUG);
	} else {
		// API gratuite : endpoint /forecast (5 jours, prévisions toutes les 3 heures)
		$url = "https://api.openweathermap.org/data/2.5/forecast?lat=" . urlencode((string)$latitude) . 
		       "&lon=" . urlencode((string)$longitude) . 
		       "&appid=" . urlencode($apiKey) . 
		       "&units=metric&lang=fr";
		dol_syslog(__METHOD__.": Utilisation de l'API gratuite (/forecast) pour récupérer les données météo", LOG_DEBUG);
	}
	
	// Créer un contexte de flux pour ajouter User-Agent à la requête HTTP
	$context = stream_context_create([
		'http' => [
			'method' => 'GET',
			'header' => "User-Agent: Dolibarr PHP Application\r\n",
			'timeout' => 10
		]
	]);
	
	// Récupérer les données
	$response = @file_get_contents($url, false, $context);
	
	if ($response === false) {
		dol_syslog(__METHOD__.": Erreur lors de la récupération des données météo", LOG_ERR);
		return false;
	}
	
	$data = json_decode($response, true);
	
	// Vérifier le code de réponse (peut être '200' en string ou 200 en int)
	// L'API payante peut ne pas retourner de champ 'cod' si tout va bien
	if (empty($data)) {
		dol_syslog(__METHOD__.": Réponse API OpenWeatherMap vide", LOG_ERR);
		return false;
	}
	
	// Vérifier les erreurs de l'API One Call 3.0 (structure différente)
	if (isset($data['cod']) && $data['cod'] != '200' && $data['cod'] != 200) {
		$errorMsg = isset($data['message']) ? $data['message'] : 'Erreur inconnue';
		$errorMsg .= ' (Code: ' . $data['cod'] . ')';
		dol_syslog(__METHOD__.": Erreur API OpenWeatherMap: " . $errorMsg, LOG_ERR);
		return false;
	}
	
	// Organiser les données par jour
	$weatherByDay = array();
	
	if ($usePaidAPI) {
		// API payante One Call API 3.0 : structure avec 'current' et 'daily'
		// Vérifier que les données sont présentes
		if (!isset($data['daily']) || !is_array($data['daily']) || empty($data['daily'])) {
			dol_syslog(__METHOD__.": Aucune donnée de prévision quotidienne dans la réponse API One Call 3.0", LOG_WARNING);
			return false;
		}
		
		// Ajouter les données actuelles (aujourd'hui) depuis 'current'
		if (isset($data['current']) && isset($data['current']['weather'][0])) {
			$today = date('Y-m-d', intval($data['current']['dt']));
			$weatherByDay[$today] = array(
				'date' => $today,
				'date_label' => $langs->trans("Today"),
				'temp_min' => isset($data['current']['temp']) ? round(floatval($data['current']['temp'])) : 0,
				'temp_max' => isset($data['current']['temp']) ? round(floatval($data['current']['temp'])) : 0,
				'temp' => isset($data['current']['temp']) ? round(floatval($data['current']['temp'])) : 0,
				'description' => isset($data['current']['weather'][0]['description']) ? ucfirst(htmlspecialchars($data['current']['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
				'icon' => isset($data['current']['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $data['current']['weather'][0]['icon']) : '',
				'humidity' => isset($data['current']['humidity']) ? intval($data['current']['humidity']) : 0,
				'wind_speed' => isset($data['current']['wind_speed']) ? round(floatval($data['current']['wind_speed']) * 3.6, 1) : 0, // Conversion m/s en km/h
				'is_today' => true
			);
		}
		
		// Traiter les prévisions quotidiennes (daily array)
		foreach ($data['daily'] as $forecast) {
			// Valider la structure des données avant traitement
			if (!isset($forecast['dt']) || !isset($forecast['temp']) || !isset($forecast['weather'][0])) {
				continue; // Ignorer les entrées invalides
			}
			
			$forecastDate = date('Y-m-d', intval($forecast['dt']));
			
			// Ignorer aujourd'hui car on a déjà les données actuelles
			if ($forecastDate == date('Y-m-d')) {
				continue;
			}
			
			// Nouveau jour
			$dateLabel = '';
			if ($forecastDate == date('Y-m-d', strtotime('+1 day'))) {
				$dateLabel = $langs->trans("Tomorrow");
			} else {
				// Utiliser date() au lieu de strftime (déprécié en PHP 8.1+)
				$dateLabel = date('l d F', intval($forecast['dt']));
				// Traduire le nom du jour si possible
				$days = array('Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 
				              'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche');
				$months = array('January' => 'janvier', 'February' => 'février', 'March' => 'mars',
				                'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
				                'July' => 'juillet', 'August' => 'août', 'September' => 'septembre',
				                'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre');
				foreach ($days as $en => $fr) {
					$dateLabel = str_replace($en, $fr, $dateLabel);
				}
				foreach ($months as $en => $fr) {
					$dateLabel = str_replace($en, $fr, $dateLabel);
				}
			}
			
			// Structure de l'API One Call 3.0 : temp.min, temp.max, temp.day, etc.
			$weatherByDay[$forecastDate] = array(
				'date' => $forecastDate,
				'date_label' => htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'),
				'temp_min' => isset($forecast['temp']['min']) ? round(floatval($forecast['temp']['min'])) : 0,
				'temp_max' => isset($forecast['temp']['max']) ? round(floatval($forecast['temp']['max'])) : 0,
				'temp' => isset($forecast['temp']['day']) ? round(floatval($forecast['temp']['day'])) : (isset($forecast['temp']['min']) && isset($forecast['temp']['max']) ? round((floatval($forecast['temp']['min']) + floatval($forecast['temp']['max'])) / 2) : 0),
				'description' => isset($forecast['weather'][0]['description']) ? ucfirst(htmlspecialchars($forecast['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
				'icon' => isset($forecast['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $forecast['weather'][0]['icon']) : '',
				'humidity' => isset($forecast['humidity']) ? intval($forecast['humidity']) : 0,
				'wind_speed' => isset($forecast['wind_speed']) ? round(floatval($forecast['wind_speed']) * 3.6, 1) : 0, // Conversion m/s en km/h
				'is_today' => false
			);
		}
	} else {
		// API gratuite : vérifier que les données de prévisions sont présentes
		if (!isset($data['list']) || !is_array($data['list']) || empty($data['list'])) {
			dol_syslog(__METHOD__.": Aucune donnée de prévision dans la réponse API", LOG_WARNING);
			return false;
		}
		
		// Récupérer aussi les données actuelles (aujourd'hui)
		$currentUrl = "https://api.openweathermap.org/data/2.5/weather?lat=" . urlencode($latitude) . 
		              "&lon=" . urlencode($longitude) . 
		              "&appid=" . urlencode($apiKey) . 
		              "&units=metric&lang=fr";
		
		$currentResponse = @file_get_contents($currentUrl, false, $context);
		$currentData = null;
		
		if ($currentResponse !== false) {
			$currentData = json_decode($currentResponse, true);
			if (empty($currentData) || $currentData['cod'] != 200) {
				$currentData = null;
			}
		}
		
		// Ajouter les données actuelles (aujourd'hui)
		if ($currentData && isset($currentData['main']) && isset($currentData['weather'][0])) {
			$today = date('Y-m-d');
			$weatherByDay[$today] = array(
				'date' => $today,
				'date_label' => $langs->trans("Today"),
				'temp_min' => isset($currentData['main']['temp_min']) ? round(floatval($currentData['main']['temp_min'])) : 0,
				'temp_max' => isset($currentData['main']['temp_max']) ? round(floatval($currentData['main']['temp_max'])) : 0,
				'temp' => isset($currentData['main']['temp']) ? round(floatval($currentData['main']['temp'])) : 0,
				'description' => isset($currentData['weather'][0]['description']) ? ucfirst(htmlspecialchars($currentData['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
				'icon' => isset($currentData['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $currentData['weather'][0]['icon']) : '',
				'humidity' => isset($currentData['main']['humidity']) ? intval($currentData['main']['humidity']) : 0,
				'wind_speed' => isset($currentData['wind']['speed']) ? round(floatval($currentData['wind']['speed']) * 3.6, 1) : 0, // Conversion m/s en km/h
				'is_today' => true
			);
		}
		
		// API gratuite : les données sont toutes les 3 heures, il faut les agréger par jour
		foreach ($data['list'] as $forecast) {
			// Valider la structure des données avant traitement
			if (!isset($forecast['dt']) || !isset($forecast['main']) || !isset($forecast['weather'][0])) {
				continue; // Ignorer les entrées invalides
			}
			
			$forecastDate = date('Y-m-d', intval($forecast['dt']));
			
			// Si c'est aujourd'hui et qu'on a déjà les données actuelles, on peut les compléter
			if ($forecastDate == date('Y-m-d') && isset($weatherByDay[$forecastDate])) {
				// Mettre à jour les températures min/max si nécessaire
				if (isset($forecast['main']['temp_min']) && isset($weatherByDay[$forecastDate]['temp_min'])) {
					$tempMin = floatval($forecast['main']['temp_min']);
					if ($tempMin < $weatherByDay[$forecastDate]['temp_min']) {
						$weatherByDay[$forecastDate]['temp_min'] = round($tempMin);
					}
				}
				if (isset($forecast['main']['temp_max']) && isset($weatherByDay[$forecastDate]['temp_max'])) {
					$tempMax = floatval($forecast['main']['temp_max']);
					if ($tempMax > $weatherByDay[$forecastDate]['temp_max']) {
						$weatherByDay[$forecastDate]['temp_max'] = round($tempMax);
					}
				}
			} else if (!isset($weatherByDay[$forecastDate])) {
				// Nouveau jour
				$dateLabel = '';
				if ($forecastDate == date('Y-m-d', strtotime('+1 day'))) {
					$dateLabel = $langs->trans("Tomorrow");
				} else {
					// Utiliser date() au lieu de strftime (déprécié en PHP 8.1+)
					$dateLabel = date('l d F', intval($forecast['dt']));
					// Traduire le nom du jour si possible
					$days = array('Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 
					              'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche');
					$months = array('January' => 'janvier', 'February' => 'février', 'March' => 'mars',
					                'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
					                'July' => 'juillet', 'August' => 'août', 'September' => 'septembre',
					                'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre');
					foreach ($days as $en => $fr) {
						$dateLabel = str_replace($en, $fr, $dateLabel);
					}
					foreach ($months as $en => $fr) {
						$dateLabel = str_replace($en, $fr, $dateLabel);
					}
				}
				
				$weatherByDay[$forecastDate] = array(
					'date' => $forecastDate,
					'date_label' => htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'),
					'temp_min' => isset($forecast['main']['temp_min']) ? round(floatval($forecast['main']['temp_min'])) : 0,
					'temp_max' => isset($forecast['main']['temp_max']) ? round(floatval($forecast['main']['temp_max'])) : 0,
					'temp' => isset($forecast['main']['temp']) ? round(floatval($forecast['main']['temp'])) : 0,
					'description' => isset($forecast['weather'][0]['description']) ? ucfirst(htmlspecialchars($forecast['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
					'icon' => isset($forecast['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $forecast['weather'][0]['icon']) : '',
					'humidity' => isset($forecast['main']['humidity']) ? intval($forecast['main']['humidity']) : 0,
					'wind_speed' => isset($forecast['wind']['speed']) ? round(floatval($forecast['wind']['speed']) * 3.6, 1) : 0,
					'is_today' => false
				);
			}
		}
	}
	
	// Trier par date et limiter selon le type d'API
	ksort($weatherByDay);
	if ($usePaidAPI) {
		// API payante One Call 3.0 : limiter à 8 jours (aujourd'hui + 7 jours)
		$weatherByDay = array_slice($weatherByDay, 0, 8, true);
	} else {
		// API gratuite : limiter à 6 jours (aujourd'hui + 5 jours)
		$weatherByDay = array_slice($weatherByDay, 0, 6, true);
	}
	
	dol_syslog(__METHOD__.": Données météo récupérées avec succès pour " . count($weatherByDay) . " jours", LOG_DEBUG);
	
	// Préparer les données actuelles pour le retour
	$currentDataForReturn = null;
	if ($usePaidAPI) {
		// Pour l'API payante, utiliser les données de 'current' de la réponse One Call
		if (isset($data['current']) && isset($data['current']['weather'][0])) {
			$currentDataForReturn = array(
				'temp' => isset($data['current']['temp']) ? round(floatval($data['current']['temp'])) : 0,
				'description' => isset($data['current']['weather'][0]['description']) ? ucfirst(htmlspecialchars($data['current']['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
				'icon' => isset($data['current']['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $data['current']['weather'][0]['icon']) : '',
				'humidity' => isset($data['current']['humidity']) ? intval($data['current']['humidity']) : 0,
				'wind_speed' => isset($data['current']['wind_speed']) ? round(floatval($data['current']['wind_speed']) * 3.6, 1) : 0
			);
		}
	} else {
		// Pour l'API gratuite, utiliser les données récupérées séparément
		if ($currentData && isset($currentData['main']) && isset($currentData['weather'][0])) {
			$currentDataForReturn = array(
				'temp' => isset($currentData['main']['temp']) ? round(floatval($currentData['main']['temp'])) : 0,
				'description' => isset($currentData['weather'][0]['description']) ? ucfirst(htmlspecialchars($currentData['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
				'icon' => isset($currentData['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $currentData['weather'][0]['icon']) : '',
				'humidity' => isset($currentData['main']['humidity']) ? intval($currentData['main']['humidity']) : 0,
				'wind_speed' => isset($currentData['wind']['speed']) ? round(floatval($currentData['wind']['speed']) * 3.6, 1) : 0
			);
		}
	}
	
	return array(
		'current' => $currentDataForReturn,
		'forecast' => array_values($weatherByDay)
	);
}

/**
 * Récupère les prévisions météo et filtre les jours favorables (sans pluie ni orage)
 *
 * @param float $latitude Latitude du site
 * @param float $longitude Longitude du site
 * @param string $apiKey Clé API OpenWeatherMap
 * @param string $apiType Type d'API : 'free' (gratuite, 5 jours) ou 'paid' (payante, 8 jours). Par défaut 'free'
 * @return array|false Tableau avec les jours favorables ou false en cas d'erreur
 */
function sites2GetFavorableWeatherDays($latitude, $longitude, $apiKey, $apiType = null)
{
	global $langs, $conf;
	
	// Récupérer le type d'API depuis la configuration si non spécifié
	if ($apiType === null || $apiType === 'free') {
		if (isset($conf->global->SITES2_WEATHER_API_TYPE) && !empty($conf->global->SITES2_WEATHER_API_TYPE)) {
			$apiType = $conf->global->SITES2_WEATHER_API_TYPE;
		} else {
			$apiType = 'free'; // Par défaut, API gratuite
		}
	}
	
	// Validation des paramètres d'entrée
	if (empty($apiKey) || empty($latitude) || empty($longitude)) {
		dol_syslog(__METHOD__.": Paramètres manquants pour récupérer la météo", LOG_WARNING);
		return false;
	}
	
	// Valider que latitude et longitude sont des nombres
	if (!is_numeric($latitude) || !is_numeric($longitude)) {
		dol_syslog(__METHOD__.": Coordonnées invalides", LOG_WARNING);
		return false;
	}
	
	$latitude = floatval($latitude);
	$longitude = floatval($longitude);
	
	// Déterminer l'endpoint selon le type d'API
	$usePaidAPI = ($apiType === 'paid');
	
	if ($usePaidAPI) {
		// API payante : One Call API 3.0 (8 jours de prévisions quotidiennes)
		// Exclure minutely et hourly pour optimiser la réponse
		$url = "https://api.openweathermap.org/data/3.0/onecall?lat=" . urlencode((string)$latitude) . 
		       "&lon=" . urlencode((string)$longitude) . 
		       "&appid=" . urlencode($apiKey) . 
		       "&units=metric&lang=fr" .
		       "&exclude=minutely,hourly,alerts"; // Exclure les données non nécessaires
		dol_syslog(__METHOD__.": Utilisation de l'API payante One Call API 3.0 pour récupérer les prévisions météo", LOG_DEBUG);
	} else {
		// API gratuite : endpoint /forecast (5 jours, données toutes les 3 heures)
		$url = "https://api.openweathermap.org/data/2.5/forecast?lat=" . urlencode((string)$latitude) . 
		       "&lon=" . urlencode((string)$longitude) . 
		       "&appid=" . urlencode($apiKey) . 
		       "&units=metric&lang=fr";
		dol_syslog(__METHOD__.": Utilisation de l'API gratuite (/forecast) pour récupérer les prévisions météo (limité à 5 jours)", LOG_DEBUG);
	}
	
	// Créer un contexte de flux
	$context = stream_context_create([
		'http' => [
			'method' => 'GET',
			'header' => "User-Agent: Dolibarr PHP Application\r\n",
			'timeout' => 10
		]
	]);
	
	// Récupérer les données
	$response = @file_get_contents($url, false, $context);
	
	if ($response === false) {
		dol_syslog(__METHOD__.": Erreur lors de la récupération des données météo", LOG_ERR);
		return false;
	}
	
	$data = json_decode($response, true);
	
	// Vérifier le code de réponse (peut être '200' en string ou 200 en int)
	// L'API payante peut ne pas retourner de champ 'cod' si tout va bien
	if (empty($data)) {
		dol_syslog(__METHOD__.": Réponse API OpenWeatherMap vide", LOG_ERR);
		return false;
	}
	
	// Si un code d'erreur est présent, vérifier qu'il n'est pas une erreur
	if (isset($data['cod']) && $data['cod'] != '200' && $data['cod'] != 200) {
		$errorMsg = isset($data['message']) ? $data['message'] : 'Erreur inconnue';
		$errorMsg .= ' (Code: ' . $data['cod'] . ')';
		dol_syslog(__METHOD__.": Erreur API OpenWeatherMap: " . $errorMsg, LOG_ERR);
		return false;
	}
	
	// Organiser les données par jour et identifier les jours favorables
	$favorableDays = array();
	$weatherByDay = array();
	
	if ($usePaidAPI) {
		// API payante One Call API 3.0 : structure avec 'daily'
		// Vérifier que les données sont présentes
		if (!isset($data['daily']) || !is_array($data['daily']) || empty($data['daily'])) {
			dol_syslog(__METHOD__.": Aucune donnée de prévision quotidienne dans la réponse API One Call 3.0", LOG_WARNING);
			return false;
		}
		
		// API payante : les données sont déjà quotidiennes, structure différente
		foreach ($data['daily'] as $forecast) {
			if (!isset($forecast['dt']) || !isset($forecast['weather'][0])) {
				continue;
			}
			
			$forecastDate = date('Y-m-d', intval($forecast['dt']));
			$weatherCode = isset($forecast['weather'][0]['id']) ? intval($forecast['weather'][0]['id']) : 0;
			
			// Codes météo OpenWeatherMap :
			// 200-299 : Orage (thunderstorm) - DÉFAVORABLE
			// 500-599 : Pluie (rain) - DÉFAVORABLE
			// 600-699 : Neige (snow) - DÉFAVORABLE
			// 800 : Ciel dégagé - FAVORABLE
			// 801-804 : Nuages - FAVORABLE (considéré comme météo clémente)
			
			// Vérifier si cette prévision est défavorable (orage, pluie, neige)
			$isUnfavorable = ($weatherCode >= 200 && $weatherCode < 300) || // Orage
			                 ($weatherCode >= 500 && $weatherCode < 600) || // Pluie
			                 ($weatherCode >= 600 && $weatherCode < 700);    // Neige
			
			$dateLabel = '';
			if ($forecastDate == date('Y-m-d')) {
				$dateLabel = $langs->trans("Today");
			} elseif ($forecastDate == date('Y-m-d', strtotime('+1 day'))) {
				$dateLabel = $langs->trans("Tomorrow");
			} else {
				$dateLabel = date('d/m/Y', intval($forecast['dt']));
			}
			
			// Structure de l'API One Call 3.0 : temp.min, temp.max, temp.day, etc.
			$weatherByDay[$forecastDate] = array(
				'date' => $forecastDate,
				'date_label' => $dateLabel,
				'is_favorable' => !$isUnfavorable, // Favorable si pas défavorable
				'weather_codes' => array($weatherCode),
				'has_unfavorable' => $isUnfavorable,
				'temp_min' => isset($forecast['temp']['min']) ? round(floatval($forecast['temp']['min'])) : 0,
				'temp_max' => isset($forecast['temp']['max']) ? round(floatval($forecast['temp']['max'])) : 0,
				'temp' => isset($forecast['temp']['day']) ? round(floatval($forecast['temp']['day'])) : (isset($forecast['temp']['min']) && isset($forecast['temp']['max']) ? round((floatval($forecast['temp']['min']) + floatval($forecast['temp']['max'])) / 2) : 0),
				'description' => isset($forecast['weather'][0]['description']) ? ucfirst(htmlspecialchars($forecast['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
				'icon' => isset($forecast['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $forecast['weather'][0]['icon']) : '',
			);
		}
	} else {
		// API gratuite : vérifier que les données de prévisions sont présentes
		if (!isset($data['list']) || !is_array($data['list']) || empty($data['list'])) {
			dol_syslog(__METHOD__.": Aucune donnée de prévision dans la réponse API", LOG_WARNING);
			return false;
		}
		
		// API gratuite : les données sont toutes les 3 heures, il faut les agréger par jour
		foreach ($data['list'] as $forecast) {
			if (!isset($forecast['dt']) || !isset($forecast['weather'][0])) {
				continue;
			}
			
			$forecastDate = date('Y-m-d', intval($forecast['dt']));
			$weatherCode = isset($forecast['weather'][0]['id']) ? intval($forecast['weather'][0]['id']) : 0;
			
			// Codes météo OpenWeatherMap :
			// 200-299 : Orage (thunderstorm) - DÉFAVORABLE
			// 300-399 : Bruine (drizzle) - Acceptable mais pas idéal
			// 500-599 : Pluie (rain) - DÉFAVORABLE
			// 600-699 : Neige (snow) - DÉFAVORABLE
			// 700-799 : Conditions atmosphériques (fog, etc.) - Acceptable
			// 800 : Ciel dégagé - FAVORABLE
			// 801-804 : Nuages - FAVORABLE (considéré comme météo clémente)
			
			// Vérifier si cette prévision est défavorable (orage, pluie, neige)
			$isUnfavorable = ($weatherCode >= 200 && $weatherCode < 300) || // Orage
			                 ($weatherCode >= 500 && $weatherCode < 600) || // Pluie
			                 ($weatherCode >= 600 && $weatherCode < 700);    // Neige
			
			if (!isset($weatherByDay[$forecastDate])) {
				$dateLabel = '';
				if ($forecastDate == date('Y-m-d')) {
					$dateLabel = $langs->trans("Today");
				} elseif ($forecastDate == date('Y-m-d', strtotime('+1 day'))) {
					$dateLabel = $langs->trans("Tomorrow");
				} else {
					$dateLabel = date('d/m/Y', intval($forecast['dt']));
				}
				
				$weatherByDay[$forecastDate] = array(
					'date' => $forecastDate,
					'date_label' => $dateLabel,
					'is_favorable' => !$isUnfavorable, // Favorable si pas défavorable
					'weather_codes' => array($weatherCode),
					'has_unfavorable' => $isUnfavorable, // Marquer si une prévision est défavorable
					'temp_min' => isset($forecast['main']['temp_min']) ? round(floatval($forecast['main']['temp_min'])) : 0,
					'temp_max' => isset($forecast['main']['temp_max']) ? round(floatval($forecast['main']['temp_max'])) : 0,
					'temp' => isset($forecast['main']['temp']) ? round(floatval($forecast['main']['temp'])) : 0,
					'description' => isset($forecast['weather'][0]['description']) ? ucfirst(htmlspecialchars($forecast['weather'][0]['description'], ENT_QUOTES, 'UTF-8')) : '',
					'icon' => isset($forecast['weather'][0]['icon']) ? preg_replace('/[^a-z0-9d]/i', '', $forecast['weather'][0]['icon']) : '',
				);
			} else {
				// Mettre à jour avec les données les plus défavorables si nécessaire
				$weatherByDay[$forecastDate]['weather_codes'][] = $weatherCode;
				// Si une prévision est défavorable, le jour devient défavorable
				if ($isUnfavorable) {
					$weatherByDay[$forecastDate]['has_unfavorable'] = true;
					$weatherByDay[$forecastDate]['is_favorable'] = false;
				}
				// Mettre à jour la description et l'icône avec la dernière prévision (pour affichage)
				if (isset($forecast['weather'][0]['description'])) {
					$weatherByDay[$forecastDate]['description'] = ucfirst(htmlspecialchars($forecast['weather'][0]['description'], ENT_QUOTES, 'UTF-8'));
				}
				if (isset($forecast['weather'][0]['icon'])) {
					$weatherByDay[$forecastDate]['icon'] = preg_replace('/[^a-z0-9d]/i', '', $forecast['weather'][0]['icon']);
				}
				// Mettre à jour les températures min/max
				if (isset($forecast['main']['temp_min'])) {
					$tempMin = round(floatval($forecast['main']['temp_min']));
					if ($tempMin < $weatherByDay[$forecastDate]['temp_min'] || $weatherByDay[$forecastDate]['temp_min'] == 0) {
						$weatherByDay[$forecastDate]['temp_min'] = $tempMin;
					}
				}
				if (isset($forecast['main']['temp_max'])) {
					$tempMax = round(floatval($forecast['main']['temp_max']));
					if ($tempMax > $weatherByDay[$forecastDate]['temp_max']) {
						$weatherByDay[$forecastDate]['temp_max'] = $tempMax;
					}
				}
			}
		}
	}
	
	// Filtrer les jours favorables et ne garder que les jours travaillés (lundi-vendredi)
	foreach ($weatherByDay as $day) {
		// Vérifier que c'est un jour travaillé (lundi=1 à vendredi=5)
		$dayOfWeek = date('N', strtotime($day['date'])); // 1=lundi, 7=dimanche
		
		// Un jour est favorable s'il n'a pas de prévision défavorable (pas de pluie, orage, neige)
		// Cela inclut les jours nuageux (801-804), couvert (804), ciel dégagé (800), bruine (300-399), etc.
		// Par défaut, un jour est favorable SAUF s'il a été explicitement marqué comme défavorable
		$isDayFavorable = true;
		if (isset($day['has_unfavorable']) && $day['has_unfavorable'] === true) {
			$isDayFavorable = false;
		}
		
		if (!empty($conf->global->MAIN_FEATURES_LEVEL) && $conf->global->MAIN_FEATURES_LEVEL >= 2) {
			$codes_str = implode(',', $day['weather_codes']);
			dol_syslog(__METHOD__.": Jour ".$day['date']." (".$dayOfWeek.") - Codes: ".$codes_str." - Favorable: ".($isDayFavorable ? 'OUI' : 'NON')." - HasUnfavorable: ".(isset($day['has_unfavorable']) ? ($day['has_unfavorable'] ? 'OUI' : 'NON') : 'NON'), LOG_DEBUG);
		}
		
		// Si aucun flag défavorable n'est défini, le jour est favorable par défaut
		if ($dayOfWeek <= 5 && $isDayFavorable) {
			$favorableDays[] = $day;
		}
	}
	
	// Limiter selon le type d'API
	$maxDays = ($usePaidAPI) ? 8 : 5; // 8 jours pour API payante, 5 jours pour API gratuite
	$favorableDays = array_slice($favorableDays, 0, $maxDays);
	
	dol_syslog(__METHOD__.": " . count($favorableDays) . " jours favorables trouvés sur les " . $maxDays . " prochains jours", LOG_DEBUG);
	
	return $favorableDays;
}

/**
 * Extrait les jours favorables à partir des données météo déjà récupérées
 * (pour éviter les incohérences avec les prévisions affichées)
 *
 * @param array $forecastData Tableau des prévisions déjà récupérées (format de sites2GetWeatherData)
 * @return array Tableau avec les jours favorables
 */
function sites2GetFavorableWeatherDaysFromData($forecastData)
{
	global $langs;
	
	if (empty($forecastData) || !is_array($forecastData)) {
		return array();
	}
	
	$favorableDays = array();
	$weatherByDay = array();
	
	// Traiter les prévisions déjà récupérées
	foreach ($forecastData as $day) {
		if (!isset($day['date']) || !isset($day['icon'])) {
			continue;
		}
		
		$forecastDate = $day['date'];
		$dayOfWeek = date('N', strtotime($forecastDate)); // 1=lundi, 7=dimanche
		
		// Ne garder que les jours travaillés (lundi-vendredi)
		if ($dayOfWeek > 5) {
			continue;
		}
		
		// Extraire le code météo de l'icône ou de la description
		// Les codes météo OpenWeatherMap peuvent être déduits de l'icône
		// 01d/01n = ciel dégagé (800) - FAVORABLE
		// 02d/02n = peu nuageux (801) - FAVORABLE
		// 03d/03n = nuageux (802) - FAVORABLE
		// 04d/04n = couvert (804) - FAVORABLE
		// 09d/09n = pluie (500-599) - DÉFAVORABLE
		// 10d/10n = pluie (500-599) - DÉFAVORABLE
		// 11d/11n = orage (200-299) - DÉFAVORABLE
		// 13d/13n = neige (600-699) - DÉFAVORABLE
		// 50d/50n = brouillard (700-799) - FAVORABLE
		
		$icon = isset($day['icon']) ? $day['icon'] : '';
		$isUnfavorable = false;
		
		// Déterminer si défavorable basé sur l'icône
		// Les icônes OpenWeatherMap commencent par 2 chiffres suivis de 'd' ou 'n'
		if (preg_match('/^(09|10|11|13)/', $icon)) {
			// 09 = pluie, 10 = pluie, 11 = orage, 13 = neige
			$isUnfavorable = true;
		}
		
		// Vérifier aussi la description pour plus de précision
		$description = isset($day['description']) ? strtolower($day['description']) : '';
		if (strpos($description, 'pluie') !== false || 
		    strpos($description, 'orage') !== false || 
		    strpos($description, 'neige') !== false ||
		    strpos($description, 'rain') !== false ||
		    strpos($description, 'thunderstorm') !== false ||
		    strpos($description, 'snow') !== false ||
		    strpos($description, 'shower') !== false) {
			$isUnfavorable = true;
		}
		
		// Un jour est favorable s'il n'est pas défavorable
		// Cela inclut : ciel dégagé, peu nuageux, nuageux, couvert, brouillard
		if (!$isUnfavorable) {
			$favorableDays[] = $day;
		}
	}
	
	// Limiter à 8 jours (maximum disponible avec l'API payante)
	$favorableDays = array_slice($favorableDays, 0, 8);
	
	dol_syslog(__METHOD__.": " . count($favorableDays) . " jours favorables trouvés à partir des données existantes", LOG_DEBUG);
	
	return $favorableDays;
} 