<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace tests\units\Glpi\Inventory\Asset;

include_once __DIR__ . '/../../../../abstracts/AbstractInventoryAsset.php';

/* Test for inc/inventory/asset/computer.class.php */

class Software extends AbstractInventoryAsset
{
    protected function assetProvider(): array
    {
        return [
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/09/2018</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Application</SYSTEM_CATEGORY>
      <VERSION>2.8.22-7.fc28</VERSION>
    </SOFTWARES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "Application", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Feodra Project", "comment": "GNU Image Manipulation Program", "_system_category": "Application", "operatingsystems_id": 0, "entities_id": 0, "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}'
            ],
            [
                'xml' => "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <OPERATINGSYSTEM>
      <ARCH>x86_64</ARCH>
      <BOOT_TIME>2017-02-20 08:11:53</BOOT_TIME>
      <FULL_NAME>Fedora release 25 (Twenty Five)</FULL_NAME>
      <HOSTID>007f0100</HOSTID>
      <KERNEL_NAME>linux</KERNEL_NAME>
      <KERNEL_VERSION>4.9.9-200.fc25.x86_64</KERNEL_VERSION>
      <NAME>Fedora</NAME>
      <VERSION>25</VERSION>
    </OPERATINGSYSTEM>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/09/2018</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>System Component</SYSTEM_CATEGORY>
      <VERSION>2.8.22-7.fc28</VERSION>
    </SOFTWARES>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>",
                'expected'  => '{"arch": "x86_64", "comments": "GNU Image Manipulation Program", "filesize": 67382735, "from": "rpm", "name": "gimp", "publisher": "Fedora Project", "system_category": "System Component", "version": "2.8.22-7.fc28", "install_date": "2018-09-03", "manufacturers_id": "Feodra Project", "comment": "GNU Image Manipulation Program", "_system_category": "System Component", "operatingsystems_id": 0, "entities_id": 0, "is_template_item": 0, "is_deleted_item": 0, "is_recursive": 0, "date_install": "2018-09-03"}'
            ]
        ];
    }

    /**
     * @dataProvider assetProvider
     */
    public function testPrepare($xml, $expected)
    {
        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();

       //Manufacturer has been imported into db...
        $expected = json_decode($expected);

        $manu = new \Manufacturer();
        $this->boolean($manu->getFromDbByCrit(['name' => $result[0]->publisher]))->isTrue();
        $expected->manufacturers_id = $manu->fields['id'];

        $softCate = new \SoftwareCategory();
        $this->boolean($softCate->getFromDbByCrit(['name' => $result[0]->system_category]))->isTrue();
        $expected->softwarecategories_id = $softCate->fields['id'];

        $this->object($result[0])->isEqualTo($expected);
    }

    public function testHandle()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');

       //first, check there are no software linked to this computer
        $sov = new \Item_SoftwareVersion();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isFalse('A software version is already linked to computer!');

       //convert data
        $expected = $this->assetProvider()[0];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $asset->setExtraData((array)$json->content);
        $result = $asset->prepare();
        $expected = json_decode($expected['expected']);
        $manu = new \Manufacturer();
        $this->boolean($manu->getFromDbByCrit(['name' => $result[0]->publisher]))->isTrue();
        $expected->manufacturers_id = $manu->fields['id'];
        $softCate = new \SoftwareCategory();
        $this->boolean($softCate->getFromDbByCrit(['name' => $result[0]->system_category]))->isTrue();
        $expected->softwarecategories_id = $softCate->fields['id'];
        $this->object($result[0])->isEqualTo($expected);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
           ->isTrue('A software version has not been linked to computer!');

        $version = new \SoftwareVersion();
        $this->boolean($version->getFromDB($sov->fields['softwareversions_id']))->isTrue();
        $this->integer($version->fields['operatingsystems_id'])->isIdenticalTo(0);

       //convert data
        $expected = $this->assetProvider()[1];

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($expected['xml']);
        $json = json_decode($data);

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $asset = new \Glpi\Inventory\Asset\Software($computer, $json->content->softwares);
        $osasset = new \Glpi\Inventory\Asset\OperatingSystem($computer, (array)$json->content->operatingsystem);
        $osasset->prepare();
       //handle
        $osasset->handleLinks();
        $osasset->handle();

        $extra_data = (array)$json->content;
        $extra_data[\Glpi\Inventory\Asset\OperatingSystem::class] = $osasset;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();
        $expected = json_decode($expected['expected']);
        $manu = new \Manufacturer();
        $this->boolean($manu->getFromDbByCrit(['name' => $result[0]->publisher]))->isTrue();
        $expected->manufacturers_id = $manu->fields['id'];
        $softCate = new \SoftwareCategory();
        $this->boolean($softCate->getFromDbByCrit(['name' => $result[0]->system_category]))->isTrue();
        $expected->softwarecategories_id = $softCate->fields['id'];
        $this->object($result[0])->isEqualTo($expected);

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer->fields['id'], 'itemtype' => 'Computer']))
         ->isTrue('A software version has not been linked to computer!');

        $this->integer($sov->fields['softwareversions_id'])->isNotEqualTo($version->fields['id']);

        $version = new \SoftwareVersion();
        $this->boolean($version->getFromDB($sov->fields['softwareversions_id']))->isTrue();
        $this->integer($version->fields['operatingsystems_id'])->isGreaterThan(0);

       //new computer with same software
        global $DB;
        $soft_reference = $DB->request(\Software::getTable());
        $this->integer(count($soft_reference))->isIdenticalTo(4);

        $computer2 = getItemByTypeName('Computer', '_test_pc02');
       //first, check there are no software linked to this computer
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer2->fields['id'], 'itemtype' => 'Computer']))
         ->isFalse('A software version is already linked to computer!');

        $asset = new \Glpi\Inventory\Asset\Software($computer2, $json->content->softwares);
        $osasset = new \Glpi\Inventory\Asset\OperatingSystem($computer2, (array)$json->content->operatingsystem);
        $osasset->prepare();
       //handle
        $osasset->handleLinks();
        $osasset->handle();

        $extra_data = (array)$json->content;
        $extra_data[\Glpi\Inventory\Asset\OperatingSystem::class] = $osasset;

        $asset->setExtraData($extra_data);
        $result = $asset->prepare();

       //handle
        $asset->handleLinks();
        $asset->handle();
        $this->boolean($sov->getFromDbByCrit(['items_id' => $computer2->fields['id'], 'itemtype' => 'Computer']))
         ->isTrue('A software version has not been linked to computer!');

        $this->integer(count($DB->request(\Software::getTable())))->isIdenticalTo(count($soft_reference));
    }

    public function testInventoryUpdate()
    {
        global $DB;
        $computer = new \Computer();
        $soft = new \Software();
        $version = new \SoftwareVersion();
        $item_version = new \Item_SoftwareVersion();
        $software_categorie = new \SoftwareCategory();

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Application</SYSTEM_CATEGORY>
      <VERSION>2.10.28-1.fc34</VERSION>
    </SOFTWARES>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>Command-line interface for PHP</COMMENTS>
      <FILESIZE>25438052</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>25/11/2021</INSTALLDATE>
      <NAME>php-cli</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Development/Languages</SYSTEM_CATEGORY>
      <VERSION>7.4.25-1.fc34</VERSION>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

       //software import behave differently: non-dynamic are not handled at all.
       //create manually a computer
        $computers_id = $computer->add([
            'name'   => 'pc002',
            'serial' => 'ggheb7ne7',
            'entities_id' => 0
        ]);
        $this->integer($computers_id)->isGreaterThan(0);

       //computer inventory knows only "gimp" and "php-cli" software
        $this->doInventory($xml_source, true);

       //we have 2 software & versions
        $softs = $soft->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($softs))->isIdenticalTo(2);
        $versions = $version->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($versions))->isIdenticalTo(2);

        //check that sofware exist with softwarecategories
        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "gimp",
                \SoftwareCategory::getTable() . ".name" => "Application"
            ]
        ];

        $iterator = $DB->request($criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);

        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "php-cli",
                \SoftwareCategory::getTable() . ".name" => "Development/Languages"
            ]
        ];

        $iterator = $DB->request($criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);

       //we have 2 softwareversion items linked to the computer
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($item_versions))->isIdenticalTo(2);

       //software present in the inventory source are dynamic
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_versions))->isIdenticalTo(2);

       //Redo inventory, but with removed "php-cli" software and change softwareCategories
        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS>GNU Image Manipulation Program</COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>gimp</NAME>
      <PUBLISHER>Fedora Project</PUBLISHER>
      <SYSTEM_CATEGORY>Web App</SYSTEM_CATEGORY>
      <VERSION>2.10.28-1.fc34</VERSION>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc002</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ggheb7ne7</SSN>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Agent_v2.3.19</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>test-pc002</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

       //we still have 2 software & versions
        $softs = $soft->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($softs))->isIdenticalTo(2);
        $versions = $version->find(['NOT' => ['name' => ['LIKE', '_test_%']]]);
        $this->integer(count($versions))->isIdenticalTo(2);

        //check that sofware still exist but with different softwarecategories
        $criteria = [
            'FROM' => \Software::getTable(),
            'LEFT JOIN' => [
                \SoftwareCategory::getTable() => [
                    'ON' => [
                        \Software::getTable() => 'softwarecategories_id',
                        \SoftwareCategory::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                \Software::getTable() . ".name" => "gimp",
                \SoftwareCategory::getTable() . ".name" => "Web App"
            ]
        ];
        $iterator = $DB->request($criteria);
        $this->integer(count($iterator))->isIdenticalTo(1);

       //we now have 1 softwareversion items linked to the computer
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id]);
        $this->integer(count($item_versions))->isIdenticalTo(1);

       //software present in the inventory source is still dynamic
        $item_versions = $item_version->find(['itemtype' => 'Computer', 'items_id' => $computers_id, 'is_dynamic' => 1]);
        $this->integer(count($item_versions))->isIdenticalTo(1);
    }


    public function testSoftwareEntity()
    {
        $this->login();
        $entity = new \Entity();
        $entity_1_id = $entity->add([
            'name' => '_test_entity_1',
            'entities_id' => 0,
            'entities_id_software' => \Entity::CONFIG_PARENT,
            'tag' => 'testtag_1',
        ]);
        $entity_2_id = $entity->add([
            'name' => '_test_entity_2',
            'entities_id' => $entity_1_id,
            'entities_id_software' => \Entity::CONFIG_NEVER,
            'tag' => 'testtag_2',
        ]);

        $rule         = new \RuleImportEntity();
        $rulecriteria = new \RuleCriteria();
        $ruleaction   = new \RuleAction();

        $rules_id = $rule->add([
            'is_active'    => 1,
            'name'         => '_test_affect_entity_by_tag',
            'match'        => 'AND',
            'sub_type'     => \RuleImportEntity::class,
            'is_recursive' => 1,
            'ranking'      => 1,
        ]);
        $this->integer((int) $rules_id)->isGreaterThan(0);

        $this->integer((int) $rulecriteria->add([
            'rules_id'    => $rules_id,
            'criteria'  => "tag",
            'pattern'   => "/(.*)/",
            'condition' => \RuleImportEntity::REGEX_MATCH
        ]))->isGreaterThan(0);

        $this->integer((int) $ruleaction->add([
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => '_affect_entity_by_tag',
            'value'       => 'testtag_2',
        ]))->isGreaterThan(0);

        $xml_source = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <SOFTWARES>
      <ARCH>x86_64</ARCH>
      <COMMENTS></COMMENTS>
      <FILESIZE>67382735</FILESIZE>
      <FROM>rpm</FROM>
      <INSTALLDATE>03/10/2021</INSTALLDATE>
      <NAME>test_software</NAME>
      <PUBLISHER>Publisher</PUBLISHER>
      <SYSTEM_CATEGORY>Unspecified</SYSTEM_CATEGORY>
      <VERSION>1.1</VERSION>
    </SOFTWARES>
    <HARDWARE>
      <NAME>pc_test_entity</NAME>
    </HARDWARE>
    <BIOS>
      <SSN>ssnexample</SSN>
    </BIOS>
    <VERSIONCLIENT>test-agent</VERSIONCLIENT>
    <ACCOUNTINFO>
      <KEYNAME>TAG</KEYNAME>
      <KEYVALUE>testtag_2</KEYVALUE>
    </ACCOUNTINFO>
  </CONTENT>
  <DEVICEID>pc_test_entity</DEVICEID>
  <QUERY>INVENTORY</QUERY>
</REQUEST>";

        $this->doInventory($xml_source, true);

        $computer = new \Computer();
        $found_computers = $computer->find(['name' => "pc_test_entity"]);
        $this->integer(count($found_computers))->isIdenticalTo(1);
        $first_computer = array_pop($found_computers);
        $this->integer($first_computer['entities_id'])->isIdenticalTo($entity_2_id);

        $soft = new \Software();
        $softs = $soft->find(['name' => "test_software"]);
        $this->integer(count($softs))->isIdenticalTo(1);
        $first_soft = array_pop($softs);
        $this->integer($first_soft['entities_id'])->isIdenticalTo($entity_2_id);
        $this->integer($first_soft['is_recursive'])->isIdenticalTo(0);
    }
}
