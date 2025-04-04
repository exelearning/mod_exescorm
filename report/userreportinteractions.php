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
require_once($CFG->dirroot.'/mod/exescorm/report/reportlib.php');
require_once($CFG->libdir . '/tablelib.php');

$id = required_param('id', PARAM_INT); // Course Module ID.
$userid = required_param('user', PARAM_INT); // User ID.
$attempt = optional_param('attempt', 1, PARAM_INT); // Attempt number.
$download = optional_param('download', '', PARAM_ALPHA);
$mode = optional_param('mode', '', PARAM_ALPHA); // Scorm mode from which reached here.

// Building the url to use for links.+ data details buildup.
$url = new moodle_url('/mod/exescorm/report/userreportinteractions.php', ['id' => $id,
    'user' => $userid,
    'attempt' => $attempt]);

$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$exescorm = $DB->get_record('exescorm', ['id' => $cm->instance], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $userid], implode(',', \core_user\fields::get_picture_fields()), MUST_EXIST);
// Get list of attempts this user has made.
$attemptids = exescorm_get_all_attempts($exescorm->id, $userid);

$PAGE->set_url($url);
// Moodle 4.0+ only.
if ($CFG->version >= 2022041900) {
    $PAGE->set_secondary_active_tab('exescormreport');
}

// END of url setting + data buildup.

// Checking login +logging +getting context.
require_login($course, false, $cm);
$contextmodule = context_module::instance($cm->id);
require_capability('mod/exescorm:viewreport', $contextmodule);

// Check user has group access.
if (!groups_user_groups_visible($course, $userid, $cm)) {
    throw new moodle_exception('nopermissiontoshow');
}

// Trigger a user interactions viewed event.
$event = \mod_exescorm\event\interactions_viewed::create([
    'context' => $contextmodule,
    'relateduserid' => $userid,
    'other' => ['attemptid' => $attempt, 'instanceid' => $exescorm->id],
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('exescorm', $exescorm);
$event->trigger();

$trackdata = $DB->get_records('exescorm_scoes_track', ['userid' => $user->id, 'exescormid' => $exescorm->id,
    'attempt' => $attempt]);
$usertrack = exescorm_format_interactions($trackdata);

$questioncount = get_exescorm_question_count($exescorm->id);

$courseshortname = format_string($course->shortname, true,
    ['context' => context_course::instance($course->id)]);
$exportfilename = $courseshortname . '-' . format_string($exescorm->name, true) . '-'
                . get_string('interactions', 'mod_exescorm');


// Set up the table.
$table = new flexible_table('mod-exescorm-userreport-interactions');
if (!$table->is_downloading($download, $exportfilename)) {

    // Print the page header.
    $strattempt = get_string('attempt', 'mod_exescorm');
    $strreport = get_string('report', 'mod_exescorm');

    $PAGE->set_title("$course->shortname: ".format_string($exescorm->name));
    $PAGE->set_heading($course->fullname);
    $PAGE->navbar->add($strreport, new moodle_url('/mod/exescorm/report.php', ['id' => $cm->id]));

    $PAGE->navbar->add(fullname($user). " - $strattempt $attempt");

    $PAGE->activityheader->set_attrs([
        'hidecompletion' => true,
        'description' => '',
    ]);

    echo $OUTPUT->header();

    // End of Print the page header.
    $currenttab = 'interactions';

    $renderer = $PAGE->get_renderer('mod_exescorm');
    $useractionreport = new \mod_exescorm\output\userreportsactionbar($id, $userid, $attempt, 'interact', $mode);
    echo $renderer->user_report_actionbar($useractionreport);

    // Printing user details.
    $output = $PAGE->get_renderer('mod_exescorm');
    echo $output->view_user_heading($user, $course, $PAGE->url, $attempt, $attemptids);

}
$table->define_baseurl($PAGE->url);
$table->define_columns(['id', 'studentanswer', 'correctanswer', 'result', 'calcweight']);
$table->define_headers([get_string('trackid', 'mod_exescorm'), get_string('response', 'mod_exescorm'),
    get_string('rightanswer', 'mod_exescorm'), get_string('result', 'mod_exescorm'),
    get_string('calculatedweight', 'mod_exescorm')]);
$table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');

$table->show_download_buttons_at([TABLE_P_BOTTOM]);
$table->setup();

for ($i = 0; $i < $questioncount; $i++) {
    $row = [];
    $element = 'cmi.interactions_'.$i.'.id';
    if (isset($usertrack->$element)) {
        $row[] = s($usertrack->$element);

        $element = 'cmi.interactions_'.$i.'.student_response';
        if (isset($usertrack->$element)) {
            $row[] = s($usertrack->$element);
        } else {
            $row[] = '&nbsp;';
        }

        $j = 0;
        $element = 'cmi.interactions_'.$i.'.correct_responses_'.$j.'.pattern';
        $rightans = '';
        if (isset($usertrack->$element)) {
            while (isset($usertrack->$element)) {
                if ($j > 0) {
                    $rightans .= ',';
                }
                $rightans .= s($usertrack->$element);
                $j++;
                $element = 'cmi.interactions_'.$i.'.correct_responses_'.$j.'.pattern';
            }
            $row[] = $rightans;
        } else {
            $row[] = '&nbsp;';
        }
        $element = 'cmi.interactions_'.$i.'.result';
        $weighting = 'cmi.interactions_'.$i.'.weighting';
        if (isset($usertrack->$element)) {
            $row[] = s($usertrack->$element);
            if ($usertrack->$element == 'correct' &&
                isset($usertrack->$weighting)) {
                $row[] = s($usertrack->$weighting);
            } else {
                $row[] = '0';
            }
        } else {
            $row[] = '&nbsp;';
        }
        $table->add_data($row);
    }
}

$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}

