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

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/exescorm/lib.php');
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');
require_once($CFG->dirroot.'/course/lib.php');

$id = optional_param('id', '', PARAM_INT);       // Course Module ID, or...
$a = optional_param('a', '', PARAM_INT);         // Exescorm ID.
$organization = optional_param('organization', '', PARAM_INT); // Organization ID.
$action = optional_param('action', '', PARAM_ALPHA);
$preventskip = optional_param('preventskip', '', PARAM_INT); // Prevent Skip view, set by javascript redirects.

if (!empty($id)) {
    if (! $cm = get_coursemodule_from_id('exescorm', $id, 0, true)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $exescorm = $DB->get_record("exescorm", array("id" => $cm->instance))) {
        throw new \moodle_exception('invalidcoursemodule');
    }
} else if (!empty($a)) {
    if (! $exescorm = $DB->get_record("exescorm", array("id" => $a))) {
        throw new \moodle_exception('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $exescorm->course))) {
        throw new \moodle_exception('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("exescorm", $exescorm->id, $course->id, true)) {
        throw new \moodle_exception('invalidcoursemodule');
    }
} else {
    throw new \moodle_exception('missingparameter');
}

$url = new moodle_url('/mod/exescorm/view.php', array('id' => $cm->id));
if ($organization !== '') {
    $url->param('organization', $organization);
}
$PAGE->set_url($url);
$forcejs = get_config('exescorm', 'forcejavascript');
if (!empty($forcejs)) {
    $PAGE->add_body_class('forcejavascript');
}

require_login($course, false, $cm);

$context = context_course::instance($course->id);
$contextmodule = context_module::instance($cm->id);

$launch = false; // Does this automatically trigger a launch based on skipview.
if (!empty($exescorm->popup)) {
    $scoid = 0;
    $orgidentifier = '';

    $result = exescorm_get_toc($USER, $exescorm, $cm->id, EXESCORM_TOCFULLURL);
    // Set last incomplete sco to launch first.
    if (!empty($result->sco->id)) {
        $sco = $result->sco;
    } else {
        $sco = exescorm_get_sco($exescorm->launch, EXESCORM_SCO_ONLY);
    }
    if (!empty($sco)) {
        $scoid = $sco->id;
        if (($sco->organization == '') && ($sco->launch == '')) {
            $orgidentifier = $sco->identifier;
        } else {
            $orgidentifier = $sco->organization;
        }
    }

    if (empty($preventskip) && $exescorm->skipview >= EXESCORM_SKIPVIEW_FIRST &&
        has_capability('mod/exescorm:skipview', $contextmodule) &&
        !has_capability('mod/exescorm:viewreport', $contextmodule)) { // Don't skip users with the capability to view reports.

        // Do we launch immediately and redirect the parent back ?
        if ($exescorm->skipview == EXESCORM_SKIPVIEW_ALWAYS || !exescorm_has_tracks($exescorm->id, $USER->id)) {
            $launch = true;
        }
    }
    // Redirect back to the section with one section per page ?

    $courseformat = course_get_format($course)->get_course();
    if ($courseformat->format == 'singleactivity') {
        $courseurl = $url->out(false, array('preventskip' => '1'));
    } else {
        $courseurl = course_get_url($course, $cm->sectionnum)->out(false);
    }
    $PAGE->requires->data_for_js('exescormplayerdata', Array('launch' => $launch,
                                                           'currentorg' => $orgidentifier,
                                                           'sco' => $scoid,
                                                           'exescorm' => $exescorm->id,
                                                           'courseurl' => $courseurl,
                                                           'cwidth' => $exescorm->width,
                                                           'cheight' => $exescorm->height,
                                                           'popupoptions' => $exescorm->options), true);
    $PAGE->requires->string_for_js('popupsblocked', 'exescorm');
    $PAGE->requires->string_for_js('popuplaunched', 'exescorm');
    $PAGE->requires->js('/mod/exescorm/view.js', true);
}

if (isset($SESSION->exescorm)) {
    unset($SESSION->exescorm);
}

$strexescorms = get_string("modulenameplural", "exescorm");
$strexescorm = get_string("modulename", "exescorm");

$shortname = format_string($course->shortname, true, array('context' => $context));
$pagetitle = strip_tags($shortname.': '.format_string($exescorm->name));

// Trigger module viewed event.
exescorm_view($exescorm, $course, $cm, $contextmodule);

if (empty($preventskip) && empty($launch) && (has_capability('mod/exescorm:skipview', $contextmodule))) {
    exescorm_simple_play($exescorm, $USER, $contextmodule, $cm->id);
}

// Print the page header.

$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);
// Let the module handle the display.
if ($CFG->version >= 2022041900) { // Moodle 4+.
    if (!empty($action) && $action == 'delete' && confirm_sesskey() &&
        has_capability('mod/exescorm:deleteownresponses', $contextmodule)
    ) {
        $PAGE->activityheader->disable();
    } else {
        $PAGE->activityheader->set_description('');
    }
}
echo $OUTPUT->header();
if (!empty($action) && confirm_sesskey() && has_capability('mod/exescorm:deleteownresponses', $contextmodule)) {
    if ($action == 'delete') {
        $confirmurl = new moodle_url($PAGE->url, array('action' => 'deleteconfirm'));
        echo $OUTPUT->confirm(get_string('deleteuserattemptcheck', 'exescorm'), $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
        exit;
    } else if ($action == 'deleteconfirm') {
        // Delete this users attempts.
        $DB->delete_records('exescorm_scoes_track', array('userid' => $USER->id, 'exescormid' => $exescorm->id));
        exescorm_update_grades($exescorm, $USER->id, true);
        echo $OUTPUT->notification(get_string('exescormresponsedeleted', 'exescorm'), 'notifysuccess');
    }
}
if ($CFG->version < 2022041900) { // Moodle prior to 4.
    echo $OUTPUT->heading(format_string($exescorm->name));
    $currenttab = 'info';
    require($CFG->dirroot . '/mod/exescorm/tabs.php');
}
// Print the main part of the page.
$attemptstatus = '';
if (empty($launch) && ($exescorm->displayattemptstatus == EXESCORM_DISPLAY_ATTEMPTSTATUS_ALL ||
         $exescorm->displayattemptstatus == EXESCORM_DISPLAY_ATTEMPTSTATUS_ENTRY)) {
    $attemptstatus = exescorm_get_attempt_status($USER, $exescorm, $cm);
}
echo $OUTPUT->box(format_module_intro('exescorm', $exescorm, $cm->id), '', 'intro');

// Check if EXESCORM available. No need to display warnings because activity dates are displayed at the top of the page.
list($available, $warnings) = exescorm_get_availability_status($exescorm);

if ($available && empty($launch)) {
    exescorm_print_launch($USER, $exescorm, 'view.php?id='.$cm->id, $cm);
}

echo $OUTPUT->box($attemptstatus);

if (!empty($forcejs)) {
    $message = $OUTPUT->box(get_string("forcejavascriptmessage", "exescorm"), "forcejavascriptmessage");
    echo html_writer::tag('noscript', $message);
}

if (!empty($exescorm->popup)) {
    $PAGE->requires->js_init_call('M.mod_exescormform.init');
}

echo $OUTPUT->footer();
