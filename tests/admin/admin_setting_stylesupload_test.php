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
 * Tests for the styles upload admin setting.
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
 * Regression tests covering the save flow of admin_setting_stylesupload.
 *
 * @covers \mod_exescorm\admin\admin_setting_stylesupload
 */
class admin_setting_stylesupload_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Build the setting under test with the same options the plugin uses
     * in its settings page.
     *
     * @return admin_setting_stylesupload
     */
    private function build_setting(): admin_setting_stylesupload {
        return new admin_setting_stylesupload(
            'exescorm/styles_drops',
            'Style ZIP package',
            'hint',
            'styles_drops',
            0,
            [
                'accepted_types' => ['.zip'],
                'maxbytes' => 0,
                'maxfiles' => -1,
                'subdirs' => 0,
            ]
        );
    }

    /**
     * Saving the settings page without dropping a new ZIP must succeed.
     * Regression test for the "Could not save setting" error users hit
     * after their first successful upload — the cached config from the
     * parent class made `admin_setting_configstoredfile::write_setting`
     * trip its `errorsetting` validation when called again with no file.
     */
    public function test_write_setting_with_no_draft_returns_empty(): void {
        $setting = $this->build_setting();
        $this->assertSame('', $setting->write_setting(''));
        $this->assertSame('', $setting->write_setting('0'));
        $this->assertSame('', $setting->write_setting(null));
    }

    /**
     * Saving with a fresh draft id but no files in it (the form was opened
     * but no ZIP was attached) must still succeed.
     */
    public function test_write_setting_with_empty_draft_returns_empty(): void {
        $setting = $this->build_setting();
        $draftitemid = file_get_unused_draft_itemid();
        $this->assertSame('', $setting->write_setting((string) $draftitemid));
    }

    /**
     * After a successful install the filearea is purged. A subsequent save
     * with no new file must also succeed — this is the actual production
     * scenario the user reported.
     */
    public function test_write_setting_after_install_then_empty_save(): void {
        global $USER;
        $setting = $this->build_setting();

        // Stage a valid style zip in a fresh draft area.
        $draftitemid = file_get_unused_draft_itemid();
        $usercontext = \context_user::instance($USER->id);
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'mystyle.zip',
        ], $this->make_style_zip_contents('mystyle'));

        // First save: the ZIP is consumed, extracted, and the filearea
        // is left empty by the service.
        $this->assertSame('', $setting->write_setting((string) $draftitemid));
        $registry = \mod_exescorm\local\styles_service::get_registry();
        $this->assertArrayHasKey('mystyle', $registry['uploaded']);

        // Second save with nothing attached must not return an error.
        $this->assertSame('', $setting->write_setting(''));
        $this->assertSame('', $setting->write_setting((string) file_get_unused_draft_itemid()));
    }

    /**
     * Build the bytes of a minimal valid style ZIP (config.xml + style.css).
     *
     * @param string $name Theme identifier baked into config.xml.
     * @return string ZIP bytes.
     */
    private function make_style_zip_contents(string $name): string {
        $tmp = tempnam(sys_get_temp_dir(), 'exescormstyle') . '.zip';
        @unlink($tmp);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmp, \ZipArchive::CREATE) === true);
        $zip->addFromString('config.xml',
            '<?xml version="1.0"?><theme>'
            . '<name>' . htmlspecialchars($name) . '</name>'
            . '<title>' . htmlspecialchars(ucfirst($name)) . '</title>'
            . '<version>1.0.0</version>'
            . '</theme>'
        );
        $zip->addFromString('style.css', 'body{color:#000}');
        $zip->close();
        $contents = file_get_contents($tmp);
        @unlink($tmp);
        return $contents;
    }
}
