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

-- Types de contact pour les sites clients
DELETE FROM llx_c_type_contact WHERE element = 'site';

INSERT INTO llx_c_type_contact (element, source, code, libelle, description, position, active) VALUES
('site', 'external', 'RESPONSIBLE',     'Responsable site',           'Responsable du site',           1, 1),
('site', 'external', 'TECHNICIAN',      'Technicien',                 'Technicien maintenance',        2, 1),
('site', 'external', 'SECURITY',        'Sécurité',                   'Contact sécurité',              3, 1),
('site', 'external', 'RECEPTIONIST',    'Réceptionniste',             'Réceptionniste / Accueil',      4, 1),
('site', 'external', 'ADMINISTRATIVE',  'Administratif',              'Contact administratif',         5, 1),
('site', 'internal', 'SITES2MANAGER',   'Gestionnaire sites',         'Gestionnaire des sites clients', 10, 1),
('site', 'internal', 'SITES2TECHNICAL', 'Responsable technique',      'Responsable technique',         11, 1),
('site', 'internal', 'SITES2COMMERC',   'Commercial',                 'Commercial en charge du site',  12, 1); 