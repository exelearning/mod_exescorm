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
 * Unit tests for the embedded editor source resolver.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for embedded_editor_source_resolver.
 *
 * @covers \mod_exescorm\local\embedded_editor_source_resolver
 */
class embedded_editor_source_resolver_test extends \advanced_testcase {

    /**
     * Test that get_moodledata_dir returns a path under dataroot.
     */
    public function test_get_moodledata_dir(): void {
        global $CFG;
        $dir = embedded_editor_source_resolver::get_moodledata_dir();
        $this->assertStringStartsWith($CFG->dataroot, $dir);
        $this->assertStringContainsString('mod_exescorm', $dir);
    }

    /**
     * Test that get_bundled_dir returns a path under dirroot.
     */
    public function test_get_bundled_dir(): void {
        global $CFG;
        $dir = embedded_editor_source_resolver::get_bundled_dir();
        $this->assertStringStartsWith($CFG->dirroot, $dir);
        $this->assertStringContainsString('dist/static', $dir);
    }

    /**
     * Test validate_editor_dir with a valid directory.
     */
    public function test_validate_editor_dir_with_valid_dir(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/valid_editor');
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html><html></html>');
        mkdir($tmpdir . '/app', 0777, true);

        $this->assertTrue(embedded_editor_source_resolver::validate_editor_dir($tmpdir));

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_dir with multiple valid asset dirs.
     */
    public function test_validate_editor_dir_with_libs_dir(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/libs_editor');
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html><html></html>');
        mkdir($tmpdir . '/libs', 0777, true);

        $this->assertTrue(embedded_editor_source_resolver::validate_editor_dir($tmpdir));

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_dir with files dir.
     */
    public function test_validate_editor_dir_with_files_dir(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/files_editor');
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html><html></html>');
        mkdir($tmpdir . '/files', 0777, true);

        $this->assertTrue(embedded_editor_source_resolver::validate_editor_dir($tmpdir));

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_dir fails when index.html is missing.
     */
    public function test_validate_editor_dir_missing_index(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/no_index');
        mkdir($tmpdir . '/app', 0777, true);

        $this->assertFalse(embedded_editor_source_resolver::validate_editor_dir($tmpdir));

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_dir fails when no asset directories exist.
     */
    public function test_validate_editor_dir_missing_asset_dirs(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/no_assets');
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html><html></html>');

        $this->assertFalse(embedded_editor_source_resolver::validate_editor_dir($tmpdir));

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_dir fails for nonexistent directory.
     */
    public function test_validate_editor_dir_nonexistent(): void {
        $this->assertFalse(embedded_editor_source_resolver::validate_editor_dir('/nonexistent/path'));
    }

    /**
     * Test active source precedence: moodledata wins over bundled.
     */
    public function test_get_active_source_moodledata_first(): void {
        $this->resetAfterTest(true);

        // Create a valid moodledata editor.
        $moodledatadir = embedded_editor_source_resolver::get_moodledata_dir();
        @mkdir($moodledatadir, 0777, true);
        file_put_contents($moodledatadir . '/index.html', '<!DOCTYPE html>');
        mkdir($moodledatadir . '/app', 0777, true);

        $source = embedded_editor_source_resolver::get_active_source();
        $this->assertEquals(embedded_editor_source_resolver::SOURCE_MOODLEDATA, $source);

        remove_dir($moodledatadir);
    }

    /**
     * Test active source falls back to remote when no local sources exist.
     */
    public function test_get_active_source_remote_fallback(): void {
        $this->resetAfterTest(true);

        // Ensure moodledata dir does not exist.
        $moodledatadir = embedded_editor_source_resolver::get_moodledata_dir();
        if (is_dir($moodledatadir)) {
            remove_dir($moodledatadir);
        }

        // The bundled dir likely doesn't exist in test environment either.
        $source = embedded_editor_source_resolver::get_active_source();

        // Should be either bundled (if dist/static exists) or none.
        $this->assertContains($source, [
            embedded_editor_source_resolver::SOURCE_BUNDLED,
            embedded_editor_source_resolver::SOURCE_NONE,
        ]);
    }

    /**
     * Test has_local_source returns true when moodledata editor exists.
     */
    public function test_has_local_source_with_moodledata(): void {
        $this->resetAfterTest(true);

        $moodledatadir = embedded_editor_source_resolver::get_moodledata_dir();
        @mkdir($moodledatadir, 0777, true);
        file_put_contents($moodledatadir . '/index.html', '<!DOCTYPE html>');
        mkdir($moodledatadir . '/app', 0777, true);

        $this->assertTrue(embedded_editor_source_resolver::has_local_source());

        remove_dir($moodledatadir);
    }

    /**
     * Test get_status returns all expected fields.
     */
    public function test_get_status_returns_complete_object(): void {
        $this->resetAfterTest(true);

        $status = embedded_editor_source_resolver::get_status();

        $this->assertObjectHasProperty('active_source', $status);
        $this->assertObjectHasProperty('active_dir', $status);
        $this->assertObjectHasProperty('moodledata_dir', $status);
        $this->assertObjectHasProperty('moodledata_available', $status);
        $this->assertObjectHasProperty('moodledata_version', $status);
        $this->assertObjectHasProperty('moodledata_installed_at', $status);
        $this->assertObjectHasProperty('bundled_dir', $status);
        $this->assertObjectHasProperty('bundled_available', $status);

        $this->assertIsBool($status->moodledata_available);
        $this->assertIsBool($status->bundled_available);
    }

    /**
     * Test get_moodledata_version returns config value.
     */
    public function test_get_moodledata_version(): void {
        $this->resetAfterTest(true);

        $this->assertNull(embedded_editor_source_resolver::get_moodledata_version());

        set_config('embedded_editor_version', '4.0.0', 'exescorm');
        $this->assertEquals('4.0.0', embedded_editor_source_resolver::get_moodledata_version());
    }

    /**
     * Test get_moodledata_installed_at returns config value.
     */
    public function test_get_moodledata_installed_at(): void {
        $this->resetAfterTest(true);

        $this->assertNull(embedded_editor_source_resolver::get_moodledata_installed_at());

        set_config('embedded_editor_installed_at', '2025-01-15 10:30:00', 'exescorm');
        $this->assertEquals('2025-01-15 10:30:00', embedded_editor_source_resolver::get_moodledata_installed_at());
    }

    /**
     * Test get_index_source returns file path when local source exists.
     */
    public function test_get_index_source_local(): void {
        $this->resetAfterTest(true);

        $moodledatadir = embedded_editor_source_resolver::get_moodledata_dir();
        @mkdir($moodledatadir, 0777, true);
        file_put_contents($moodledatadir . '/index.html', '<!DOCTYPE html>');
        mkdir($moodledatadir . '/app', 0777, true);

        $source = embedded_editor_source_resolver::get_index_source();
        $this->assertStringEndsWith('/index.html', $source);
        $this->assertStringStartsNotWith('http', $source);

        remove_dir($moodledatadir);
    }
}
