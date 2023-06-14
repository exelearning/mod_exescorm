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
                $errors['packagefile'] = get_string('badexelarningpackage', 'exescorm');
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
                $errors['packagefile'] = get_string('badexelarningpackage', 'exescorm');
                return $errors;
            }
        }
        return $errors;
    }
}
