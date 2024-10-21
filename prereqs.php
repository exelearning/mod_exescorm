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

// This page is called via AJAX to repopulte the TOC when LMSFinish() is called.

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

$id = optional_param('id', '', PARAM_INT);                  // Course Module ID, or...
$a = optional_param('a', '', PARAM_INT);                    // Exescorm ID.
$scoid = required_param('scoid', PARAM_INT);                // Sco ID.
$attempt = required_param('attempt', PARAM_INT);            // Attempt number.
$mode = optional_param('mode', 'normal', PARAM_ALPHA);      // Navigation mode.
$currentorg = optional_param('currentorg', '', PARAM_RAW);  // Selected organization.

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

// PARAM_RAW is used for $currentorg, validate it against records stored in the table.
if (!empty($currentorg)) {
    if (!$DB->record_exists('exescorm_scoes', ['exescorm' => $exescorm->id, 'identifier' => $currentorg])) {
        $currentorg = '';
    }
}

$PAGE->set_url('/mod/exescorm/prereqs.php', ['scoid' => $scoid, 'attempt' => $attempt, 'id' => $cm->id]);

require_login($course, false, $cm);

$exescorm->version = strtolower(clean_param($exescorm->version, PARAM_SAFEDIR));   // Just to be safe.
if (!file_exists($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'lib.php')) {
    $exescorm->version = 'SCORM_12';
}
require_once($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'lib.php');


if (confirm_sesskey() && (!empty($scoid))) {
    $result = true;
    $request = null;
    if (has_capability('mod/exescorm:savetrack', context_module::instance($cm->id))) {
        $result = exescorm_get_toc($USER, $exescorm, $cm->id, EXESCORM_TOCJSLINK, $currentorg,
                                    $scoid, $mode, $attempt, true, false);
        echo $result->toc;
    }
}
