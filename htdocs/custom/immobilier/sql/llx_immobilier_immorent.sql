-- Copyright (C) ---Put here your own copyright and developer email---
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
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE llx_immobilier_immorent(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	entity integer DEFAULT 1 NOT NULL, 
	notice varchar(255) NOT NULL, 
	rentamount double(24,8) NOT NULL, 
	chargesamount double(24,8) NOT NULL, 
	totalamount double(24,8) NOT NULL, 
	deposit double(24,8) NOT NULL, 
	vat varchar(4), 
	fk_soc integer, 
	fk_property integer, 
	fk_renter integer, 
	note_public text, 
	note_private text, 
	date_start date NOT NULL, 
	date_end date NOT NULL, 
	date_next_rent date NOT NULL, 
	date_last_regul date NOT NULL, 
	date_creation datetime NOT NULL, 
	tms timestamp NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	import_key varchar(14), 
	status integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;