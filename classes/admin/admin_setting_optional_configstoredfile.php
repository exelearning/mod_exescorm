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
 * Optional variant of Moodle's stored-file admin setting.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\admin;

/**
 * Optional stored-file admin setting that is safe to leave empty.
 *
 * Moodle flags an admin setting as "new" whenever its get_setting() returns
 * null (see admin_output_new_settings_by_page() in lib/adminlib.php), which
 * forces the administrator through the "New settings" upgrade page after a
 * plugin upgrade. The stock admin_setting_configstoredfile keeps returning
 * null until a file is stored, and its write_setting() rejects the save with
 * an "errorsetting" message when the filemanager posts no draft item id. For
 * an optional file (such as the package template) that combination traps the
 * administrator on the "New settings" page unless they upload a file, even
 * though no file is required (see issue #2000).
 *
 * This subclass keeps the normal stored-file behaviour for real uploads,
 * replacements and intentional clears, but turns a "nothing submitted /
 * nothing changed" save into a safe no-op that never deletes an already
 * stored file and never leaves the configured value as null.
 */
class admin_setting_optional_configstoredfile extends \admin_setting_configstoredfile {
    /** @var bool Whether the last write_setting() was a no-op over a stored file. */
    private $nofilechange = false;

    /**
     * Return the stored value, substituting '' for null.
     *
     * Returning a non-null value stops admin_output_new_settings_by_page()
     * from treating an empty optional file as a new/unconfigured setting on
     * every upgrade. When a file is stored, the real config value (the stored
     * file path) is returned unchanged so replace/clear detection and the
     * filemanager rendering keep working as in core.
     *
     * @return string The stored file path, or '' when nothing is stored.
     */
    public function get_setting() {
        $value = parent::get_setting();
        return $value === null ? '' : $value;
    }

    /**
     * Persist an uploaded file, or accept an empty submission as a no-op.
     *
     * @param mixed $data Draft item id submitted by the filemanager.
     * @return string Empty string on success, or an error message on failure.
     */
    public function write_setting($data) {
        // No usable draft item id: the filemanager was left untouched or its
        // draft area was never initialised (issue #2000), or an empty value
        // ('', null, '0', ...) was posted. Treat it as a successful no-op:
        // never call file_save_draft_area_files() (it would delete an already
        // stored file when handed an empty draft) and never return an error.
        if (!is_number($data) || (int) $data <= 0) {
            if ($this->get_setting() === '') {
                // Nothing is stored: record an explicit empty value so the
                // optional setting is not reported as new on the next upgrade.
                $this->nofilechange = false;
                return ($this->config_write($this->name, '') ? '' : get_string('errorsetting', 'admin'));
            }
            // A file is already stored: keep it untouched and report no change.
            $this->nofilechange = true;
            return '';
        }

        // A positive draft item id was submitted: defer to Moodle core, which
        // saves uploaded files, honours an intentionally cleared filemanager
        // and protects an existing file when the draft area is missing.
        $this->nofilechange = false;
        return parent::write_setting($data);
    }

    /**
     * Report whether the stored file changed.
     *
     * The parent compares stored file hashes to detect a change. Our no-op
     * branch deliberately skips that bookkeeping, so report "unchanged" here
     * to avoid a spurious "changes saved" notification when an existing file
     * was left untouched.
     *
     * @param mixed $original Value returned by get_setting() before the write.
     * @return bool True when the stored file changed.
     */
    public function post_write_settings($original) {
        if ($this->nofilechange) {
            return false;
        }
        return parent::post_write_settings($original);
    }
}
