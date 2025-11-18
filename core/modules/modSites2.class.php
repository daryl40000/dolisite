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
 * \defgroup   sites2     Module Sites2
 * \brief      Module Sites2 descriptor.
 *
 * \file       htdocs/custom/sites2/core/modules/modSites2.class.php
 * \ingroup    sites2
 * \brief      Description and activation file for module Sites2
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Sites2
 */
class modSites2 extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		// ID reserved for this module
		$this->numero = 192070;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'sites2';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','marketing','interface',etc
		// It is used to group modules by family in module setup page
		$this->family = "crm";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Module label (no space allowed), used if translation string 'ModuleSites2Name' not found (Sites2 is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleSites2Desc' not found (Sites2 is name of module).
		$this->description = "Module de gestion des sites clients des tiers";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Module pour gérer les sites clients des tiers avec calcul des distances";

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = '2.3.6';

		// Key used in llx_const table to save module status enabled/disabled (where SITES2 is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'fa-warehouse';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /sites2/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /sites2/core/modules/barcode)
		// for specific css file (eg: /sites2/css/sites2.css.php)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 0,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				'data' => array(
					'thirdpartycard',
					'globalcard',
					'searchform'
				),
				'entity' => '0'
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
			// Intégration dans la recherche globale
			'fulltext' => array(
				'llx_sites2_site:label,ref,address,zip,town,description' => 'SiteRef'
			)
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/sites2/temp","/sites2/subdir");
		$this->dirs = array("/sites2/temp", "/sites2/site");

		// Config pages. Put here list of php page, stored into sites2/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@sites2");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array('modAgenda'); // Seule dépendance à Agenda pour l'affichage des dates
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("sites2@sites2");

		// Prerequisites
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, 0); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'Sites2WasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('SITES2_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('SITES2_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			// Définition du répertoire de sortie
			1 => array('SITES2_DIR_OUTPUT', 'chaine', 'DOL_DATA_ROOT/sites2', 'Répertoire de sortie pour les documents du module Sites2', 0, 'current', 1),
			2 => array('SITES2_TEMP_DIR', 'chaine', 'DOL_DATA_ROOT/sites2/temp', 'Répertoire temporaire pour les documents du module Sites2', 0, 'current', 1),
			3 => array('SITES2_SITE_DIR', 'chaine', 'DOL_DATA_ROOT/sites2/site', 'Répertoire pour les sites du module Sites2', 0, 'current', 1),
		);

		// Initialiser les structures de répertoires pour le module
		if (!isset($conf->sites2) || !isset($conf->sites2->enabled)) {
			$conf->sites2 = new stdClass();
			$conf->sites2->enabled = 0;
		}
		
		// S'assurer que les répertoires sont configurés correctement
		$conf->sites2->dir_output = str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $this->const[1][2]);
		$conf->sites2->dir_temp = str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $this->const[2][2]);
		
		// Configurer le multidir_output pour le multicompany
		if (!isset($conf->sites2->multidir_output)) {
			$conf->sites2->multidir_output = array();
		}
		$conf->sites2->multidir_output[$conf->entity] = $conf->sites2->dir_output;

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@sites2:$user->rights->sites2->read:/sites2/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@sites2:$user->rights->othermodule->read:/sites2/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view

		// Dictionaries
		$this->dictionaries = array(
			'langs' => 'sites2@sites2',
			// List of tables we want to see into dictonary editor
			'tabname' => array(MAIN_DB_PREFIX.'c_type_contact'),
			// Label of tables
			'tablib' => array($langs->trans('ContactTypeSites2')),
			// Request to select fields
			'tabsql' => array('SELECT f.rowid as rowid, f.code, f.libelle, f.position, f.active FROM '.MAIN_DB_PREFIX.'c_type_contact as f WHERE f.element=\'site\''),
			// Sort order
			'tabsqlsort' => array("position ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield' => array("code,libelle,position"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue' => array("code,libelle,position"),
			// List of fields (list of fields for insert)
			'tabfieldinsert' => array("code,libelle,position,element,source"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid' => array("rowid"),
			// Condition to show each dictionary
			'tabcond' => array($conf->sites2->enabled)
		);

		// Boxes/Widgets
		// Add here list of php file(s) stored in sites2/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'sites2widget1.php@sites2',
			//      'note' => 'Widget provided by Sites2',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/sites2/class/myobject.class.php',
			//      'objectname' => 'MyObject',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->sites2->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->sites2->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->sites2->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'Lire les sites';
		$this->rights[$r][4] = 'site';
		$this->rights[$r][5] = 'read';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'Créer/Modifier les sites';
		$this->rights[$r][4] = 'site';
		$this->rights[$r][5] = 'write';
		$r++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'Supprimer les sites';
		$this->rights[$r][4] = 'site';
		$this->rights[$r][5] = 'delete';
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++]=array(
			'fk_menu'=>'',      // '' if this is a top menu
			'type'=>'top',
			'titre'=>'Sites2Area',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'sites2',
			'leftmenu'=>'',
			'url'=>'/sites2/site_list.php',
			'langs'=>'sites2@sites2',
			'position'=>100+$r,
			'enabled'=>'$conf->sites2->enabled',
			'perms'=>'$user->rights->sites2->site->read',
			'target'=>'',
			'user'=>2,
		);
		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU SITE */
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=sites2',      // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
			'type'=>'left',
			'titre'=>'ListOfSites',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'sites2',
			'leftmenu'=>'sites_list',
			'url'=>'/sites2/site_list.php',
			'langs'=>'sites2@sites2',
			'position'=>1000+$r,
			'enabled'=>'$conf->sites2->enabled',
			'perms'=>'$user->rights->sites2->site->read',
			'target'=>'',
			'user'=>2,
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=sites2,fk_leftmenu=sites_list',
			'type'=>'left',
			'titre'=>'SitesMap',
			'prefix' => img_picto('', 'map-marker', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'sites2',
			'leftmenu'=>'sites_map',
			'url'=>'/sites2/site_map.php',
			'langs'=>'sites2@sites2',
			'position'=>1000+$r,
			'enabled'=>'$conf->sites2->enabled',
			'perms'=>'$user->rights->sites2->site->read',
			'target'=>'',
			'user'=>2,
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=sites2,fk_leftmenu=sites_list',
			'type'=>'left',
			'titre'=>'NewSite',
			'mainmenu'=>'sites2',
			'leftmenu'=>'sites2_new',
			'url'=>'/sites2/site_card.php?action=create',
			'langs'=>'sites2@sites2',
			'position'=>1000+$r,
			'enabled'=>'$conf->sites2->enabled',
			'perms'=>'$user->rights->sites2->site->write',
			'target'=>'',
			'user'=>2,
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=sites2,fk_leftmenu=sites_list',
			'type'=>'left',
			'titre'=>'StatistiquesSites',
			'prefix' => img_picto('', 'fa-chart-pie', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'sites2',
			'leftmenu'=>'sites2_stats',
			'url'=>'/sites2/statistiques.php',
			'langs'=>'sites2@sites2',
			'position'=>1000+$r,
			'enabled'=>'$conf->sites2->enabled',
			'perms'=>'$user->rights->sites2->site->read',
			'target'=>'',
			'user'=>2,
		);
		/* END MODULEBUILDER LEFTMENU SITE */
		
		/* BEGIN MODULEBUILDER LEFTMENU CHANTIER */
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=sites2',      // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy'
			'type'=>'left',
			'titre'=>'ScheduledWorkSites',
			'prefix' => img_picto('', 'fa-calendar-check', 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'sites2',
			'leftmenu'=>'chantier_list',
			'url'=>'/sites2/chantier_list.php',
			'langs'=>'sites2@sites2',
			'position'=>1000+$r,
			'enabled'=>'$conf->sites2->enabled',
			'perms'=>'$user->rights->sites2->site->read',
			'target'=>'',
			'user'=>2,
		);
		/* END MODULEBUILDER LEFTMENU CHANTIER */

		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT */
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ExportSites'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'site@sites2';
		$this->export_permission[$r] = array(array("sites2", "site", "read"));
		$this->export_fields_array[$r] = array(
			's.rowid' => 'Id',
			's.ref' => 'Ref',
			's.label' => 'Label',
			's.description' => 'Description',
			's.address' => 'Address',
			's.zip' => 'Zip',
			's.town' => 'Town',
			's.phone' => 'Phone',
			's.status' => 'Status',
			's.latitude' => 'Latitude',
			's.longitude' => 'Longitude',
			's.distance_km' => 'DistanceKm',
			's.travel_time' => 'TravelTime',
			's.fk_soc' => 'ThirdPartyId',
			'soc.nom' => 'ThirdPartyName',
			's.date_creation' => 'DateCreation',
			's.tms' => 'DateModification',
			'u.login' => 'CreatedBy'
		);
		$this->export_TypeFields_array[$r] = array(
			's.rowid' => 'Numeric',
			's.ref' => 'Text',
			's.label' => 'Text',
			's.description' => 'Text',
			's.address' => 'Text',
			's.zip' => 'Text',
			's.town' => 'Text',
			's.phone' => 'Text',
			's.status' => 'Numeric',
			's.latitude' => 'Numeric',
			's.longitude' => 'Numeric',
			's.distance_km' => 'Numeric',
			's.travel_time' => 'Text',
			's.fk_soc' => 'Numeric',
			'soc.nom' => 'Text',
			's.date_creation' => 'Date',
			's.tms' => 'Date',
			'u.login' => 'Text'
		);
		$this->export_entities_array[$r] = array(
			's.rowid' => 'site',
			's.ref' => 'site',
			's.label' => 'site',
			's.description' => 'site',
			's.address' => 'site',
			's.zip' => 'site',
			's.town' => 'site',
			's.phone' => 'site',
			's.status' => 'site',
			's.latitude' => 'site',
			's.longitude' => 'site',
			's.distance_km' => 'site',
			's.travel_time' => 'site',
			's.fk_soc' => 'site',
			'soc.nom' => 'thirdparty',
			's.date_creation' => 'site',
			's.tms' => 'site',
			'u.login' => 'user'
		);
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM '.MAIN_DB_PREFIX.'sites2_site as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as soc ON s.fk_soc = soc.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON s.fk_user_creat = u.rowid';
		$this->export_sql_end[$r] .= ' WHERE 1 = 1';
		$r++;
		/* END MODULEBUILDER EXPORT MYOBJECT */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
		/* END MODULEBUILDER IMPORT MYOBJECT */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/sites2/sql/');
		if ($result < 0) return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		$this->_load_tables('/sites2/sql/', 'llx_c_type_contact_sites2.sql');

		// Create extrafields during init
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		$sql = array(
			"CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."sites2_site(
				rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
				ref varchar(50) DEFAULT '' NOT NULL,
				label varchar(255) DEFAULT '' NOT NULL,
				description text,
				address text,
				zip varchar(10) DEFAULT '',
				town varchar(50) DEFAULT '',
				phone varchar(20) DEFAULT '',
				type integer DEFAULT 1,
				status integer DEFAULT 0,
				latitude DECIMAL(10,8) DEFAULT NULL,
				longitude DECIMAL(11,8) DEFAULT NULL,
				distance_km double DEFAULT NULL,
				travel_time varchar(50) DEFAULT NULL,
				fk_soc integer DEFAULT NULL,
				note_public text,
				note_private text,
				date_creation datetime NOT NULL, 
				tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
				fk_user_creat integer NOT NULL, 
				fk_user_modif integer DEFAULT NULL,
				import_key varchar(14) DEFAULT NULL,
				model_pdf varchar(255) DEFAULT NULL,
				last_main_doc varchar(255) DEFAULT NULL
			) ENGINE=innodb;",
			"CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."sites2_site_extrafields(
				rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
				tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				fk_object integer NOT NULL,
				import_key varchar(14) DEFAULT NULL
			) ENGINE=innodb;",
			"CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."sites2_chantier(
				rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
				fk_site integer NOT NULL,
				fk_propal integer DEFAULT NULL COMMENT 'Référence au devis (propal)',
				date_debut date DEFAULT NULL COMMENT 'Date de début théorique du chantier',
				date_fin date DEFAULT NULL COMMENT 'Date de fin théorique du chantier',
				note_public text COMMENT 'Note publique sur le chantier',
				note_private text COMMENT 'Note privée sur le chantier',
				status integer DEFAULT 0 COMMENT 'Statut du chantier (0=draft, 1=validated, etc.)',
				location_type integer DEFAULT 1 COMMENT 'Type de localisation (1=extérieur, 0=intérieur)',
				date_creation datetime NOT NULL,
				tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				fk_user_creat integer NOT NULL,
				fk_user_modif integer DEFAULT NULL,
				import_key varchar(14) DEFAULT NULL,
				entity integer DEFAULT 1 NOT NULL,
				INDEX idx_fk_site (fk_site),
				INDEX idx_fk_propal (fk_propal),
				INDEX idx_date_debut (date_debut),
				FOREIGN KEY (fk_site) REFERENCES ".MAIN_DB_PREFIX."sites2_site(rowid) ON DELETE CASCADE
			) ENGINE=innodb;"
		);

		// Définir les chemins de répertoires avec des constantes globales
		$dataDir = DOL_DATA_ROOT.'/sites2';
		$tempDir = $dataDir.'/temp';
		$siteDir = $dataDir.'/site';
		
		// Journaliser l'initialisation
		dol_syslog("modSites2::init - Initialisation des répertoires du module", LOG_DEBUG);
		
		// Création des répertoires de base
		if (!file_exists($dataDir)) {
			if (dol_mkdir($dataDir) < 0) {
				dol_syslog("modSites2::init - Erreur lors de la création du répertoire ".$dataDir, LOG_ERR);
			} else {
				dol_syslog("modSites2::init - Répertoire créé: ".$dataDir, LOG_DEBUG);
			}
		}
		
		// Création des sous-répertoires
		$dirs_to_create = array(
			$tempDir,
			$siteDir
		);
		
		foreach ($dirs_to_create as $dir) {
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					dol_syslog("modSites2::init - Erreur lors de la création du répertoire ".$dir, LOG_ERR);
				} else {
					dol_syslog("modSites2::init - Répertoire créé: ".$dir, LOG_DEBUG);
				}
			}
		}
		
		// Définir les constantes du module
		dolibarr_set_const($this->db, 'SITES2_DIR_OUTPUT', $dataDir, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'SITES2_TEMP_DIR', $tempDir, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($this->db, 'SITES2_SITE_DIR', $siteDir, 'chaine', 0, '', $conf->entity);
		
		// Configuration pour le multicompany
		if (!isset($conf->sites2)) {
			$conf->sites2 = new stdClass();
		}
		
		$conf->sites2->dir_output = $dataDir;
		$conf->sites2->dir_temp = $tempDir;
		
		if (!isset($conf->sites2->multidir_output)) {
			$conf->sites2->multidir_output = array();
		}
		$conf->sites2->multidir_output[$conf->entity] = $dataDir;
		
		if (!isset($conf->sites2->multidir_temp)) {
			$conf->sites2->multidir_temp = array();
		}
		$conf->sites2->multidir_temp[$conf->entity] = $tempDir;
		
		// Journaliser les chemins configurés
		dol_syslog("modSites2::init - SITES2_DIR_OUTPUT=".$dataDir, LOG_DEBUG);
		dol_syslog("modSites2::init - SITES2_TEMP_DIR=".$tempDir, LOG_DEBUG);
		dol_syslog("modSites2::init - SITES2_SITE_DIR=".$siteDir, LOG_DEBUG);

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
} 