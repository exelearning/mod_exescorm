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

// This page prints a particular instance of aicc/exescorm package.

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('cm', '', PARAM_INT);                          // Course Module ID, or...
$a = optional_param('a', '', PARAM_INT);                            // Exelearning ID.
$scoid = required_param('scoid', PARAM_INT);                        // Sco ID.
$mode = optional_param('mode', 'normal', PARAM_ALPHA);              // Navigation mode.
$currentorg = optional_param('currentorg', '', PARAM_RAW);          // Selected organization.
$newattempt = optional_param('newattempt', 'off', PARAM_ALPHA);     // The user request to start a new attempt.
$displaymode = optional_param('display', '', PARAM_ALPHA);

if (!empty($id)) {
    if (! $cm = get_coursemodule_from_id('exescorm', $id, 0, true)) {
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
    if (! $cm = get_coursemodule_from_instance("exescorm", $exescorm->id, $course->id, true)) {
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

// If new attempt is being triggered set normal mode and increment attempt number.
$attempt = exescorm_get_last_attempt($exescorm->id, $USER->id);

// Check mode is correct and set/validate mode/attempt/newattempt (uses pass by reference).
exescorm_check_mode($exescorm, $newattempt, $attempt, $USER->id, $mode);

if (!empty($scoid)) {
    $scoid = exescorm_check_launchable_sco($exescorm, $scoid);
}

$url = new moodle_url('/mod/exescorm/player.php', ['scoid' => $scoid, 'cm' => $cm->id]);
if ($mode !== 'normal') {
    $url->param('mode', $mode);
}
if ($currentorg !== '') {
    $url->param('currentorg', $currentorg);
}
if ($newattempt !== 'off') {
    $url->param('newattempt', $newattempt);
}
if ($displaymode !== '') {
    $url->param('display', $displaymode);
}
$PAGE->set_url($url);
// Moodle 4.0+ only.
if ($CFG->version >= 2022041900) {
    $PAGE->set_secondary_active_tab('modulepage');
}

$forcejs = get_config('exescorm', 'forcejavascript');
if (!empty($forcejs)) {
    $PAGE->add_body_class('forcejavascript');
}
$collapsetocwinsize = get_config('exescorm', 'collapsetocwinsize');
if (empty($collapsetocwinsize)) {
    // Set as default window size to collapse TOC.
    $collapsetocwinsize = 767;
} else {
    $collapsetocwinsize = intval($collapsetocwinsize);
}

require_login($course, false, $cm);

$strexescorms = get_string('modulenameplural', 'mod_exescorm');
$strexescorm = get_string('modulename', 'mod_exescorm');
$strpopup = get_string('popup', 'mod_exescorm');
$strexit = get_string('exitactivity', 'mod_exescorm');

$coursecontext = context_course::instance($course->id);

if ($displaymode == 'popup') {
    $PAGE->set_pagelayout('embedded');
} else {
    $shortname = format_string($course->shortname, true, ['context' => $coursecontext]);
    $pagetitle = strip_tags("$shortname: ".format_string($exescorm->name));
    $PAGE->set_title($pagetitle);
    $PAGE->set_heading($course->fullname);
}
if (!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', context_module::instance($cm->id))) {
    echo $OUTPUT->header();
    notice(get_string("activityiscurrentlyhidden"));
    echo $OUTPUT->footer();
    die;
}

// Check if EXESCORM available.
list($available, $warnings) = exescorm_get_availability_status($exescorm);
if (!$available) {
    $reason = current(array_keys($warnings));
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string($reason, "exescorm", $warnings[$reason]), "generalbox boxaligncenter");
    echo $OUTPUT->footer();
    die;
}

// TOC processing.
$exescorm->version = strtolower(clean_param($exescorm->version, PARAM_SAFEDIR));   // Just to be safe.
if (!file_exists($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'lib.php')) {
    $exescorm->version = 'SCORM_12';
}
require_once($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'lib.php');

$result = exescorm_get_toc($USER, $exescorm, $cm->id, EXESCORM_TOCJSLINK, $currentorg, $scoid,
    $mode, $attempt, true, true);
$sco = $result->sco;
if ($exescorm->lastattemptlock == 1 && $result->attemptleft == 0) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('exceededmaxattempts', 'mod_exescorm'));
    echo $OUTPUT->footer();
    exit;
}

