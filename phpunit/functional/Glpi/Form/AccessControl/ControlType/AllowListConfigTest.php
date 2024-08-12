<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use AbstractRightsDropdown;
use Glpi\Form\AccessControl\ControlType\AllowList;
use Glpi\Form\AccessControl\ControlType\AllowListConfig;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Tests\FormBuilder;
use Group;
use Profile;
use User;

final class AllowListConfigTest extends \DbTestCase
{
    public function testJsonDeserialize(): void
    {
        $config = AllowListConfig::jsonDeserialize([
            'user_ids'    => [1, 2, 3],
            'group_ids'   => [4, 5, 6],
            'profile_ids' => [7, 8, 9],
        ]);
        $this->assertEquals([1, 2, 3], $config->getUserIds());
        $this->assertEquals([4, 5, 6], $config->getGroupIds());
        $this->assertEquals([7, 8, 9], $config->getProfileIds());
    }

    public function testGetUserIds(): void
    {
        $allow_list_config = new AllowListConfig(
            user_ids: [1, 2, 3],
        );
        $this->assertEquals([1, 2, 3], $allow_list_config->getUserIds());
    }

    public function testGetGroupIds(): void
    {
        $allow_list_config = new AllowListConfig(
            group_ids: [4, 5, 6],
        );
        $this->assertEquals([4, 5, 6], $allow_list_config->getGroupIds());
    }

    public function testGetProfileIds(): void
    {
        $allow_list_config = new AllowListConfig(
            profile_ids: [7, 8, 9],
        );
        $this->assertEquals([7, 8, 9], $allow_list_config->getProfileIds());
    }

    public function testDeserializeWithoutDatabaseIdsRequirements(): void
    {
        $all_users = AbstractRightsDropdown::ALL_USERS;

        // Arrange: create a config with references to multiple users, groups
        // and profiles.
        list($user_1, $user_2) = $this->createItemsWithNames(
            User::class,
            ["User 1", "User 2"]
        );
        list($group_1, $group_2) = $this->createItemsWithNames(
            Group::class,
            ["Group 1",  "Group 2"]
        );
        list($profile_1, $profile_2) = $this->createItemsWithNames(
            Profile::class,
            ["Profile 1", "Profile 2"]
        );

        $config = new AllowListConfig(
            user_ids: [$user_1->getID(), $user_2->getID(), $all_users],
            group_ids: [$group_1->getID(), $group_2->getID()],
            profile_ids: [$profile_1->getID(), $profile_2->getID()],
        );

        // Act: get deserialize requirements
        $requirements = $config->getJsonDeserializeWithoutDatabaseIdsRequirements();

        // Assert: validate that all referenced items are required
        $this->assertEquals([
            new DataRequirementSpecification(User::class, "User 1"),
            new DataRequirementSpecification(User::class, "User 2"),
            new DataRequirementSpecification(Group::class, "Group 1"),
            new DataRequirementSpecification(Group::class, "Group 2"),
            new DataRequirementSpecification(Profile::class, "Profile 1"),
            new DataRequirementSpecification(Profile::class, "Profile 2"),
        ], $requirements);
    }
}
