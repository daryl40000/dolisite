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

-- Ajout du champ location_type pour distinguer les chantiers intérieur/extérieur
-- 0 = intérieur, 1 = extérieur (par défaut extérieur pour compatibilité)
-- Cette requête peut être exécutée plusieurs fois sans erreur si la colonne existe déjà

-- Note: Si vous obtenez une erreur "Duplicate column", c'est normal, cela signifie que la colonne existe déjà
ALTER TABLE llx_sites2_chantier ADD COLUMN location_type integer DEFAULT 1 COMMENT 'Type de localisation: 0=intérieur, 1=extérieur' AFTER date_fin;

