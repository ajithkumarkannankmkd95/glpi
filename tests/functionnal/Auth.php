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

/* Test for inc/auth.class.php */

class Auth extends DbTestCase
{
    protected function loginProvider()
    {
        return [
            ['john', true],
            ['john doe', true],
            ['john_doe', true],
            ['john-doe', true],
            ['john.doe', true],
            ['john \'o doe', true],
            ['john@doe.com', true],
            ['@doe.com', true],
            ['john " doe', false],
            ['john^doe', false],
            ['john$doe', false],
            [null, false],
            ['', false]
        ];
    }

    /**
     * @dataProvider loginProvider
     */
    public function testIsValidLogin($login, $isvalid)
    {
        $this->boolean(\Auth::isValidLogin($login))->isIdenticalTo($isvalid);
    }

    public function testGetLoginAuthMethods()
    {
        $methods = \Auth::getLoginAuthMethods();
        $expected = [
            '_default'  => 'local',
            'local'     => 'GLPI internal database'
        ];
        $this->array($methods)->isIdenticalTo($expected);
    }

    /**
     * Provides data to test account lock strategy on password expiration.
     *
     * @return array
     */
    protected function lockStrategyProvider()
    {
        $tests = [];

       // test with no password expiration
        $tests[] = [
            'last_update'   => date('Y-m-d H:i:s', strtotime('-10 years')),
            'exp_delay'     => -1,
            'lock_delay'    => -1,
            'expected_lock' => false,
        ];

       // tests with no lock on password expiration
        $cases = [
            '-5 days'  => false,
            '-30 days' => false,
        ];
        foreach ($cases as $last_update => $expected_lock) {
            $tests[] = [
                'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
                'exp_delay'     => 15,
                'lock_delay'    => -1,
                'expected_lock' => $expected_lock,
            ];
        }

       // tests with immediate lock on password expiration
        $cases = [
            '-5 days'  => false,
            '-30 days' => true,
        ];
        foreach ($cases as $last_update => $expected_lock) {
            $tests[] = [
                'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
                'exp_delay'     => 15,
                'lock_delay'    => 0,
                'expected_lock' => $expected_lock,
            ];
        }

       // tests with delayed lock on password expiration
        $cases = [
            '-5 days'  => false,
            '-20 days' => false,
            '-30 days' => true,
        ];
        foreach ($cases as $last_update => $expected_lock) {
            $tests[] = [
                'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
                'exp_delay'     => 15,
                'lock_delay'    => 10,
                'expected_lock' => $expected_lock,
            ];
        }

        return $tests;
    }

    /**
     * Test that account is lock when authentication is done using an expired password.
     *
     * @dataProvider lockStrategyProvider
     */
    public function testAccountLockStrategy(string $last_update, int $exp_delay, int $lock_delay, bool $expected_lock)
    {
        global $CFG_GLPI;

       // reset session to prevent session having less rights to create a user
        $this->login();

        $user = new \User();
        $username = 'test_lock_' . mt_rand();
        $user_id = (int) $user->add([
            'name'         => $username,
            'password'     => 'test',
            'password2'    => 'test',
            '_profiles_id' => 1,
        ]);
        $this->integer($user_id)->isGreaterThan(0);
        $this->boolean($user->update(['id' => $user_id, 'password_last_update' => $last_update]))->isTrue();

        $CFG_GLPI['password_expiration_delay'] = $exp_delay;
        $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;
        $auth = new \Auth();
        $is_logged = $auth->login($username, 'test', true);

        $this->boolean($is_logged)->isEqualTo(!$expected_lock);
        $this->boolean($user->getFromDB($user->fields['id']))->isTrue();
        $this->boolean((bool)$user->fields['is_active'])->isEqualTo(!$expected_lock);
    }
}
