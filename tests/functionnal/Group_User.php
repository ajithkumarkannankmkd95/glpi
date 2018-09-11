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

/* Test for inc/group_user.class.php */

class Group_User extends \DbTestCase {

   public function testGetGroupUsers() {
      $group = new \Group();
      $gid = (int)$group->add([
         'name' => 'Test group'
      ]);
      $this->integer($gid)->isGreaterThan(0);

      $uid1 = (int)getItemByTypeName('User', 'normal', true);
      $uid2 = (int)getItemByTypeName('User', 'tech', true);

      $group_user = $this->newTestedInstance();
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $gid,
            'users_id'  => $uid1
         ])
      );

      $this->integer(
         (int)$group_user->add([
            'groups_id'    => $gid,
            'users_id'     => $uid2,
            'is_manager'   => 1
         ])
      );

      $group_users = \Group_User::getGroupUsers($gid);
      $this->array($group_users)->hasSize(2);

      $this->exception(
         function() use ($gid, $uid2) {
            $group_users = \Group_User::getGroupUsers($gid, 'is_manager = 1');
            $this->array($group_users)->hasSize(1);
            $this->integer((int)$group_users[0]['id'])->isIdenticalTo($uid2);
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('getGroupUsers condition must be an array!');

      $group_users = \Group_User::getGroupUsers($gid, ['is_manager' => 1]);
      $this->array($group_users)->hasSize(1);
      $this->integer((int)$group_users[0]['id'])->isIdenticalTo($uid2);

      //cleanup
      $this->boolean($group->delete(['id' => $gid], true))->isTrue();

      $group_users = \Group_User::getGroupUsers($gid);
      $this->array($group_users)->hasSize(0);
   }

   public function testGetUserGroups() {
      $uid = (int)getItemByTypeName('User', 'normal', true);

      $group = new \Group();
      $gid1 = (int)$group->add([
         'name' => 'Test group'
      ]);
      $this->integer($gid1)->isGreaterThan(0);

      $gid2 = (int)$group->add([
         'name' => 'Test group 2'
      ]);
      $this->integer($gid2)->isGreaterThan(0);

      $group_user = $this->newTestedInstance();
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $gid1,
            'users_id'  => $uid
         ])
      );

      $this->integer(
         (int)$group_user->add([
            'groups_id'    => $gid2,
            'users_id'     => $uid,
            'is_manager'   => 1
         ])
      );

      $group_users = \Group_User::getUserGroups($uid);
      $this->array($group_users)->hasSize(2);

      $this->exception(
         function() use ($uid, $gid2) {
            $group_users = \Group_User::getUserGroups($uid, 'glpi_groups_users.is_manager = 1');
         }
      )
         ->isInstanceOf('RuntimeException')
         ->message->contains('getUserGroups condition must be an array!');

      $group_users = \Group_User::getUserGroups($uid, ['glpi_groups_users.is_manager' => 1]);
      $this->array($group_users)->hasSize(1);
      $this->integer((int)$group_users[0]['id'])->isIdenticalTo($gid2);

      //cleanup
      $this->boolean($group_user->deleteByCriteria(['users_id' => $uid]))->isTrue();

      $group_users = \Group_User::getUserGroups($uid);
      $this->array($group_users)->hasSize(0);
   }

   public function testgetListForItemParams() {
      $this->newTestedInstance();
      $user = getItemByTypeName('User', TU_USER);

      $expected = [];
      $this->array(iterator_to_array($this->testedInstance->getListForItem($user)))->isIdenticalTo($expected);

      //Now, add groups to user
      $group = new \Group();
      $gid1 = (int)$group->add([
         'name' => 'Test group'
      ]);
      $this->integer($gid1)->isGreaterThan(0);

      $gid2 = (int)$group->add([
         'name' => 'Test group 2'
      ]);
      $this->integer($gid2)->isGreaterThan(0);

      $group_user = $this->newTestedInstance();
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $gid1,
            'users_id'  => $user->getID()
         ])
      );

      $this->integer(
         (int)$group_user->add([
            'groups_id'    => $gid2,
            'users_id'     => $user->getID(),
            'is_manager'   => 1
         ])
      );

      $list_items = iterator_to_array($this->testedInstance->getListForItem($user));
      $this->array($list_items)
         ->hasSize(2)
         ->hasKeys([$gid1, $gid2]);

      $this->array($list_items[$gid1])
         ->hasKey('linkid')
         ->string['name']->isIdenticalTo('Test group');

      $this->array($list_items[$gid2])
         ->hasKey('linkid')
         ->string['name']->isIdenticalTo('Test group 2');

      $group->getFromDB($gid2);
      $list_items = iterator_to_array($this->testedInstance->getListForItem($group));
      $this->array($list_items)
         ->hasSize(1)
         ->hasKey($user->getID());

      $this->array($list_items[$user->getID()])
         ->hasKeys(['linkid', 'is_manager', 'is_userdelegate'])
         ->string['name']->isIdenticalTo('_test_user');

      $this->integer($this->testedInstance->countForItem($user))->isIdenticalTo(2);
      $this->integer($this->testedInstance->countForItem($group))->isIdenticalTo(1);
   }
}
