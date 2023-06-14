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

namespace mod_exescorm\backup;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Restore date tests.
 *
 * @package    mod_exescorm
 * @copyright  2017 onwards Ankit Agarwal <ankit.agrr@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_date_test extends \restore_date_testcase {

    public function test_restore_dates() {
        global $DB, $USER;

        $time = 10000;

        list($course, $exescorm) = $this->create_course_and_module('exescorm', ['timeopen' => $time, 'timeclose' => $time]);
        $scoes = exescorm_get_scoes($exescorm->id);
        $sco = array_shift($scoes);
        exescorm_insert_track($USER->id, $exescorm->id, $sco->id, 4, 'cmi.core.score.raw', 10);

        // We do not want second differences to fail our test because of execution delays.
        $DB->set_field('exescorm_scoes_track', 'timemodified', $time);

        // Do backup and restore.
        $newcourseid = $this->backup_and_restore($course);
        $newexescorm = $DB->get_record('exescorm', ['course' => $newcourseid]);

        $this->assertFieldsNotRolledForward($exescorm, $newexescorm, ['timemodified']);
        $props = ['timeopen', 'timeclose'];
        $this->assertFieldsRolledForward($exescorm, $newexescorm, $props);

        $tracks = $DB->get_records('exescorm_scoes_track', ['exescormid' => $newexescorm->id]);
        foreach ($tracks as $track) {
            $this->assertEquals($time, $track->timemodified);
        }
    }
}
