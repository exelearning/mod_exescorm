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

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

$id = optional_param('id', '', PARAM_INT);       // Course Module ID, or...
$a = optional_param('a', '', PARAM_INT);         // Exescorm ID.
$scoid = required_param('scoid', PARAM_INT);  // Sco ID.
$attempt = required_param('attempt', PARAM_INT);  // Attempt number.

if (!empty($id)) {
    if (! $cm = get_coursemodule_from_id('exescorm', $id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", ["id" => $cm->course])) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $exescorm = $DB->get_record("exescorm", ["id" => $cm->instance])) {
        throw new \moodle_exception('invalidcoursemodule');
    }
} else if (!empty($a)) {
    if (! $exescorm = $DB->get_record("exescorm", ["id" => $a])) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", ["id" => $exescorm->course])) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("exescorm", $exescorm->id, $course->id)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
} else {
    throw new \moodle_exception('missingparameter');
}

$PAGE->set_url('/mod/exescorm/datamodel.php', ['scoid' => $scoid, 'attempt' => $attempt, 'id' => $cm->id]);

require_login($course, false, $cm);

if (confirm_sesskey() && (!empty($scoid))) {
    $result = true;
    $request = null;
    if (has_capability('mod/exescorm:savetrack', context_module::instance($cm->id))) {
        // Preload all current tracking data.
        $trackdata = $DB->get_records('exescorm_scoes_track',
                                    ['userid' => $USER->id, 'exescormid' => $exescorm->id, 'scoid' => $scoid,
                                    'attempt' => $attempt], '', 'element, id, value, timemodified'
                                );
        foreach (data_submitted() as $element => $value) {
            $element = str_replace('__', '.', $element);
            if (substr($element, 0, 3) == 'cmi') {
                $netelement = preg_replace('/\.N(\d+)\./', "\.\$1\.", $element);
                $result = exescorm_insert_track($USER->id, $exescorm->id, $scoid, $attempt, $element, $value,
                            $exescorm->forcecompleted, $trackdata) && $result;
            }
            if (substr($element, 0, 15) == 'adl.nav.request') {
                // SCORM 2004 Sequencing Request.
                require_once($CFG->dirroot.'/mod/exescorm/datamodels/scorm_13lib.php');

                $search = ['@continue@', '@previous@', '@\{target=(\S+)\}choice@', '@exit@',
                                    '@exitAll@', '@abandon@', '@abandonAll@'];
                $replace = ['continue_', 'previous_', '\1', 'exit_', 'exitall_', 'abandon_', 'abandonall'];
                $action = preg_replace($search, $replace, $value);

                if ($action != $value) {
                    // Evaluating navigation request.
                    $valid = exescorm_seq_overall ($scoid, $USER->id, $action, $attempt);
                    $valid = 'true';

                    // Set valid request.
                    $search = ['@continue@', '@previous@', '@\{target=(\S+)\}choice@'];
                    $replace = ['true', 'true', 'true'];
                    $matched = preg_replace($search, $replace, $value);
                    if ($matched == 'true') {
                        $request = 'adl.nav.request_valid["'.$action.'"] = "'.$valid.'";';
                    }
                }
            }
        }
    }
    if ($result) {
        echo "true\n0";
    } else {
        echo "false\n101";
    }
    if ($request != null) {
        echo "\n".$request;
    }
}
