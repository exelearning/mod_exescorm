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
 * Tests for the optional stored-file admin setting.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\admin;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Regression tests covering the save flow of admin_setting_optional_configstoredfile.
 *
 * @covers \mod_exescorm\admin\admin_setting_optional_configstoredfile
 */
class admin_setting_optional_configstoredfile_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Build the setting under test with the same options the plugin uses in
     * its settings page.
     *
     * @return admin_setting_optional_configstoredfile
     */
    private function build_setting(): admin_setting_optional_configstoredfile {
        return new admin_setting_optional_configstoredfile(
            'exescorm/template',
            'New package template',
            'Optional package template.',
            'config',
            0,
            [
                'accepted_types' => ['.zip'],
                'maxbytes' => 0,
                'maxfiles' => 1,
                'subdirs' => 0,
            ]
        );
    }

    /**
     * Stage a file in a fresh user draft area and return its draft item id.
     *
     * @param string $filename Name of the file to stage.
     * @param string $content Raw file contents.
     * @return int Draft item id holding the staged file.
     */
    private function stage_template_file(string $filename, string $content): int {
        global $USER;
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($USER->id);
        get_file_storage()->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => $filename,
        ], $content);
        return $draftitemid;
    }

    /**
     * Return the files stored in the plugin config filearea (excluding dirs).
     *
     * @return \stored_file[]
     */
    private function get_stored_files(): array {
        $syscontext = \context_system::instance();
        return get_file_storage()->get_area_files(
            $syscontext->id,
            'exescorm',
            'config',
            0,
            'itemid, filepath, filename',
            false
        );
    }

    /**
     * A fresh optional setting must report '' instead of null, so Moodle does
     * not keep flagging it as a new setting on the upgrade page.
     */
    public function test_get_setting_returns_empty_string_for_fresh_setting(): void {
        $setting = $this->build_setting();
        $this->assertSame('', $setting->get_setting());
    }

    /**
     * Saving with no draft item id ('', '0' or null) must succeed without
     * error, store nothing, and persist an explicit (non-null) empty value.
     */
    public function test_write_setting_with_empty_values_returns_empty(): void {
        $setting = $this->build_setting();
        $this->assertSame('', $setting->write_setting(''));
        $this->assertSame('', $setting->write_setting('0'));
        $this->assertSame('', $setting->write_setting(null));
        $this->assertCount(0, $this->get_stored_files());
        // The persisted value must be '' (not null) so the setting is never
        // treated as new on the upgrade page.
        $this->assertSame('', get_config('exescorm', 'template'));
    }

    /**
     * Saving with a valid but empty draft area (the widget was opened but no
     * file attached) must still succeed and store nothing.
     */
    public function test_write_setting_with_empty_draft_returns_empty(): void {
        $setting = $this->build_setting();
        $draftitemid = file_get_unused_draft_itemid();
        $this->assertSame('', $setting->write_setting((string) $draftitemid));
        $this->assertCount(0, $this->get_stored_files());
    }

    /**
     * Uploading a template must store exactly one file in the plugin config
     * filearea and expose its path through get_setting().
     */
    public function test_write_setting_stores_uploaded_file(): void {
        $setting = $this->build_setting();
        $draftitemid = $this->stage_template_file('template.zip', 'dummy package');

        $this->assertSame('', $setting->write_setting((string) $draftitemid));

        $files = $this->get_stored_files();
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('template.zip', $file->get_filename());
        $this->assertSame('/template.zip', $setting->get_setting());
    }

    /**
     * Uploading a second file must replace the stored template, leaving
     * exactly one file in the filearea.
     */
    public function test_write_setting_replaces_stored_file(): void {
        $setting = $this->build_setting();
        $setting->write_setting((string) $this->stage_template_file('template.zip', 'first'));
        $this->assertCount(1, $this->get_stored_files());

        $setting->write_setting((string) $this->stage_template_file('template2.zip', 'second'));

        $files = $this->get_stored_files();
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('template2.zip', $file->get_filename());
        $this->assertSame('/template2.zip', $setting->get_setting());
    }

    /**
     * Once a template is stored, a later save with no draft (the admin simply
     * saves the settings page without touching the widget) must keep the file.
     */
    public function test_stored_file_is_preserved_on_empty_save(): void {
        $setting = $this->build_setting();
        $draftitemid = $this->stage_template_file('template.zip', 'dummy package');
        $setting->write_setting((string) $draftitemid);
        $this->assertCount(1, $this->get_stored_files());

        $this->assertSame('', $setting->write_setting(''));
        $this->assertSame('', $setting->write_setting('0'));
        $this->assertSame('', $setting->write_setting(null));

        $this->assertCount(1, $this->get_stored_files());
        $this->assertSame('/template.zip', $setting->get_setting());
    }

    /**
     * An empty save that leaves an existing file untouched must not be
     * reported as a change by post_write_settings().
     */
    public function test_empty_save_over_stored_file_reports_no_change(): void {
        $setting = $this->build_setting();
        $setting->write_setting((string) $this->stage_template_file('template.zip', 'dummy package'));

        $original = $setting->get_setting();
        $this->assertSame('', $setting->write_setting(''));
        $this->assertFalse($setting->post_write_settings($original));
    }

    /**
     * Intentionally clearing the filemanager (an existing but empty draft
     * area) must remove the stored template.
     */
    public function test_intentional_clear_removes_stored_file(): void {
        global $USER;
        $setting = $this->build_setting();
        $draftitemid = $this->stage_template_file('template.zip', 'dummy package');
        $setting->write_setting((string) $draftitemid);
        $this->assertCount(1, $this->get_stored_files());

        // Simulate the admin opening the filemanager and deleting the file:
        // a draft area that exists but contains no files.
        $cleardraftid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($USER->id);
        get_file_storage()->create_directory($usercontext->id, 'user', 'draft', $cleardraftid, '/');

        $this->assertSame('', $setting->write_setting((string) $cleardraftid));
        $this->assertCount(0, $this->get_stored_files());
        $this->assertSame('', $setting->get_setting());
    }
}
