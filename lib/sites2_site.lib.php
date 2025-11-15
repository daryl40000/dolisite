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
 * \file    lib/sites2_site.lib.php
 * \ingroup sites2
 * \brief   Library files with common functions for Site
 */

/**
 * Prepare array of tabs for Site
 *
 * @param	Site	$object		Site
 * @return 	array				Array of tabs
 */
function sitePrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("sites2@sites2");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/sites2/site_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	// Ajout de l'onglet pour les contacts
	$head[$h][0] = dol_buildpath('/sites2/site_contact.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ContactsAddresses");
	$head[$h][2] = 'contact';
	$h++;
	
	// Ajout de l'onglet pour les chantiers programmés
	$head[$h][0] = dol_buildpath('/sites2/site_chantier.php', 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("ScheduledWorkSite");
	$head[$h][2] = 'chantier';
	$h++;
	
	// Ajout de l'onglet pour les équipements
	if (isModEnabled('equipement')) {
		$head[$h][0] = dol_buildpath('/sites2/site_equipement.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans("Equipements");
		$head[$h][2] = 'equipement';
		$h++;
	}

	// Note: Les onglets Notes, Documents et Events ont été supprimés car ils ne fonctionnent pas correctement

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@sites2:/sites2/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@sites2:/sites2/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'site@sites2');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'site@sites2', 'remove');

	return $head;
} 