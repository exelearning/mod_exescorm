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
 * Tests for the uploaded styles list admin setting.
 *
 * @package    mod_exescorm
 * @copyright  2026 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\admin;

use mod_exescorm\local\styles_service;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Regression tests covering the disable flow of admin_setting_stylesuploaded.
 *
 * @covers \mod_exescorm\admin\admin_setting_stylesuploaded
 */
class admin_setting_stylesuploaded_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Seed the registry with a single enabled uploaded style and return its slug.
     *
     * @param string $slug
     * @return string
     */
    private function seed_uploaded_style(string $slug = 'mystyle'): string {
        styles_service::save_registry([
            'uploaded' => [
                $slug => [
                    'title' => ucfirst($slug),
                    'version' => '1.0.0',
                    'css_files' => ['style.css'],
                    'enabled' => true,
                ],
            ],
            'disabled_builtins' => [],
        ]);
        return $slug;
    }

    /**
     * Posting an empty array (every checkbox cleared) must flip the
     * `enabled` flag on each uploaded style to false. Without the sentinel
     * input added in output_html() Moodle's admin pipeline never even calls
     * write_setting(), so this test pairs with the rendering test below.
     */
    public function test_write_setting_with_empty_array_disables_all(): void {
        $slug = $this->seed_uploaded_style();
        $setting = new admin_setting_stylesuploaded('exescorm/styles_uploaded');

        $setting->write_setting([]);

        $registry = styles_service::get_registry();
        $this->assertSame(false, $registry['uploaded'][$slug]['enabled']);
    }

    /**
     * Posting null (the value Moodle hands us when the parent name is
     * missing from $_POST entirely) must be treated like an empty array
     * and still disable everything.
     */
    public function test_write_setting_with_null_disables_all(): void {
        $slug = $this->seed_uploaded_style();
        $setting = new admin_setting_stylesuploaded('exescorm/styles_uploaded');

        $setting->write_setting(null);

        $registry = styles_service::get_registry();
        $this->assertSame(false, $registry['uploaded'][$slug]['enabled']);
    }

    /**
     * The sentinel key the rendering layer emits must be tolerated by the
     * write path: it is not a real slug, so it must not corrupt the registry.
     */
    public function test_write_setting_ignores_sentinel_key(): void {
        $slug = $this->seed_uploaded_style();
        $setting = new admin_setting_stylesuploaded('exescorm/styles_uploaded');

        $setting->write_setting(['__sentinel' => '1', $slug => '1']);

        $registry = styles_service::get_registry();
        $this->assertArrayNotHasKey('__sentinel', $registry['uploaded']);
        $this->assertSame(true, $registry['uploaded'][$slug]['enabled']);
    }

    /**
     * The rendered widget must include a hidden input under the parent
     * name so the browser always submits the key, ensuring
     * admin_find_write_settings() will route the form value to write_setting().
     */
    public function test_output_html_includes_form_sentinel(): void {
        $this->seed_uploaded_style();
        $setting = new admin_setting_stylesuploaded('exescorm/styles_uploaded');

        $html = $setting->output_html(null);

        $this->assertStringContainsString(
            'name="s_exescorm_styles_uploaded[__sentinel]"',
            $html
        );
    }
}
