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
 * Reuses Moodle's native `admin_setting_configstoredfile` only for its
 * filemanager rendering. Persistence is handled here because our pipeline
 * is fire-and-forget: dropped ZIPs are extracted into moodledata by
 * {@see styles_service::install_from_zip()} and then deleted from the
 * filearea, so the parent's "remember the last filepath in config" flow
 * would either be wrong (config points at a file we already removed) or
 * trip the parent's `errorsetting` validation when the page is resaved
 * without changes.
 */
class admin_setting_stylesupload extends \admin_setting_configstoredfile {

    /**
     * @var string Component that owns the style upload filearea.
     *
     * `admin_setting_configstoredfile` derives the component from the
     * first segment of the setting name ('exescorm/styles_drops' →
     * 'exescorm') and uses it when moving the draft into the plugin
     * file area, so we must read from the same component here or the
     * saved ZIP stays invisible to the registry walker.
     */
    public const COMPONENT = 'exescorm';

    /** @var string Filearea used to receive drops before extraction. */
    public const FILEAREA = 'styles_drops';

    /**
     * Stage any uploaded drafts into the plugin filearea and extract them.
     *
     * We bypass `admin_setting_configstoredfile::write_setting()` because
     * that path persists the submitted file's path in plugin config and
     * trips an `errorsetting` validation when the form is saved a second
     * time without changes — by then the cached config still points at a
     * file `consume_pending_uploads()` already extracted and removed.
     * Since we never read the config value back, just move the drafts and
     * return success regardless of whether anything was attached.
     *
     * @param string $data The draft item id.
     * @return string Always empty (success); install errors surface as notifications.
     */
    public function write_setting($data) {
        if (is_numeric($data) && (int) $data > 0) {
            $options = $this->get_options();
            $component = is_null($this->plugin) ? 'core' : $this->plugin;
            file_save_draft_area_files(
                $data,
                $options['context']->id,
                $component,
                $this->filearea,
                $this->itemid,
                $options
            );
        }
        $summary = $this->consume_pending_uploads();
        foreach ($summary['installed'] as $title) {
            \core\notification::success(
                get_string('stylesupload_success', 'mod_exescorm', $title)
            );
        }
        foreach ($summary['errors'] as $error) {
            \core\notification::error($error);
        }
        return '';
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
