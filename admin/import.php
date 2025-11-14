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
 * \file    sites2/admin/import.php
 * \ingroup sites2
 * \brief   Page for data import
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
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user, $conf, $db;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
dol_include_once('/sites2/lib/sites2.lib.php');
dol_include_once('/sites2/class/site.class.php');

// Translations
$langs->loadLangs(array("admin", "sites2@sites2"));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$upload_dir = $conf->sites2->dir_temp;

/*
 * Actions
 */

if ($action == 'import' && !empty($_FILES['importfile']['tmp_name'])) {
    $importfile = $_FILES['importfile']['tmp_name'];
    $skip_distance_calculation = GETPOST('skip_distance_calculation', 'int');
    
    // Vérifier que le fichier est bien un CSV
    if (is_uploaded_file($importfile) && pathinfo($_FILES['importfile']['name'], PATHINFO_EXTENSION) == 'csv') {
        // Prétraitement du fichier CSV pour s'assurer qu'il est correctement formaté
        $processedFile = preprocessCSVFile($importfile);
        if ($processedFile === false) {
            setEventMessages($langs->trans('ErrorProcessingFile'), null, 'errors');
        } else {
            $result = importSitesFromCSV($processedFile, $skip_distance_calculation);
            if ($result['success']) {
                setEventMessages($langs->trans('ImportDone', $result['count']), null, 'mesgs');
                
                // Afficher les avertissements s'il y en a
                if (isset($result['warnings']) && !empty($result['warnings'])) {
                    setEventMessages(null, $result['warnings'], 'warnings');
                }
            } else {
                setEventMessages($langs->trans('ImportError', $result['error']), null, 'errors');
            }
            
            // Supprimer le fichier temporaire si créé
            if ($processedFile !== $importfile && file_exists($processedFile)) {
                unlink($processedFile);
            }
        }
    } else {
        setEventMessages($langs->trans('ErrorWrongFileFormat'), null, 'errors');
    }
}

/*
 * View
 */

$page_name = "Sites2Import";
$help_url = '';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = sites2AdminPrepareHead();
print dol_get_fiche_head($head, 'import', $langs->trans("Module641444Name"), -1, "sites2@sites2");

// Affichage du formulaire d'importation
print '<form method="POST" enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" name="formimport">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="import">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("FileToImport").'</td>';
print '<td>'.$langs->trans("Comment").'</td>';
print '</tr>';

// Import de fichier CSV
print '<tr class="oddeven">';
print '<td>';
print '<input type="file" name="importfile" id="importfile" />';
print '</td>';
print '<td>';
print $langs->trans("CSVFormatDescription");
print '<br><span class="opacitymedium">'.$langs->trans("CSVFieldsDescription").' : ';
print $langs->trans("CSVFieldsList", 'label;ref;address;zip;town;phone;status;latitude;longitude;fk_soc;description;distance_km;travel_time;type');
print '</span>';
print '<br><br>';
print '<div class="info">';
print $langs->trans("ImportAutomaticCalculation");
print '</div>';
print '<br><div class="notice">';
print '<strong>Format attendu :</strong><br>';
print '- Fichier CSV (extension .csv)<br>';
print '- Séparateur point-virgule (;) recommandé, mais virgule (,), tabulation ou barre verticale (|) aussi acceptés<br>';
print '- Encodage UTF-8 recommandé<br>';
print '- Première ligne = en-têtes des colonnes<br>';
print '- Minimum 5 colonnes obligatoires : label, ref, address, zip, town<br>';
print '- Pour tester, utilisez le fichier exemple fourni ci-dessous';
print '</div>';
print '</td>';
print '</tr>';

// Option pour désactiver le calcul des distances
print '<tr class="oddeven">';
print '<td>'.$langs->trans("Options").'</td>';
print '<td>';
print '<input type="checkbox" name="skip_distance_calculation" id="skip_distance_calculation" value="1" checked="checked" /> ';
print '<label for="skip_distance_calculation">'.$langs->trans("SkipDistanceCalculation").'</label>';
print '<br><small>'.$langs->trans("SkipDistanceCalculationHelp").'</small>';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br><div class="center">';
print '<input class="button" type="submit" value="'.$langs->trans("Import").'">';
print '</div>';
print '</form>';

// Exemple de fichier à télécharger
print '<br><div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("SampleCSVFile").'</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>';
print '<a href="'.dol_buildpath('/sites2/admin/export/sample_sites_import.csv', 1).'" download>';
print img_picto('', 'download').' '.$langs->trans("DownloadSampleCSVFile");
print '</a>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';

