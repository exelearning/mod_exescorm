<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * EXescorm url manager class.
 *
 * @package     mod_exescorm
 * @category    exescorm
 * @copyright   2023 3&Punt
 * @author      Juan Carrera <juan@treipunt.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm;


class exescorm_package {

    /**
     * Check if a stored file is a valid package file (ZIP or ELPX).
     *
     * ELPX files are ZIP archives with a different extension. Browsers may
     * report them as application/octet-stream, so we also check the extension.
     *
     * @param \stored_file $file
     * @return bool
     */
    public static function is_valid_package_file(\stored_file $file) {
        $mimetype = $file->get_mimetype();
        $filename = $file->get_filename();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Accept ZIP mimetype or ELPX extension (which is a ZIP archive).
        $validmimes = ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'];
        $validexts = ['zip', 'elpx'];

        if (in_array($ext, $validexts) && in_array($mimetype, $validmimes)) {
            return true;
        }
        if ($mimetype === 'application/zip') {
            return true;
        }
        return false;
    }

    /**
     * Whether a package carries the eXeLearning source the embedded editor needs.
     *
     * The editor re-opens the stored package on the next edit, so a package saved
     * through editor/save.php must contain a root `content.xml` (or a legacy
     * `contentvN.xml`). An uploaded package does not: it is played, never edited,
     * and the "Edit in eXeLearning" button is not offered for it.
     *
     * @param array $filelist Entries as returned by \stored_file::list_files().
     * @return bool True when a root eXeLearning source file is present.
     */
    public static function has_editable_source($filelist) {
        if (!is_array($filelist)) {
            return false;
        }
        foreach ($filelist as $info) {
            if (!empty($info->is_directory)) {
                continue;
            }
            if (preg_match('/^content(v\d+)?\.xml$/', $info->pathname)) {
                return true;
            }
        }
        return false;
    }

    public static function validate_file_list($filelist) {
        $errors = [];

        $exescormconfig = get_config('exescorm');
        $forbiddenfileslist = $exescormconfig->forbiddenfileslist ?? '';
        $forbiddenfilesrearray = explode("\n", $forbiddenfileslist);
        $forbiddenfilesrearray = array_filter(array_map('trim', $forbiddenfilesrearray));
        $mandatoryfileslist = $exescormconfig->mandatoryfileslist ?? '';
        $mandatoryfilesrearray = explode("\n", $mandatoryfileslist);
        $mandatoryfilesrearray = array_filter(array_map('trim', $mandatoryfilesrearray));
        // Get path names to check against.
        $filepaths = array_column($filelist, 'pathname', 'pathname');

        // Check for mandatory files. Return as soon as any mandatory RE is mising.
        foreach ($mandatoryfilesrearray as $mfre) {
            $found = preg_grep($mfre, $filepaths);
            if (empty($found)) {
                $errors['packagefile'] = get_string('badexelearningpackage', 'mod_exescorm');
                return $errors;
            }
            // We unset mandatory files, so can be an exception for forbidden ones.
            foreach ($found as $key => $unused) {
                unset($filepaths[$key]);
            }
        }
        // Check for forbidden paths. Return as soon as any forbidden RE is found.
        foreach ($forbiddenfilesrearray as $ffre) {
            if (preg_grep($ffre, $filepaths)) {
                $errors['packagefile'] = get_string('badexelearningpackage', 'mod_exescorm');
                return $errors;
            }
        }
        return $errors;
    }
}
