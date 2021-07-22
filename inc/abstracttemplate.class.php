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

use Glpi\ContentTemplates\Parameters\ChangeParameters;
use Glpi\ContentTemplates\Parameters\ParametersTypes\AttributeParameter;
use Glpi\ContentTemplates\Parameters\ParametersTypes\ObjectParameter;
use Glpi\ContentTemplates\Parameters\ProblemParameters;
use Glpi\ContentTemplates\Parameters\TicketParameters;
use Glpi\ContentTemplates\TemplateManager;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Base template class
 *
 * @since 10.0.0
 */
abstract class AbstractTemplate extends CommonDropdown
{
   function showForm($ID, $options = []) {
      parent::showForm($ID, $options);

      // Add autocompletion for ticket properties (twig templates)
      Html::activateUserTemplateAutocompletion('textarea[name=content]', [
         (new AttributeParameter('itemtype', __('Itemtype')))->compute(),
         (new ObjectParameter(new TicketParameters()))->compute(),
         (new ObjectParameter(new ChangeParameters()))->compute(),
         (new ObjectParameter(new ProblemParameters()))->compute(),
      ]);
   }

   function prepareInputForAdd($input) {
      $input = parent::prepareInputForUpdate($input);

      // Validate twig template
      if (isset($input['content']) && !TemplateManager::validate(stripslashes($input['content']), __('Content'), true)) {
         $this->saveInput();
         return false;
      }

      return $input;
   }

   function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);

      // Validate twig template
      if (isset($input['content']) && !TemplateManager::validate(stripslashes($input['content']), __('Content'), true)) {
         $this->saveInput();
         return false;
      }

      return $input;
   }

   /**
    * Get content rendered by template engine, using given ITIL item to build parameters.
    *
    * @param CommonITILObject $itil_item
    *
    * @return string
    */
   public function getRenderedContent(CommonITILObject $itil_item): string {
      $parameters_class = $itil_item->getContentTemplatesParametersClass();
      $parameters = new $parameters_class();
      return TemplateManager::render(
         $this->fields['content'],
         [
            'itemtype' => $itil_item->getType(),
            $parameters->getDefaultNodeName() => $parameters->getValues($itil_item),
         ],
         true
      );
   }
}
