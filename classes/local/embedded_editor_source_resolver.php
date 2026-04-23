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
 * Embedded editor source resolver for mod_exescorm.
 *
 * Single source of truth for determining which embedded editor source is active.
 * Implements a precedence policy: moodledata → bundled → none.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\local;

/**
 * Resolves which embedded editor source should be used at runtime.
 *
 * Sources are checked in order:
 * 1. Admin-installed editor in moodledata (highest priority).
 * 2. Bundled editor inside the plugin dist/static/ directory.
 * If neither is available the embedded editor is not usable.
 */
class embedded_editor_source_resolver {

    /** @var string Active source is the admin-installed copy in moodledata. */
    const SOURCE_MOODLEDATA = 'moodledata';

    /** @var string Active source is the bundled copy inside the plugin. */
    const SOURCE_BUNDLED = 'bundled';

    /** @var string No usable source found. */
    const SOURCE_NONE = 'none';

    /** @var string Subdirectory under dataroot for admin-installed editor. */
    const MOODLEDATA_SUBDIR = 'mod_exescorm/embedded_editor';

    /**
     * Directories expected in a valid static editor bundle.
     * At least one must exist alongside index.html.
     *
     * @var string[]
     */
    const EXPECTED_ASSET_DIRS = ['app', 'libs', 'files'];

    /**
     * Get the moodledata directory for the admin-installed editor.
     *
     * @return string Absolute path.
     */
    public static function get_moodledata_dir(): string {
        global $CFG;
        return $CFG->dataroot . '/' . self::MOODLEDATA_SUBDIR;
    }

    /**
     * Get the bundled editor directory inside the plugin.
     *
     * @return string Absolute path.
     */
    public static function get_bundled_dir(): string {
        global $CFG;
        return $CFG->dirroot . '/mod/exescorm/dist/static';
    }

    /**
     * Validate that a directory contains a usable static editor installation.
     *
     * Checks that index.html exists and is readable, and that at least one
     * of the expected asset directories (app, libs, files) is present.
     *
     * @param string $dir Absolute path to the editor directory.
     * @return bool True if the directory passes integrity checks.
     */
    public static function validate_editor_dir(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }

        $indexpath = rtrim($dir, '/') . '/index.html';
        if (!is_file($indexpath) || !is_readable($indexpath)) {
            return false;
        }

        // At least one expected asset directory must exist.
        $dir = rtrim($dir, '/');
        foreach (self::EXPECTED_ASSET_DIRS as $assetdir) {
            if (is_dir($dir . '/' . $assetdir)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the installed version of the moodledata editor, if known.
     *
     * @return string|null Version string or null if not recorded.
     */
    public static function get_moodledata_version(): ?string {
        $version = get_config('exescorm', 'embedded_editor_version');
        return ($version !== false && $version !== '') ? $version : null;
    }

    /**
     * Get the installation timestamp of the moodledata editor, if known.
     *
     * @return string|null Datetime string or null if not recorded.
     */
    public static function get_moodledata_installed_at(): ?string {
        $installedat = get_config('exescorm', 'embedded_editor_installed_at');
        return ($installedat !== false && $installedat !== '') ? $installedat : null;
    }

    /**
     * Determine which source is active according to the precedence policy.
     *
     * Precedence: moodledata → bundled → none.
     *
     * @return string One of the SOURCE_* constants.
     */
    public static function get_active_source(): string {
        if (self::validate_editor_dir(self::get_moodledata_dir())) {
            return self::SOURCE_MOODLEDATA;
        }

        if (self::validate_editor_dir(self::get_bundled_dir())) {
            return self::SOURCE_BUNDLED;
        }

        return self::SOURCE_NONE;
    }

    /**
     * Get the filesystem path for the active local editor source.
     *
     * @return string|null Absolute path to the active editor directory, or null if no source is available.
     */
    public static function get_active_dir(): ?string {
        $source = self::get_active_source();

        switch ($source) {
            case self::SOURCE_MOODLEDATA:
                return self::get_moodledata_dir();
            case self::SOURCE_BUNDLED:
                return self::get_bundled_dir();
            default:
                return null;
        }
    }

    /**
     * Check whether any local editor source (moodledata or bundled) is available.
     *
     * @return bool True if at least one local source passes validation.
     */
    public static function has_local_source(): bool {
        return self::get_active_dir() !== null;
    }

    /**
     * Get the source used to read the editor index HTML.
     *
     * Returns a filesystem path when a local source is available, or null otherwise.
     *
     * @return string|null Path to index.html, or null if no source is available.
     */
    public static function get_index_source(): ?string {
        $activedir = self::get_active_dir();
        if ($activedir !== null) {
            return $activedir . '/index.html';
        }

        return null;
    }

    /**
     * Build a comprehensive status object for the admin UI.
     *
     * @return \stdClass Status information with all relevant fields.
     */
    public static function get_status(): \stdClass {
        $status = new \stdClass();

        $status->active_source = self::get_active_source();
        $status->active_dir = self::get_active_dir();

        // Moodledata source.
        $status->moodledata_dir = self::get_moodledata_dir();
        $status->moodledata_available = self::validate_editor_dir($status->moodledata_dir);
        $status->moodledata_version = self::get_moodledata_version();
        $status->moodledata_installed_at = self::get_moodledata_installed_at();

        // Bundled source.
        $status->bundled_dir = self::get_bundled_dir();
        $status->bundled_available = self::validate_editor_dir($status->bundled_dir);

        return $status;
    }
}
