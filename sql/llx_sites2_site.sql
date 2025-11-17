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

CREATE TABLE llx_sites2_site(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(50) DEFAULT '' NOT NULL,
	label varchar(255) DEFAULT '' NOT NULL,
	description text,
	address text,
	zip varchar(10) DEFAULT '',
	town varchar(50) DEFAULT '',
	phone varchar(20) DEFAULT '',
	type integer DEFAULT 1,
	status integer DEFAULT 0,
	latitude DECIMAL(10,8) DEFAULT NULL,
	longitude DECIMAL(11,8) DEFAULT NULL,
	distance_km double DEFAULT NULL,
	travel_time varchar(50) DEFAULT NULL,
	fk_soc integer DEFAULT NULL,
	note_public text,
	note_private text,
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer DEFAULT NULL,
	import_key varchar(14) DEFAULT NULL,
	model_pdf varchar(255) DEFAULT NULL,
	last_main_doc varchar(255) DEFAULT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb; 