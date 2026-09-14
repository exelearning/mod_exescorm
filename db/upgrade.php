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
 * Upgrade script for the exescorm module.
 *
 * @package    mod_exescorm
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @global moodle_database $DB
 * @param int $oldversion
 * @return bool
 */
function xmldb_exescorm_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2021052501) {
        $table = new xmldb_table('exescorm');
        $field = new xmldb_field('displayactivityname');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2021052501, 'exescorm');
    }

    // Automatically generated Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.1.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2026021200) {
        $table = new xmldb_table('exescorm');
        $field = new xmldb_field('teachermodevisible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1',
            'displaycoursestructure');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026021200, 'exescorm');
    }

    // teachermodevisible now defaults to 0 (align with eXeLearning #1772: teacher
    // content is hidden by default, opt-in to reveal). When on, the plugin appends
    // the package's own ?exe-teacher=1 URL parameter so its teacher-layer selector
    // is available to every viewer; when off (the new default) the package is served
    // unchanged with teacher content hidden. Lower the default 1 -> 0 and reset
    // existing rows to the new default.
    if ($oldversion < 2026021201) {
        $table = new xmldb_table('exescorm');

        $DB->set_field('exescorm', 'teachermodevisible', 0);

        $field = new xmldb_field('teachermodevisible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0',
            'displaycoursestructure');

        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }

        upgrade_mod_savepoint(true, 2026021201, 'exescorm');
    }

    // The mandatory-files rule no longer requires an eXeLearning content.xml by
    // default: exescorm_validate_package() already enforces SCORM validity (a root
    // imsmanifest.xml or an AICC .cst), so the rule only rejected plain SCORM
    // packages -- including one eXeLearning produces when the author turns the
    // "Editable export" property off (exelearning/exelearning#2415). Such a
    // package plays here and simply is not editable.
    //
    // Only clear the stored value when it is still the old default, byte for byte.
    // A site that customised the list keeps its own policy.
    if ($oldversion < 2026091400) {
        $oldmandatory = '/^content(v\d+)?\.xml$/';
        if (get_config('exescorm', 'mandatoryfileslist') === $oldmandatory) {
            set_config('mandatoryfileslist', '', 'exescorm');
        }

        upgrade_mod_savepoint(true, 2026091400, 'exescorm');
    }

    return true;
}
