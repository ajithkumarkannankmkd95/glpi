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

namespace tests\units;

use \DbTestCase;

/* Test for inc/plugin.class.php */

class Plugin extends DbTestCase {

   private $test_plugin_directory = 'test';

   public function afterTestMethod($method) {

      // Remove directory and files generated by tests
      $test_plugin_path = $this->getTestPluginPath();
      if (file_exists($test_plugin_path)) {
         \Toolbox::deleteDir($test_plugin_path);
      }

      parent::afterTestMethod($method);
   }

   public function testGetGlpiVersion() {
      $plugin = new \Plugin();
      $this->string($plugin->getGlpiVersion())->isIdenticalTo(GLPI_VERSION);
   }

   public function testGetGlpiPrever() {
      $plugin = new \Plugin();
      if (defined('GLPI_PREVER')) {
         $this->string($plugin->getGlpiPrever())->isIdenticalTo(GLPI_PREVER);
      } else {
         $this->when(
            function () use ($plugin) {
               $plugin->getGlpiPrever();
            }
         )->error
            ->exists();
      }
   }

   public function testIsGlpiPrever() {
      $plugin = new \Plugin();
      if (defined('GLPI_PREVER')) {
         $this->boolean($plugin->isGlpiPrever())->isTrue();
      } else {
         $this->boolean($plugin->isGlpiPrever())->isFalse();
      }
   }


