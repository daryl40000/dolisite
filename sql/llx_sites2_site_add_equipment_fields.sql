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

-- Ajout des champs de référence pour les équipements dans la table sites2_site
-- Ces champs sont nécessaires pour assurer la compatibilité avec les triggers du module équipement

-- Ajout du champ fk_access pour référencer les contrôles d'accès
ALTER TABLE llx_sites2_site ADD COLUMN fk_access integer DEFAULT NULL AFTER fk_soc;

-- Ajout du champ fk_alarm pour référencer les alarmes
ALTER TABLE llx_sites2_site ADD COLUMN fk_alarm integer DEFAULT NULL AFTER fk_access;

-- Ajout du champ fk_video pour référencer les enregistreurs vidéo
ALTER TABLE llx_sites2_site ADD COLUMN fk_video integer DEFAULT NULL AFTER fk_alarm;

-- Ajout des index pour améliorer les performances
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_fk_access (fk_access);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_fk_alarm (fk_alarm);
ALTER TABLE llx_sites2_site ADD INDEX idx_sites2_site_fk_video (fk_video);

-- Ajout des contraintes de clé étrangère si le module équipement est installé
-- Remarque: Commentées par défaut pour éviter les erreurs si le module équipement n'est pas installé
-- Décommentez ces lignes si nécessaire après avoir vérifié que le module équipement est bien installé

-- ALTER TABLE llx_sites2_site ADD CONSTRAINT fk_sites2_site_access FOREIGN KEY (fk_access) REFERENCES llx_equipement_access(rowid) ON DELETE SET NULL;
-- ALTER TABLE llx_sites2_site ADD CONSTRAINT fk_sites2_site_alarm FOREIGN KEY (fk_alarm) REFERENCES llx_equipement_alarm(rowid) ON DELETE SET NULL;
-- ALTER TABLE llx_sites2_site ADD CONSTRAINT fk_sites2_site_video FOREIGN KEY (fk_video) REFERENCES llx_equipement_video(rowid) ON DELETE SET NULL; 