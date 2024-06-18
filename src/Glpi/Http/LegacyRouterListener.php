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

use Glpi\Kernel\Kernel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LegacyRouterListener implements EventSubscriberInterface
{
    use LegacyRouterTrait;

    /**
     * GLPI root directory.
     */
    protected string $glpi_root;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        Kernel $kernel,
    ) {
        $this->glpi_root = $projectDir;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 260],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $response = $this->runLegacyRouter($request);

        if ($response) {
            $event->setResponse($response);
        }
    }

    private function runLegacyRouter(Request $request): ?Response
    {
        dd($this);

        /**
         * GLPI web router.
         *
         * This router is used to be able to expose only the `/public` directory on the webserver.
         */
        [$uri_prefix, $path] = $this->extractPathAndPrefix($request);

        [$path, $pathinfo] = $this->getTargetPathInfo($path);

        // Enforce legacy index file for root URL.
        // This prevents Symfony from being called.
        if ($path === '/') {
            $path = '/index.php';
        }

        $response = $this->handleRedirects($path, $uri_prefix);
        if ($response) {
            return $response;
        }

        $target_file = $this->glpi_root . $path;

        if (
            !$this->isTargetAPhpScript($path)
            || !$this->isPathAllowed($path)
            || !is_file($target_file)
        ) {
            // Let the previous router do the trick, it's fine.
            return null;
        }

        // Ensure `getcwd()` and inclusion path is based on requested file FS location.
        // use `@` to silence errors on unit tests (`chdir` does not work on streamed mocked dir)
        @chdir(dirname($target_file));

        // (legacy) Redefine some $_SERVER variables to have same values whenever scripts are called directly
        // or through current router.
        $target_path     = $uri_prefix . $path;
        $_SERVER['PATH_INFO']       = $pathinfo;
        $_SERVER['PHP_SELF']        = $target_path;
        $_SERVER['SCRIPT_FILENAME'] = $target_file;
        $_SERVER['SCRIPT_NAME']     = $target_path;

        // New server overrides:
        $request->server->set('PATH_INFO', $pathinfo);
        $request->server->set('PHP_SELF', $target_path);
        $request->server->set('SCRIPT_FILENAME', $target_file);
        $request->server->set('SCRIPT_NAME', $target_path);

        return new StreamedResponse(static function () use ($target_file) {
            require($target_file);
        });
    }

    private function getTargetPathInfo(string $init_path): array
    {
        $path = '';
        $pathinfo = null;

        // Parse URI to find requested script and PathInfo
        $slash_pos = 0;
        while ($slash_pos !== false && ($dot_pos = strpos($init_path, '.', $slash_pos)) !== false) {
            $slash_pos = strpos($init_path, '/', $dot_pos);
            $filepath = substr($init_path, 0, $slash_pos !== false ? $slash_pos : strlen($init_path));
            if (is_file($this->glpi_root . $filepath)) {
                $path = $filepath;

                $pathinfo = substr($init_path, strlen($filepath));
                if ($pathinfo !== '') {
                    // On any regular PHP script that is directly served by Apache, `$_SERVER['PATH_INFO']`
                    // contains decoded URL.
                    // We have to reproduce this decoding operation to prevent issues with endoded chars.
                    $pathinfo = urldecode($pathinfo);
                } else {
                    $pathinfo = '';
                }
                break;
            }
        }

        if ($path === '') {
            // Fallback to requested URI
            $path = $init_path;

            // Clean trailing `/`.
            $path = rtrim($path, '/');

            // If URI matches a directory path, consider `index.php` is the requested script.
            if (is_dir($this->glpi_root . $path) && is_file($this->glpi_root . $path . '/index.php')) {
                $path .= '/index.php';
            }
        }

        return [$path, $pathinfo];
    }

    /**
     *  Handle well-known URIs as defined in RFC 5785.
     *  https://www.iana.org/assignments/well-known-uris/well-known-uris.xhtml
     */
    private function handleRedirects(string $path, string $uri_prefix): ?Response
    {
        // Handle well-known URIs
        if (preg_match('/^\/\.well-known\//', $path) !== 1) {
            return null;
        }

        // Get the requested URI (the part after .well-known/)
        $requested_uri = explode('/', $path);
        $requested_uri = strtolower(end($requested_uri));

        // Some password managers can use this URI to help with changing passwords
        // Redirect to the change password page
        if ($requested_uri === 'change-password') {
            return new RedirectResponse($uri_prefix . '/front/updatepassword.php', 307);
        }

        return null;
    }
}
