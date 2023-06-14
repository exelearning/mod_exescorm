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
 * This page displays the user data from a single attempt
 *
 * @package mod_exescorm
 * @copyright 1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');
require_once($CFG->libdir.'/tablelib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$userid = required_param('user', PARAM_INT); // User ID.
$scoid = required_param('scoid', PARAM_INT); // SCO ID.
$mode = required_param('mode', PARAM_ALPHA); // Scorm mode.
$attempt = optional_param('attempt', 1, PARAM_INT); // Attempt number.
$download = optional_param('download', '', PARAM_ALPHA);

// Building the url to use for links.+ data details buildup.
$url = new moodle_url('/mod/exescorm/report/userreporttracks.php', array('id' => $id,
    'user' => $userid,
    'attempt' => $attempt,
    'scoid' => $scoid,
    'mode' => $mode));
$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$exescorm = $DB->get_record('exescorm', array('id' => $cm->instance), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $userid), implode(',', \core_user\fields::get_picture_fields()), MUST_EXIST);
$selsco = $DB->get_record('exescorm_scoes', array('id' => $scoid), '*', MUST_EXIST);

$PAGE->set_url($url);
// END of url setting + data buildup.

// Checking login +logging +getting context.
require_login($course, false, $cm);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/exescorm:viewreport', $contextmodule);

// Check user has group access.
if (!groups_user_groups_visible($course, $userid, $cm)) {
    throw new moodle_exception('nopermissiontoshow');
}

// Trigger a tracks viewed event.
$event = \mod_exescorm\event\tracks_viewed::create(array(
    'context' => $contextmodule,
    'relateduserid' => $userid,
    'other' => array('attemptid' => $attempt, 'instanceid' => $exescorm->id, 'scoid' => $scoid, 'mode' => $mode)
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('exescorm', $exescorm);
$event->trigger();

// Print the page header.
$strreport = get_string('report', 'exescorm');
$strattempt = get_string('attempt', 'exescorm');

$PAGE->set_title("$course->shortname: ".format_string($exescorm->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strreport, new moodle_url('/mod/exescorm/report.php', array('id' => $cm->id)));
$PAGE->navbar->add("$strattempt $attempt - ".fullname($user),
    new moodle_url('/mod/exescorm/report/userreport.php', array('id' => $id, 'user' => $userid, 'attempt' => $attempt)));
$PAGE->navbar->add($selsco->title . ' - '. get_string('details', 'exescorm'));
// Moodle 4.0+ only.
if ($CFG->version >= 2022041900) {
    $PAGE->set_secondary_active_tab('exescormreport');
}

if ($trackdata = exescorm_get_tracks($selsco->id, $userid, $attempt)) {
    if ($trackdata->status == '') {
        $trackdata->status = 'notattempted';
    }
} else {
    $trackdata = new stdClass();
    $trackdata->status = 'notattempted';
    $trackdata->total_time = '';
}

$courseshortname = format_string($course->shortname, true,
    array('context' => context_course::instance($course->id)));
$exportfilename = $courseshortname . '-' . format_string($exescorm->name, true) . '-' . get_string('details', 'exescorm');

$table = new flexible_table('mod_exescorm-userreporttracks');

if (!$table->is_downloading($download, $exportfilename)) {
    $PAGE->activityheader->set_attrs([
        'hidecompletion' => true,
        'description' => ''
    ]);
    echo $OUTPUT->header();
    $currenttab = '';
    $renderer = $PAGE->get_renderer('mod_exescorm');
    $useractionreport = new \mod_exescorm\output\userreportsactionbar($id, $userid, $attempt, 'attempt', $mode, $scoid);
    echo $renderer->user_report_actionbar($useractionreport);
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->heading("$strattempt $attempt - ". fullname($user).': '.
    format_string($selsco->title). ' - '. get_string('details', 'exescorm'), 3);
}
$table->define_baseurl($PAGE->url);
$table->define_columns(array('element', 'value'));
$table->define_headers(array(get_string('element', 'exescorm'), get_string('value', 'exescorm')));
$table->set_attribute('class', 'generaltable generalbox boxaligncenter exescormtrackreport');
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->setup();

foreach ($trackdata as $element => $value) {
    if (substr($element, 0, 3) == 'cmi') {
        $existelements = true;
        $row = array();
        $string = false;
        if (stristr($element, '.id') !== false) {
            $string = "trackid";
        } else if (stristr($element, '.result') !== false) {
            $string = "trackresult";
        } else if (stristr($element, '.student_response') !== false || // SCORM 1.2 value.
            stristr($element, '.learner_response') !== false) { // SCORM 2004 value.
            $string = "trackresponse";
        } else if (stristr($element, '.type') !== false) {
            $string = "tracktype";
        } else if (stristr($element, '.weighting') !== false) {
            $string = "trackweight";
        } else if (stristr($element, '.time') !== false) {
            $string = "tracktime";
        } else if (stristr($element, '.correct_responses._count') !== false) {
            $string = "trackcorrectcount";
        } else if (stristr($element, '.score.min') !== false) {
            $string = "trackscoremin";
        } else if (stristr($element, '.score.max') !== false) {
            $string = "trackscoremax";
        } else if (stristr($element, '.score.raw') !== false) {
            $string = "trackscoreraw";
        } else if (stristr($element, '.latency') !== false) {
            $string = "tracklatency";
        } else if (stristr($element, '.pattern') !== false) {
            $string = "trackpattern";
        } else if (stristr($element, '.suspend_data') !== false) {
            $string = "tracksuspenddata";
        }

        if (empty($string) || $table->is_downloading()) {
            $row[] = s($element);
        } else {
            $row[] = s($element) . $OUTPUT->help_icon($string, 'exescorm');
        }
        if (strpos($element, '_time') === false) {
            $row[] = s($value);
        } else {
            $row[] = s(exescorm_format_duration($value));
        }
        $table->add_data($row);
    }
}
$table->finish_output();
if (!$table->is_downloading()) {
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
}