   public function testcheckGlpiVersion() {
      //$this->constant->GLPI_VERSION = '9.1';
      $plugin = new \mock\Plugin();

      // Test min compatibility
      $infos = ['min' => '0.90'];

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '9.2';
      $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '9.2';
      $this->calling($plugin)->getGlpiVersion = '9.2-dev';
      $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '0.89';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI >= 0.90.');

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '0.89';
      $this->calling($plugin)->getGlpiVersion = '0.89-dev';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI >= 0.90.');

      // Test max compatibility
      $infos = ['max' => '9.3'];

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '9.2';
      $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '9.2';
      $this->calling($plugin)->getGlpiVersion = '9.2-dev';
      $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '9.3';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI < 9.3.');

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '9.3';
      $this->calling($plugin)->getGlpiVersion = '9.3-dev';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI < 9.3.');

      // Test min and max compatibility
      $infos = ['min' => '0.90', 'max' => '9.3'];

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '9.2';
      $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '9.2';
      $this->calling($plugin)->getGlpiVersion = '9.2-dev';
      $this->boolean($plugin->checkGlpiVersion($infos))->isTrue();

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '0.89';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos, true))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI >= 0.90 and < 9.3.');

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '0.89';
      $this->calling($plugin)->getGlpiVersion = '0.89-dev';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI >= 0.90 and < 9.3.');

      $this->calling($plugin)->isGlpiPrever = false;
      $this->calling($plugin)->getGlpiVersion = '9.3';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI >= 0.90 and < 9.3.');

      $this->calling($plugin)->isGlpiPrever = true;
      $this->calling($plugin)->getGlpiPrever = '9.3';
      $this->calling($plugin)->getGlpiVersion = '9.3-dev';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkGlpiVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI >= 0.90 and < 9.3.');
   }

   public function testcheckPhpVersion() {
      //$this->constant->PHP_VERSION = '7.1';
      $plugin = new \mock\Plugin();

      $infos = ['min' => '5.6'];
      $this->boolean($plugin->checkPhpVersion($infos))->isTrue();

      $this->calling($plugin)->getPhpVersion = '5.4';
      $this->output(
         function () use ($plugin, $infos) {
            $this->boolean($plugin->checkPhpVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires PHP >= 5.6.');

      $this->calling($plugin)->getPhpVersion = '7.1';
      $this->boolean($plugin->checkPhpVersion($infos))->isTrue();

      $this->output(
         function () use ($plugin) {
            $infos = ['min' => '5.6', 'max' => '7.0'];
            $this->boolean($plugin->checkPhpVersion($infos))->isFalse();
         }
      )->isIdenticalTo('This plugin requires PHP >= 5.6 and < 7.0.');

      $infos = ['min' => '5.6', 'max' => '7.2'];
      $this->boolean($plugin->checkPhpVersion($infos))->isTrue();
   }

   public function testCheckPhpExtensions() {
      $plugin = new \Plugin();

      $this->output(
         function () use ($plugin) {
            $exts = ['gd' => ['required' => true]];
            $this->boolean($plugin->checkPhpExtensions($exts))->isTrue();
         }
      )->isEmpty();

      $this->output(
         function () use ($plugin) {
            $exts = ['myext' => ['required' => true]];
            $this->boolean($plugin->checkPhpExtensions($exts))->isFalse();
         }
      )->isIdenticalTo('This plugin requires PHP extension myext<br/>');
   }

   public function testCheckGlpiParameters() {
      global $CFG_GLPI;

      $params = ['my_param'];

      $plugin = new \Plugin();

      $this->output(
         function () use ($plugin, $params) {
            $this->boolean($plugin->checkGlpiParameters($params))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI parameter my_param<br/>');

      $CFG_GLPI['my_param'] = '';
      $this->output(
         function () use ($plugin, $params) {
            $this->boolean($plugin->checkGlpiParameters($params))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI parameter my_param<br/>');

      $CFG_GLPI['my_param'] = '0';
      $this->output(
         function () use ($plugin, $params) {
            $this->boolean($plugin->checkGlpiParameters($params))->isFalse();
         }
      )->isIdenticalTo('This plugin requires GLPI parameter my_param<br/>');

      $CFG_GLPI['my_param'] = 'abc';
      $this->output(
         function () use ($plugin, $params) {
            $this->boolean($plugin->checkGlpiParameters($params))->isTrue();
         }
      )->isEmpty();
   }

   public function testCheckGlpiPlugins() {
      $plugin = new \mock\Plugin();

      $this->calling($plugin)->isInstalled = false;
      $this->calling($plugin)->isActivated = false;

      $this->output(
         function () use ($plugin) {
            $this->boolean($plugin->checkGlpiPlugins(['myplugin']))->isFalse();
         }
      )->isIdenticalTo('This plugin requires myplugin plugin<br/>');

      $this->calling($plugin)->isInstalled = true;

      $this->output(
         function () use ($plugin) {
            $this->boolean($plugin->checkGlpiPlugins(['myplugin']))->isFalse();
         }
      )->isIdenticalTo('This plugin requires myplugin plugin<br/>');

      $this->calling($plugin)->isInstalled = true;
      $this->calling($plugin)->isActivated = true;

      $this->output(
         function () use ($plugin) {
            $this->boolean($plugin->checkGlpiPlugins(['myplugin']))->isTrue();
         }
      )->isEmpty();

   }

   /**
    * Test state checking on an invalid directory corresponding to an unknown plugin.
    * Should have no effect.
    */
   public function testCheckPluginStateForInvalidUnknownPlugin() {

      $this->doTestCheckPluginState(null, null, null);
   }

   /**
    * Test state checking on an invalid directory corresponding to a known plugin.
    * Should results in changing plugin state to "TOBECLEANED".
    */
   public function testCheckPluginStateForInvalidKnownPlugin() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $expected_data = array_merge(
         $initial_data,
         [
            'state' => \Plugin::TOBECLEANED,
         ]
      );

      $this->doTestCheckPluginState(
         $initial_data,
         null,
         $expected_data,
         'Unable to load plugin "' . $this->test_plugin_directory . '" informations. Its state has been changed to "To be cleaned".'
      );
   }

   /**
    * Test state checking on a valid directory corresponding to an unknown plugin.
    * Should results in creating plugin with "NOTINSTALLED" state.
    */
   public function testCheckPluginStateForNewPlugin() {

      $setup_informations = [
         'name'      => 'Test plugin',
         'version'   => '1.0',
      ];
      $expected_data = array_merge(
         $setup_informations,
         [
            'directory' => $this->test_plugin_directory,
            'state'     => \Plugin::NOTINSTALLED,
         ]
      );

      $this->doTestCheckPluginState(
         null,
         $setup_informations,
         $expected_data
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known and installed plugin
    * with a different version.
    * Should results in changing plugin state to "NOTUPDATED".
    */
   public function testCheckPluginStateForInstalledAndUpdatablePlugin() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $setup_informations = [
         'name'    => 'Test plugin NG',
         'version' => '2.0',
      ];
      $expected_data = array_merge(
         $initial_data,
         $setup_informations,
         [
            'state' => \Plugin::NOTUPDATED,
         ]
      );

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data,
         'Plugin "' . $this->test_plugin_directory . '" version changed. It has been deactivated as its update process has to be launched.'
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known and NOT installed plugin
    * with a different version.
    * Should results in keeping plugin state to "NOTINSTALLED".
    */
   public function testCheckPluginStateForNotInstalledAndUpdatablePlugin() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::NOTINSTALLED,
      ];
      $setup_informations = [
         'name'    => 'Test plugin NG',
         'version' => '2.0',
      ];
      $expected_data = array_merge(
         $initial_data,
         $setup_informations,
         [
            'state' => \Plugin::NOTINSTALLED,
         ]
      );

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known plugin that has been renamed.
    * Should results in changing plugin directory to new value and state to "NOTUPDATED".
    */
   public function testCheckPluginStateForRenamedPlugin() {

      $initial_data = [
         'directory' => 'oldnameofplugin',
         'name'      => 'Old plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $setup_informations = [
         'name'    => 'Test plugin revamped',
         'oldname' => 'oldnameofplugin',
         'version' => '2.0',
      ];
      $expected_data = array_merge(
         $setup_informations,
         [
            'directory' => $this->test_plugin_directory,
            'state'     => \Plugin::NOTUPDATED,
         ]
      );

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data,
         'Plugin "' . $this->test_plugin_directory . '" version changed. It has been deactivated as its update process has to be launched.'
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications.
    * Should results in no changes.
    */
   public function testCheckPluginStateForInactiveAndNotUpdatedPlugin() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::NOTACTIVATED,
      ];
      $setup_informations = [
         'name'      => 'Test plugin',
         'version'   => '1.0',
      ];
      $expected_data = $initial_data;

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications
    * but not matching versions.
    * Should results in changing plugin state to "NOTACTIVATED".
    */
   public function testCheckPluginStateForActiveAndNotUpdatedPluginNotMatchingVersions() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $setup_informations = [
         'name'         => 'Test plugin',
         'version'      => '1.0',
         'requirements' => [
            'glpi' => [
               'min' => '15.0',
            ],
         ],
      ];
      $expected_data = array_merge(
         $initial_data,
         [
            'state' => \Plugin::NOTACTIVATED,
         ]
      );

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data,
         'Plugin "' . $this->test_plugin_directory . '" prerequisites are not matched. It has been deactivated.'
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications
    * but not matching prerequisites.
    * Should results in changing plugin state to "NOTACTIVATED".
    */
   public function testCheckPluginStateForActiveAndNotUpdatedPluginNotMatchingPrerequisites() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $setup_informations = [
         'name'    => 'Test plugin',
         'version' => '1.0',
      ];
      $expected_data = array_merge(
         $initial_data,
         [
            'state' => \Plugin::NOTACTIVATED,
         ]
      );

      $this->function->plugin_test_check_prerequisites = false;

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data,
         'Plugin "' . $this->test_plugin_directory . '" prerequisites are not matched. It has been deactivated.'
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications
    * but not validating config.
    * Should results in changing plugin state to "NOTACTIVATED".
    */
   public function testCheckPluginStateForActiveAndNotUpdatedPluginNotValidationConfig() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $setup_informations = [
         'name'    => 'Test plugin',
         'version' => '1.0',
      ];
      $expected_data = array_merge(
         $initial_data,
         [
            'state' => \Plugin::NOTACTIVATED,
         ]
      );

      $this->function->plugin_test_check_config = false;

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data,
         'Plugin "' . $this->test_plugin_directory . '" prerequisites are not matched. It has been deactivated.'
      );
   }

   /**
    * Test state checking on a valid directory corresponding to a known inactive plugin with no modifications,
    * matching prerequisites and validating config.
    * Should results in no changes.
    */
   public function testCheckPluginStateForActiveAndNotUpdatedPluginMatchingPrerequisitesAndConfig() {

      $initial_data = [
         'directory' => $this->test_plugin_directory,
         'name'      => 'Test plugin',
         'version'   => '1.0',
         'state'     => \Plugin::ACTIVATED,
      ];
      $setup_informations = [
         'name'    => 'Test plugin',
         'version' => '1.0',
      ];
      $expected_data = $initial_data;

      $this->function->plugin_test_check_prerequisites = true;
      $this->function->plugin_test_check_config = true;

      $this->doTestCheckPluginState(
         $initial_data,
         $setup_informations,
         $expected_data
      );
   }

   /**
    * Test that state checking on a plugin directory.
    *
    * /!\ Each iteration on this method has to be done on a different test method, unless you change
    * the plugin directory on each time. Not doing this will prevent updating the `init` function of
    * the plugin on each test.
    *
    * @param array|null  $initial_data       Initial data in DB, null for none.
    * @param array|null  $setup_informations Informations hosted by setup file, null for none.
    * @param array|null  $expected_data      Expected data in DB, null for none.
    * @param string|null $expected_warning   Expected warning message, null for none.
    *
    * @return void
    */
   private function doTestCheckPluginState($initial_data, $setup_informations, $expected_data, $expected_warning = null) {

      $plugin_directory = $this->test_plugin_directory;
      $test_plugin_path = $this->getTestPluginPath();
      $plugin           = new \Plugin();

      // Fail if plugin already exists in DB or filesystem, as this is not expected
      $this->boolean($plugin->getFromDBByCrit(['directory' => $plugin_directory]))->isFalse();
      $this->boolean(file_exists($test_plugin_path))->isFalse();

      // Create initial state of plugin
      $plugin_id = null;
      if (null !== $initial_data) {
         $plugin_id = $plugin->add($initial_data);
         $this->integer((int)$plugin_id)->isGreaterThan(0);
      }

      // Create test plugin files
      $this->createTestPluginFiles(
         null !== $setup_informations,
         null !== $setup_informations ? $setup_informations : []
      );

      // Check state
      if (null !== $expected_warning) {
         $this->when(
            function () use ($plugin, $plugin_directory) {
               $plugin->checkPluginState($plugin_directory);
            }
         )->error()
            ->withType(E_USER_WARNING)
            ->withMessage($expected_warning)
               ->exists();
      } else {
         $plugin->checkPluginState($plugin_directory);
      }

      // Assert that data in DB matches expected
      if (null !== $expected_data) {
         $this->boolean($plugin->getFromDBByCrit(['directory' => $plugin_directory]))->isTrue();

         $this->string($plugin->fields['directory'])->isIdenticalTo($expected_data['directory']);
         $this->string($plugin->fields['name'])->isIdenticalTo($expected_data['name']);
         $this->string($plugin->fields['version'])->isIdenticalTo($expected_data['version']);
         $this->integer((int)$plugin->fields['state'])->isIdenticalTo($expected_data['state']);
      } else {
         $this->boolean($plugin->getFromDBByCrit(['directory' => $plugin_directory]))->isFalse();
      }
   }

   /**
    * Returns test plugin files path.
    *
    * @return string
    */
   private function getTestPluginPath() {

      return implode(DIRECTORY_SEPARATOR, [GLPI_ROOT, 'plugins', $this->test_plugin_directory]);
   }

   /**
    * Create test plugin files.
    *
    * @param boolean $withsetup    Include setup file ?
    * @param array   $informations Informations to put in setup files.
    */
   private function createTestPluginFiles($withsetup = true, array $informations = []) {

      $plugin_path = $this->getTestPluginPath();

      $this->boolean(
         mkdir($plugin_path, 0700, true)
      )->isTrue();

      if ($withsetup) {
         $informations_str = var_export($informations, true);

         $this->variable(
            file_put_contents(
               implode(DIRECTORY_SEPARATOR, [$plugin_path, 'setup.php']),
               <<<PHP
<?php
function plugin_version_test() {
   return {$informations_str};
}
PHP
            )
         )->isNotEqualTo(false);
      }
   }
}
