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

-- Ajoute la colonne description à la table llx_c_type_contact si elle n'existe pas

-- Vérifier d'abord si la colonne existe
SET @col_exists = 0;
SELECT 1 INTO @col_exists 
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'llx_c_type_contact'
AND COLUMN_NAME = 'description';

-- Ajouter la colonne si elle n'existe pas
SET @s = IF(@col_exists = 0, 'ALTER TABLE llx_c_type_contact ADD COLUMN description VARCHAR(255) NULL AFTER libelle', 'SELECT "La colonne description existe déjà"');
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 