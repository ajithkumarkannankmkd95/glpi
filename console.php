#!/usr/bin/env php
<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

// If "config-dir" option is used in command line, defines GLPI_CONFIG_DIR with its value
$options = [];
if (isset($_SERVER['argv'])) {
   for ($i = 1; $i < count($_SERVER['argv']); $i++) {
      $chunks = explode('=', $_SERVER['argv'][$i], 2);
      $chunks[0] = preg_replace('/^--/', '', $chunks[0]);
      $options[$chunks[0]] = (isset($chunks[1]) ? $chunks[1] : true);
   }
}

if (array_key_exists('config-dir', $options)) {
   $config_dir = $options['config-dir'];

   if (false === $config_dir || !@is_dir($config_dir)) {
      die(
         sprintf('Invalid value "%s" for --config-dir option.' . "\n", $config_dir)
      );
   }

   define('GLPI_CONFIG_DIR', realpath($config_dir));
}


// Init GLPI
define('GLPI_ROOT', __DIR__);

include_once(GLPI_ROOT . '/inc/based_config.php');
include_once(GLPI_ROOT . '/inc/db.function.php');
@include_once(GLPI_CONFIG_DIR . '/config_db.php');

// Run console application
use Glpi\Console\Application;

$application = new Application();
$application->run();
