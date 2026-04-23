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
 * Admin setting that accepts style ZIP uploads and auto-installs them.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\admin;

defined('MOODLE_INTERNAL') || die();

use mod_exescorm\local\styles_service;

/**
 * Inline filemanager setting for the Styles admin section.
 *
 * Reuses Moodle's native `admin_setting_configstoredfile` — the exact
 * same pattern as the 'Default template' filemanager already present in
 * the plugin settings. After Moodle moves the draft files into the
 * plugin's file area on save, we walk them through
 * {@see styles_service::install_from_zip()}: successful installs are
 * removed from the file area (we only care about the extracted output);
 * failed ones are kept so the admin can spot them and a notification is
 * queued with the specific error.
 */
class admin_setting_stylesupload extends \admin_setting_configstoredfile {

    /** @var string Component that owns the style upload filearea. */
    public const COMPONENT = 'mod_exescorm';

    /** @var string Filearea used to receive drops before extraction. */
    public const FILEAREA = 'styles_drops';

    /**
     * Persist the uploaded files and extract any fresh ZIPs.
     *
     * @param string $data The draft item id.
     * @return string Empty on success or error message string.
     */
    public function write_setting($data) {
        $return = parent::write_setting($data);
        $summary = $this->consume_pending_uploads();
        foreach ($summary['installed'] as $title) {
            \core\notification::success(
                get_string('stylesupload_success', 'mod_exescorm', $title)
            );
        }
        foreach ($summary['errors'] as $error) {
            \core\notification::error($error);
        }
        return $return;
    }

    /**
     * Walk the filearea, install each ZIP, and drop files we successfully
     * consumed so the next render starts clean.
     *
     * @return array{installed: string[], errors: string[]}
     */
    protected function consume_pending_uploads(): array {
        $summary = ['installed' => [], 'errors' => []];
        $fs = get_file_storage();
        $context = \context_system::instance();
        $files = $fs->get_area_files(
            $context->id, self::COMPONENT, self::FILEAREA,
            0, 'sortorder, id', false
        );
        if (empty($files)) {
            return $summary;
        }
        $tmpdir = make_request_directory();
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $tmppath = $tmpdir . '/' . clean_param($filename, PARAM_FILE);
            try {
                $file->copy_content_to($tmppath);
                $entry = styles_service::install_from_zip($tmppath, $filename);
                $summary['installed'][] = $entry['title'] ?? $entry['name'];
                $file->delete();
            } catch (\Throwable $e) {
                $summary['errors'][] = $filename . ': ' . $e->getMessage();
            } finally {
                if (is_file($tmppath)) {
                    @unlink($tmppath);
                }
            }
        }
        return $summary;
    }
}
