-- Copyright (C) 2023-2024 Module Sites2
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
-- along with this program.  If not, see <https://www.gnu.org/licenses/>.

-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_rowid (rowid);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_ref (ref);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_label (label);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_fk_soc (fk_soc);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_status (status);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_town (town);
-- END MODULEBUILDER INDEXES

ALTER TABLE llx_sites2_site ADD CONSTRAINT llx_sites2_site_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_sites2_site ADD CONSTRAINT llx_sites2_site_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe(rowid); 