<?php
/**
 * Script d'installation de l'intégration entre le module sites2 et le module équipement
 * 
 * Ce script doit être exécuté depuis le navigateur ou en ligne de commande.
 * Il utilise les paramètres de connexion déjà configurés dans Dolibarr.
 * 
 * Usage web : http://votre-dolibarr/custom/sites2/scripts/install_integration.php
 * Usage CLI : php install_integration.php
 */

// Définir des constantes pour éviter de charger des éléments inutiles
define('NOREQUIREDB', '0');       // On a besoin de la base de données
define('NOREQUIREUSER', '1');
define('NOREQUIRESOC', '1');
define('NOREQUIRETRAN', '1');
define('NOCSRFCHECK', '1');
define('NOTOKENRENEWAL', '1');
define('NOREQUIREMENU', '1');
define('NOREQUIREHTML', '1');
define('NOREQUIREAJAX', '1');

// Détecter si le script est exécuté en ligne de commande
$is_cli = (php_sapi_name() == 'cli');

// Trouver le chemin vers main.inc.php
$path = '';
$res = 0;

if (file_exists("../../../main.inc.php")) {
    $path = "../../../";
} elseif (file_exists("../../../../main.inc.php")) {
    $path = "../../../../";
} elseif (file_exists("../../../../../main.inc.php")) {
    $path = "../../../../../";
} else {
    die("Impossible de trouver le fichier main.inc.php. Assurez-vous d'exécuter ce script depuis le répertoire sites2/scripts/.\n");
}

// Inclure les fichiers nécessaires
require_once $path . "main.inc.php";

// Fonction pour afficher un message
function output($message) {
    global $is_cli;
    if ($is_cli) {
        echo $message . PHP_EOL;
    } else {
        echo $message . "<br/>\n";
    }
}

// Initialisation
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<html><head><title>Installation de l'intégration sites2-équipement</title>";
    echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }";
    echo ".success { color: green; }";
    echo ".warning { color: orange; }";
    echo ".error { color: red; }";
    echo "pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }</style>";
    echo "</head><body>";
    echo "<h1>Installation de l'intégration entre sites2 et équipement</h1>";
}

output("Début de l'installation...");

// Vérification des modules
output("Vérification des modules...");
$modulesSites2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "const WHERE name = 'MAIN_MODULE_SITES2'");
$modulesEquipement = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "const WHERE name = 'MAIN_MODULE_EQUIPEMENT'");

if ($db->num_rows($modulesSites2) == 0) {
    output("<span class='error'>ERREUR: Le module sites2 n'est pas activé.</span>");
    goto end_script;
}

if ($db->num_rows($modulesEquipement) == 0) {
    output("<span class='error'>ERREUR: Le module équipement n'est pas activé.</span>");
    goto end_script;
}

output("<span class='success'>Les deux modules sont activés.</span>");

// Lecture des fichiers SQL
output("Lecture des fichiers SQL...");

$file_add_fields = file_get_contents(dirname(__FILE__) . "/../sql/llx_sites2_site_add_equipment_fields.sql");
if (!$file_add_fields) {
    output("<span class='error'>ERREUR: Impossible de lire le fichier llx_sites2_site_add_equipment_fields.sql</span>");
    goto end_script;
}

$file_triggers = file_get_contents(dirname(__FILE__) . "/../sql/llx_sites2_equipement_triggers.sql");
if (!$file_triggers) {
    output("<span class='error'>ERREUR: Impossible de lire le fichier llx_sites2_equipement_triggers.sql</span>");
    goto end_script;
}

output("<span class='success'>Fichiers SQL lus avec succès.</span>");

// Exécution des requêtes SQL pour ajouter les champs
output("<h2>Étape 1: Ajout des champs dans la table sites2_site</h2>");

// Séparer les requêtes ALTER TABLE
$alter_queries = extractAlterQueries($file_add_fields);
$success_add_fields = true;

