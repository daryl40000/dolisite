#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2024 D.A.R.Y.L.
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
 * Script de migration des relations entre équipements et sites
 * Ce script transfère les relations du module site vers le module sites2
 * 
 * Usage: php migrate_equipment_relations.php
 */

if (! defined('NOREQUIREDB')) define('NOREQUIREDB', '1');
if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (! defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN', '1');
if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

// Load Dolibarr environment
$res = 0;
// Try master.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/master.inc.php";
}
// Try master.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/master.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/master.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/master.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/master.inc.php";
}
// Try master.inc.php using relative path
if (!$res && file_exists("../master.inc.php")) {
	$res = @include "../master.inc.php";
}
if (!$res && file_exists("../../master.inc.php")) {
	$res = @include "../../master.inc.php";
}
if (!$res && file_exists("../../../master.inc.php")) {
	$res = @include "../../../master.inc.php";
}
if (!$res) {
	die("Include of master fails");
}

// Charger l'utilisateur admin
$user = new User($db);
$user->fetch(1); // ID 1 devrait être l'admin par défaut
$user->getrights();

// Vérifier que les modules nécessaires sont installés
if (!isModEnabled('sites2')) {
    die("Le module sites2 n'est pas activé. Veuillez l'activer avant d'exécuter ce script.\n");
}

if (!isModEnabled('site')) {
    die("L'ancien module site n'est pas activé. Impossible de migrer les données.\n");
}

if (!isModEnabled('equipement')) {
    die("Le module equipement n'est pas activé. Veuillez l'activer avant d'exécuter ce script.\n");
}

// Fonction pour journaliser les messages
function migrationLog($message) {
    echo date('Y-m-d H:i:s') . ' - ' . $message . "\n";
}

// Début de la migration
migrationLog("Démarrage de la migration des relations équipements-sites...");

// Étape 1: Vérifier la structure des tables
migrationLog("Vérification de la structure des tables...");

// Vérifier si les champs nécessaires existent dans sites2_site
$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."sites2_site LIKE 'fk_access'";
$resql = $db->query($sql);
if ($db->num_rows($resql) == 0) {
    migrationLog("ERREUR: Le champ fk_access n'existe pas dans la table sites2_site. Veuillez exécuter le script SQL d'ajout des champs d'abord.");
    die();
}

$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."sites2_site LIKE 'fk_alarm'";
$resql = $db->query($sql);
if ($db->num_rows($resql) == 0) {
    migrationLog("ERREUR: Le champ fk_alarm n'existe pas dans la table sites2_site. Veuillez exécuter le script SQL d'ajout des champs d'abord.");
    die();
}

$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."sites2_site LIKE 'fk_video'";
$resql = $db->query($sql);
if ($db->num_rows($resql) == 0) {
    migrationLog("ERREUR: Le champ fk_video n'existe pas dans la table sites2_site. Veuillez exécuter le script SQL d'ajout des champs d'abord.");
    die();
}

// Étape 2: Vérifier si les champs existent dans l'ancienne table
// Nous devons d'abord vérifier si ces champs existent vraiment dans l'ancienne table
$oldFieldsExist = true;

$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."site_site LIKE 'fk_access'";
$resql = $db->query($sql);
if ($db->num_rows($resql) == 0) {
    migrationLog("AVERTISSEMENT: Le champ fk_access n'existe pas dans la table site_site. La migration de ce champ sera ignorée.");
    $oldFieldsExist = false;
} else {
    migrationLog("Le champ fk_access existe dans la table site_site.");
}

$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."site_site LIKE 'fk_alarm'";
$resql = $db->query($sql);
if ($db->num_rows($resql) == 0) {
    migrationLog("AVERTISSEMENT: Le champ fk_alarm n'existe pas dans la table site_site. La migration de ce champ sera ignorée.");
    $oldFieldsExist = false;
} else {
    migrationLog("Le champ fk_alarm existe dans la table site_site.");
}

$sql = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."site_site LIKE 'fk_video'";
$resql = $db->query($sql);
if ($db->num_rows($resql) == 0) {
    migrationLog("AVERTISSEMENT: Le champ fk_video n'existe pas dans la table site_site. La migration de ce champ sera ignorée.");
    $oldFieldsExist = false;
} else {
    migrationLog("Le champ fk_video existe dans la table site_site.");
}

if (!$oldFieldsExist) {
    migrationLog("AVERTISSEMENT: Certains champs n'existent pas dans l'ancienne table. La migration se fera en utilisant les équipements liés par leur champ fk_site.");
}

// Étape 3: Migration des relations équipements-sites
migrationLog("Migration des relations équipements-sites...");

