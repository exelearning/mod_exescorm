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
 * Unit tests for the embedded editor installer.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for embedded_editor_installer.
 *
 * Does not test live GitHub API calls or actual downloads to avoid flaky tests.
 *
 * @covers \mod_exescorm\local\embedded_editor_installer
 */
class embedded_editor_installer_test extends \advanced_testcase {

    /**
     * Test get_asset_url constructs the correct direct URL outside Playground.
     */
    public function test_get_asset_url(): void {
        $installer = new embedded_editor_installer();

        $url = $installer->get_asset_url('4.0.0');
        $this->assertEquals(
            'https://github.com/exelearning/exelearning/releases/download/v4.0.0/exelearning-static-v4.0.0.zip',
            $url
        );

        $url = $installer->get_asset_url('4.0.0-beta3');
        $this->assertEquals(
            'https://github.com/exelearning/exelearning/releases/download/v4.0.0-beta3/exelearning-static-v4.0.0-beta3.zip',
            $url
        );
    }

    /**
     * Test validate_zip with valid PK magic bytes.
     */
    public function test_validate_zip_valid(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test');
        $tmpfile = $tmpdir . '/valid.zip';
        file_put_contents($tmpfile, "PK\x03\x04" . str_repeat("\x00", 100));

        $installer = new embedded_editor_installer();

        // Should not throw.
        $installer->validate_zip($tmpfile);
        $this->assertTrue(true);

        @unlink($tmpfile);
    }

    /**
     * Test validate_zip rejects non-ZIP files.
     */
    public function test_validate_zip_invalid(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test');
        $tmpfile = $tmpdir . '/invalid.zip';
        file_put_contents($tmpfile, 'This is not a zip file');

        $installer = new embedded_editor_installer();

        $this->expectException(\moodle_exception::class);
        $installer->validate_zip($tmpfile);

        @unlink($tmpfile);
    }

    /**
     * Test normalize_extraction with flat layout (index.html at root).
     */
    public function test_normalize_extraction_flat(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/flat_extract');
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html>');
        mkdir($tmpdir . '/app', 0777, true);

        $installer = new embedded_editor_installer();
        $result = $installer->normalize_extraction($tmpdir);

        $this->assertEquals($tmpdir, $result);

        remove_dir($tmpdir);
    }

    /**
     * Test normalize_extraction with single nested directory.
     */
    public function test_normalize_extraction_single_nested(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/nested_extract');
        $innerdir = $tmpdir . '/exelearning-static-v4.0.0';
        mkdir($innerdir, 0777, true);
        file_put_contents($innerdir . '/index.html', '<!DOCTYPE html>');
        mkdir($innerdir . '/app', 0777, true);

        $installer = new embedded_editor_installer();
        $result = $installer->normalize_extraction($tmpdir);

        $this->assertEquals($innerdir, $result);

        remove_dir($tmpdir);
    }

    /**
     * Test normalize_extraction with double nested directory.
     */
    public function test_normalize_extraction_double_nested(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/double_nested');
        $innerdir = $tmpdir . '/wrapper/exelearning-static';
        mkdir($innerdir, 0777, true);
        file_put_contents($innerdir . '/index.html', '<!DOCTYPE html>');
        mkdir($innerdir . '/app', 0777, true);

        $installer = new embedded_editor_installer();
        $result = $installer->normalize_extraction($tmpdir);

        $this->assertEquals($innerdir, $result);

        remove_dir($tmpdir);
    }

