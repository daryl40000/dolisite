<?php
/* Copyright (C) 2025 [Your Company]
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
 * Page pour afficher les sites associés à un contact
 */

// Load Dolibarr environment
if (file_exists("../../main.inc.php")) {
    require_once "../../main.inc.php";
} else if (file_exists("../../../main.inc.php")) {
    require_once "../../../main.inc.php";
} else {
    die("Unable to find main.inc.php file");
}

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/sites2/class/sites.class.php';

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

// Initialize objects
$contact = new Contact($db);
$result = $contact->fetch($id);
if ($result <= 0) {
    accessforbidden('Contact not found');
}

// Security check
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

// Load translation files
$langs->loadLangs(array("companies", "other", "sites@sites2"));

$title = $langs->trans("Sites");
$help_url = '';

/*
 * Actions
 */
// Actions go here

/*
 * View
 */
llxHeader('', $title, $help_url);

// Onglets du contact
$head = contact_prepare_head($contact);
print dol_get_fiche_head($head, 'sites', $langs->trans("Contact"), -1, 'contact');

// Affichage des informations du contact
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<table class="border tableforfield" width="100%">';

print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
print $contact->getFullName($langs);
print '</td></tr>';

if (!empty($contact->socid)) {
    print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
    $soc = new Societe($db);
    $soc->fetch($contact->socid);
    print $soc->getNomUrl(1);
    print '</td></tr>';
    
    // Vérifier si le contact a des tiers multiples
    if (isModEnabled('tiersmultiple')) {
        require_once DOL_DOCUMENT_ROOT.'/custom/tiersmultiple/class/multicontact.class.php';
        $multicontact = new MultiContact($db);
        $thirdparties = $multicontact->getContactThirdparties($contact->id);
        
        if (is_array($thirdparties) && count($thirdparties) > 1) {
            print '<tr><td>'.$langs->trans("OtherThirdParties").'</td><td>';
            $first = true;
            foreach ($thirdparties as $tp) {
                if ($tp['id'] != $contact->socid) {
                    if (!$first) print ', ';
                    $first = false;
                    
                    $s = new Societe($db);
                    $s->fetch($tp['id']);
                    print $s->getNomUrl(1);
                }
            }
            print '</td></tr>';
        }
    }
}

print '</table>';
print '</div>';
print '</div>';

print dol_get_fiche_end();

// Liste des sites associés à ce contact
print load_fiche_titre($langs->trans("AssociatedSites"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("ContactType").'</td>';
print '<td>'.$langs->trans("ThirdParty").'</td>';
print '<td class="center">'.$langs->trans("Status").'</td>';
print '</tr>';

// Récupération des sites associés à ce contact
$sql = "SELECT s.rowid, s.ref, s.label, s.statut,";
$sql.= " tc.libelle as type_contact,";
$sql.= " soc.nom as thirdparty_name, soc.rowid as thirdparty_id";
$sql.= " FROM ".MAIN_DB_PREFIX."element_contact as ec";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."sites2_site as s ON s.rowid = ec.element_id";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON tc.rowid = ec.fk_c_type_contact";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as c ON c.rowid = ec.fk_socpeople";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
$sql.= " WHERE ec.fk_socpeople = ".$contact->id;
$sql.= " AND tc.element = 'site'";
$sql.= " ORDER BY s.ref";

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    if ($num > 0) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            
            print '<td><a href="'.DOL_URL_ROOT.'/custom/sites2/site_card.php?id='.$obj->rowid.'">';
            print $obj->ref;
            print '</a></td>';
            
            print '<td>'.$obj->label.'</td>';
            print '<td>'.$obj->type_contact.'</td>';
            
            print '<td>';
            if ($obj->thirdparty_id) {
                print '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$obj->thirdparty_id.'">';
                print $obj->thirdparty_name;
                print '</a>';
            } else {
                print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
            }
            print '</td>';
            
            print '<td class="center">';
            if ($obj->statut) {
                print $langs->trans("Active");
            } else {
                print $langs->trans("Disabled");
            }
            print '</td>';
            
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans("NoSitesYet").'</td></tr>';
    }
    $db->free($resql);
} else {
    dol_print_error($db);
}

print '</table>';
print '</div>';

// Footer
llxFooter();
$db->close(); 