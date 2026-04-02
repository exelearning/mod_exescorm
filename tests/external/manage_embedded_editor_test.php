<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the manage_embedded_editor external functions.
 *
 * @package    mod_exescorm
 * @category   external
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\external;

defined('MOODLE_INTERNAL') || die();

use mod_exescorm\local\embedded_editor_installer;

/**
 * Tests for manage_embedded_editor external API.
 *
 * @covers \mod_exescorm\external\manage_embedded_editor
 */
class manage_embedded_editor_test extends \advanced_testcase {

    /**
     * Test that get_status returns a response with all expected keys.
     *
     * Uses checklatest=false to avoid any network calls.
     */
    public function test_get_status_returns_expected_structure(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $result = manage_embedded_editor::get_status(false);

        $this->assertIsArray($result);
        $expectedkeys = [
            'active_source',
            'moodledata_available',
            'bundled_available',
            'installing',
            'install_stale',
            'can_install',
            'can_update',
            'can_repair',
            'can_uninstall',
        ];
        foreach ($expectedkeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }

    /**
     * Test that execute_action throws invalid_parameter_exception for unknown actions.
     */
    public function test_execute_action_invalid_action(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->expectException(\invalid_parameter_exception::class);
        manage_embedded_editor::execute_action('invalid');
    }

    /**
     * Test that execute_action requires mod/exescorm:manageembeddededitor capability.
     *
     * A regular user without the capability should get required_capability_exception.
     */
    public function test_execute_action_requires_manageembeddededitor_capability(): void {
        $this->resetAfterTest(true);

        // Create a user without mod/exescorm:manageembeddededitor.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        manage_embedded_editor::execute_action('install');
    }

    /**
     * Test that execute_action requires moodle/site:config capability.
     *
     * A user with manageembeddededitor but not moodle/site:config should be denied.
     */
    public function test_execute_action_requires_site_config_capability(): void {
        global $DB;

        $this->resetAfterTest(true);

        // Create a user and assign only mod/exescorm:manageembeddededitor (not moodle/site:config).
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $systemcontext = \context_system::instance();
        assign_capability('mod/exescorm:manageembeddededitor', CAP_ALLOW, $roleid, $systemcontext->id, true);
        role_assign($roleid, $user->id, $systemcontext->id);
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        manage_embedded_editor::execute_action('install');
    }

    /**
     * Test that get_status correctly reports an active install lock.
     *
     * When CONFIG_INSTALLING is set to a recent timestamp, installing should be
     * true and install_stale should be false.
     */
    public function test_get_status_detects_installing_lock(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config(embedded_editor_installer::CONFIG_INSTALLING, time(), 'exescorm');

        $result = manage_embedded_editor::get_status(false);

        $this->assertTrue($result['installing'], 'Expected installing to be true with active lock');
        $this->assertFalse($result['install_stale'], 'Expected install_stale to be false with fresh lock');
    }

    /**
     * Test that get_status correctly reports a stale install lock.
     *
     * When CONFIG_INSTALLING is set to a timestamp older than INSTALL_LOCK_TIMEOUT,
     * installing should be true (lock present) and install_stale should be true.
     */
    public function test_get_status_detects_stale_lock(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Set a timestamp well beyond the timeout (300s).
        set_config(embedded_editor_installer::CONFIG_INSTALLING, time() - 400, 'exescorm');

        $result = manage_embedded_editor::get_status(false);

        $this->assertFalse($result['installing'], 'Expected installing to be false when lock is stale');
        $this->assertTrue($result['install_stale'], 'Expected install_stale to be true with expired lock');
    }
}