    /**
     * Test normalize_extraction fails when no index.html found.
     */
    public function test_normalize_extraction_no_index(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/no_index_extract');
        mkdir($tmpdir . '/somedir/app', 0777, true);

        $installer = new embedded_editor_installer();

        $this->expectException(\moodle_exception::class);
        $installer->normalize_extraction($tmpdir);

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_contents with valid directory.
     */
    public function test_validate_editor_contents_valid(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/valid_contents');
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html>');
        mkdir($tmpdir . '/app', 0777, true);

        $installer = new embedded_editor_installer();

        // Should not throw.
        $installer->validate_editor_contents($tmpdir);
        $this->assertTrue(true);

        remove_dir($tmpdir);
    }

    /**
     * Test validate_editor_contents fails with invalid directory.
     */
    public function test_validate_editor_contents_invalid(): void {
        $tmpdir = make_temp_directory('mod_exescorm_test/invalid_contents');
        // Only index.html, no asset dirs.
        file_put_contents($tmpdir . '/index.html', '<!DOCTYPE html>');

        $installer = new embedded_editor_installer();

        $this->expectException(\moodle_exception::class);
        $installer->validate_editor_contents($tmpdir);

        remove_dir($tmpdir);
    }

    /**
     * Test store_metadata writes config values.
     */
    public function test_store_metadata(): void {
        $this->resetAfterTest(true);

        $installer = new embedded_editor_installer();
        $installer->store_metadata('4.0.0');

        $this->assertEquals('4.0.0', get_config('exescorm', 'embedded_editor_version'));
        $this->assertNotEmpty(get_config('exescorm', 'embedded_editor_installed_at'));
    }

    /**
     * Test clear_metadata removes config values.
     */
    public function test_clear_metadata(): void {
        $this->resetAfterTest(true);

        set_config('embedded_editor_version', '4.0.0', 'exescorm');
        set_config('embedded_editor_installed_at', '2025-01-15 10:30:00', 'exescorm');

        $installer = new embedded_editor_installer();
        $installer->clear_metadata();

        $this->assertFalse(get_config('exescorm', 'embedded_editor_version'));
        $this->assertFalse(get_config('exescorm', 'embedded_editor_installed_at'));
    }

    /**
     * Test uninstall removes directory and clears metadata.
     */
    public function test_uninstall(): void {
        $this->resetAfterTest(true);

        // Create a fake moodledata editor.
        $moodledatadir = embedded_editor_source_resolver::get_moodledata_dir();
        @mkdir($moodledatadir, 0777, true);
        file_put_contents($moodledatadir . '/index.html', '<!DOCTYPE html>');
        mkdir($moodledatadir . '/app', 0777, true);
        set_config('embedded_editor_version', '4.0.0', 'exescorm');
        set_config('embedded_editor_installed_at', '2025-01-15 10:30:00', 'exescorm');

        $installer = new embedded_editor_installer();
        $installer->uninstall();

        $this->assertFalse(is_dir($moodledatadir));
        $this->assertFalse(get_config('exescorm', 'embedded_editor_version'));
        $this->assertFalse(get_config('exescorm', 'embedded_editor_installed_at'));
    }

    /**
     * Test safe_install copies files to the target directory.
     */
    public function test_safe_install(): void {
        $this->resetAfterTest(true);

        // Create a source directory.
        $sourcedir = make_temp_directory('mod_exescorm_test/install_source');
        file_put_contents($sourcedir . '/index.html', '<!DOCTYPE html>');
        mkdir($sourcedir . '/app', 0777, true);
        file_put_contents($sourcedir . '/app/main.js', 'console.log("test");');

        // Ensure target does not exist.
        $targetdir = embedded_editor_source_resolver::get_moodledata_dir();
        if (is_dir($targetdir)) {
            remove_dir($targetdir);
        }

        $installer = new embedded_editor_installer();
        $installer->safe_install($sourcedir);

        $this->assertTrue(is_dir($targetdir));
        $this->assertTrue(is_file($targetdir . '/index.html'));
        $this->assertTrue(is_dir($targetdir . '/app'));

        // Clean up.
        remove_dir($targetdir);
        // Source may have been moved, clean up if still there.
        if (is_dir($sourcedir)) {
            remove_dir($sourcedir);
        }
    }

    /**
     * Test safe_install with existing installation (backup/replace).
     */
    public function test_safe_install_replaces_existing(): void {
        $this->resetAfterTest(true);

        $targetdir = embedded_editor_source_resolver::get_moodledata_dir();

        // Create existing installation.
        @mkdir($targetdir, 0777, true);
        file_put_contents($targetdir . '/index.html', 'OLD');
        mkdir($targetdir . '/app', 0777, true);

        // Create new source.
        $sourcedir = make_temp_directory('mod_exescorm_test/replace_source');
        file_put_contents($sourcedir . '/index.html', 'NEW');
        mkdir($sourcedir . '/app', 0777, true);
        mkdir($sourcedir . '/libs', 0777, true);

        $installer = new embedded_editor_installer();
        $installer->safe_install($sourcedir);

        // Verify new content is in place.
        $this->assertEquals('NEW', file_get_contents($targetdir . '/index.html'));
        $this->assertTrue(is_dir($targetdir . '/libs'));

        // Clean up.
        remove_dir($targetdir);
        if (is_dir($sourcedir)) {
            remove_dir($sourcedir);
        }
    }

    /**
     * Test concurrent install lock.
     */
    public function test_concurrent_install_lock(): void {
        $this->resetAfterTest(true);

        // Simulate an active lock.
        set_config('embedded_editor_installing', time(), 'exescorm');

        $installer = new embedded_editor_installer();

        $this->expectException(\moodle_exception::class);
        // This uses a reflection trick to call acquire_lock directly.
        $method = new \ReflectionMethod($installer, 'acquire_lock');
        $method->setAccessible(true);
        $method->invoke($installer);
    }

    /**
     * Test stale lock is ignored.
     */
    public function test_stale_lock_ignored(): void {
        $this->resetAfterTest(true);

        // Simulate a stale lock (older than timeout).
        set_config('embedded_editor_installing', time() - 600, 'exescorm');

        $installer = new embedded_editor_installer();

        // Should not throw -- stale lock is overridden.
        $method = new \ReflectionMethod($installer, 'acquire_lock');
        $method->setAccessible(true);
        $method->invoke($installer);

        // Clean up.
        $releasemethod = new \ReflectionMethod($installer, 'release_lock');
        $releasemethod->setAccessible(true);
        $releasemethod->invoke($installer);

        $this->assertTrue(true);
    }
}
