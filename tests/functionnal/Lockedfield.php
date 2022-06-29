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

namespace tests\units;

use DbTestCase;

/* Test for inc/savedsearch.class.php */

class Lockedfield extends DbTestCase
{
    public function testWithComputer()
    {
        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->integer($cid)->isGreaterThan(0);

        $lockedfield = new \Lockedfield();
        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isEmpty();

        //update computer manually, to add a locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'AZERTY'])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure new dynamic update does not override otherserial again
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'otherserial'  => '789012',
                'is_dynamic'   => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('AZERTY');
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure new dynamic update do not set new lock on regular update
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'name'         => 'Computer name changed',
                'is_dynamic'   => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure regular update do work on locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        )->isTrue();
        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
    }

    public function testGlobalLock()
    {
        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->integer($cid)->isGreaterThan(0);

        $lockedfield = new \Lockedfield();
        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isEmpty();

        //add a global lock on otherserial field
        $this->integer(
            $lockedfield->add([
                'item' => 'Computer - otherserial'
            ])
        )->isGreaterThan(0);

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure new dynamic update does not override otherserial again
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'otherserial'  => 'changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('789012');
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure new dynamic update do not set new lock on regular update
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'name' => 'Computer name changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure regular update do work on locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        )->isTrue();
        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
    }

    /**
     * Check for global locks adding an itemtype
     */
    public function testGlobalLockAdd()
    {
        $lockedfield = new \Lockedfield();

        //add a global lock on otherserial field
        $this->integer(
            $lockedfield->add([
                'item' => 'Computer - otherserial'
            ])
        )->isGreaterThan(0);

        $computer = new \Computer();
        $cid = (int)$computer->add([
            'name'         => 'Computer from inventory',
            'serial'       => '123456',
            'otherserial'  => '789012',
            'entities_id'  => 0,
            'is_dynamic'   => 1
        ]);
        $this->integer($cid)->isGreaterThan(0);

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('');

        $this->boolean($lockedfield->isHandled($computer))->isTrue();
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure new dynamic update does not override otherserial again
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'otherserial'  => 'changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('');
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure new dynamic update do not set new lock on regular update
        $this->boolean(
            (bool)$computer->update([
                'id' => $cid,
                'name' => 'Computer name changed',
                'is_dynamic' => 1
            ])
        )->isTrue();

        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['name'])->isEqualTo('Computer name changed');
        $this->array($lockedfield->getLocks($computer->getType(), $cid))->isIdenticalTo(['otherserial']);

        //ensure regular update do work on locked field
        $this->boolean(
            (bool)$computer->update(['id' => $cid, 'otherserial' => 'QWERTY'])
        )->isTrue();
        $this->boolean($computer->getFromDB($cid))->isTrue();
        $this->variable($computer->fields['otherserial'])->isEqualTo('QWERTY');
    }

    public function testNoRelation()
    {
        global $DB;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<REQUEST>
  <CONTENT>
    <HARDWARE>
      <NAME>glpixps</NAME>
      <UUID>25C1BB60-5BCB-11D9-B18F-5404A6A534C4</UUID>
    </HARDWARE>
    <BIOS>
      <MSN>640HP72</MSN>
      <SSN>000</SSN>
      <SMANUFACTURER>Da Manuf</SMANUFACTURER>
    </BIOS>
    <VERSIONCLIENT>FusionInventory-Inventory_v2.4.1-2.fc28</VERSIONCLIENT>
  </CONTENT>
  <DEVICEID>glpixps.teclib.infra-2018-10-03-08-42-36</DEVICEID>
  <QUERY>INVENTORY</QUERY>
  </REQUEST>";

        $existing_manufacturers = countElementsInTable(\Manufacturer::getTable());
        $lockedfield = new \Lockedfield();

        //add a global lock on manufacturers_id field
        $this->integer(
            $lockedfield->add([
                'item' => 'Computer - manufacturers_id'
            ])
        )->isGreaterThan(0);

        $converter = new \Glpi\Inventory\Converter();
        $data = $converter->convert($xml);
        $json = json_decode($data);

        $inventory = new \Glpi\Inventory\Inventory($json);

        if ($inventory->inError()) {
            $this->dump($inventory->getErrors());
        }
        $this->boolean($inventory->inError())->isFalse();
        $this->array($inventory->getErrors())->isEmpty();

        //check matchedlogs
        $criteria = [
            'FROM' => \RuleMatchedLog::getTable(),
            'LEFT JOIN' => [
                \Rule::getTable() => [
                    'ON' => [
                        \RuleMatchedLog::getTable() => 'rules_id',
                        \Rule::getTable() => 'id'
                    ]
                ]
            ],
            'WHERE' => []
        ];
        $iterator = $DB->request($criteria);
        $this->string($iterator->current()['name'])->isIdenticalTo('Computer import (by serial + uuid)');

        //check created agent
        $agents = $DB->request(['FROM' => \Agent::getTable()]);
        $this->integer(count($agents))->isIdenticalTo(1);
        $agent = $agents->current();
        $this->array($agent)
            ->string['deviceid']->isIdenticalTo('glpixps.teclib.infra-2018-10-03-08-42-36')
            ->string['itemtype']->isIdenticalTo('Computer');

        //check created computer
        $computers_id = $agent['items_id'];

        $this->integer($computers_id)->isGreaterThan(0);
        $computer = new \Computer();
        $this->boolean($computer->getFromDB($computers_id))->isTrue();
        $this->integer($computer->fields['manufacturers_id'])->isEqualTo(0);

        //ensure no new manufacturer has been added
        $this->integer(countElementsInTable(\Manufacturer::getTable()))->isIdenticalTo($existing_manufacturers);
    }
}