foreach ($alter_queries as $query) {
    output("Exécution de: <pre>" . htmlspecialchars($query) . "</pre>");
    
    // Vérifier si on ajoute une colonne déjà existante
    if (preg_match('/ADD COLUMN (\w+)/', $query, $matches)) {
        $column_name = $matches[1];
        $check_column = $db->query("SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "sites2_site LIKE '" . $column_name . "'");
        
        if ($db->num_rows($check_column) > 0) {
            output("<span class='warning'>AVERTISSEMENT: La colonne " . $column_name . " existe déjà dans la table.</span>");
            continue;
        }
    }
    
    // Exécuter la requête
    $result = $db->query($query);
    if (!$result) {
        output("<span class='warning'>AVERTISSEMENT: Erreur lors de l'exécution de la requête: " . $db->lasterror() . "</span>");
        $success_add_fields = false;
    } else {
        output("<span class='success'>Requête exécutée avec succès.</span>");
    }
}

if ($success_add_fields) {
    output("<span class='success'>Structure de la table mise à jour avec succès.</span>");
} else {
    output("<span class='warning'>Des erreurs sont survenues lors de la mise à jour de la structure de la table.</span>");
}

// Exécution des triggers
output("<h2>Étape 2: Création des triggers de synchronisation</h2>");

// Supprimer le délimiteur et séparer les triggers
$trigger_queries = extractTriggerQueries($file_triggers);
$success_triggers = true;

foreach ($trigger_queries as $trigger_name => $query) {
    output("Création du trigger: " . $trigger_name);
    
    // Vérifier si le trigger existe déjà
    $check_trigger = $db->query("SHOW TRIGGERS WHERE `Trigger` = '" . $trigger_name . "'");
    
    if ($db->num_rows($check_trigger) > 0) {
        output("<span class='warning'>AVERTISSEMENT: Le trigger " . $trigger_name . " existe déjà. Tentative de suppression...</span>");
        
        // Supprimer le trigger existant
        $drop_result = $db->query("DROP TRIGGER IF EXISTS " . $trigger_name);
        if (!$drop_result) {
            output("<span class='error'>ERREUR: Impossible de supprimer le trigger existant: " . $db->lasterror() . "</span>");
            $success_triggers = false;
            continue;
        } else {
            output("<span class='success'>Trigger existant supprimé avec succès.</span>");
        }
    }
    
    // Exécuter la requête pour créer le trigger
    $result = $db->query($query);
    if (!$result) {
        output("<span class='error'>ERREUR: Impossible de créer le trigger: " . $db->lasterror() . "</span>");
        $success_triggers = false;
    } else {
        output("<span class='success'>Trigger créé avec succès.</span>");
    }
}

if ($success_triggers) {
    output("<span class='success'>Triggers créés avec succès.</span>");
} else {
    output("<span class='warning'>Des erreurs sont survenues lors de la création des triggers.</span>");
}

// Résumé de l'installation
output("<h2>Résumé de l'installation</h2>");
if ($success_add_fields && $success_triggers) {
    output("<span class='success'>L'installation a été effectuée avec succès.</span>");
} else {
    output("<span class='warning'>L'installation a été effectuée avec des avertissements ou des erreurs.</span>");
}

output("Vous pouvez maintenant associer des équipements à vos sites dans le module sites2.");
output("Rappel: Les relations existantes devront être recréées manuellement.");

// Fin du script
end_script:
if (!$is_cli) {
    echo "</body></html>";
}

/**
 * Fonction pour extraire les requêtes ALTER TABLE du fichier SQL
 */
function extractAlterQueries($sql_content) {
    $queries = array();
    
    // Supprimer les commentaires
    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
    
    // Trouver toutes les requêtes ALTER TABLE
    if (preg_match_all('/ALTER TABLE.*?;/s', $sql_content, $matches)) {
        foreach ($matches[0] as $query) {
            $queries[] = trim($query);
        }
    }
    
    return $queries;
}

/**
 * Fonction pour extraire les requêtes CREATE TRIGGER du fichier SQL
 */
function extractTriggerQueries($sql_content) {
    $triggers = array();
    
    // Supprimer les commentaires
    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
    
    // Supprimer les lignes DELIMITER
    $sql_content = preg_replace('/DELIMITER.*$/m', '', $sql_content);
    
    // Trouver tous les CREATE TRIGGER
    if (preg_match_all('/CREATE TRIGGER\s+(\w+).*?END;/s', $sql_content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $trigger_name = $match[1];
            $trigger_query = trim($match[0]);
            $triggers[$trigger_name] = $trigger_query;
        }
    }
    
    return $triggers;
} 