$scoidstr = '&amp;scoid='.$sco->id;
$modestr = '&amp;mode='.$mode;

$SESSION->exescorm = new stdClass();
$SESSION->exescorm->scoid = $sco->id;
$SESSION->exescorm->exescormstatus = 'Not Initialized';
$SESSION->exescorm->exescormmode = $mode;
$SESSION->exescorm->attempt = $attempt;

// Mark module viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Generate the exit button.
$exiturl = "";
if (empty($exescorm->popup) || $displaymode == 'popup') {
    if ($course->format == 'singleactivity' && $exescorm->skipview == EXESCORM_SKIPVIEW_ALWAYS
        && !has_capability('mod/exescorm:viewreport', context_module::instance($cm->id))) {
        // Redirect students back to site home to avoid redirect loop.
        $exiturl = $CFG->wwwroot;
    } else {
        // Redirect back to the correct section if one section per page is being used.
        $exiturl = course_get_url($course, $cm->sectionnum)->out();
    }
}

// Print the page header.
$stringforjs = ['player:next', 'player:prev', 'player:skipnext', 'player:skipprev', 'player:toogleFullscreen', 'player:up' ];
$PAGE->requires->strings_for_js($stringforjs, 'mod_exescorm');
$PAGE->requires->data_for_js('exescormplayerdata', ['launch' => false,
                                                       'currentorg' => '',
                                                       'sco' => 0,
                                                       'exescorm' => 0,
                                                       'courseid' => $exescorm->course,
                                                       'cwidth' => $exescorm->width,
                                                       'cheight' => $exescorm->height,
                                                       'popupoptions' => $exescorm->options], true);
$PAGE->requires->js('/mod/exescorm/request.js', true);
$PAGE->requires->js('/lib/cookies.js', true);

if (file_exists($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'.js')) {
    $PAGE->requires->js('/mod/exescorm/datamodels/'.$exescorm->version.'.js', true);
} else {
    $PAGE->requires->js('/mod/exescorm/datamodels/scorm_12.js', true);
}
if ($CFG->version >= 2022041900) { // Moodle 4+.
    $activityheader = $PAGE->activityheader;
    $headerconfig = [
        'description' => '',
        'hidecompletion' => true,
    ];

    $activityheader->set_attrs($headerconfig);
}
echo $OUTPUT->header();
$PAGE->requires->string_for_js('navigation', 'mod_exescorm');
$PAGE->requires->string_for_js('toc', 'mod_exescorm');
$PAGE->requires->string_for_js('hide', 'moodle');
$PAGE->requires->string_for_js('show', 'moodle');
$PAGE->requires->string_for_js('popupsblocked', 'mod_exescorm');

$name = false;

$renderer = $PAGE->get_renderer('mod_exescorm');
// Exit button should ONLY be displayed when in the current window.
if ($displaymode !== 'popup') {
    echo $renderer->generate_editexitbar($exiturl, $cm);
}

echo html_writer::start_div('', ['id' => 'exescormpage']);
echo html_writer::start_div('', ['id' => 'tocbox']);
echo html_writer::div(html_writer::tag('script', '', ['id' => 'external-exescormapi', 'type' => 'text/JavaScript']), '',
                        ['id' => 'exescormapi-parent']);


if ($exescorm->hidetoc == EXESCORM_TOC_POPUP || $mode == 'browse' || $mode == 'review') {
    echo html_writer::start_div('pl-2', ['id' => 'exescormtop']);
    if ($mode == 'browse' || $mode == 'review') {
        echo html_writer::div(get_string("{$mode}mode", 'mod_exescorm'), 'exescorm-left h3', ['id' => 'exescormmode']);
    }
    if ($exescorm->hidetoc == EXESCORM_TOC_POPUP) {
        echo html_writer::div($result->tocmenu, 'exescorm-right', ['id' => 'exescormnav']);
    }
    echo html_writer::end_div();
}

