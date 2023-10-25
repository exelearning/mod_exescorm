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
require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

$id = required_param('id', PARAM_INT);   // Course id.

$PAGE->set_url('/mod/exescorm/index.php', array('id' => $id));

if (!empty($id)) {
    if (!$course = $DB->get_record('course', array('id' => $id))) {
        throw new \moodle_exception('invalidcourseid');
    }
} else {
    throw new \moodle_exception('missingparameter');
}

require_course_login($course);
$PAGE->set_pagelayout('incourse');

// Trigger instances list viewed event.
$event = \mod_exescorm\event\course_module_instance_list_viewed::create(['context' => context_course::instance($course->id), ]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strexescorm = get_string("modulename", "mod_exescorm");
$strexescorms = get_string("modulenameplural", "mod_exescorm");
$strname = get_string("name");
$strsummary = get_string("summary");
$strreport = get_string("report", 'mod_exescorm');
$strlastmodified = get_string("lastmodified");

$PAGE->set_title($strexescorms);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strexescorms);
echo $OUTPUT->header();

$usesections = course_format_uses_sections($course->format);

if ($usesections) {
    $sortorder = "cw.section ASC";
} else {
    $sortorder = "m.timemodified DESC";
}

if (! $exescorms = get_all_instances_in_course("exescorm", $course)) {
    notice(get_string('thereareno', 'moodle', $strexescorms), "../../course/view.php?id=$course->id");
    exit;
}

$table = new html_table();

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head = array ($strsectionname, $strname, $strsummary, $strreport);
    $table->align = array ("center", "left", "left", "left");
} else {
    $table->head = array ($strlastmodified, $strname, $strsummary, $strreport);
    $table->align = array ("left", "left", "left", "left");
}

foreach ($exescorms as $exescorm) {
    $context = context_module::instance($exescorm->coursemodule);
    $tt = "";
    if ($usesections) {
        if ($exescorm->section) {
            $tt = get_section_name($course, $exescorm->section);
        }
    } else {
        $tt = userdate($exescorm->timemodified);
    }
    $report = '&nbsp;';
    $reportshow = '&nbsp;';
    if (has_capability('mod/exescorm:viewreport', $context)) {
        $trackedusers = exescorm_get_count_users($exescorm->id, $exescorm->groupingid);
        if ($trackedusers > 0) {
            $reportshow = html_writer::link('report.php?id='.$exescorm->coursemodule,
                                                get_string('viewallreports', 'mod_exescorm', $trackedusers));
        } else {
            $reportshow = get_string('noreports', 'mod_exescorm');
        }
    } else if (has_capability('mod/exescorm:viewscores', $context)) {
        require_once('locallib.php');
        $report = exescorm_grade_user($exescorm, $USER->id);
        $reportshow = get_string('score', 'mod_exescorm').": ".$report;
    }
    $options = (object)array('noclean' => true);
    if (!$exescorm->visible) {
        // Show dimmed if the mod is hidden.
        $table->data[] = array ($tt, html_writer::link('view.php?id='.$exescorm->coursemodule,
                                                        format_string($exescorm->name),
                                                        array('class' => 'dimmed')),
                                format_module_intro('exescorm', $exescorm, $exescorm->coursemodule), $reportshow);
    } else {
        // Show normal if the mod is visible.
        $table->data[] = [$tt,
                            html_writer::link('view.php?id='.$exescorm->coursemodule, format_string($exescorm->name)),
                            format_module_intro('exescorm', $exescorm, $exescorm->coursemodule),
                            $reportshow,
                        ];
    }
}

echo html_writer::empty_tag('br');

echo html_writer::table($table);

echo $OUTPUT->footer();
