<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024  D.A.R.Y.L.
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
 * \file        class/site.class.php
 * \ingroup     sites2
 * \brief       This file is a CRUD class file for Site (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Site
 */
class Site extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'sites2';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'site';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'sites2_site';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for site. Must be the part after the 'object_' into object_site.png
	 */
	public $picto = 'fa-warehouse';

	/**
	 * @var int Désactive l'enregistrement dans l'agenda
	 * 0=Enregistrement dans l'agenda activé, 1=Enregistrement dans l'agenda désactivé
	 */
	public $supprimer_evenements_agenda = 1;


	const STATUS_DRAFT = 0;     // statut brouillon
	const STATUS_VALIDATED = 1; // statut actif
	const STATUS_CANCELED = 9;  // statut inactif


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'ref' => array('type'=>'varchar(50)', 'label'=>'Name', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>2, 'index'=>1, 'searchall'=>1, 'validate'=>'1', 'comment'=>"Reference of object"),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1:((status:=:1) AND (entity:IN:__SHARED_ENTITIES__))', 'label'=>'ThirdParty', 'enabled'=>'$conf->societe->enabled', 'position'=>3000, 'notnull'=>-1, 'visible'=>1, 'index'=>1,),
		'fk_access' => array('type'=>'integer:Access:custom/equipement/class/access.class.php', 'label'=>'Access', 'enabled'=>'isModEnabled("equipement")', 'position'=>3100, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'fk_alarm' => array('type'=>'integer:Alarm:custom/equipement/class/alarm.class.php', 'label'=>'Alarm', 'enabled'=>'isModEnabled("equipement")', 'position'=>3200, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'fk_video' => array('type'=>'integer:Video:custom/equipement/class/video.class.php', 'label'=>'Video', 'enabled'=>'isModEnabled("equipement")', 'position'=>3300, 'notnull'=>0, 'visible'=>0, 'index'=>1,),
		'description' => array('type'=>'text', 'label'=>'Description', 'enabled'=>'1', 'position'=>9000, 'notnull'=>0, 'visible'=>3, 'alwayseditable'=>'1', 'comment'=>"Site description"),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>61, 'notnull'=>0, 'visible'=>0,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>62, 'notnull'=>0, 'visible'=>0,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'town' => array('type'=>'varchar(50)', 'label'=>'Town', 'enabled'=>'1', 'position'=>6000, 'notnull'=>-1, 'visible'=>1, 'comment'=>"Town"),
		'phone' => array('type'=>'varchar(20)', 'label'=>'Phone', 'enabled'=>'1', 'position'=>7000, 'notnull'=>0, 'visible'=>1, 'comment'=>"Phone number"),
		'address' => array('type'=>'text', 'label'=>'Address', 'enabled'=>'1', 'position'=>4000, 'notnull'=>0, 'visible'=>1, 'comment'=>"Site address"),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>8000, 'notnull'=>-1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Ouvert', '9'=>'Fermé'), 'comment'=>"Site status"),
		'zip' => array('type'=>'varchar(10)', 'label'=>'Postcode', 'enabled'=>'1', 'position'=>5000, 'notnull'=>-1, 'visible'=>1, 'comment'=>"Postcode"),
		'type' => array('type'=>'integer', 'label'=>'Type', 'enabled'=>'1', 'position'=>2000, 'notnull'=>-1, 'visible'=>1, 'index'=>1, 'arrayofkeyval'=>array('1'=>'Professionnel', '9'=>'Particulier'), 'comment'=>"Site type"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Name', 'enabled'=>'1', 'position'=>100, 'notnull'=>1, 'visible'=>3, 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'comment'=>"Name of object"),
		'latitude' => array('type'=>'varchar(20)', 'label'=>'Latitude', 'enabled'=>'1', 'position'=>10000, 'notnull'=>0, 'visible'=>3,),
		'longitude' => array('type'=>'varchar(20)', 'label'=>'Longitude', 'enabled'=>'1', 'position'=>11000, 'notnull'=>0, 'visible'=>3,),
		'distance_km' => array('type'=>'double', 'label'=>'DistanceKm', 'enabled'=>'1', 'position'=>12000, 'notnull'=>0, 'visible'=>3, 'comment'=>"Distance en kilomètres depuis le siège social"),
		'travel_time' => array('type'=>'varchar(50)', 'label'=>'TravelTime', 'enabled'=>'1', 'position'=>13000, 'notnull'=>0, 'visible'=>3, 'comment'=>"Temps de trajet depuis le siège social"),
	);
	public $rowid;
	public $ref;
	public $fk_soc;
	public $fk_access;
	public $fk_alarm;
	public $fk_video;
	public $description;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $town;
	public $phone;
	public $address;
	public $status;
	public $zip;
	public $type;
	public $label;
	public $latitude;
	public $longitude;
	public $distance_km;
	public $travel_time;
	// END MODULEBUILDER PROPERTIES

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
		
		// Vérifier si la table des extrafields existe
		if ($this->isextrafieldmanaged) {
			$extrafields_table = MAIN_DB_PREFIX . $this->table_element . '_extrafields';
			$sql = "SHOW TABLES LIKE '" . $extrafields_table . "'";
			$resql = $this->db->query($sql);
			if (!$resql || $this->db->num_rows($resql) == 0) {
				// La table n'existe pas, on désactive la gestion des extrafields
				$this->isextrafieldmanaged = 0;
				dol_syslog(get_class($this) . "::__construct - La table des extrafields n'existe pas, gestion désactivée", LOG_INFO);
			}
			if ($resql) {
				$this->db->free($resql);
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		// Utiliser le label comme référence si non défini
		if (empty($this->ref)) {
			$this->ref = $this->label;
		}
		
		dol_syslog(__METHOD__.": Création d'un nouveau site - Label: ".$this->label.", Ref: ".$this->ref, LOG_DEBUG);

		// Gérer les coordonnées géographiques
		$coordinates_updated = false;
		
		// Si on n'a pas de coordonnées mais une adresse complète, tenter le géocodage
		if (empty($this->latitude) || empty($this->longitude)) {
			if (!empty($this->address) && !empty($this->zip) && !empty($this->town)) {
				dol_syslog(__METHOD__.": Tentative de géocodage pour obtenir les coordonnées", LOG_DEBUG);
				$coordinates_updated = $this->geocodeAddress();
				
				if ($coordinates_updated) {
					dol_syslog(__METHOD__.": Géocodage réussi - Lat: ".$this->latitude.", Lng: ".$this->longitude, LOG_DEBUG);
				} else {
					dol_syslog(__METHOD__.": Échec du géocodage, impossible d'obtenir les coordonnées", LOG_WARNING);
				}
			} else {
				dol_syslog(__METHOD__.": Adresse incomplète, impossible de géocoder", LOG_DEBUG);
			}
		} else {
			// On a déjà des coordonnées
			$coordinates_updated = true;
			dol_syslog(__METHOD__.": Coordonnées déjà fournies - Lat: ".$this->latitude.", Lng: ".$this->longitude, LOG_DEBUG);
		}
		
		// Calculer la distance seulement si on a des coordonnées valides
		if ($coordinates_updated || (!empty($this->latitude) && !empty($this->longitude))) {
			dol_syslog(__METHOD__.": Calcul des distances et temps de trajet", LOG_DEBUG);
			$distance_updated = $this->calculateDistanceFromHQ();
			
			if ($distance_updated) {
				dol_syslog(__METHOD__.": Calcul des distances réussi - ".$this->distance_km." km, ".$this->travel_time, LOG_DEBUG);
			} else {
				dol_syslog(__METHOD__.": Échec du calcul des distances", LOG_WARNING);
				// On continue malgré l'échec du calcul des distances
			}
		}

		// Création en base de données
		$resultcreate = $this->createCommon($user, $notrigger || $this->supprimer_evenements_agenda);
		if ($resultcreate > 0) {
			dol_syslog(__METHOD__.": Création réussie (ID: ".$resultcreate.")", LOG_DEBUG);
		} else {
			dol_syslog(__METHOD__.": Échec de la création - Code d'erreur: ".$resultcreate, LOG_ERR);
		}
		
		return $resultcreate;
	}

	/**
	 * Geocode an address to get coordinates
	 *
	 * @return bool True if geocoding successful, False otherwise
	 */
	private function geocodeAddress()
	{
		global $conf;
		
		// Vérifier que l'adresse est complète
		if (empty($this->address) || empty($this->zip) || empty($this->town)) {
			dol_syslog(__METHOD__.": Adresse incomplète, impossible de géocoder", LOG_WARNING);
			return false;
		}
		
		$address = urlencode($this->address . ' ' . $this->zip . ' ' . $this->town);
		$provider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
		
		dol_syslog(__METHOD__.": Tentative de géocodage pour ".$this->address.", ".$this->zip.", ".$this->town, LOG_DEBUG);
		
		if ($provider == 'googlemaps' && !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY)) {
			// Utilisation de l'API Google Maps Geocoding
			$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=".$conf->global->SITES2_GOOGLE_MAPS_API_KEY;
			dol_syslog(__METHOD__.": Utilisation de Google Maps, URL: ".$url, LOG_DEBUG);
			
			$context = stream_context_create([
				'http' => [
					'method' => 'GET',
					'header' => "User-Agent: Dolibarr PHP Application\r\n"
				]
			]);

			$response = file_get_contents($url, false, $context);
			$data = json_decode($response);

			if (!empty($data) && $data->status == 'OK' && !empty($data->results[0])) {
				$this->latitude = $data->results[0]->geometry->location->lat;
				$this->longitude = $data->results[0]->geometry->location->lng;
				dol_syslog(__METHOD__.": Géocodage réussi avec Google Maps - Lat: ".$this->latitude.", Lng: ".$this->longitude, LOG_DEBUG);
				return true;
			} else {
				dol_syslog(__METHOD__.": Échec du géocodage avec Google Maps - ".(isset($data) ? $data->status : 'Pas de réponse'), LOG_WARNING);
			}
		} else {
			// Utilisation de l'API OpenStreetMap (Nominatim)
			$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=jsonv2";
			dol_syslog(__METHOD__.": Utilisation d'OpenStreetMap, URL: ".$url, LOG_DEBUG);
			
			// Ajouter la clé API si configurée
			if (!empty($conf->global->SITES2_OPENSTREETMAP_API_KEY)) {
				$url .= "&key=".$conf->global->SITES2_OPENSTREETMAP_API_KEY;
			}
			
			// Create a stream context to add User-Agent to the HTTP request
			$context = stream_context_create([
				'http' => [
					'method' => 'GET',
					'header' => "User-Agent: Dolibarr PHP Application\r\n"
				]
			]);

			$response = file_get_contents($url, false, $context);
			$data = json_decode($response);

			if (!empty($data) && count($data) > 0) {
				$this->latitude = $data[0]->lat;
				$this->longitude = $data[0]->lon;
				dol_syslog(__METHOD__.": Géocodage réussi avec OpenStreetMap - Lat: ".$this->latitude.", Lng: ".$this->longitude, LOG_DEBUG);
				return true;
			} else {
				dol_syslog(__METHOD__.": Échec du géocodage avec OpenStreetMap - Pas de résultats", LOG_WARNING);
			}
		}
		
		dol_syslog(__METHOD__.": Échec général du géocodage", LOG_ERR);
		return false;
	}

	/**
	 * Calculate distance and travel time from headquarters or reference agency
	 *
	 * @return bool  True if success, False if error
	 */
	public function calculateDistanceFromHQ()
	{
		global $db, $conf, $DOL_DOCUMENT_ROOT;

		dol_syslog(__METHOD__.": Début du calcul de distance pour le site ".$this->ref, LOG_DEBUG);
		
		// Vérification des coordonnées du site
		if (empty($this->latitude) || empty($this->longitude)) {
			dol_syslog(__METHOD__.": Échec - Latitude ou longitude manquante pour le site", LOG_WARNING);
			return false;
		}

		// Variables pour stocker les coordonnées du point de référence (adresse de référence de l'agence)
		$ref_lat = null;
		$ref_lng = null;
		
		// ÉTAPE 1: Toujours utiliser l'agence de référence configurée dans les paramètres
		// Option 1: Utiliser l'agence de référence configurée dans les paramètres
		if (!empty($conf->global->SITES2_REFERENCE_AGENCY_LATITUDE) && !empty($conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE)) {
			$ref_lat = $conf->global->SITES2_REFERENCE_AGENCY_LATITUDE;
			$ref_lng = $conf->global->SITES2_REFERENCE_AGENCY_LONGITUDE;
			dol_syslog(__METHOD__.": Utilisation des coordonnées de l'agence de référence: lat=$ref_lat, lng=$ref_lng", LOG_DEBUG);
		} else {
			// Si l'agence de référence n'a pas de coordonnées configurées, essayer de les calculer
			if (!empty($conf->global->SITES2_REFERENCE_AGENCY_ADDRESS) && 
				!empty($conf->global->SITES2_REFERENCE_AGENCY_ZIP) && 
				!empty($conf->global->SITES2_REFERENCE_AGENCY_TOWN)) {
				
				dol_syslog(__METHOD__.": Géocodage de l'adresse de l'agence de référence", LOG_DEBUG);
				$address = urlencode($conf->global->SITES2_REFERENCE_AGENCY_ADDRESS . ' ' . 
									 $conf->global->SITES2_REFERENCE_AGENCY_ZIP . ' ' . 
									 $conf->global->SITES2_REFERENCE_AGENCY_TOWN);
				$provider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
				
				if ($provider == 'googlemaps' && !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY)) {
					// Utilisation de l'API Google Maps Geocoding
					$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=".$conf->global->SITES2_GOOGLE_MAPS_API_KEY;
					
					$context = stream_context_create([
						'http' => [
							'method' => 'GET',
							'header' => "User-Agent: Dolibarr PHP Application\r\n"
						]
					]);

					$response = file_get_contents($url, false, $context);
					$data = json_decode($response);

					if (!empty($data) && $data->status == 'OK' && !empty($data->results[0])) {
						$ref_lat = $data->results[0]->geometry->location->lat;
						$ref_lng = $data->results[0]->geometry->location->lng;
						dol_syslog(__METHOD__.": Coordonnées obtenues via Google Maps: lat=$ref_lat, lng=$ref_lng", LOG_DEBUG);
					}
				} else {
					// Utilisation de l'API OpenStreetMap (Nominatim)
					$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=jsonv2";
					
					// Ajouter la clé API si configurée
					if (!empty($conf->global->SITES2_OPENSTREETMAP_API_KEY)) {
						$url .= "&key=".$conf->global->SITES2_OPENSTREETMAP_API_KEY;
					}
					
					// Create a stream context to add User-Agent to the HTTP request
					$context = stream_context_create([
						'http' => [
							'method' => 'GET',
							'header' => "User-Agent: Dolibarr PHP Application\r\n"
						]
					]);

					$response = file_get_contents($url, false, $context);
					$data = json_decode($response);

					if(!empty($data) && isset($data[0])) {
						$ref_lat = $data[0]->lat;
						$ref_lng = $data[0]->lon;
						dol_syslog(__METHOD__.": Coordonnées obtenues via OpenStreetMap: lat=$ref_lat, lng=$ref_lng", LOG_DEBUG);
					}
				}
			} else {
				dol_syslog(__METHOD__.": Adresse de l'agence de référence incomplète ou non configurée", LOG_WARNING);
				return false;
			}
		}
		
		if (empty($ref_lat) || empty($ref_lng)) {
			dol_syslog(__METHOD__.": Impossible d'obtenir les coordonnées de l'agence de référence", LOG_WARNING);
			return false;
		}

		// ÉTAPE 2: Calculer la distance selon le mode configuré (itinéraire réel ou à vol d'oiseau)
		if (!empty($ref_lat) && !empty($ref_lng)) {
			// Assurer que les coordonnées sont des nombres valides (convertir "," en "." si nécessaire)
			$site_lat = (float)str_replace(',', '.', $this->latitude);
			$site_lng = (float)str_replace(',', '.', $this->longitude);
			$ref_lat = (float)str_replace(',', '.', $ref_lat);
			$ref_lng = (float)str_replace(',', '.', $ref_lng);
			
			dol_syslog(__METHOD__.": Calcul entre site [$site_lat, $site_lng] et référence [$ref_lat, $ref_lng]", LOG_DEBUG);
			
			// Vérifier que les coordonnées sont dans des plages valides
			if ($site_lat < -90 || $site_lat > 90 || $site_lng < -180 || $site_lng > 180 || 
				$ref_lat < -90 || $ref_lat > 90 || $ref_lng < -180 || $ref_lng > 180) {
				dol_syslog(__METHOD__.": Coordonnées hors plage valide", LOG_WARNING);
				return false;
			}
			
			// Déterminer le mode de calcul à utiliser
			$calculation_mode = !empty($conf->global->SITES2_DISTANCE_CALCULATION_MODE) ? $conf->global->SITES2_DISTANCE_CALCULATION_MODE : 'route';
			
			// Si le mode est Haversine (vol d'oiseau) ou si le mode route est choisi mais échoue, utiliser Haversine
			if ($calculation_mode === 'haversine') {
				dol_syslog(__METHOD__.": Mode de calcul à vol d'oiseau (Haversine) configuré", LOG_DEBUG);
				return $this->calculateDistanceHaversine($site_lat, $site_lng, $ref_lat, $ref_lng);
			}
			
			// Sinon, tenter d'utiliser l'API de calcul d'itinéraire
			$provider = !empty($conf->global->SITES2_MAP_PROVIDER) ? $conf->global->SITES2_MAP_PROVIDER : 'openstreetmap';
			
			if ($provider == 'googlemaps' && !empty($conf->global->SITES2_GOOGLE_MAPS_API_KEY)) {
				// Utilisation de l'API Google Maps Directions
				$url = "https://maps.googleapis.com/maps/api/directions/json?";
				$url .= "origin=".$ref_lat.",".$ref_lng;
				$url .= "&destination=".$site_lat.",".$site_lng;
				$url .= "&key=".$conf->global->SITES2_GOOGLE_MAPS_API_KEY;
				
				$context = stream_context_create([
					'http' => [
						'method' => 'GET',
						'header' => "User-Agent: Dolibarr PHP Application\r\n"
					]
				]);

				$response = @file_get_contents($url, false, $context);
				if ($response === false) {
					// Erreur de connexion à l'API
					dol_syslog(__METHOD__.": Erreur de connexion à l'API Google Maps Directions", LOG_WARNING);
					// Fallback à la méthode Haversine
					return $this->calculateDistanceHaversine($site_lat, $site_lng, $ref_lat, $ref_lng);
				}
				
				$data = json_decode($response);

				if (!empty($data) && $data->status == 'OK' && !empty($data->routes[0])) {
					// Récupérer la distance et le temps de trajet
					$distanceValue = $data->routes[0]->legs[0]->distance->value; // en mètres
					$durationValue = $data->routes[0]->legs[0]->duration->value; // en secondes
					
					// Convertir en kilomètres et arrondir à 2 décimales
					$this->distance_km = round($distanceValue / 1000, 2);
					
					// Calculer les heures et minutes
					$hours = floor($durationValue / 3600);
					$minutes = round(($durationValue % 3600) / 60);
					
					$this->travel_time = '';
					if ($hours > 0) {
						$this->travel_time .= $hours . 'h ';
					}
					$this->travel_time .= $minutes . 'min';
					
					dol_syslog(__METHOD__.": Distance calculée via Google Maps: ".$this->distance_km." km, Temps de trajet: ".$this->travel_time, LOG_DEBUG);
					return true;
				} else {
					dol_syslog(__METHOD__.": Échec du calcul avec Google Maps Directions - ".(isset($data->status) ? $data->status : 'Erreur inconnue'), LOG_WARNING);
					// Fallback à la méthode Haversine
					return $this->calculateDistanceHaversine($site_lat, $site_lng, $ref_lat, $ref_lng);
				}
			} else {
				// Utilisation de l'API OSRM (Open Source Routing Machine) compatible avec OpenStreetMap
				$url = "https://router.project-osrm.org/route/v1/driving/";
				$url .= $ref_lng.",".$ref_lat.";".$site_lng.",".$site_lat;
				$url .= "?overview=false";
				
				$context = stream_context_create([
					'http' => [
						'method' => 'GET',
						'header' => "User-Agent: Dolibarr PHP Application\r\n"
					]
				]);

				$response = @file_get_contents($url, false, $context);
				if ($response === false) {
					// Erreur de connexion à l'API
					dol_syslog(__METHOD__.": Erreur de connexion à l'API OSRM", LOG_WARNING);
					// Fallback à la méthode Haversine
					return $this->calculateDistanceHaversine($site_lat, $site_lng, $ref_lat, $ref_lng);
				}
				
				$data = json_decode($response);

				if (!empty($data) && $data->code == 'Ok' && !empty($data->routes[0])) {
					// Récupérer la distance et la durée
					$distanceValue = $data->routes[0]->distance; // en mètres
					$durationValue = $data->routes[0]->duration; // en secondes
					
					// Convertir en kilomètres et arrondir à 2 décimales
					$this->distance_km = round($distanceValue / 1000, 2);
					
					// Calculer les heures et minutes
					$hours = floor($durationValue / 3600);
					$minutes = round(($durationValue % 3600) / 60);
					
					$this->travel_time = '';
					if ($hours > 0) {
						$this->travel_time .= $hours . 'h ';
					}
					$this->travel_time .= $minutes . 'min';
					
					dol_syslog(__METHOD__.": Distance calculée via OSRM: ".$this->distance_km." km, Temps de trajet: ".$this->travel_time, LOG_DEBUG);
					return true;
				} else {
					dol_syslog(__METHOD__.": Échec du calcul avec OSRM - ".(isset($data->code) ? $data->code : 'Erreur inconnue'), LOG_WARNING);
					// Fallback à la méthode Haversine
					return $this->calculateDistanceHaversine($site_lat, $site_lng, $ref_lat, $ref_lng);
				}
			}
		}
		
		dol_syslog(__METHOD__.": Impossible de calculer la distance faute de coordonnées de référence", LOG_WARNING);
		return false;
	}
	
	/**
	 * Calculate distance using Haversine formula (straight line distance)
	 * Used as fallback when APIs fail
	 *
	 * @param float $site_lat Site latitude
	 * @param float $site_lng Site longitude
	 * @param float $ref_lat Reference point latitude
	 * @param float $ref_lng Reference point longitude
	 * @return bool Success or failure
	 */
	private function calculateDistanceHaversine($site_lat, $site_lng, $ref_lat, $ref_lng)
	{
		dol_syslog(__METHOD__.": Calcul de distance par méthode Haversine (à vol d'oiseau)", LOG_DEBUG);
		
		// Calcul de la distance en utilisant la formule de Haversine
		$earth_radius = 6371; // Rayon de la Terre en km

		$dLat = deg2rad($site_lat - $ref_lat);
		$dLon = deg2rad($site_lng - $ref_lng);

		$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($ref_lat)) * cos(deg2rad($site_lat)) * sin($dLon/2) * sin($dLon/2);
		$c = 2 * atan2(sqrt($a), sqrt(1-$a));
		$distance = $earth_radius * $c;
		
		// Arrondir à 2 décimales
		$this->distance_km = round($distance, 2);
		
		// Vérifier que la distance est un nombre valide
		if (!is_numeric($this->distance_km) || is_nan($this->distance_km) || is_infinite($this->distance_km)) {
			dol_syslog(__METHOD__.": Distance calculée non valide: ".$this->distance_km, LOG_WARNING);
			return false;
		}

		// Estimation du temps de trajet (approximation basique)
		// On considère une vitesse moyenne de 60 km/h
		$hours = floor($this->distance_km / 60);
		$minutes = round(($this->distance_km / 60 - $hours) * 60);
		
		$this->travel_time = '';
		if ($hours > 0) {
			$this->travel_time .= $hours . 'h ';
		}
		$this->travel_time .= $minutes . 'min';
		
		dol_syslog(__METHOD__.": Distance à vol d'oiseau calculée: ".$this->distance_km." km, Temps de trajet estimé: ".$this->travel_time, LOG_DEBUG);
		return true;
	}

	/**
	 * Update object in database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		// Gérer les coordonnées géographiques
		$coordinates_updated = false;
		
		// Cas 1: Si on n'a pas de coordonnées mais une adresse complète, tenter le géocodage
		if (empty($this->latitude) || empty($this->longitude)) {
			if (!empty($this->address) && !empty($this->zip) && !empty($this->town)) {
				dol_syslog(__METHOD__.": Tentative de géocodage pour obtenir les coordonnées", LOG_DEBUG);
				$coordinates_updated = $this->geocodeAddress();
				
				if ($coordinates_updated) {
					dol_syslog(__METHOD__.": Géocodage réussi - Lat: ".$this->latitude.", Lng: ".$this->longitude, LOG_DEBUG);
				} else {
					dol_syslog(__METHOD__.": Échec du géocodage, impossible d'obtenir les coordonnées", LOG_WARNING);
				}
			} else {
				dol_syslog(__METHOD__.": Adresse incomplète, impossible de géocoder", LOG_DEBUG);
			}
		} else {
			// Cas 2: On a déjà des coordonnées
			$coordinates_updated = true;
			dol_syslog(__METHOD__.": Coordonnées déjà disponibles - Lat: ".$this->latitude.", Lng: ".$this->longitude, LOG_DEBUG);
		}
		
		// Calculer la distance seulement si on a des coordonnées valides
		if ($coordinates_updated || (!empty($this->latitude) && !empty($this->longitude))) {
			dol_syslog(__METHOD__.": Calcul des distances et temps de trajet", LOG_DEBUG);
			$distance_updated = $this->calculateDistanceFromHQ();
			
			if ($distance_updated) {
				dol_syslog(__METHOD__.": Calcul des distances réussi - ".$this->distance_km." km, ".$this->travel_time, LOG_DEBUG);
			} else {
				dol_syslog(__METHOD__.": Échec du calcul des distances", LOG_WARNING);
				// On continue malgré l'échec du calcul des distances
			}
		}

		// Sauvegarde en base de données
		$result = $this->updateCommon($user, $notrigger || $this->supprimer_evenements_agenda);
		if ($result > 0) {
			dol_syslog(__METHOD__.": Mise à jour réussie (ID: ".$this->id.")", LOG_DEBUG);
		} else {
			dol_syslog(__METHOD__.": Échec de la mise à jour (ID: ".$this->id.")", LOG_WARNING);
		}
		
		return $result;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $extrafields, $DOL_DOCUMENT_ROOT;
		
		// S'assurer que $langs est disponible
		global $langs;
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}
		
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			// Créer une référence unique pour le clone
			$object->ref = $this->getNextNumRef();
			// Si la méthode getNextNumRef n'est pas disponible ou retourne une valeur vide
			if (empty($object->ref)) {
				$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref."_".dol_print_date(dol_now(), 'dayhourlog') : $this->fields['ref']['default'];
			}
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}

		// Assurez-vous que les coordonnées GPS restent identiques
		// Les champs distance_km et travel_time seront recalculés lors de la sauvegarde

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
	
	/**
	 * Get next reference value (for a new site)
	 *
	 * @return string      Value
	 */
	private function getNextNumRef()
	{
		global $db, $conf;
		
		// D'abord, on récupère le module de numérotation configuré
		$mask = !empty($conf->global->SITES2_SITE_ADDON) ? $conf->global->SITES2_SITE_ADDON : 'mod_site_standard';
		
		if ($mask == 'mod_site_standard') {
			// Format standard : SIT-{year}-{0000}
			$prefix = 'SIT-'.dol_print_date(dol_now(), '%Y').'-';
			
			// On cherche dans la base de données la plus grande valeur
			$sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".strlen($prefix)." + 1) AS SIGNED)) as max_id";
			$sql .= " FROM ".MAIN_DB_PREFIX."sites2_site";
			$sql .= " WHERE ref LIKE '".$prefix."%'";
			
			$resql = $db->query($sql);
			if ($resql) {
				if ($db->num_rows($resql) > 0) {
					$obj = $db->fetch_object($resql);
					$max = intval($obj->max_id);
				} else {
					$max = 0;
				}
				$db->free($resql);
				$ref = $prefix.sprintf('%04d', $max + 1);
				return $ref;
			}
		}
		
		// Si on n'a pas pu générer une référence, on retourne une chaîne vide
		// et la méthode createFromClone utilisera une référence par défaut
		return '';
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 *	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option						Link option
	 *  @param	    int   	$notooltip					1=Disable tooltip
	 *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param		int		$addlabel					0=Default, 1=Add label into string, >1=Add first chars into string
	 *	@return		string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $hookmanager, $DOL_DOCUMENT_ROOT;
		
		// S'assurer que $langs est disponible
		global $langs;
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$url = dol_buildpath('/sites2/site_card.php', 1).'?id='.$this->id;

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Site").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('sitedao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *	Return the status
	 *
	 *	@param	int		$mode   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@return	string  		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		global $langs;
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}

		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  	int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return 	string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// Toujours s'assurer que $langs est disponible
		global $langs;
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}
		
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Active');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Inactive');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Active');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Inactive');
		}

		// Vérifier que le statut existe dans les tableaux
		if (!isset($status) || !isset($this->labelStatus[$status]) || !isset($this->labelStatusShort[$status])) {
			// Retourner une valeur par défaut si le statut n'existe pas
			return $langs->trans('Unknown');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status8';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 * Return HTML select list of statuses
	 *
	 * @param	int		$selected          Id status selected
	 * @param  	int		$htmlname          Name of HTML select
	 * @param	int		$mode              0=Standard list, 1=Status for ticket
	 * @return 	string                    HTML select with status
	 */
	public function selectLibStatut($selected = 0, $htmlname = 'status')
	{
		// S'assurer que $langs est disponible
		global $langs;
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}

		// Définir les statuts à partir des constantes de classe
		$labelStatusShort = array(
			self::STATUS_DRAFT => $langs->trans('SiteStatusDraftShort'),
			self::STATUS_VALIDATED => $langs->trans('SiteStatusValidatedShort'),
			self::STATUS_CANCELED => $langs->trans('SiteStatusClosedShort')
		);

		$statusType = array(
			self::STATUS_DRAFT => 'status0',
			self::STATUS_VALIDATED => 'status4',
			self::STATUS_CANCELED => 'status8'
		);

		$return = '<select class="flat" name="'.$htmlname.'">';
		$return .= '<option value="-1">&nbsp;</option>';
		
		foreach ($labelStatusShort as $key => $value) {
			$selected_attribute = '';
			if ($key == $selected) {
				$selected_attribute = ' selected="selected"';
			}
			$return .= '<option value="'.$key.'"'.$selected_attribute.'>';
			$return .= $value;
			$return .= '</option>';
		}
		
		$return .= '</select>';
		
		return $return;
	}

	/**
	 * Information on record
	 *
	 * @param  int      $id      Id of record
	 * @return void
	 */
	public function info($id)
	{
		$sql = "SELECT s.rowid, s.date_creation, s.tms as date_modification,";
		$sql .= " s.fk_user_creat, s.fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as s";
		$sql .= " WHERE s.rowid = ".((int) $id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = empty($obj->date_modification) ? '' : $this->db->jdate($obj->date_modification);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialize the object as an example
	 * Used by developers for creating new objects
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		global $langs, $conf;
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}

		$this->id = 1;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->site_name = 'Site spécimen';
		$this->description = 'Ceci est un site spécimen pour les tests et démos';
		$this->address = '123 Rue de l\'exemple';
		$this->additional_address = '';
		$this->zip = '75000';
		$this->town = 'Paris';
		$this->country_id = 1;
		$this->country_code = 'FR';
		$this->latitude = 48.8566;
		$this->longitude = 2.3522;
		$this->distance_km = 0;
		$this->travel_time = '0min';
		$this->status = self::STATUS_VALIDATED;
		$this->date_creation = dol_now();
		$this->tms = dol_now();
		$this->fk_user_creat = 1;
		$this->fk_user_modif = 1;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	int		$id			Id object
	 * @param	string	$ref		Ref
	 * @return	int					<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $calculate_distance = false)
	{
		$result = $this->fetchCommon($id, $ref);
		
		// Recalcul automatique des distances et temps de trajet uniquement si explicitement demandé
		if ($result > 0 && $calculate_distance && !empty($this->latitude) && !empty($this->longitude)) {
			$this->calculateDistanceFromHQ();
		}
		
		//if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === 0) {
					$sqlwhere[] = $key . ' LIKE \'' . $this->db->escape($value) . '\'';
				} else {
					$sqlwhere[] = $key . ' = \'' . $this->db->escape($value) . '\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param  User $user      User that deletes
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		global $conf, $langs, $DOL_DOCUMENT_ROOT;
		
		// S'assurer que $langs est disponible
		if (!is_object($langs)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', array());
			$langs->setDefaultLang(empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
			$langs->load("sites2@sites2");
		}

		dol_syslog(__METHOD__ . ": début de la suppression du site ID=" . $this->id, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		// Appeler les triggers si nécessaire
		if (!$error && !$notrigger) {
			dol_syslog(__METHOD__ . ": appel des triggers avant suppression", LOG_DEBUG);
			// Call triggers
			$result = $this->call_trigger('SITE_DELETE', $user);
			if ($result < 0) {
				$error++;
				dol_syslog(__METHOD__ . ": erreur lors de l'appel des triggers: " . $this->error, LOG_ERR);
			}
			// End call triggers
		}

		// Supprimer les liens avec les catégories (si le module des catégories est activé)
		if (!$error && !empty($conf->categorie->enabled)) {
			dol_syslog(__METHOD__ . ": suppression des liens avec les catégories", LOG_DEBUG);
			
			// Utiliser la table llx_categorie_object qui est standard dans Dolibarr
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "categorie_object";
			$sql .= " WHERE fk_object = " . $this->id . " AND type = " . $this->db->escape($this->element);
			
			$resql = $this->db->query($sql);
			if (!$resql) {
				// Si la requête échoue, on le note mais on ne bloque pas la suppression
				dol_syslog(__METHOD__ . ": avertissement - erreur lors de la suppression des catégories: " . $this->db->lasterror(), LOG_WARNING);
				// Ne pas générer d'erreur ici pour éviter de bloquer la suppression
			}
		}

		// Supprimer les contacts associés au site
		if (!$error) {
			dol_syslog(__METHOD__ . ": suppression des contacts associés", LOG_DEBUG);
			
			// Vérifier d'abord si la requête de sélection des types de contact fonctionne
			$sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_type_contact WHERE element = '" . $this->element . "'";
			$resql_check = $this->db->query($sql_check);
			
			if ($resql_check && $this->db->num_rows($resql_check) > 0) {
				// Si des types de contact existent, on supprime les liens
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "element_contact";
				$sql .= " WHERE element_id = " . $this->id . " AND fk_c_type_contact IN ";
				$sql .= " (SELECT rowid FROM " . MAIN_DB_PREFIX . "c_type_contact WHERE element = '" . $this->element . "')";
				$resql = $this->db->query($sql);
				
				if (!$resql) {
					dol_syslog(__METHOD__ . ": avertissement - erreur lors de la suppression des contacts: " . $this->db->lasterror(), LOG_WARNING);
					// Ne pas générer d'erreur ici pour éviter de bloquer la suppression
				}
			} else {
				// Sinon, on essaie de supprimer directement avec le type d'élément
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "element_contact";
				$sql .= " WHERE element_id = " . $this->id . " AND element_type = '" . $this->element . "'";
				$resql = $this->db->query($sql);
				
				if (!$resql) {
					dol_syslog(__METHOD__ . ": avertissement - erreur lors de la suppression des contacts (méthode alternative): " . $this->db->lasterror(), LOG_WARNING);
					// Ne pas générer d'erreur ici pour éviter de bloquer la suppression
				}
			}
			
			if ($resql_check) {
				$this->db->free($resql_check);
			}
		}

		// Supprimer les événements liés au site
		if (!$error) {
			dol_syslog(__METHOD__ . ": suppression des événements associés", LOG_DEBUG);
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "actioncomm";
			$sql .= " WHERE fk_element = " . $this->id . " AND elementtype = '" . $this->element . "@sites2'";
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_syslog(__METHOD__ . ": avertissement - erreur lors de la suppression des événements: " . $this->db->lasterror(), LOG_WARNING);
				// Ne pas générer d'erreur ici pour éviter de bloquer la suppression
			}
		}

		// Supprimer les liens avec les documents
		if (!$error) {
			dol_syslog(__METHOD__ . ": suppression des liens vers les documents", LOG_DEBUG);
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "links";
			$sql .= " WHERE objecttype = '" . $this->element . "' AND fk_object = " . $this->id;
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_syslog(__METHOD__ . ": avertissement - erreur lors de la suppression des liens vers les documents: " . $this->db->lasterror(), LOG_WARNING);
				// Ne pas générer d'erreur ici pour éviter de bloquer la suppression
			}
		}

		// Supprimer les extrafields
		if (!$error && $this->isextrafieldmanaged) {
			dol_syslog(__METHOD__ . ": vérification de l'existence de la table des extrafields", LOG_DEBUG);
			
			// Vérifier si la table des extrafields existe avant d'essayer de les supprimer
			$extrafields_table = MAIN_DB_PREFIX . $this->table_element . '_extrafields';
			$sql_check = "SHOW TABLES LIKE '" . $extrafields_table . "'";
			$resql_check = $this->db->query($sql_check);
			
			if ($resql_check && $this->db->num_rows($resql_check) > 0) {
				// La table existe, on peut supprimer les extrafields
				dol_syslog(__METHOD__ . ": suppression des extrafields", LOG_DEBUG);
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$error++;
					dol_syslog(__METHOD__ . ": erreur lors de la suppression des extrafields: " . $this->error, LOG_ERR);
				}
			} else {
				// La table n'existe pas, on le note mais on continue
				dol_syslog(__METHOD__ . ": la table des extrafields n'existe pas, aucune suppression nécessaire", LOG_INFO);
			}
			
			if ($resql_check) {
				$this->db->free($resql_check);
			}
		}

		// Supprimer l'entrée principale
		if (!$error) {
			dol_syslog(__METHOD__ . ": suppression de l'enregistrement principal", LOG_DEBUG);
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid = ' . $this->id;
			
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(__METHOD__ . ": erreur lors de la suppression de l'enregistrement principal: " . $this->error, LOG_ERR);
			}
		}

		// Supprimer les fichiers physiques associés
		if (!$error) {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			$upload_dir = $conf->sites2->dir_output . "/site/" . dol_sanitizeFileName($this->ref);
			dol_syslog(__METHOD__ . ": suppression des fichiers dans " . $upload_dir, LOG_DEBUG);
			if (dol_delete_dir_recursive($upload_dir)) {
				dol_syslog(__METHOD__ . ": les fichiers ont été supprimés avec succès", LOG_DEBUG);
			} else {
				dol_syslog(__METHOD__ . ": avertissement - impossible de supprimer complètement les fichiers", LOG_WARNING);
				// Ne pas générer d'erreur ici, ce n'est qu'un avertissement
			}
		}

		// Commit ou rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			dol_syslog(__METHOD__ . ": rollback de la transaction à cause d'erreurs", LOG_ERR);
			return -1 * $error;
		} else {
			$this->db->commit();
			dol_syslog(__METHOD__ . ": commit de la transaction - suppression réussie", LOG_DEBUG);
			return 1;
		}
	}

	/**
	 * Add a link between site and a contact
	 *
	 * @param int     $fk_socpeople  ID of contact
	 * @param int     $type_contact  Type of contact (see c_type_contact)
	 * @param string  $source        external=Contact comes from a third party (customer, supplier...), internal=Contact comes from users
	 * @param int     $notrigger     Disable all triggers
	 * @param int     $force_external Force to accept contact from any third party, not just the linked one (1=Yes, 0=No)
	 * @return int                   <=0 if KO, >0 if OK
	 */
	public function add_contact($fk_socpeople, $type_contact, $source = 'external', $notrigger = 0, $force_external = 0)
	{
		global $user, $langs;

		dol_syslog(__METHOD__ . ": fk_socpeople=" . $fk_socpeople . ", type_contact=" . $type_contact . ", source=" . $source . ", force_external=" . $force_external);

		// Vérifier que les paramètres sont valides
		if ($fk_socpeople <= 0) {
			$this->error = "ErrorBadValueForContactId";
			dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
			return -1;
		}

		// S'assurer que le site est associé à un tiers
		if (empty($this->fk_soc)) {
			$this->error = 'SiteHasNoThirdParty';
			dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
			return -2;
		}

		// Vérifier que le contact appartient bien au tiers associé au site
		// ou que l'option force_external est activée
		if ($source == 'external' && !$force_external) {
			$sql = "SELECT fk_soc FROM " . MAIN_DB_PREFIX . "socpeople WHERE rowid = " . $fk_socpeople;
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					if ($obj->fk_soc != $this->fk_soc) {
						$this->error = "ContactNotBelongToThirdParty";
						dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
						return -3;
					}
				} else {
					$this->error = "ContactNotFound";
					dol_syslog(__METHOD__ . ": Contact not found, ID=" . $fk_socpeople, LOG_ERR);
					return -4;
				}
				$this->db->free($resql);
			} else {
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
				return -5;
			}
		}

		// Vérifier si le lien existe déjà - adapté pour la structure de table actuelle
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "element_contact WHERE";
		$sql .= " element_id = " . $this->id;
		$sql .= " AND fk_c_type_contact = " . (int) $type_contact;
		$sql .= " AND fk_socpeople = " . (int) $fk_socpeople;
		
		// Vérifier si la colonne element_type existe
		$sql_check = "SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "element_contact LIKE 'element_type'";
		$resql_check = $this->db->query($sql_check);
		$has_element_type = false;

		if ($resql_check && $this->db->num_rows($resql_check) > 0) {
			// La colonne element_type existe
			$has_element_type = true;
			$sql .= " AND element_type = '" . $this->db->escape($this->element) . "'";
		} else {
			// La colonne element_type n'existe pas, utiliser la colonne standard fk_c_type_contact
			dol_syslog(__METHOD__ . ": La colonne element_type n'existe pas, utilisation de fk_c_type_contact uniquement", LOG_DEBUG);
		}

		// Libérer le résultat de la vérification
		if ($resql_check) {
			$this->db->free($resql_check);
			$resql_check = null;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$this->error = "DB_ERROR_RECORD_ALREADY_EXISTS";
				dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
				$this->db->free($resql);
				return -6;
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
			return -7;
		}

		// Si tout est OK, on peut ajouter le lien
		// Adapter la requête d'insertion en fonction de la structure de la table
		if ($has_element_type) {
			// Avec la colonne element_type
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "element_contact";
			$sql .= " (element_id, fk_socpeople, fk_c_type_contact, element_type, datecreate) VALUES ";
			$sql .= " (" . $this->id . ",";
			$sql .= " " . $fk_socpeople . ",";
			$sql .= " " . $type_contact . ",";
			$sql .= " '" . $this->db->escape($this->element) . "',";
			$sql .= " '" . $this->db->idate(dol_now()) . "'";
			$sql .= ")";
		} else {
			// Sans la colonne element_type
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "element_contact";
			$sql .= " (element_id, fk_socpeople, fk_c_type_contact, datecreate) VALUES ";
			$sql .= " (" . $this->id . ",";
			$sql .= " " . $fk_socpeople . ",";
			$sql .= " " . $type_contact . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "'";
			$sql .= ")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			// Appeler le trigger si nécessaire
			if (!$notrigger) {
				// Trigger_name à définir dans les constantes du module
				$trigger_name = 'SITE_CONTACT_ADD';
				$result = $this->call_trigger($trigger_name, $user);
				if ($result < 0) return -8;
			}

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
			return -10;
		}
	}

	/**
	 * Delete a link to a contact
	 *
	 * @param int $lineid ID of the link contact site
	 * @param int $notrigger Disable all triggers
	 * @return int <=0 if KO, >0 if OK
	 */
	public function delete_contact($lineid, $notrigger = 0)
	{
		global $user;

		dol_syslog(__METHOD__ . ": lineid=" . $lineid);

		// Vérification supplémentaire optionnelle: s'assurer que le contact appartient bien à ce site
		// Cette étape est facultative si la vérification sur lineid est suffisante
		/*
		$sql = "SELECT ec.rowid FROM " . MAIN_DB_PREFIX . "element_contact as ec";
		$sql .= " WHERE ec.rowid = " . $lineid;
		$sql .= " AND ec.element_id = " . $this->id;
		$resql = $this->db->query($sql);
		if (!$resql || $this->db->num_rows($resql) == 0) {
			$this->error = "ContactNotLinkedToThisSite";
			return -2;
		}
		*/

		// Suppression du lien
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "element_contact";
		$sql .= " WHERE rowid = " . $lineid;

		$resql = $this->db->query($sql);
		if ($resql) {
			// Appeler le trigger si nécessaire
			if (!$notrigger) {
				// Trigger_name à définir dans les constantes du module
				$trigger_name = 'SITE_CONTACT_DELETE';
				$result = $this->call_trigger($trigger_name, $user);
				if ($result < 0) return -1;
			}

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Toggle the status of a contact link
	 *
	 * @param int $lineid ID of the contact link
	 * @return int >0 if success, <0 if error
	 */
	public function swapContactStatus($lineid)
	{
		dol_syslog(__METHOD__ . ": lineid=" . $lineid);

		// Vérifier d'abord si le statut actuel
		$sql = "SELECT statut FROM " . MAIN_DB_PREFIX . "element_contact";
		$sql .= " WHERE rowid = " . $lineid;

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($obj = $this->db->fetch_object($resql)) {
				$statut = ($obj->statut == 1) ? 0 : 1;
				$this->db->free($resql);

				// Mettre à jour le statut
				$sql = "UPDATE " . MAIN_DB_PREFIX . "element_contact SET";
				$sql .= " statut = " . $statut;
				$sql .= " WHERE rowid = " . $lineid;

				$resql = $this->db->query($sql);
				if ($resql) {
					return 1;
				} else {
					$this->error = $this->db->lasterror();
					dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
					return -2;
				}
			} else {
				$this->error = "ContactLinkNotFound";
				dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
				return -3;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__ . ": " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Fonction pour générer un select des sites
	 *
	 * @param   int     $selected       ID du site sélectionné
	 * @param   string  $htmlname       Nom du champ select
	 * @param   int     $showempty      1=Ajouter une ligne vide, 0=Pas de ligne vide
	 * @param   string  $filter         Filtre SQL additionnel sur les sites
	 * @param   string  $htmloptions    Options supplémentaires pour la balise select
	 * @return  string                  Le champ select HTML
	 */
	public function select_sites($selected = 0, $htmlname = 'fk_site', $showempty = 1, $filter = '', $htmloptions = '')
	{
		global $langs;
		
		$out = '';
		$sql = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "sites2_site";
		$sql .= " WHERE 1=1";
		if ($filter) {
			$sql .= " AND " . $filter;
		}
		$sql .= " ORDER BY ref";
		
		$result = $this->db->query($sql);
		if ($result) {
			$out .= '<select class="flat" name="' . $htmlname . '" ' . $htmloptions . '>';
			if ($showempty) {
				$out .= '<option value="">&nbsp;</option>';
			}
			
			$num = $this->db->num_rows($result);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($result);
					$out .= '<option value="' . $obj->rowid . '"';
					if ($selected > 0 && $selected == $obj->rowid) {
						$out .= ' selected';
					}
					$out .= '>' . $obj->ref . ' - ' . $obj->label . '</option>';
					$i++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error($this->db);
		}
		
		return $out;
	}
} 