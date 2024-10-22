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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

$userdata = new stdClass();
$def = new stdClass();
$cmiobj = new stdClass();
$cmiint = new stdClass();
$cmicommentsuser = new stdClass();
$cmicommentslms = new stdClass();

if (!isset($currentorg)) {
    $currentorg = '';
}

if ($scoes = $DB->get_records('exescorm_scoes', ['exescorm' => $exescorm->id], 'sortorder, id')) {
    // Drop keys so that it is a simple array.
    $scoes = array_values($scoes);
    foreach ($scoes as $sco) {
        $def->{($sco->id)} = new stdClass();
        $userdata->{($sco->id)} = new stdClass();
        $def->{($sco->id)} = get_exescorm_default($userdata->{($sco->id)}, $exescorm, $sco->id, $attempt, $mode);

        // Reconstitute objectives, comments_from_learner and comments_from_lms.
        $cmiobj->{($sco->id)} = exescorm_reconstitute_array_element($exescorm->version, $userdata->{($sco->id)},
                                                                    'cmi.objectives', ['score']);
        $cmiint->{($sco->id)} = exescorm_reconstitute_array_element($exescorm->version, $userdata->{($sco->id)},
                                                                    'cmi.interactions', ['objectives', 'correct_responses']);
        $cmicommentsuser->{($sco->id)} = exescorm_reconstitute_array_element($exescorm->version, $userdata->{($sco->id)},
                                                                    'cmi.comments_from_learner', []);
        $cmicommentslms->{($sco->id)} = exescorm_reconstitute_array_element($exescorm->version, $userdata->{($sco->id)},
                                                                    'cmi.comments_from_lms', []);
    }
}

$exescorm->autocommit = ($exescorm->autocommit === "1") ? true : false;
$PAGE->requires->js_init_call(
                                'M.exescorm_api.init',
                                 [$def, $cmiobj, $cmiint, $cmicommentsuser, $cmicommentslms,
                                exescorm_debugging($exescorm), $exescorm->auto, $exescorm->id, $CFG->wwwroot,
                                sesskey(), $scoid, $attempt, $mode, $id, $currentorg, $exescorm->autocommit]
                            );


// Pull in the debugging utilities.
if (exescorm_debugging($exescorm)) {
    require_once($CFG->dirroot.'/mod/exescorm/datamodels/debug.js.php');
    echo html_writer::script('AppendToLog("Moodle SCORM 1.3 API Loaded, Activity: '.
                                $exescorm->name.', SCO: '.$sco->identifier.'", 0);');
}