// Après l'exemple de fichier à télécharger, ajout d'une section d'aide détaillée sur les champs
print '<br><div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ImportCSVFieldsHelp").'</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>';
print '<div class="importfieldshelp">';
print '<ul>';
print '<li>'.$langs->trans("ImportFieldLabel").'</li>';
print '<li>'.$langs->trans("ImportFieldRef").'</li>';
print '<li>'.$langs->trans("ImportFieldAddress").'</li>';
print '<li>'.$langs->trans("ImportFieldZip").'</li>';
print '<li>'.$langs->trans("ImportFieldTown").'</li>';
print '<li>'.$langs->trans("ImportFieldPhone").'</li>';
print '<li>'.$langs->trans("ImportFieldStatus").'</li>';
print '<li>'.$langs->trans("ImportFieldLatitude").'</li>';
print '<li>'.$langs->trans("ImportFieldLongitude").'</li>';
print '<li>'.$langs->trans("ImportFieldFkSoc").'</li>';
print '<li>'.$langs->trans("ImportFieldDescription").'</li>';
print '<li>'.$langs->trans("ImportFieldDistanceKm").'</li>';
print '<li>'.$langs->trans("ImportFieldTravelTime").'</li>';
print '<li>'.$langs->trans("ImportFieldType").'</li>';
print '</ul>';
print '</div>';
print '</td>';
print '</tr>';
print '</table>';
print '</div>';

// Page end
print dol_get_fiche_end();
llxFooter();
$db->close();

/**
 * Import sites from CSV file
 *
 * @param string $file Path to the CSV file
 * @param int $skip_distance_calculation 1 to skip automatic distance calculation
 * @return array Result of the import
 */
