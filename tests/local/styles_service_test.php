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
 * Unit tests for the styles service.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for mod_exescorm\local\styles_service.
 *
 * @covers \mod_exescorm\local\styles_service
 */
class styles_service_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_registry_defaults_to_empty_shape(): void {
        $r = styles_service::get_registry();
        $this->assertSame([], $r['uploaded']);
        $this->assertSame([], $r['disabled_builtins']);
    }

    public function test_set_builtin_enabled_toggles_disabled_list(): void {
        styles_service::set_builtin_enabled('zen', false);
        $this->assertSame(['zen'], styles_service::get_registry()['disabled_builtins']);

        styles_service::set_builtin_enabled('zen', false); // idempotent.
        $this->assertSame(['zen'], styles_service::get_registry()['disabled_builtins']);

        styles_service::set_builtin_enabled('zen', true);
        $this->assertSame([], styles_service::get_registry()['disabled_builtins']);
    }

    public function test_set_uploaded_enabled_returns_false_for_unknown_slug(): void {
        $this->assertFalse(styles_service::set_uploaded_enabled('nope', true));
    }

    public function test_validate_zip_rejects_missing_config(): void {
        $zip = $this->make_zip(['style.css' => '.x{}']);
        $this->expectException(\moodle_exception::class);
        try {
            styles_service::validate_zip($zip);
        } finally {
            @unlink($zip);
        }
    }

    public function test_validate_zip_rejects_traversal_entry(): void {
        $zip = $this->make_zip([
            'config.xml' => $this->sample_config_xml('acme'),
            '../evil.css' => 'pwn',
        ]);
        $this->expectException(\moodle_exception::class);
        try {
            styles_service::validate_zip($zip);
        } finally {
            @unlink($zip);
        }
    }

    public function test_validate_zip_rejects_php_entry(): void {
        $zip = $this->make_zip([
            'config.xml' => $this->sample_config_xml('acme'),
            'evil.php' => '<?php',
        ]);
        $this->expectException(\moodle_exception::class);
        try {
            styles_service::validate_zip($zip);
        } finally {
            @unlink($zip);
        }
    }

    public function test_validate_zip_accepts_valid_package(): void {
        $zip = $this->make_zip([
            'config.xml' => $this->sample_config_xml('acme-2026', 'Acme 2026', '1.0.0'),
            'style.css' => 'body{color:#000}',
        ]);
        $result = styles_service::validate_zip($zip);
        $this->assertSame('acme-2026', $result['config']['name']);
        $this->assertSame('Acme 2026', $result['config']['title']);
        $this->assertSame('', $result['prefix']);
        @unlink($zip);
    }

    public function test_install_extracts_and_registers(): void {
        $zip = $this->make_zip([
            'config.xml' => $this->sample_config_xml('acme', 'Acme', '1.0.0'),
            'style.css' => 'body{color:red}',
        ]);
        $entry = styles_service::install_from_zip($zip, 'acme.zip');
        $this->assertSame('acme', $entry['name']);
        $this->assertTrue($entry['enabled']);
        $this->assertContains('style.css', $entry['css_files']);

        $extracted = styles_service::get_storage_dir() . '/acme/style.css';
        $this->assertFileExists($extracted);

        $registry = styles_service::get_registry();
        $this->assertArrayHasKey('acme', $registry['uploaded']);
        @unlink($zip);
    }

    public function test_install_allocates_unique_slug_on_collision(): void {
        $zip1 = $this->make_zip([
            'config.xml' => $this->sample_config_xml('duo'),
            'style.css' => 'a{}',
        ]);
        $zip2 = $this->make_zip([
            'config.xml' => $this->sample_config_xml('duo'),
            'style.css' => 'b{}',
        ]);
        $a = styles_service::install_from_zip($zip1);
        $b = styles_service::install_from_zip($zip2);
        $this->assertSame('duo', $a['name']);
        $this->assertSame('duo-2', $b['name']);
        @unlink($zip1);
        @unlink($zip2);
    }

    public function test_delete_uploaded_clears_files_and_registry(): void {
        $zip = $this->make_zip([
            'config.xml' => $this->sample_config_xml('bye'),
            'style.css' => 'x{}',
        ]);
        styles_service::install_from_zip($zip);
        $dir = styles_service::get_storage_dir() . '/bye';
        $this->assertDirectoryExists($dir);

        $this->assertTrue(styles_service::delete_uploaded('bye'));
        $this->assertDirectoryDoesNotExist($dir);
        $this->assertArrayNotHasKey('bye', styles_service::get_registry()['uploaded']);
        @unlink($zip);
    }

    public function test_build_theme_registry_override_respects_enabled_flag(): void {
        $zip = $this->make_zip([
            'config.xml' => $this->sample_config_xml('seen'),
            'style.css' => 'a{}',
        ]);
        styles_service::install_from_zip($zip);
        styles_service::set_builtin_enabled('zen', false);

        $override = styles_service::build_theme_registry_override();
        $this->assertSame(['zen'], $override['disabledBuiltins']);
        $this->assertTrue($override['blockImportInstall']); // Default: block.
        $this->assertSame('base', $override['fallbackTheme']);
        $this->assertCount(1, $override['uploaded']);
        $this->assertSame('seen', $override['uploaded'][0]['id']);

        styles_service::set_uploaded_enabled('seen', false);
        $override = styles_service::build_theme_registry_override();
        $this->assertCount(0, $override['uploaded']);
        @unlink($zip);
    }

    public function test_import_blocked_follows_admin_config(): void {
        $this->assertTrue(styles_service::is_import_blocked()); // Default locked down.

        set_config('stylesblockimport', 0, 'exescorm');
        $this->assertFalse(styles_service::is_import_blocked());

        $override = styles_service::build_theme_registry_override();
        $this->assertFalse($override['blockImportInstall']);

        set_config('stylesblockimport', 1, 'exescorm');
        $this->assertTrue(styles_service::is_import_blocked());
    }

    // -----------------------------------------------------------------
    // Helpers.
    // -----------------------------------------------------------------

    /**
     * Create a temp ZIP with the given entries.
     *
     * @param array<string,string> $entries
     * @return string Absolute path to the ZIP.
     */
    private function make_zip(array $entries): string {
        $path = tempnam(sys_get_temp_dir(), 'exescormstyle') . '.zip';
        @unlink($path);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();
        return $path;
    }

    /**
     * Minimal valid config.xml.
     *
     * @param string $name
     * @param string $title
     * @param string $version
     * @return string
     */
    private function sample_config_xml(string $name, string $title = '', string $version = '1.0.0'): string {
        $title = $title === '' ? ucfirst($name) : $title;
        return '<?xml version="1.0"?>'
            . '<theme>'
            . '<name>' . htmlspecialchars($name) . '</name>'
            . '<title>' . htmlspecialchars($title) . '</title>'
            . '<version>' . htmlspecialchars($version) . '</version>'
            . '<author>Test</author>'
            . '<license>CC-BY-SA</license>'
            . '<description>Test theme.</description>'
            . '</theme>';
    }
}
