<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\ContentTemplates\Parameters\ParametersTypes;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * AttributeParameter represent a simple parameter value accessed by a key.
 * This can be a simple value from the database (e.g the title of a ticket) or a
 * computed value (e.g. the link to a ticket).
 *
 * @since 10.0.0
 */
class AttributeParameter extends AbstractParameterType
{
   /**
    * Suggested twig filter to use when displaying the value of this parameter
    * This may be a 'raw" filter when the value is raw html, a 'date' filter
    * when dealing with timestamp so the user know how to reformat the date as
    * needed, ...
    *
    * @var string
    */
   protected $filter;

   /**
    * @param string $key    Key to access this value
    * @param string $label  Label to display in the autocompletion widget
    * @param string $filter Recommanded twig filter to apply on this value
    */
   public function __construct(string $key, string $label, string $filter = "") {
      $this->key = $key;
      $this->label = $label;
      $this->filter = $filter;
   }

   public function compute(): array {
      return [
         'type'   => "AttributeParameter",
         'key'    => $this->key,
         'label'  => $this->label,
         'filter' => $this->filter,
      ];
   }
}
