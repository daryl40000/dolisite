-- Copyright (C) 2023-2024 D.A.R.Y.L.
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

-- Ajoute la colonne element_type à la table llx_element_contact si elle n'existe pas

-- Vérifier d'abord si la colonne existe
SET @col_exists = 0;
SELECT 1 INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'llx_element_contact'
AND COLUMN_NAME = 'element_type';

-- Ajouter la colonne si elle n'existe pas
SET @s = IF(@col_exists = 0, 'ALTER TABLE llx_element_contact ADD COLUMN element_type VARCHAR(50) NULL AFTER fk_c_type_contact', 'SELECT "La colonne element_type existe déjà"');
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mettre à jour la colonne element_type pour les contacts de sites si elle a été ajoutée
SET @update_needed = 0;
SELECT 1 INTO @update_needed 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'llx_element_contact'
AND COLUMN_NAME = 'element_type';

-- Mettre à jour les enregistrements existants pour les sites
SET @e = IF(@update_needed = 1 AND @col_exists = 0, 
    'UPDATE llx_element_contact SET element_type = "site" WHERE element_id IN (SELECT rowid FROM llx_sites2_site) AND element_type IS NULL', 
    'SELECT "Pas de mise à jour nécessaire"');
PREPARE stmt FROM @e;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 