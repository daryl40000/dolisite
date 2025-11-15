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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    class/sites2.searchform.class.php
 * \ingroup sites2
 * \brief   Hook to add search on Sites in global search
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhook.class.php';

/**
 * Class to manage hooks for Sites2 module
 */
class Sites2SearchForm
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * List of hook to add
     *
     * @var array
     */
    public $hooks = array(
        'searchform'
    );

    /**
     * Execute action searchform
     *
     * @param array $parameters Parameters
     * @param object $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int
     */
    public function searchform($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user, $conf;

        // Vérifier que le module est activé
        if (empty($conf->sites2->enabled)) {
            return 0;
        }

        // Vérifier les droits de lecture sur les sites
        if (!$user->rights->sites2->site->read) {
            return 0;
        }

        // Ajouter la traduction
        $langs->load('sites2@sites2');

        // Extraire les paramètres
        $search_boxvalue = $parameters['search_boxvalue'];

        // Préparer la requête SQL
        $sql = "SELECT s.rowid, s.ref, s.label, s.address, s.zip, s.town, s.status";
        $sql .= " FROM ".MAIN_DB_PREFIX."sites2_site as s";
        $sql .= " WHERE (s.ref LIKE '%".$this->db->escape($this->db->escapeforlike($search_boxvalue))."%'";
        $sql .= " OR s.label LIKE '%".$this->db->escape($this->db->escapeforlike($search_boxvalue))."%'";
        $sql .= " OR s.address LIKE '%".$this->db->escape($this->db->escapeforlike($search_boxvalue))."%'";
        $sql .= " OR s.zip LIKE '%".$this->db->escape($this->db->escapeforlike($search_boxvalue))."%'";
        $sql .= " OR s.town LIKE '%".$this->db->escape($this->db->escapeforlike($search_boxvalue))."%')";
        $sql .= " AND s.entity IN (".getEntity('sites2').")";
        $sql .= " ORDER BY s.label ASC";
        $sql .= $this->db->plimit(10); // Limiter à 10 résultats

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            
            if ($num > 0) {
                // Ajouter les titres des colonnes
                $this->db->fetch_object($resql);
                
                $hookmanager->resPrint .= '<div class="searchselete">';
                $hookmanager->resPrint .= '<div class="center boxstats"><div class="boxstatstext">'.$langs->trans("Sites").'</div></div>';
                $hookmanager->resPrint .= '<table class="noborder" style="width:100%">';
                
                $i = 0;
                $this->db->data_seek($resql, 0);
                
                while ($obj = $this->db->fetch_object($resql) AND $i < 10) {
                    $url = dol_buildpath('/sites2/site_card.php', 1).'?id='.$obj->rowid;
                    
                    $status = '';
                    if ($obj->status == 0) {
                        $status = '<span class="badge badge-status0 badge-status">'.$langs->trans('SiteStatusDraftShort').'</span>';
                    } elseif ($obj->status == 1) {
                        $status = '<span class="badge badge-status1 badge-status">'.$langs->trans('SiteStatusValidatedShort').'</span>';
                    } elseif ($obj->status == 9) {
                        $status = '<span class="badge badge-status9 badge-status">'.$langs->trans('SiteStatusClosedShort').'</span>';
                    }
                    
                    $address = '';
                    if (!empty($obj->address)) {
                        $address .= $obj->address;
                        if (!empty($obj->zip) || !empty($obj->town)) {
                            $address .= ', ';
                        }
                    }
                    if (!empty($obj->zip)) {
                        $address .= $obj->zip.' ';
                    }
                    if (!empty($obj->town)) {
                        $address .= $obj->town;
                    }
                    
                    $hookmanager->resPrint .= '<tr class="oddeven">';
                    $hookmanager->resPrint .= '<td><a href="'.$url.'">'.$obj->ref.'</a> '.$status.'</td>';
                    $hookmanager->resPrint .= '<td>'.$obj->label.'</td>';
                    $hookmanager->resPrint .= '<td>'.$address.'</td>';
                    $hookmanager->resPrint .= '</tr>';
                    
                    $i++;
                }
                
                $hookmanager->resPrint .= '</table>';
                $hookmanager->resPrint .= '</div>';
            }
            
            return 1;
        }
        
        return 0;
    }
} 