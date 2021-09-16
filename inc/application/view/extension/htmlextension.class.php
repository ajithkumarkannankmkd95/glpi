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

namespace Glpi\Application\View\Extension;

use Html;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class HtmlExtension extends AbstractExtension {

   public function getFunctions(): array {
      return [
         new TwigFunction('showMassiveActions', [$this, 'showMassiveActions'], ['is_safe' => ['html']]),
         new TwigFunction('formatNumber', [Html::class, 'formatNumber'], ['is_safe' => ['html']]),
         new TwigFunction('Html__initEditorSystem', [Html::class , 'initEditorSystem'], ['is_safe' => ['html']]),
         new TwigFunction('Html__cleanId', [Html::class , 'cleanId'], ['is_safe' => ['html']]),
         new TwigFunction('Html__file', [Html::class , 'file'], ['is_safe' => ['html']]),
         new TwigFunction('Html__getCheckbox', [Html::class , 'getCheckbox'], ['is_safe' => ['html']]),
         new TwigFunction('Html__showToolTip', [Html::class , 'showToolTip'], ['is_safe' => ['html']]),
         new TwigFunction('Html__showCheckbox', [Html::class , 'showCheckbox'], ['is_safe' => ['html']]),
         new TwigFunction('Html__parseAttributes', [Html::class, 'parseAttributes'], ['is_safe' => ['html']]),
      ];
   }

   public function showMassiveActions(array $params = []): string {
      return Html::showMassiveActions($params + ['display' => false]);
   }
}
