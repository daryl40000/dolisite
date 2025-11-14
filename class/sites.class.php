/**
 * Ajoute un contact à un site
 *
 * @param int $fk_socpeople ID du contact à ajouter
 * @param int $type Type de relation (type de contact)
 * @param string $source Source du contact ('external' ou 'internal')
 * @return int <0 si erreur, >0 si OK
 */
public function add_contact($fk_socpeople, $fk_c_type_contact, $source = 'external')
{
    global $user;
    
    dol_syslog(__METHOD__." fk_socpeople=".$fk_socpeople." type=".$fk_c_type_contact." source=".$source);
    
    // Vérifier si le contact existe déjà pour ce site avec ce type
    $sql = "SELECT ec.rowid FROM ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.= " WHERE ec.element_id = ".$this->id;
    $sql.= " AND ec.fk_socpeople = ".$fk_socpeople;
    $sql.= " AND ec.fk_c_type_contact = ".$fk_c_type_contact;
    
    $result = $this->db->query($sql);
    if ($result && $this->db->num_rows($result) > 0) {
        $this->error = 'DB_ERROR_RECORD_ALREADY_EXISTS';
        return -1;
    }
    
    $datecreate = dol_now();
    
    // Insertion dans la base
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."element_contact";
    $sql.= " (element_id, fk_socpeople, datecreate, statut, fk_c_type_contact)";
    $sql.= " VALUES (".$this->id.", ".$fk_socpeople.", '".$this->db->idate($datecreate)."', 4, ".$fk_c_type_contact.")";
    
    $resql = $this->db->query($sql);
    if ($resql) {
        // Call trigger
        $result = $this->call_trigger('SITE_ADD_CONTACT', $user);
        if ($result < 0) {
            $this->db->rollback();
            return -1;
        }
        
        return 1;
    } else {
        $this->error = $this->db->lasterror();
        return -1;
    }
} 