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

namespace Glpi\Kernel;

use Glpi\Application\ErrorHandler;
use Glpi\Config\ConfigProviderConsoleExclusiveInterface;
use Glpi\Config\ConfigProviderWithRequestInterface;
use Glpi\Config\LegacyConfigProviders;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(?string $env = null)
    {
        $env ??= $_ENV['GLPI_ENVIRONMENT_TYPE'] ?? $_SERVER['GLPI_ENVIRONMENT_TYPE'] ?? 'production';
        parent::__construct($env, $env === 'production');
    }

    public function __destruct()
    {
        $this->triggerGlobalsDeprecation();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 3);
    }

    public function getCacheDir(): string
    {
        // TODO Use GLPI_CACHE_DIR
        return $this->getProjectDir() . '/files/_cache/symfony/' . $this->environment . '/';
    }

    public function getLogDir(): string
    {
        // TODO Use GLPI_LOG_DIR
        return $this->getProjectDir() . '/files/_log/symfony/' . $this->environment . '/';
    }

    public function registerBundles(): iterable
    {
        $bundles = [];

        $bundles[] = new FrameworkBundle();

        if ($this->environment === 'development') {
            $bundles[] = new WebProfilerBundle();
            $bundles[] = new TwigBundle();
        }

        return $bundles;
    }

    public function loadCommonGlobalConfig(): void
    {
        $this->boot();

        /** @var LegacyConfigProviders $providers */
        $providers = $this->container->get(LegacyConfigProviders::class);
        foreach ($providers->getProviders() as $provider) {
            if ($provider instanceof ConfigProviderWithRequestInterface) {
                continue;
            }
            $provider->execute();
        }
    }

    public function loadCliConsoleOnlyConfig(): void
    {
        $this->boot();

        /** @var LegacyConfigProviders $providers */
        $providers = $this->container->get(LegacyConfigProviders::class);
        foreach ($providers->getProviders() as $provider) {
            if ($provider instanceof ConfigProviderConsoleExclusiveInterface) {
                $provider->execute();
            }
        }
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $projectDir = $this->getProjectDir();

        $container->import($projectDir . '/dependency_injection/services.php', 'php');
        $container->import($projectDir . '/dependency_injection/legacyConfigProviders.php', 'php');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        //  Global core controllers
        $routes->import($this->getProjectDir() . '/src/Glpi/Controller', 'attribute');

        // Env-specific route files.
        if (\is_file($path = $this->getProjectDir() . '/routes/'.$this->environment.'.php')) {
            (require $path)($routes->withPath($path), $this);
        }
    }

    private function triggerGlobalsDeprecation(): void
    {
        if (in_array(GLPI_ROOT . '/inc/includes.php', get_included_files(), true)) {
            // The following deprecations/warnings are already triggered in `inc/includes.php`.
            return;
        }

        // Do not output error messages, the response has already been sent
        ErrorHandler::getInstance()->disableOutput();

        /**
         * @var mixed|null $AJAX_INCLUDE
         */
        global $AJAX_INCLUDE;
        if (isset($AJAX_INCLUDE)) {
            \Toolbox::deprecated('The global `$AJAX_INCLUDE` variable usage is deprecated. Use "$this->setAjax()" from your controllers instead.');
        }

        /**
         * @var mixed|null $SECURITY_STRATEGY
         */
        global $SECURITY_STRATEGY;
        if (isset($SECURITY_STRATEGY)) {
            // TODO Add a Route attribute to define the Firewall strategy.
            trigger_error(
                'The global `$SECURITY_STRATEGY` variable has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $USEDBREPLICATE
         * @var mixed|null $DBCONNECTION_REQUIRED
         */
        global $USEDBREPLICATE, $DBCONNECTION_REQUIRED;
        if (isset($USEDBREPLICATE) || isset($DBCONNECTION_REQUIRED)) {
            trigger_error(
                'The global `$USEDBREPLICATE` and `$DBCONNECTION_REQUIRED` variables has no effect anymore. Use "DBConnection::getReadConnection()" to get the most apporpriate connection for read only operations.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $PLUGINS_EXCLUDED
         * @var mixed|null $PLUGINS_INCLUDED
         */
        global $PLUGINS_EXCLUDED, $PLUGINS_INCLUDED;
        if (isset($PLUGINS_EXCLUDED) || isset($PLUGINS_INCLUDED)) {
            trigger_error(
                'The global `$PLUGINS_EXCLUDED` and `$PLUGINS_INCLUDED` variables has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $skip_db_check
         */
        global $skip_db_check;
        if (isset($skip_db_check)) {
            trigger_error(
                'The global `$skip_db_check` variable has no effect anymore.',
                E_USER_WARNING
            );
        }

        /**
         * @var mixed|null $dont_check_maintenance_mode
         */
        global $dont_check_maintenance_mode;
        if (isset($dont_check_maintenance_mode)) {
            trigger_error(
                'The global `$dont_check_maintenance_mode` variable has no effect anymore.',
                E_USER_WARNING
            );
        }
    }
}
