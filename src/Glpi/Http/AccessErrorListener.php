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

namespace Glpi\Http;

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\SessionExpiredHttpException;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AccessErrorListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            // priority = 1 to be executed before the default Symfony listeners
            KernelEvents::EXCEPTION => ['onKernelException', 1],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // Ignore sub-requests.
            return;
        }

        $throwable = $event->getThrowable();

        $response = null;

        if ($throwable instanceof SessionExpiredHttpException) {
            Session::destroy(); // destroy the session to prevent pesistence of unexcpected data

            $request = $event->getRequest();
            $response = new RedirectResponse(
                sprintf(
                    '%s/?redirect=%s&error=3',
                    $request->getBasePath(),
                    \rawurlencode($request->getPathInfo() . '?' . $request->getQueryString())
                )
            );
        } elseif (
            $throwable instanceof AccessDeniedHttpException
            && ($_SESSION['_redirected_from_profile_selector'] ?? false)
        ) {
            unset($_SESSION['_redirected_from_profile_selector']);

            $request = $event->getRequest();
            $response = new RedirectResponse(
                sprintf(
                    '%s/front/central.php',
                    $request->getBasePath()
                )
            );
        }

        if ($response !== null) {
            $event->setResponse($response);
        }
    }
}
