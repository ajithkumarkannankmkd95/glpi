<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Controller;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GenericListController extends AbstractController
{
    #[Route("/{class}/Search", name: "glpi_generic_list")]
    public function __invoke(Request $request): Response
    {
        $class = $request->attributes->getString('class');

        if (!\class_exists($class)) {
            throw new NotFoundHttpException(\sprintf("Class \"%s\" does not exist.", $class));
        }
        if (!\is_a($class, \CommonDBTM::class, true)) {
            throw new NotFoundHttpException(\sprintf("Class \"%s\" is not a DB object.", $class));
        }

        if (!$class::canView()) {
            throw new AccessDeniedHttpException();
        }

        return $this->render('search/generic_list.html.twig', [
            'object_class' => $class,
        ]);
    }
}
