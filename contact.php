<?php

// Dans la section qui récupère les contacts
$sql = "SELECT DISTINCT c.rowid, c.lastname, c.firstname, c.poste, c.phone, c.email,
        s.nom as socname, s.rowid as socid
        FROM " . MAIN_DB_PREFIX . "socpeople as c
        LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "multicontact_link as ml ON c.rowid = ml.fk_socpeople
        WHERE c.entity IN (" . getEntity('contact') . ")";

// Si un tiers est sélectionné
if ($socid > 0) {
    $sql .= " AND (c.fk_soc = " . $socid . " OR ml.fk_soc = " . $socid . ")";
}

$sql .= $db->order($sortfield, $sortorder); 