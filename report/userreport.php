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

$id = required_param('id', PARAM_INT); // Course Module ID.
$userid = required_param('user', PARAM_INT); // User ID.
$attempt = optional_param('attempt', 1, PARAM_INT); // Attempt number.
$mode = optional_param('mode', '', PARAM_ALPHA); // Scorm mode from which reached here.

// Building the url to use for links.+ data details buildup.
$url = new moodle_url('/mod/exescorm/report/userreport.php', ['id' => $id,
    'user' => $userid,
    'attempt' => $attempt]);
$tracksurl = new moodle_url('/mod/exescorm/report/userreporttracks.php', ['id' => $id,
    'user' => $userid,
    'attempt' => $attempt,
     'mode' => $mode]);
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

// Trigger a user report viewed event.
$event = \mod_exescorm\event\user_report_viewed::create([
    'context' => $contextmodule,
    'relateduserid' => $userid,
    'other' => ['attemptid' => $attempt, 'instanceid' => $exescorm->id],
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('exescorm', $exescorm);
$event->trigger();

// Print the page header.
$strreport = get_string('report', 'mod_exescorm');
$strattempt = get_string('attempt', 'mod_exescorm');

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
$currenttab = 'scoes';

$renderer = $PAGE->get_renderer('mod_exescorm');
$useractionreport = new \mod_exescorm\output\userreportsactionbar($id, $userid, $attempt, 'learning', $mode);
echo $renderer->user_report_actionbar($useractionreport);

// Printing user details.
$output = $PAGE->get_renderer('mod_exescorm');
echo $output->view_user_heading($user, $course, $PAGE->url, $attempt, $attemptids);

if ($scoes = $DB->get_records('exescorm_scoes', ['exescorm' => $exescorm->id], 'sortorder, id')) {
    // Print general score data.
    $table = new html_table();
    $table->head = [
            get_string('title', 'mod_exescorm'),
            get_string('status', 'mod_exescorm'),
            get_string('time', 'mod_exescorm'),
            get_string('score', 'mod_exescorm'),
            ''];
    $table->align = ['left', 'center', 'center', 'right', 'left'];
    $table->wrap = ['nowrap', 'nowrap', 'nowrap', 'nowrap', 'nowrap'];
    $table->width = '80%';
    $table->size = ['*', '*', '*', '*', '*'];
    foreach ($scoes as $sco) {
        if ($sco->launch != '') {
            $row = [];
            $score = '&nbsp;';
            if ($trackdata = exescorm_get_tracks($sco->id, $userid, $attempt)) {
                if ($trackdata->score_raw != '') {
                    $score = $trackdata->score_raw;
                }
                if ($trackdata->status == '') {
                    if (!empty($trackdata->progress)) {
                        $trackdata->status = $trackdata->progress;
                    } else {
                        $trackdata->status = 'notattempted';
                    }
                }
                $tracksurl->param('scoid', $sco->id);
                $detailslink = html_writer::link($tracksurl, get_string('details', 'mod_exescorm'));
            } else {
                $trackdata = new stdClass();
                $trackdata->status = 'notattempted';
                $trackdata->total_time = '&nbsp;';
                $detailslink = '&nbsp;';
            }
            $strstatus = get_string($trackdata->status, 'mod_exescorm');
            $row[] = $OUTPUT->pix_icon($trackdata->status, $strstatus, 'exescorm') . '&nbsp;'.format_string($sco->title);
            $row[] = get_string($trackdata->status, 'mod_exescorm');
            $row[] = exescorm_format_duration($trackdata->total_time);
            $row[] = $score;
            $row[] = $detailslink;
        } else {
            $row = [format_string($sco->title), '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;'];
        }
        $table->data[] = $row;
    }
    echo html_writer::table($table);
}

// Print footer.

echo $OUTPUT->footer();