function importSitesFromCSV($file, $skip_distance_calculation = 0)
{
    global $db, $user, $langs;
    
    // Augmenter la limite de temps d'exécution pour permettre le traitement des grands fichiers
    $currentTimeLimit = ini_get('max_execution_time');
    set_time_limit(300); // 5 minutes
    
    $result = array('success' => false, 'count' => 0, 'error' => '');
    
    try {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $count = 0;
            $line = 0;
            $errors = array();
            $sitesToProcess = array();
            
            // Détection automatique du délimiteur
            $sample = fgets($handle);
            rewind($handle);
            
            $delimiter = ";"; // Délimiteur par défaut
            $possibleDelimiters = array(";", ",", "\t", "|");
            $maxCount = 0;
            
            foreach ($possibleDelimiters as $testDelimiter) {
                $testCount = count(str_getcsv($sample, $testDelimiter));
                if ($testCount > $maxCount) {
                    $maxCount = $testCount;
                    $delimiter = $testDelimiter;
                }
            }
            
            // Lecture du fichier et préparation des sites à importer
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                $line++;
                
                // Ignorer la première ligne d'en-tête
                if ($line == 1) continue;
                
                // Ignorer les lignes vides
                if (count($data) == 1 && empty(trim($data[0]))) {
                    continue;
                }
                
                // Vérifier le nombre minimum de colonnes
                if (count($data) < 5) {
                    $errors[] = $langs->trans('NotEnoughColumns', $line) . ' (' . count($data) . ' colonnes trouvées, 5 minimum requises)';
                    continue;
                }
                
                // Préparer les données du site à importer
                $siteData = array(
                    'line' => $line,
                    'label' => trim($data[0]),
                    'ref' => !empty($data[1]) ? trim($data[1]) : trim($data[0]),
                    'address' => trim($data[2]),
                    'zip' => trim($data[3]),
                    'town' => trim($data[4]),
                    'phone' => isset($data[5]) ? trim($data[5]) : '',
                    'status' => isset($data[6]) && in_array($data[6], array('0', '1', '9')) ? $data[6] : '0',
                    'latitude' => isset($data[7]) && is_numeric($data[7]) ? $data[7] : null,
                    'longitude' => isset($data[8]) && is_numeric($data[8]) ? $data[8] : null,
                    'fk_soc' => isset($data[9]) && is_numeric($data[9]) ? $data[9] : null,
                    'description' => isset($data[10]) && !empty($data[10]) ? trim($data[10]) : null,
                    'distance_km' => isset($data[11]) && is_numeric($data[11]) ? $data[11] : null,
                    'travel_time' => isset($data[12]) && !empty($data[12]) ? trim($data[12]) : null,
                    'type' => isset($data[13]) && in_array($data[13], array('1', '9')) ? $data[13] : '1'
                );
                
                // Vérifier les données obligatoires
                if (empty($siteData['label'])) {
                    $errors[] = $langs->trans('EmptyLabel', $line);
                    continue;
                }
                
                $sitesToProcess[] = $siteData;
            }
            
            fclose($handle);
            
            // Traiter les sites par lots pour éviter les timeouts
            $batchSize = 5; // Nombre de sites à traiter par lot
            $batches = array_chunk($sitesToProcess, $batchSize);
            
            foreach ($batches as $batch) {
                foreach ($batch as $siteData) {
                    $site = new Site($db);
                    
                    // Remplir les données du site
                    $site->label = $siteData['label'];
                    $site->ref = $siteData['ref'];
                    $site->address = $siteData['address'];
                    $site->zip = $siteData['zip'];
                    $site->town = $siteData['town'];
                    $site->phone = $siteData['phone'];
                    $site->status = $siteData['status'];
                    $site->latitude = $siteData['latitude'];
                    $site->longitude = $siteData['longitude'];
                    $site->fk_soc = $siteData['fk_soc'];
                    $site->type = $siteData['type'];
                    
                    if (!empty($siteData['description'])) {
                        $site->description = $siteData['description'];
                    }
                    
                    if (!empty($siteData['distance_km'])) {
                        $site->distance_km = $siteData['distance_km'];
                    }
                    
                    if (!empty($siteData['travel_time'])) {
                        $site->travel_time = $siteData['travel_time'];
                    }
                    
                    // Création du site
                    $id = $site->create($user);
                    if ($id <= 0) {
                        $errors[] = $langs->trans('CreateError', $siteData['line'], $site->error);
                        continue;
                    }
                    
                    // Calcul des coordonnées et distances uniquement si nécessaire
                    // si l'adresse est complète, et si l'option de calcul n'est pas désactivée
                    if ($skip_distance_calculation == 0) {
                        $shouldCalculate = (
                            ($site->latitude === null || $site->longitude === null || 
                             $site->distance_km === null || $site->travel_time === null) && 
                            !empty($site->address) && !empty($site->zip) && !empty($site->town)
                        );
                        
                        if ($shouldCalculate) {
                            // Essayer d'abord de géocoder si nécessaire
                            if ($site->latitude === null || $site->longitude === null) {
                                try {
                                    // Au lieu d'appeler directement la méthode privée geocodeAddress
                                    // On met à jour le site pour déclencher le géocodage lors de la création/mise à jour
                                    $site->latitude = null; // On force à null pour s'assurer que le géocodage sera tenté
                                    $site->longitude = null;
                                    $site->update($user);
                                    
                                    // Si après la mise à jour on n'a toujours pas de coordonnées, 
                                    // on peut essayer de recharger le site pour voir si les coordonnées ont été mises à jour
                                    if (($site->latitude === null || $site->longitude === null) && $site->id > 0) {
                                        $site->fetch($site->id);
                                    }
                                } catch (Exception $e) {
                                    dol_syslog("Erreur de géocodage pour le site {$site->ref}: " . $e->getMessage(), LOG_WARNING);
                                }
                            }
                            
                            // Ensuite calculer la distance si on a des coordonnées
                            if ($site->latitude !== null && $site->longitude !== null &&
                                ($site->distance_km === null || $site->travel_time === null)) {
                                try {
                                    if (method_exists($site, 'calculateDistanceFromHQ')) {
                                        $site->calculateDistanceFromHQ();
                                        $site->update($user);
                                    }
                                } catch (Exception $e) {
                                    dol_syslog("Erreur de calcul de distance pour le site {$site->ref}: " . $e->getMessage(), LOG_WARNING);
                                }
                            }
                        }
                    }
                    
                    $count++;
                }
                
                // Libérer la mémoire après chaque lot
                gc_collect_cycles();
            }
            
            if (!empty($errors)) {
                if ($count > 0) {
                    // Il y a eu des erreurs mais aussi des succès
                    $result['success'] = true;
                    $result['count'] = $count;
                    $result['warnings'] = $errors;
                } else {
                    // Uniquement des erreurs
                    $result['error'] = implode('<br>', $errors);
                }
            } else {
                $result['success'] = true;
                $result['count'] = $count;
            }
        } else {
            $result['error'] = $langs->trans('CantOpenFile');
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    // Restaurer la limite de temps d'exécution d'origine
    if ($currentTimeLimit > 0) {
        set_time_limit($currentTimeLimit);
    }
    
    return $result;
}

/**
 * Preprocess CSV file to ensure it's correctly formatted
 *
 * @param string $file Path to the CSV file
 * @return string|false Path to the processed file or false on error
 */
function preprocessCSVFile($file)
{
    global $conf;
    
    try {
        // Créer un fichier temporaire pour le traitement
        $tempFile = tempnam($conf->sites2->dir_temp, 'csv_import_');
        
        // Lire le contenu du fichier
        $content = file_get_contents($file);
        if ($content === false) {
            return false;
        }
        
        // Détecter l'encodage et convertir en UTF-8 si nécessaire
        $encoding = mb_detect_encoding($content, 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252', true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Normaliser les fins de lignes (CRLF -> LF)
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        // Supprimer les BOM UTF-8 si présent
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        
        // Supprimer les lignes vides consécutives
        $content = preg_replace("/\n+/", "\n", $content);
        
        // S'assurer que le fichier se termine par une nouvelle ligne
        if (substr($content, -1) !== "\n") {
            $content .= "\n";
        }
        
        // Écrire le contenu traité dans le fichier temporaire
        if (file_put_contents($tempFile, $content) === false) {
            return false;
        }
        
        return $tempFile;
    } catch (Exception $e) {
        return false;
    }
} 