<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Controller\Config\Helpdesk;

use Config;
use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Helpdesk\Tile\TilesManager;
use Glpi\Session\SessionInfo;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FetchTilesController extends AbstractController
{
    private TilesManager $tiles_manager;

    public function __construct()
    {
        $this->tiles_manager = new TilesManager();
    }

    #[Route(
        "/Config/Helpdesk/FetchTiles",
        name: "glpi_config_helpdesk_fetch_tiles",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        if (!Session::haveRight(Config::$rightname, READ)) {
            throw new AccessDeniedHttpException();
        }

        $profile_id = $request->query->getInt('profile_id');
        $tiles = $this->tiles_manager->getTiles(new SessionInfo(
            profile_id: $profile_id,
        ), check_availability: false);

        return $this->render('pages/admin/profile/helpdesk_home/tiles.html.twig', [
            'tiles_manager' => $this->tiles_manager,
            'tiles' => $tiles,
        ]);
    }
}
