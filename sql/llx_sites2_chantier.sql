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

-- Table pour stocker les chantiers programmés associés aux sites
CREATE TABLE IF NOT EXISTS llx_sites2_chantier(
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	fk_site integer NOT NULL,
	fk_propal integer DEFAULT NULL COMMENT 'Référence au devis (propal)',
	date_debut date DEFAULT NULL COMMENT 'Date de début théorique du chantier',
	date_fin date DEFAULT NULL COMMENT 'Date de fin théorique du chantier',
	note_public text COMMENT 'Note publique sur le chantier',
	note_private text COMMENT 'Note privée sur le chantier',
	status integer DEFAULT 0 COMMENT 'Statut du chantier (0=draft, 1=validated, etc.)',
	date_creation datetime NOT NULL,
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	fk_user_creat integer NOT NULL,
	fk_user_modif integer DEFAULT NULL,
	import_key varchar(14) DEFAULT NULL,
	entity integer DEFAULT 1 NOT NULL,
	INDEX idx_fk_site (fk_site),
	INDEX idx_fk_propal (fk_propal),
	INDEX idx_date_debut (date_debut),
	FOREIGN KEY (fk_site) REFERENCES llx_sites2_site(rowid) ON DELETE CASCADE
) ENGINE=innodb;

