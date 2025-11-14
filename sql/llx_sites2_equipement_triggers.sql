-- Copyright (C) 2024 D.A.R.Y.L.
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- Déclarer le délimiteur pour les triggers
DELIMITER //

-- Trigger pour synchroniser les contrôles d'accès lors de l'insertion
CREATE TRIGGER sync_access_to_sites2_insert
AFTER INSERT
ON llx_equipement_access FOR EACH ROW
BEGIN
    -- Mettre à jour la table sites2_site
    UPDATE llx_sites2_site
    SET fk_access = NEW.rowid
    WHERE rowid = NEW.fk_site;
END;

//

-- Trigger pour synchroniser les contrôles d'accès lors de la mise à jour
CREATE TRIGGER sync_access_to_sites2_update
AFTER UPDATE
ON llx_equipement_access FOR EACH ROW
BEGIN
    -- Si le site a changé, mettre à jour les deux sites concernés
    IF OLD.fk_site != NEW.fk_site THEN
        -- Effacer la référence dans l'ancien site
        UPDATE llx_sites2_site
        SET fk_access = NULL
        WHERE rowid = OLD.fk_site AND fk_access = OLD.rowid;
    END IF;
    
    -- Mettre à jour le nouveau site
    UPDATE llx_sites2_site
    SET fk_access = NEW.rowid
    WHERE rowid = NEW.fk_site;
END;

//

-- Trigger pour synchroniser les contrôles d'accès lors de la suppression
CREATE TRIGGER sync_access_to_sites2_delete
AFTER DELETE
ON llx_equipement_access FOR EACH ROW
BEGIN
    -- Effacer la référence dans le site
    UPDATE llx_sites2_site
    SET fk_access = NULL
    WHERE rowid = OLD.fk_site AND fk_access = OLD.rowid;
END;

//

-- Trigger pour synchroniser les alarmes lors de l'insertion
CREATE TRIGGER sync_alarm_to_sites2_insert
AFTER INSERT
ON llx_equipement_alarm FOR EACH ROW
BEGIN
    -- Mettre à jour la table sites2_site
    UPDATE llx_sites2_site
    SET fk_alarm = NEW.rowid
    WHERE rowid = NEW.fk_site;
END;

//

-- Trigger pour synchroniser les alarmes lors de la mise à jour
CREATE TRIGGER sync_alarm_to_sites2_update
AFTER UPDATE
ON llx_equipement_alarm FOR EACH ROW
BEGIN
    -- Si le site a changé, mettre à jour les deux sites concernés
    IF OLD.fk_site != NEW.fk_site THEN
        -- Effacer la référence dans l'ancien site
        UPDATE llx_sites2_site
        SET fk_alarm = NULL
        WHERE rowid = OLD.fk_site AND fk_alarm = OLD.rowid;
    END IF;
    
    -- Mettre à jour le nouveau site
    UPDATE llx_sites2_site
    SET fk_alarm = NEW.rowid
    WHERE rowid = NEW.fk_site;
END;

//

-- Trigger pour synchroniser les alarmes lors de la suppression
CREATE TRIGGER sync_alarm_to_sites2_delete
AFTER DELETE
ON llx_equipement_alarm FOR EACH ROW
BEGIN
    -- Effacer la référence dans le site
    UPDATE llx_sites2_site
    SET fk_alarm = NULL
    WHERE rowid = OLD.fk_site AND fk_alarm = OLD.rowid;
END;

//

-- Trigger pour synchroniser les enregistreurs vidéo lors de l'insertion
CREATE TRIGGER sync_video_to_sites2_insert
AFTER INSERT
ON llx_equipement_video FOR EACH ROW
BEGIN
    -- Mettre à jour la table sites2_site
    UPDATE llx_sites2_site
    SET fk_video = NEW.rowid
    WHERE rowid = NEW.fk_site;
END;

//

-- Trigger pour synchroniser les enregistreurs vidéo lors de la mise à jour
CREATE TRIGGER sync_video_to_sites2_update
AFTER UPDATE
ON llx_equipement_video FOR EACH ROW
BEGIN
    -- Si le site a changé, mettre à jour les deux sites concernés
    IF OLD.fk_site != NEW.fk_site THEN
        -- Effacer la référence dans l'ancien site
        UPDATE llx_sites2_site
        SET fk_video = NULL
        WHERE rowid = OLD.fk_site AND fk_video = OLD.rowid;
    END IF;
    
    -- Mettre à jour le nouveau site
    UPDATE llx_sites2_site
    SET fk_video = NEW.rowid
    WHERE rowid = NEW.fk_site;
END;

//

-- Trigger pour synchroniser les enregistreurs vidéo lors de la suppression
CREATE TRIGGER sync_video_to_sites2_delete
AFTER DELETE
ON llx_equipement_video FOR EACH ROW
BEGIN
    -- Effacer la référence dans le site
    UPDATE llx_sites2_site
    SET fk_video = NULL
    WHERE rowid = OLD.fk_site AND fk_video = OLD.rowid;
END;

//

-- Rétablir le délimiteur standard
DELIMITER ; 