// 3.1 Vérifier les correspondances entre les anciens et nouveaux sites
migrationLog("Vérification des correspondances entre les anciens et nouveaux sites...");

// Créer une table de correspondance des sites
$siteMappings = array();
$sql = "SELECT old.rowid as old_id, new.rowid as new_id 
        FROM ".MAIN_DB_PREFIX."site_site as old, ".MAIN_DB_PREFIX."sites2_site as new 
        WHERE old.ref = new.ref";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    migrationLog("$num sites correspondants trouvés.");
    
    $i = 0;
    while ($i < $num) {
        $obj = $db->fetch_object($resql);
        $siteMappings[$obj->old_id] = $obj->new_id;
        $i++;
    }
    $db->free($resql);
} else {
    migrationLog("ERREUR: Impossible de récupérer les correspondances de sites.");
    die();
}

// 3.2 Mise à jour des relations pour les contrôles d'accès
migrationLog("Migration des relations pour les contrôles d'accès...");
$countAccess = 0;

$sql = "SELECT a.rowid, a.fk_site FROM ".MAIN_DB_PREFIX."equipement_access as a";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    migrationLog("$num contrôles d'accès trouvés.");
    
    $i = 0;
    while ($i < $num) {
        $obj = $db->fetch_object($resql);
        
        if (isset($siteMappings[$obj->fk_site])) {
            // Mettre à jour la relation dans la nouvelle table
            $sql2 = "UPDATE ".MAIN_DB_PREFIX."sites2_site SET fk_access = ".$obj->rowid." WHERE rowid = ".$siteMappings[$obj->fk_site];
            $resql2 = $db->query($sql2);
            
            if ($resql2) {
                $countAccess++;
            } else {
                migrationLog("ERREUR: Impossible de mettre à jour la relation pour le contrôle d'accès ID ".$obj->rowid);
            }
        }
        
        $i++;
    }
    $db->free($resql);
    migrationLog("$countAccess relations de contrôles d'accès migrées avec succès.");
} else {
    migrationLog("ERREUR: Impossible de récupérer les contrôles d'accès.");
}

// 3.3 Mise à jour des relations pour les alarmes
migrationLog("Migration des relations pour les alarmes...");
$countAlarm = 0;

$sql = "SELECT a.rowid, a.fk_site FROM ".MAIN_DB_PREFIX."equipement_alarm as a";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    migrationLog("$num alarmes trouvées.");
    
    $i = 0;
    while ($i < $num) {
        $obj = $db->fetch_object($resql);
        
        if (isset($siteMappings[$obj->fk_site])) {
            // Mettre à jour la relation dans la nouvelle table
            $sql2 = "UPDATE ".MAIN_DB_PREFIX."sites2_site SET fk_alarm = ".$obj->rowid." WHERE rowid = ".$siteMappings[$obj->fk_site];
            $resql2 = $db->query($sql2);
            
            if ($resql2) {
                $countAlarm++;
            } else {
                migrationLog("ERREUR: Impossible de mettre à jour la relation pour l'alarme ID ".$obj->rowid);
            }
        }
        
        $i++;
    }
    $db->free($resql);
    migrationLog("$countAlarm relations d'alarmes migrées avec succès.");
} else {
    migrationLog("ERREUR: Impossible de récupérer les alarmes.");
}

// 3.4 Mise à jour des relations pour les enregistreurs vidéo
migrationLog("Migration des relations pour les enregistreurs vidéo...");
$countVideo = 0;

$sql = "SELECT v.rowid, v.fk_site FROM ".MAIN_DB_PREFIX."equipement_video as v";
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    migrationLog("$num enregistreurs vidéo trouvés.");
    
    $i = 0;
    while ($i < $num) {
        $obj = $db->fetch_object($resql);
        
        if (isset($siteMappings[$obj->fk_site])) {
            // Mettre à jour la relation dans la nouvelle table
            $sql2 = "UPDATE ".MAIN_DB_PREFIX."sites2_site SET fk_video = ".$obj->rowid." WHERE rowid = ".$siteMappings[$obj->fk_site];
            $resql2 = $db->query($sql2);
            
            if ($resql2) {
                $countVideo++;
            } else {
                migrationLog("ERREUR: Impossible de mettre à jour la relation pour l'enregistreur vidéo ID ".$obj->rowid);
            }
        }
        
        $i++;
    }
    $db->free($resql);
    migrationLog("$countVideo relations d'enregistreurs vidéo migrées avec succès.");
} else {
    migrationLog("ERREUR: Impossible de récupérer les enregistreurs vidéo.");
}

// Fin de la migration
migrationLog("Migration terminée.");
migrationLog("Résumé: $countAccess contrôles d'accès, $countAlarm alarmes et $countVideo enregistreurs vidéo migrés.");
?> 