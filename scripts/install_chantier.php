#!/usr/bin/env php
<?php
/**
 * Script d'installation de la table des chantiers programmés
 * 
 * Ce script doit être exécuté depuis le navigateur ou en ligne de commande.
 * Il utilise les paramètres de connexion déjà configurés dans Dolibarr.
 * 
 * Usage web : http://votre-dolibarr/custom/sites2/scripts/install_chantier.php
 * Usage CLI : php install_chantier.php
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
    echo "<html><head><title>Installation de la table des chantiers programmés</title>";
    echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }";
    echo ".success { color: green; font-weight: bold; }";
    echo ".warning { color: orange; }";
    echo ".error { color: red; font-weight: bold; }";
    echo "pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }</style>";
    echo "</head><body>";
    echo "<h1>Installation de la table des chantiers programmés</h1>";
}

output("Début de l'installation...");

// Vérification du module sites2
output("Vérification du module sites2...");
$modulesSites2 = $db->query("SELECT * FROM " . MAIN_DB_PREFIX . "const WHERE name = 'MAIN_MODULE_SITES2'");

if ($db->num_rows($modulesSites2) == 0) {
    output("<span class='error'>ERREUR: Le module sites2 n'est pas activé.</span>");
    goto end_script;
}

output("<span class='success'>Le module sites2 est activé.</span>");

// Vérifier si la table existe déjà
output("Vérification de l'existence de la table...");
$table_name = MAIN_DB_PREFIX . "sites2_chantier";
$check_table = $db->query("SHOW TABLES LIKE '" . $table_name . "'");

if ($db->num_rows($check_table) > 0) {
    output("<span class='warning'>AVERTISSEMENT: La table " . $table_name . " existe déjà.</span>");
    output("Le script va tenter de la créer quand même (CREATE TABLE IF NOT EXISTS).");
    output("Si la table existe déjà avec la bonne structure, aucune erreur ne devrait survenir.");
}

// Lecture du fichier SQL principal
output("Lecture du fichier SQL...");

$sql_file = dirname(__FILE__) . "/../sql/llx_sites2_chantier.sql";
if (!file_exists($sql_file)) {
    output("<span class='error'>ERREUR: Impossible de trouver le fichier llx_sites2_chantier.sql</span>");
    goto end_script;
}

$sql_content = file_get_contents($sql_file);
if (!$sql_content) {
    output("<span class='error'>ERREUR: Impossible de lire le fichier llx_sites2_chantier.sql</span>");
    goto end_script;
}

output("<span class='success'>Fichier SQL lu avec succès.</span>");

// Lecture du fichier SQL pour ajouter le champ location_type
$sql_file_location = dirname(__FILE__) . "/../sql/llx_sites2_chantier_add_location_type.sql";
if (file_exists($sql_file_location)) {
    output("Lecture du fichier SQL pour le champ location_type...");
    $sql_content_location = file_get_contents($sql_file_location);
    if ($sql_content_location) {
        // Ajouter le contenu au SQL principal
        $sql_content .= "\n\n" . $sql_content_location;
        output("<span class='success'>Fichier location_type lu avec succès.</span>");
    }
}

// Remplacer le préfixe de table par celui configuré dans Dolibarr
$sql_content = str_replace('llx_sites2_chantier', MAIN_DB_PREFIX . 'sites2_chantier', $sql_content);
$sql_content = str_replace('llx_sites2_site', MAIN_DB_PREFIX . 'sites2_site', $sql_content);

// Supprimer les commentaires SQL (-- ...)
$sql_content = preg_replace('/--.*$/m', '', $sql_content);

// Nettoyer le contenu SQL (supprimer les lignes vides multiples)
$sql_content = preg_replace('/\n\s*\n+/', "\n", $sql_content);
$sql_content = trim($sql_content);

// Extraire toutes les requêtes SQL (CREATE TABLE et ALTER TABLE)
$queries = array();

// Extraire la requête CREATE TABLE
if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS.*?ENGINE=innodb;/is', $sql_content, $matches)) {
    $queries[] = $matches[0];
}

// Extraire les requêtes ALTER TABLE (pour le champ location_type)
if (preg_match_all('/ALTER\s+TABLE.*?;/is', $sql_content, $alter_matches)) {
    foreach ($alter_matches[0] as $alter_query) {
        $queries[] = trim($alter_query);
    }
}

// Si aucune requête n'a été trouvée, utiliser la méthode de séparation par point-virgule
if (empty($queries)) {
    $current_query = '';
    $lines = explode("\n", $sql_content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || preg_match('/^--/', $line)) continue; // Ignorer les commentaires
        
        $current_query .= $line . " ";
        
        // Si la ligne se termine par un point-virgule, c'est la fin d'une requête
        if (substr(rtrim($line), -1) == ';') {
            $queries[] = trim($current_query);
            $current_query = '';
        }
    }
}

// Exécution des requêtes SQL
output("<h2>Création de la table des chantiers programmés</h2>");

$success = true;
foreach ($queries as $query) {
    if (empty(trim($query))) continue;
    
    // Nettoyer la requête (supprimer les commentaires)
    $clean_query = preg_replace('/--.*$/m', '', $query);
    $clean_query = trim($clean_query);
    
    // Remplacer le préfixe de table
    $clean_query = str_replace('llx_sites2_chantier', MAIN_DB_PREFIX . 'sites2_chantier', $clean_query);
    
    if (empty($clean_query)) continue;
    
    // Pour les requêtes ALTER TABLE ADD COLUMN, vérifier si la colonne existe déjà
    if (preg_match('/ALTER\s+TABLE.*?ADD\s+COLUMN\s+(\w+)/is', $clean_query, $col_matches)) {
        $column_name = $col_matches[1];
        // Vérifier si la colonne existe déjà
        $check_column = $db->query("SHOW COLUMNS FROM " . MAIN_DB_PREFIX . "sites2_chantier LIKE '" . $column_name . "'");
        if ($check_column && $db->num_rows($check_column) > 0) {
            output("<span class='warning'>AVERTISSEMENT: La colonne " . $column_name . " existe déjà. Ignorée.</span>");
            $db->free($check_column);
            continue;
        }
        if ($check_column) {
            $db->free($check_column);
        }
    }
    
    output("Exécution de: <pre>" . htmlspecialchars($clean_query) . "</pre>");
    
    // Exécuter la requête
    $result = $db->query($clean_query);
    if (!$result) {
        // Si c'est une erreur de colonne existante, ce n'est pas grave
        if (strpos($db->lasterror(), 'Duplicate column') !== false || strpos($db->lasterror(), 'duplicate column') !== false) {
            output("<span class='warning'>AVERTISSEMENT: " . $db->lasterror() . " (ignoré - la colonne existe déjà)</span>");
        } else {
            output("<span class='error'>ERREUR: " . $db->lasterror() . "</span>");
            $success = false;
        }
    } else {
        output("<span class='success'>Requête exécutée avec succès.</span>");
    }
}

// Résumé de l'installation
output("<h2>Résumé de l'installation</h2>");
if ($success) {
    output("<span class='success'>L'installation a été effectuée avec succès.</span>");
    output("La table " . $table_name . " a été créée.");
    output("Vous pouvez maintenant utiliser l'onglet 'Chantier programmé' dans les fiches de sites.");
} else {
    output("<span class='error'>Des erreurs sont survenues lors de l'installation.</span>");
    output("Vérifiez les messages d'erreur ci-dessus.");
}

// Fin du script
end_script:
if (!$is_cli) {
    echo "</body></html>";
}