echo html_writer::start_div('', ['id' => 'toctree']);

// ADD actions button.
echo $renderer->generate_tocbox_action_buttons();


echo html_writer::div('', '', ['id' => 'exescorm_toc_title']);

if (empty($exescorm->popup) || $displaymode == 'popup') {
    echo $result->toc;
} else {
    // Added incase javascript popups are blocked we don't provide a direct link
    // to the pop-up as JS communication can fail - the user must disable their pop-up blocker.
    $linkcourse = html_writer::link($CFG->wwwroot.'/course/view.php?id='.
                    $exescorm->course, get_string('finishexescormlinkname', 'mod_exescorm'));
    echo $OUTPUT->box(get_string('finishexescorm', 'mod_exescorm', $linkcourse), 'generalbox', 'altfinishlink');
}
echo html_writer::end_div(); // Toc tree ends.
echo html_writer::end_div(); // Toc box ends.
echo html_writer::tag('noscript', html_writer::div(get_string('noscriptnoexescorm', 'mod_exescorm'), '', ['id' => 'noscript']));

if ($result->prerequisites) {
    if ($exescorm->popup != 0 && $displaymode !== 'popup') {
        // Clean the name for the window as IE is fussy.
        $name = preg_replace("/[^A-Za-z0-9]/", "", $exescorm->name);
        if (!$name) {
            $name = 'DefaultPlayerWindow';
        }
        $name = 'exescorm_'.$name;
        echo html_writer::script('', $CFG->wwwroot.'/mod/exescorm/player.js');
        $url = new moodle_url($PAGE->url, ['scoid' => $sco->id, 'display' => 'popup', 'mode' => $mode]);
        echo html_writer::script(
            js_writer::function_call('exescorm_openpopup', [$url->out(false),
                                                       $name, $exescorm->options,
                                                       $exescorm->width, $exescorm->height]));
        echo html_writer::tag('noscript', html_writer::tag('iframe', '',
                ['id' => 'main', 'class' => 'scoframe', 'name' => 'main',
                'src' => 'loadSCO.php?id='.$cm->id.$scoidstr.$modestr]));
    }
} else {
    echo $OUTPUT->box(get_string('noprerequisites', 'mod_exescorm'));
}
echo html_writer::end_div(); // Scorm page ends.

$scoes = exescorm_get_toc_object($USER, $exescorm, $currentorg, $sco->id, $mode, $attempt);
$adlnav = exescorm_get_adlnav_json($scoes['scoes']);

if (empty($exescorm->popup) || $displaymode == 'popup') {
    if (!isset($result->toctitle)) {
        $result->toctitle = get_string('toc', 'mod_exescorm');
    }
    $jsmodule = [
        'name' => 'mod_exescorm',
        'fullpath' => '/mod/exescorm/module.js',
        'requires' => ['json'],
    ];
    $exescorm->nav = intval($exescorm->nav);
    $PAGE->requires->js_init_call('M.mod_exescorm.init', [$exescorm->nav, $exescorm->navpositionleft,
                        $exescorm->navpositiontop, $exescorm->hidetoc, $collapsetocwinsize, $result->toctitle,
                        $name, $sco->id, $adlnav], false, $jsmodule);
}
if (!empty($forcejs)) {
    $message = $OUTPUT->box(get_string("forcejavascriptmessage", "mod_exescorm"),
                            "generalbox boxaligncenter forcejavascriptmessage");
    echo html_writer::tag('noscript', $message);
}

if (file_exists($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'.php')) {
    include_once($CFG->dirroot.'/mod/exescorm/datamodels/'.$exescorm->version.'.php');
} else {
    include_once($CFG->dirroot.'/mod/exescorm/datamodels/scorm_12.php');
}

// Add the keepalive system to keep checking for a connection.
\core\session\manager::keepalive('networkdropped', 'mod_exescorm', 30, 10);

$PAGE->requires->js_call_amd('mod_exescorm/fullscreen', 'init');

echo $OUTPUT->footer();

// Set the start time of this SCO.
exescorm_insert_track($USER->id, $exescorm->id, $scoid, $attempt, 'x.start.time', time());
