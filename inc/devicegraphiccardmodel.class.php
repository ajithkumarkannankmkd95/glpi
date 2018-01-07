<?php
/*
 * @version $Id: devicegraphiccardmodel.class.php 22810 2014-03-21 12:04:41Z yllen $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/// Class DeviceGraphicCardModel
class DeviceGraphicCardModel extends CommonDeviceModel {

   var $additional_fields_for_dictionnary = ['manufacturer'];


   static function getTypeName($nb = 0) {
      return _n('Device harddrive model', 'Device harddrive models', $nb);
   }
}
