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
 * @package   mod_exescorm
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** EXESCORM_TYPE_LOCAL = local */
define('EXESCORM_TYPE_LOCAL', 'local');
/** EXESCORM_TYPE_EXESCORMNET = exescorm */
define('EXESCORM_TYPE_EXESCORMNET', 'exescormnet');
/** EXESCORM_TYPE_LOCALSYNC = localsync */
define('EXESCORM_TYPE_LOCALSYNC', 'localsync');
/** EXESCORM_TYPE_EXTERNAL = external */
define('EXESCORM_TYPE_EXTERNAL', 'external');
/** EXESCORM_TYPE_AICCURL = external AICC url */
define('EXESCORM_TYPE_AICCURL', 'aiccurl');

define('EXESCORM_TOC_SIDE', 0);
define('EXESCORM_TOC_HIDDEN', 1);
define('EXESCORM_TOC_POPUP', 2);
define('EXESCORM_TOC_DISABLED', 3);

// Used to show/hide navigation buttons and set their position.
define('EXESCORM_NAV_DISABLED', 0);
define('EXESCORM_NAV_UNDER_CONTENT', 1);
define('EXESCORM_NAV_FLOATING', 2);

// Used to check what SCORM version is being used.
define('EXESCORM_SCORM_12', 1);
define('EXESCORM_SCORM_13', 2);
define('EXESCORM_SCORM_AICC', 3);


// List of possible attemptstatusdisplay options.
define('EXESCORM_DISPLAY_ATTEMPTSTATUS_NO', 0);
define('EXESCORM_DISPLAY_ATTEMPTSTATUS_ALL', 1);
define('EXESCORM_DISPLAY_ATTEMPTSTATUS_MY', 2);
define('EXESCORM_DISPLAY_ATTEMPTSTATUS_ENTRY', 3);

define('EXESCORM_EVENT_TYPE_OPEN', 'open');
define('EXESCORM_EVENT_TYPE_CLOSE', 'close');

require_once(__DIR__ . '/deprecatedlib.php');

/**
 * Return an array of status options
 *
 * Optionally with translated strings
 *
 * @param   bool    $with_strings   (optional)
 * @return  array
 */
function exescorm_status_options($withstrings = false) {
    // Id's are important as they are bits.
    $options = array(
        2 => 'passed',
        4 => 'completed'
    );

    if ($withstrings) {
        foreach ($options as $key => $value) {
            $options[$key] = get_string('completionstatus_'.$value, 'mod_exescorm');
        }
    }

    return $options;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global stdClass
 * @global object
 * @uses CONTEXT_MODULE
 * @uses EXESCORM_TYPE_LOCAL
 * @uses EXESCORM_TYPE_LOCALSYNC
 * @uses EXESCORM_TYPE_EXTERNAL
 * @param object $exescorm Form data
 * @param object $mform
 * @return int new instance id
 */
function exescorm_add_instance($exescorm, $mform=null) {
    global $CFG, $DB, $USER;

    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

    if (empty($exescorm->timeopen)) {
        $exescorm->timeopen = 0;
    }
    if (empty($exescorm->timeclose)) {
        $exescorm->timeclose = 0;
    }
    if (empty($exescorm->completionstatusallscos)) {
        $exescorm->completionstatusallscos = 0;
    }
    $cmid = $exescorm->coursemodule;
    $cmidnumber = $exescorm->cmidnumber;
    $courseid = $exescorm->course;

    $context = context_module::instance($cmid);

    $exescorm = exescorm_option2text($exescorm);
    $exescorm->width = (int)str_replace('%', '', $exescorm->width);
    $exescorm->height = (int)str_replace('%', '', $exescorm->height);

    if (!isset($exescorm->whatgrade)) {
        $exescorm->whatgrade = 0;
    }

    $id = $DB->insert_record('exescorm', $exescorm);

    // Update course module record - from now on this instance properly exists and all function may be used.
    $DB->set_field('course_modules', 'instance', $id, array('id' => $cmid));

    // Reload exescorm instance.
    $record = $DB->get_record('exescorm', array('id' => $id));

    if ($exescorm->exescormtype === EXESCORM_TYPE_EXESCORMNET) {
        $record->exescormtype = EXESCORM_TYPE_LOCAL;

        $fs = get_file_storage();
        $templatename = get_config('exescorm', 'template');
        $templatefile = false;
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_exescorm',
            'filearea' => 'package',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'default_package.zip',
            'userid' => $USER->id,
            'source' => 'default_package.zip',
            'author' => fullname($USER),
            'license' => 'unknown',
        ];

        if (! empty($templatename)) {
            $templatefile = $fs->get_file(1, 'exescorm', 'config', 0, '/', ltrim($templatename, '/'));
        }

        if ($templatefile) {
            $file = $fs->create_file_from_storedfile($fileinfo, $templatefile);
        } else {
            $defaultpackagepath = $CFG->dirroot . '/mod/exescorm/data/default_package.zip';

            $file = $fs->create_file_from_pathname($fileinfo, $defaultpackagepath);
        }

        $filename = $file->get_filename();
        if ($filename !== false) {
            $record->reference = $filename;
        }
    } else if ($record->exescormtype === EXESCORM_TYPE_LOCAL) {
        // Store the package and verify.
        if (!empty($exescorm->packagefile)) {
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'mod_exescorm', 'package');
            file_save_draft_area_files($exescorm->packagefile, $context->id, 'mod_exescorm', 'package',
                0, array('subdirs' => 0, 'maxfiles' => 1));
            // Get filename of zip that was uploaded.
            $files = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, '', false);
            $file = reset($files);
            $filename = $file->get_filename();
            if ($filename !== false) {
                $record->reference = $filename;
            }
        }

    } else if ($record->exescormtype === EXESCORM_TYPE_LOCALSYNC) {
        $record->reference = $exescorm->packageurl;
    } else if ($record->exescormtype === EXESCORM_TYPE_EXTERNAL) {
        $record->reference = $exescorm->packageurl;
    } else if ($record->exescormtype === EXESCORM_TYPE_AICCURL) {
        $record->reference = $exescorm->packageurl;
        $record->hidetoc = EXESCORM_TOC_DISABLED; // TOC is useless for direct AICCURL so disable it.
    } else {
        return false;
    }

    // Save reference.
    $DB->update_record('exescorm', $record);

    // Extra fields required in grade related functions.
    $record->course = $courseid;
    $record->cmidnumber = $cmidnumber;
    $record->cmid = $cmid;

    exescorm_parse($record, true);

    exescorm_grade_item_update($record);
    exescorm_update_calendar($record, $cmid);
    if (!empty($exescorm->completionexpected)) {
        \core_completion\api::update_completion_date_event($cmid, 'exescorm', $record, $exescorm->completionexpected);
    }

    return $record->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global stdClass
 * @global object
 * @uses CONTEXT_MODULE
 * @uses EXESCORM_TYPE_LOCAL
 * @uses EXESCORM_TYPE_LOCALSYNC
 * @uses EXESCORM_TYPE_EXTERNAL
 * @param object $exescorm Form data
 * @param object $mform
 * @return bool
 */
function exescorm_update_instance($exescorm, $mform=null) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

    if (empty($exescorm->timeopen)) {
        $exescorm->timeopen = 0;
    }
    if (empty($exescorm->timeclose)) {
        $exescorm->timeclose = 0;
    }
    if (empty($exescorm->completionstatusallscos)) {
        $exescorm->completionstatusallscos = 0;
    }

    $cmid = $exescorm->coursemodule;
    $cmidnumber = $exescorm->cmidnumber;
    $courseid = $exescorm->course;

    $exescorm->id = $exescorm->instance;

    $context = context_module::instance($cmid);

    if ($exescorm->exescormtype === EXESCORM_TYPE_EXESCORMNET) {
        $exescorm->exescormtype = EXESCORM_TYPE_LOCAL;
    }

    if ($exescorm->exescormtype === EXESCORM_TYPE_LOCAL) {
        if (!empty($exescorm->packagefile)) {
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, 'mod_exescorm', 'package');
            file_save_draft_area_files($exescorm->packagefile, $context->id, 'mod_exescorm', 'package',
                0, array('subdirs' => 0, 'maxfiles' => 1));
            // Get filename of zip that was uploaded.
            $files = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, '', false);
            $file = reset($files);
            $filename = $file->get_filename();
            if ($filename !== false) {
                $exescorm->reference = $filename;
            }
        }

    } else if ($exescorm->exescormtype === EXESCORM_TYPE_LOCALSYNC) {
        $exescorm->reference = $exescorm->packageurl;
    } else if ($exescorm->exescormtype === EXESCORM_TYPE_EXTERNAL) {
        $exescorm->reference = $exescorm->packageurl;
    } else if ($exescorm->exescormtype === EXESCORM_TYPE_AICCURL) {
        $exescorm->reference = $exescorm->packageurl;
        $exescorm->hidetoc = EXESCORM_TOC_DISABLED; // TOC is useless for direct AICCURL so disable it.
    } else {
        return false;
    }

    $exescorm = exescorm_option2text($exescorm);
    $exescorm->width = (int)str_replace('%', '', $exescorm->width);
    $exescorm->height = (int)str_replace('%', '', $exescorm->height);
    $exescorm->timemodified = time();

    if (!isset($exescorm->whatgrade)) {
        $exescorm->whatgrade = 0;
    }

    $DB->update_record('exescorm', $exescorm);
    // We need to find this out before we blow away the form data.
    $completionexpected = (!empty($exescorm->completionexpected)) ? $exescorm->completionexpected : null;

    $exescorm = $DB->get_record('exescorm', array('id' => $exescorm->id));

    // Extra fields required in grade related functions.
    $exescorm->course = $courseid;
    $exescorm->idnumber = $cmidnumber;
    $exescorm->cmid = $cmid;

    exescorm_parse($exescorm, (bool)$exescorm->updatefreq);

    exescorm_grade_item_update($exescorm);
    exescorm_update_grades($exescorm);
    exescorm_update_calendar($exescorm, $cmid);
    \core_completion\api::update_completion_date_event($cmid, 'exescorm', $exescorm, $completionexpected);

    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global stdClass
 * @global object
 * @param int $id Scorm instance id
 * @return boolean
 */
function exescorm_delete_instance($id) {
    global $CFG, $DB;

    if (! $exescorm = $DB->get_record('exescorm', array('id' => $id))) {
        return false;
    }

    $result = true;

    // Delete any dependent records.
    if (! $DB->delete_records('exescorm_scoes_track', array('exescormid' => $exescorm->id))) {
        $result = false;
    }
    if ($scoes = $DB->get_records('exescorm_scoes', array('exescorm' => $exescorm->id))) {
        foreach ($scoes as $sco) {
            if (! $DB->delete_records('exescorm_scoes_data', array('scoid' => $sco->id))) {
                $result = false;
            }
        }
        $DB->delete_records('exescorm_scoes', array('exescorm' => $exescorm->id));
    }

    exescorm_grade_item_delete($exescorm);

    // We must delete the module record after we delete the grade item.
    if (! $DB->delete_records('exescorm', array('id' => $exescorm->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @global stdClass
 * @param stdClass $course Course object
 * @param stdClass $user User object
 * @param int $mod
 * @param stdClass $exescorm The exescorm object
 * @return mixed
 */
function exescorm_user_outline($course, $user, $mod, $exescorm) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

    require_once("$CFG->libdir/gradelib.php");
    /** @var Object $grades */
    $grades = grade_get_grades($course->id, 'mod', 'exescorm', $exescorm->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset(($grades->items[0])->grades);
        $result = (object) [
            'time' => grade_get_date_for_user_grade($grade, $user),
        ];
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            $result->info = get_string('gradenoun', 'mod_exescorm') . ': ' . $grade->str_long_grade;
        } else {
            $result->info = get_string('gradenoun', 'mod_exescorm') . ': ' . get_string('hidden', 'grades');
        }

        return $result;
    }
    return null;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @global stdClass
 * @global object
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $exescorm
 * @return boolean
 */
function exescorm_user_complete($course, $user, $mod, $exescorm) {
    global $CFG, $DB, $OUTPUT;
    require_once("$CFG->libdir/gradelib.php");

    $liststyle = 'structlist';
    $now = time();
    $firstmodify = $now;
    $lastmodify = 0;
    $sometoreport = false;
    $report = '';

    // First Access and Last Access dates for SCOs.
    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');
    $timetracks = exescorm_get_sco_runtime($exescorm->id, false, $user->id);
    $firstmodify = $timetracks->start;
    $lastmodify = $timetracks->finish;
    /** @var Object $grades */
    $grades = grade_get_grades($course->id, 'mod', 'exescorm', $exescorm->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        if (!$grade->hidden || has_capability('moodle/grade:viewhidden', context_course::instance($course->id))) {
            echo $OUTPUT->container(get_string('gradenoun', 'mod_exescorm') . ': ' . $grade->str_long_grade);
            if ($grade->str_feedback) {
                echo $OUTPUT->container(get_string('feedback').': '.$grade->str_feedback);
            }
        } else {
            echo $OUTPUT->container(get_string('gradenoun', 'mod_exescorm') . ': ' . get_string('hidden', 'grades'));
        }
    }

    if ($orgs = $DB->get_records_select('exescorm_scoes', 'exescorm = ? AND '.
                                         $DB->sql_isempty('exescorm_scoes', 'launch', false, true).' AND '.
                                         $DB->sql_isempty('exescorm_scoes', 'organization', false, false),
                                         array($exescorm->id), 'sortorder, id', 'id, identifier, title')) {
        if (count($orgs) <= 1) {
            unset($orgs);
            $orgs = array();
            $org = new stdClass();
            $org->identifier = '';
            $orgs[] = $org;
        }
        $report .= html_writer::start_div('mod-exescorm');
        foreach ($orgs as $org) {
            $conditions = array();
            $currentorg = '';
            if (!empty($org->identifier)) {
                $report .= html_writer::div($org->title, 'orgtitle');
                $currentorg = $org->identifier;
                $conditions['organization'] = $currentorg;
            }
            $report .= html_writer::start_tag('ul', array('id' => '0', 'class' => $liststyle));
                $conditions['exescorm'] = $exescorm->id;
            if ($scoes = $DB->get_records('exescorm_scoes', $conditions, "sortorder, id")) {
                // Drop keys so that we can access array sequentially.
                $scoes = array_values($scoes);
                $level = 0;
                $sublist = 1;
                $parents[$level] = '/';
                foreach ($scoes as $pos => $sco) {
                    if ($parents[$level] != $sco->parent) {
                        if ($level > 0 && $parents[$level - 1] == $sco->parent) {
                            $report .= html_writer::end_tag('ul').html_writer::end_tag('li');
                            $level--;
                        } else {
                            $i = $level;
                            $closelist = '';
                            while (($i > 0) && ($parents[$level] != $sco->parent)) {
                                $closelist .= html_writer::end_tag('ul').html_writer::end_tag('li');
                                $i--;
                            }
                            if (($i == 0) && ($sco->parent != $currentorg)) {
                                $report .= html_writer::start_tag('li');
                                $report .= html_writer::start_tag('ul', array('id' => $sublist, 'class' => $liststyle));
                                $level++;
                            } else {
                                $report .= $closelist;
                                $level = $i;
                            }
                            $parents[$level] = $sco->parent;
                        }
                    }
                    $report .= html_writer::start_tag('li');
                    if (isset($scoes[$pos + 1])) {
                        $nextsco = $scoes[$pos + 1];
                    } else {
                        $nextsco = false;
                    }
                    if (($nextsco !== false) && ($sco->parent != $nextsco->parent) &&
                            (($level == 0) || (($level > 0) && ($nextsco->parent == $sco->identifier)))) {
                        $sublist++;
                    } else {
                        $report .= $OUTPUT->spacer(array("height" => "12", "width" => "13"));
                    }

                    if ($sco->launch) {
                        $score = '';
                        $totaltime = '';
                        if ($usertrack = exescorm_get_tracks($sco->id, $user->id)) {
                            if ($usertrack->status == '') {
                                $usertrack->status = 'notattempted';
                            }
                            $strstatus = get_string($usertrack->status, 'mod_exescorm');
                            $report .= $OUTPUT->pix_icon($usertrack->status, $strstatus, 'exescorm');
                        } else {
                            if ($sco->exescormtype == 'sco') {
                                $report .= $OUTPUT->pix_icon(
                                            'notattempted',
                                            get_string('notattempted', 'mod_exescorm'),
                                            'exescorm'
                                        );
                            } else {
                                $report .= $OUTPUT->pix_icon(
                                            'asset',
                                            get_string('asset', 'mod_exescorm'),
                                            'exescorm'
                                        );
                            }
                        }
                        $report .= "&nbsp;$sco->title $score$totaltime".html_writer::end_tag('li');
                        if ($usertrack !== false) {
                            $sometoreport = true;
                            $report .= html_writer::start_tag('li').html_writer::start_tag('ul', array('class' => $liststyle));
                            foreach ($usertrack as $element => $value) {
                                if (substr($element, 0, 3) == 'cmi') {
                                    $report .= html_writer::tag('li', s($element) . ' => ' . s($value));
                                }
                            }
                            $report .= html_writer::end_tag('ul').html_writer::end_tag('li');
                        }
                    } else {
                        $report .= "&nbsp;$sco->title".html_writer::end_tag('li');
                    }
                }
                for ($i = 0; $i < $level; $i++) {
                    $report .= html_writer::end_tag('ul').html_writer::end_tag('li');
                }
            }
            $report .= html_writer::end_tag('ul').html_writer::empty_tag('br');
        }
        $report .= html_writer::end_div();
    }
    if ($sometoreport) {
        if ($firstmodify < $now) {
            $timeago = format_time($now - $firstmodify);
            echo get_string('firstaccess', 'mod_exescorm') . ': ' . userdate($firstmodify) . ' (' . $timeago . ")"
                . html_writer::empty_tag('br');
        }
        if ($lastmodify > 0) {
            $timeago = format_time($now - $lastmodify);
            echo get_string('lastaccess', 'mod_exescorm').': '.userdate($lastmodify).' ('.$timeago.")".html_writer::empty_tag('br');
        }
        echo get_string('report', 'mod_exescorm').":".html_writer::empty_tag('br');
        echo $report;
    } else {
        print_string('noactivity', 'mod_exescorm');
    }

    return true;
}

/**
 * Function to be run periodically according to the moodle Tasks API
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @global stdClass
 * @global object
 * @return boolean
 */
function exescorm_cron_scheduled_task () {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

    $sitetimezone = core_date::get_server_timezone();
    // Now see if there are any exescorm updates to be done.

    if (!isset($CFG->exescorm_updatetimelast)) {    // To catch the first time.
        set_config('exescorm_updatetimelast', 0);
    }

    $timenow = time();
    $updatetime = usergetmidnight($timenow, $sitetimezone);

    if ($CFG->exescorm_updatetimelast < $updatetime && $timenow > $updatetime) {

        set_config('exescorm_updatetimelast', $timenow);

        mtrace('Updating exescorm packages which require daily update');// We are updating.

        $exescormsupdate = $DB->get_records('exescorm', array('updatefreq' => EXESCORM_UPDATE_EVERYDAY));
        foreach ($exescormsupdate as $exescormupdate) {
            exescorm_parse($exescormupdate, true);
        }

        // Now clear out AICC session table with old session data.
        $cfgexescorm = get_config('exescorm');
        if (!empty($cfgexescorm->allowaicchacp)) {
            $expiretime = time() - ($cfgexescorm->aicchacpkeepsessiondata * 24 * 60 * 60);
            $DB->delete_records_select('exescorm_aicc_session', 'timemodified < ?', array($expiretime));
        }
    }

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $exescormid id of exescorm
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function exescorm_get_user_grades($exescorm, $userid=0) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

    $grades = array();
    if (empty($userid)) {
        $scousers = $DB->get_records_select('exescorm_scoes_track', "exescormid=? GROUP BY userid",
                                            array($exescorm->id), "", "userid,null");
        if ($scousers) {
            foreach ($scousers as $scouser) {
                $grades[$scouser->userid] = new stdClass();
                $grades[$scouser->userid]->id = $scouser->userid;
                $grades[$scouser->userid]->userid = $scouser->userid;
                $grades[$scouser->userid]->rawgrade = exescorm_grade_user($exescorm, $scouser->userid);
            }
        } else {
            return false;
        }

    } else {
        $preattempt = $DB->get_records_select('exescorm_scoes_track', "exescormid=? AND userid=? GROUP BY userid",
                                                array($exescorm->id, $userid), "", "userid,null");
        if (!$preattempt) {
            return false; // No attempt yet.
        }
        $grades[$userid] = new stdClass();
        $grades[$userid]->id = $userid;
        $grades[$userid]->userid = $userid;
        $grades[$userid]->rawgrade = exescorm_grade_user($exescorm, $userid);
    }

    return $grades;
}

/**
 * Update grades in central gradebook
 *
 * @category grade
 * @param object $exescorm
 * @param int $userid specific user only, 0 mean all
 * @param bool $nullifnone
 */
function exescorm_update_grades($exescorm, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->libdir.'/completionlib.php');

    if ($grades = exescorm_get_user_grades($exescorm, $userid)) {
        exescorm_grade_item_update($exescorm, $grades);
        // Set complete.
        exescorm_set_completion($exescorm, $userid, COMPLETION_COMPLETE, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        exescorm_grade_item_update($exescorm, $grade);
        // Set incomplete.
        exescorm_set_completion($exescorm, $userid, COMPLETION_INCOMPLETE);
    } else {
        exescorm_grade_item_update($exescorm);
    }
}

/**
 * Update/create grade item for given exescorm
 *
 * @category grade
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $exescorm object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function exescorm_grade_item_update($exescorm, $grades=null) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/exescorm/locallib.php');
    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    $params = array('itemname' => $exescorm->name);
    if (isset($exescorm->cmidnumber)) {
        $params['idnumber'] = $exescorm->cmidnumber;
    }

    if ($exescorm->grademethod == EXESCORM_GRADESCOES) {
        $maxgrade = $DB->count_records_select('exescorm_scoes', 'exescorm = ? AND '
                    . $DB->sql_isnotempty('exescorm_scoes', 'launch', false, true), array($exescorm->id));
        if ($maxgrade) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax'] = $maxgrade;
            $params['grademin'] = 0;
        } else {
            $params['gradetype'] = GRADE_TYPE_NONE;
        }
    } else {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $exescorm->maxgrade;
        $params['grademin'] = 0;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/exescorm', $exescorm->course, 'mod', 'exescorm', $exescorm->id, 0, $grades, $params);
}

/**
 * Delete grade item for given exescorm
 *
 * @category grade
 * @param object $exescorm object
 * @return object grade_item
 */
function exescorm_grade_item_delete($exescorm) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update(
        'mod/exescorm',
        $exescorm->course,
        'mod',
        'exescorm',
        $exescorm->id,
        0,
        null,
        array('deleted' => 1)
    );
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function exescorm_get_view_actions() {
    return array('pre-view', 'view', 'view all', 'report');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function exescorm_get_post_actions() {
    return array();
}

/**
 * @param object $exescorm
 * @return object $exescorm
 */
function exescorm_option2text($exescorm) {
    $exescormpopoupoptions = exescorm_get_popup_options_array();

    if (isset($exescorm->popup)) {
        if ($exescorm->popup == 1) {
            $optionlist = array();
            foreach ($exescormpopoupoptions as $name => $option) {
                if (isset($exescorm->$name)) {
                    $optionlist[] = $name.'='.$exescorm->$name;
                } else {
                    $optionlist[] = $name.'=0';
                }
            }
            $exescorm->options = implode(',', $optionlist);
        } else {
            $exescorm->options = '';
        }
    } else {
        $exescorm->popup = 0;
        $exescorm->options = '';
    }
    return $exescorm;
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the exescorm.
 *
 * @param object $mform form passed by reference
 */
function exescorm_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'exescormheader', get_string('modulenameplural', 'mod_exescorm'));
    $mform->addElement('advcheckbox', 'reset_exescorm', get_string('deleteallattempts', 'mod_exescorm'));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function exescorm_reset_course_form_defaults($course) {
    return array('reset_exescorm' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function exescorm_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
              FROM {exescorm} s, {course_modules} cm, {modules} m
             WHERE m.name='exescorm' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

    if ($exescorms = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($exescorms as $exescorm) {
            exescorm_grade_item_update($exescorm, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * exescorm attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function exescorm_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'mod_exescorm');
    $status = array();

    if (!empty($data->reset_exescorm)) {
        $exescormssql = "SELECT s.id
                         FROM {exescorm} s
                        WHERE s.course=?";

        $DB->delete_records_select('exescorm_scoes_track', "exescormid IN ($exescormssql)", array($data->courseid));

        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            exescorm_reset_gradebook($data->courseid);
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallattempts', 'mod_exescorm'), 'error' => false);
    }

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    shift_course_mod_dates('exescorm', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
    $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => false);

    return $status;
}

/**
 * Lists all file areas current user may browse
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function exescorm_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('areacontent', 'mod_exescorm');
    $areas['package'] = get_string('areapackage', 'mod_exescorm');
    return $areas;
}

/**
 * File browsing support for EXESCORM file areas
 *
 * @package  mod_exescorm
 * @category files
 * @param file_browser $browser file browser instance
 * @param array $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function exescorm_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        return null;
    }

    // No writing for now!

    $fs = get_file_storage();

    if ($filearea === 'content') {

        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_exescorm', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_exescorm', 'content', 0);
            } else {
                // Not found.
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/exescorm/locallib.php");
        return new exescorm_package_file_info(
            $browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, false, false
        );

    } else if ($filearea === 'package') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_exescorm', 'package', 0, $filepath, $filename)) {
            if ($filepath === '/' && $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_exescorm', 'package', 0);
            } else {
                // Not found.
                return null;
            }
        }
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $areas[$filearea], false, true, false, false);
    }

    // Scorm_intro handled in file_browser.

    return false;
}

/**
 * Serves exescorm content, introduction images and packages. Implements needed access control ;-)
 *
 * @package  mod_exescorm
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function exescorm_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    $canmanageactivity = has_capability('moodle/course:manageactivities', $context);
    $lifetime = null;

    // Check EXESCORM availability.
    if (!$canmanageactivity) {
        require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

        $exescorm = $DB->get_record('exescorm', array('id' => $cm->instance), 'id, timeopen, timeclose', MUST_EXIST);
        list($available, $warnings) = exescorm_get_availability_status($exescorm);
        if (!$available) {
            return false;
        }
    }

    if ($filearea === 'content') {
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_exescorm/content/0/$relativepath";
        $options['immutable'] = true; // Add immutable option, $relativepath changes on file update.

    } else if ($filearea === 'package') {
        // Check if the global setting for disabling package downloads is enabled.
        $protectpackagedownloads = get_config('exescorm', 'protectpackagedownloads');
        if ($protectpackagedownloads && !$canmanageactivity) {
            return false;
        }
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_exescorm/package/0/$relativepath";
        $lifetime = 0; // No caching here.

    } else if ($filearea === 'imsmanifest') { // This isn't a real filearea, it's a url parameter for this type of package.
        $revision = (int)array_shift($args); // Prevents caching problems - ignored here.
        $relativepath = implode('/', $args);

        // Get imsmanifest file.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, '', false);
        $file = reset($files);

        // Check that the package file is an imsmanifest.xml file - if not then this method is not allowed.
        $packagefilename = $file->get_filename();
        if (strtolower($packagefilename) !== 'imsmanifest.xml') {
            return false;
        }

        $file->send_relative_file($relativepath);
    } else {
        return false;
    }

    $fs = get_file_storage();
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        if ($filearea === 'content') { // Return file not found straight away to improve performance.
            send_header_404();
            die;
        }
        return false;
    }

    // Allow SVG files to be loaded within EXESCORM content, instead of forcing download.
    $options['dontforcesvgdownload'] = true;

    // Finally send the file.
    send_stored_file($file, $lifetime, 0, false, $options);
}

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function exescorm_supports($feature) {
    global $CFG;
    // Features from version 4.0. This hack is avoid warnings.
    if ($CFG->version < 2022041900) {
        defined('FEATURE_MOD_PURPOSE') || define('FEATURE_MOD_PURPOSE', 'mod_purpose');
        defined('MOD_PURPOSE_CONTENT') || define('MOD_PURPOSE_CONTENT', 'content');
    }

    switch($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_MOD_INTRO:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Get the filename for a temp log file
 *
 * @param string $type - type of log(aicc,exescorm12,exescorm13) used as prefix for filename
 * @param integer $scoid - scoid of object this log entry is for
 * @return string The filename as an absolute path
 */
function exescorm_debug_log_filename($type, $scoid) {
    global $CFG, $USER;

    $logpath = $CFG->tempdir.'/exescormlogs';
    $logfile = $logpath.'/'.$type.'debug_'.$USER->id.'_'.$scoid.'.log';
    return $logfile;
}

/**
 * writes log output to a temp log file
 *
 * @param string $type - type of log(aicc,exescorm12,exescorm13) used as prefix for filename
 * @param string $text - text to be written to file.
 * @param integer $scoid - scoid of object this log entry is for.
 */
function exescorm_debug_log_write($type, $text, $scoid) {
    global $CFG;

    $debugenablelog = get_config('exescorm', 'allowapidebug');
    if (!$debugenablelog || empty($text)) {
        return;
    }
    if (make_temp_directory('exescormlogs/')) {
        $logfile = exescorm_debug_log_filename($type, $scoid);
        @file_put_contents($logfile, date('Y/m/d H:i:s O')." DEBUG $text\r\n", FILE_APPEND);
        @chmod($logfile, $CFG->filepermissions);
    }
}

/**
 * Remove debug log file
 *
 * @param string $type - type of log(aicc,exescorm12,exescorm13) used as prefix for filename
 * @param integer $scoid - scoid of object this log entry is for
 * @return boolean True if the file is successfully deleted, false otherwise
 */
function exescorm_debug_log_remove($type, $scoid) {

    $debugenablelog = get_config('exescorm', 'allowapidebug');
    $logfile = exescorm_debug_log_filename($type, $scoid);
    if (!$debugenablelog || !file_exists($logfile)) {
        return false;
    }

    return @unlink($logfile);
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function exescorm_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array('mod-exescorm-*' => get_string('page-mod-exescorm-x', 'mod_exescorm'));
    return $modulepagetype;
}

/**
 * Returns the EXESCORM version used.
 * @param string $exescormversion comes from $exescorm->version
 * @param string $version one of the defined vars SCORM_12, SCORM_13, SCORM_AICC (or empty)
 * @return Scorm version.
 */
function exescorm_version_check($exescormversion, $version='') {
    $exescormversion = trim(strtolower($exescormversion));
    if (empty($version) || $version == EXESCORM_SCORM_12) {
        if ($exescormversion == 'SCORM_12' || $exescormversion == 'scorm_1.2') {
            return EXESCORM_SCORM_12;
        }
        if (!empty($version)) {
            return false;
        }
    }
    if (empty($version) || $version == EXESCORM_SCORM_13) {
        if ($exescormversion == 'SCORM_13' || $exescormversion == 'scorm_1.3') {
            return EXESCORM_SCORM_13;
        }
        if (!empty($version)) {
            return false;
        }
    }
    if (empty($version) || $version == EXESCORM_SCORM_AICC) {
        if (strpos($exescormversion, 'aicc')) {
            return EXESCORM_SCORM_AICC;
        }
        if (!empty($version)) {
            return false;
        }
    }
    return false;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function exescorm_dndupload_register() {
    return array('files' => array(
        array('extension' => 'zip', 'message' => get_string('dnduploadexescorm', 'mod_exescorm'))
    ));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function exescorm_dndupload_handle($uploadinfo) {

    $context = context_module::instance($uploadinfo->coursemodule);
    file_save_draft_area_files($uploadinfo->draftitemid, $context->id, 'mod_exescorm', 'package', 0);
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, 'sortorder, itemid, filepath, filename', false);
    $file = reset($files);

    // Validate the file, make sure it's a valid EXESCORM package!
    $errors = exescorm_validate_package($file);
    if (!empty($errors)) {
        return false;
    }
    // Create a default exescorm object to pass to exescorm_add_instance()!
    $exescorm = get_config('exescorm');
    $exescorm->course = $uploadinfo->course->id;
    $exescorm->coursemodule = $uploadinfo->coursemodule;
    $exescorm->cmidnumber = '';
    $exescorm->name = $uploadinfo->displayname;
    $exescorm->exescormtype = EXESCORM_TYPE_LOCAL;
    $exescorm->reference = $file->get_filename();
    $exescorm->intro = '';
    $exescorm->width = $exescorm->framewidth;
    $exescorm->height = $exescorm->frameheight;

    return exescorm_add_instance($exescorm, null);
}

/**
 * Sets activity completion state
 *
 * @param object $exescorm object
 * @param int $userid User ID
 * @param int $completionstate Completion state
 * @param array $grades grades array of users with grades - used when $userid = 0
 */
function exescorm_set_completion($exescorm, $userid, $completionstate = COMPLETION_COMPLETE, $grades = array()) {
    $course = new stdClass();
    $course->id = $exescorm->course;
    $completion = new completion_info($course);

    // Check if completion is enabled site-wide, or for the course.
    if (!$completion->is_enabled()) {
        return;
    }

    $cm = get_coursemodule_from_instance('exescorm', $exescorm->id, $exescorm->course);
    if (empty($cm) || !$completion->is_enabled($cm)) {
            return;
    }

    if (empty($userid)) { // We need to get all the relevant users from $grades param.
        foreach ($grades as $grade) {
            $completion->update_state($cm, $completionstate, $grade->userid);
        }
    } else {
        $completion->update_state($cm, $completionstate, $userid);
    }
}

/**
 * Check that a Zip file contains a valid EXESCORM package
 *
 * @param \stored_file $file A Zip file.
 * @return array empty if no issue is found. Array of error message otherwise
 */
function exescorm_validate_package($file) {
    $packer = get_file_packer('application/zip');
    $errors = [];
    if ($file->is_external_file()) { // Get zip file so we can check it is correct.
        $file->import_external_file_contents();
    }
    $filelist = $file->list_files($packer);

    if (!is_array($filelist)) {
        $errors['packagefile'] = get_string('badarchive', 'mod_exescorm');
        return $errors;
    }
    $errors = \mod_exescorm\exescorm_package::validate_file_list($filelist);
    if (!empty($errors)) {
        return $errors;
    }

    $aiccfound = false;
    $badmanifestpresent = false;
    foreach ($filelist as $info) {
        if ($info->pathname == 'imsmanifest.xml') {
            return [];
        } else if (strpos($info->pathname, 'imsmanifest.xml') !== false) {
            // This package has an imsmanifest file inside a folder of the package.
            $badmanifestpresent = true;
        }
        if (preg_match('/\.cst$/', $info->pathname)) {
            return [];
        }
    }
    if (!$aiccfound) {
        if ($badmanifestpresent) {
            $errors['packagefile'] = get_string('badimsmanifestlocation', 'mod_exescorm');
        } else {
            $errors['packagefile'] = get_string('nomanifest', 'mod_exescorm');
        }
    }

    return $errors;
}

/**
 * Check and set the correct mode and attempt when entering a EXESCORM package.
 *
 * @param object $exescorm object
 * @param string $newattempt should a new attempt be generated here.
 * @param int $attempt the attempt number this is for.
 * @param int $userid the userid of the user.
 * @param string $mode the current mode that has been selected.
 */
function exescorm_check_mode($exescorm, &$newattempt, &$attempt, $userid, &$mode) {
    global $DB;

    if (($mode == 'browse')) {
        if ($exescorm->hidebrowse == 1) {
            // Prevent Browse mode if hidebrowse is set.
            $mode = 'normal';
        } else {
            // We don't need to check attempts as browse mode is set.
            return;
        }
    }

    if ($exescorm->forcenewattempt == EXESCORM_FORCEATTEMPT_ALWAYS) {
        // This EXESCORM is configured to force a new attempt on every re-entry.
        $newattempt = 'on';
        $mode = 'normal';
        if ($attempt == 1) {
            // Check if the user has any existing data or if this is really the first attempt.
            $exists = $DB->record_exists(
                'exescorm_scoes_track', array('userid' => $userid, 'exescormid' => $exescorm->id)
            );
            if (!$exists) {
                // No records yet - Attempt should == 1.
                return;
            }
        }
        $attempt++;

        return;
    }
    // Check if the exescorm module is incomplete (used to validate user request to start a new attempt).
    $incomplete = true;

    // Note - inEXESCORM_SCORM_13 the cmi-core.lesson_status field was split into
    // 'cmi.completion_status' and 'cmi.success_status'.
    // 'cmi.completion_status' can only contain values 'completed', 'incomplete', 'not attempted' or 'unknown'.
    // This means the values 'passed' or 'failed' will never be reported for a track inEXESCORM_SCORM_13 and
    // the only status that will be treated as complete is 'completed'.

    $completionelements = array(
       EXESCORM_SCORM_12 => 'cmi.core.lesson_status',
       EXESCORM_SCORM_13 => 'cmi.completion_status',
       EXESCORM_SCORM_AICC => 'cmi.core.lesson_status'
    );
    $exescormversion = exescorm_version_check($exescorm->version);
    if ($exescormversion === false) {
        $exescormversion = EXESCORM_SCORM_12;
    }
    $completionelement = $completionelements[$exescormversion];

    $sql = "SELECT sc.id, t.value
              FROM {exescorm_scoes} sc
         LEFT JOIN {exescorm_scoes_track} t ON sc.exescorm = t.exescormid AND sc.id = t.scoid
                   AND t.element = ? AND t.userid = ? AND t.attempt = ?
             WHERE sc.exescormtype = 'sco' AND sc.exescorm = ?";
    $tracks = $DB->get_recordset_sql($sql, array($completionelement, $userid, $attempt, $exescorm->id));

    foreach ($tracks as $track) {
        if (($track->value == 'completed') || ($track->value == 'passed') || ($track->value == 'failed')) {
            $incomplete = false;
        } else {
            $incomplete = true;
            break; // Found an incomplete sco, so the result as a whole is incomplete.
        }
    }
    $tracks->close();

    // Validate user request to start a new attempt.
    if ($incomplete === true) {
        // The option to start a new attempt should never have been presented. Force false.
        $newattempt = 'off';
    } else if (!empty($exescorm->forcenewattempt)) {
        // A new attempt should be forced for already completed attempts.
        $newattempt = 'on';
    }

    if (($newattempt == 'on') && (($attempt < $exescorm->maxattempt) || ($exescorm->maxattempt == 0))) {
        $attempt++;
        $mode = 'normal';
    } else { // Check if review mode should be set.
        if ($incomplete === true) {
            $mode = 'normal';
        } else {
            $mode = 'review';
        }
    }
}

/**
 * Trigger the course_module_viewed event.
 *
 * @param  stdClass $exescorm        exescorm object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function exescorm_view($exescorm, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $exescorm->id
    );

    $event = \mod_exescorm\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('exescorm', $exescorm);
    $event->trigger();
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param  cm_info $cm course module data
 * @param  int $from the time to check updates from
 * @param  array $filter  if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.2
 */
function exescorm_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;
    require_once($CFG->dirroot . '/mod/exescorm/locallib.php');

    $exescorm = $DB->get_record($cm->modname, array('id' => $cm->instance), '*', MUST_EXIST);
    $updates = new stdClass();
    list($available, $warnings) = exescorm_get_availability_status($exescorm, true, $cm->context);
    if (!$available) {
        return $updates;
    }
    $updates = course_check_module_updates_since($cm, $from, array('package'), $filter);

    $updates->tracks = (object) array('updated' => false);
    $select = 'exescormid = ? AND userid = ? AND timemodified > ?';
    $params = array($exescorm->id, $USER->id, $from);
    $tracks = $DB->get_records_select('exescorm_scoes_track', $select, $params, '', 'id');
    if (!empty($tracks)) {
        $updates->tracks->updated = true;
        $updates->tracks->itemids = array_keys($tracks);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/exescorm:viewreport', $cm->context)) {
        $select = 'exescormid = ? AND timemodified > ?';
        $params = array($exescorm->id, $from);

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->usertracks = (object) array('updated' => false);
        $tracks = $DB->get_records_select('exescorm_scoes_track', $select, $params, '', 'id');
        if (!empty($tracks)) {
            $updates->usertracks->updated = true;
            $updates->usertracks->itemids = array_keys($tracks);
        }
    }
    return $updates;
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_exescorm_get_fontawesome_icon_map() {
    return [
        'mod_exescorm:assetc' => 'fa-file-archive-o',
        'mod_exescorm:asset' => 'fa-file-archive-o',
        'mod_exescorm:browsed' => 'fa-book',
        'mod_exescorm:completed' => 'fa-check-square-o',
        'mod_exescorm:failed' => 'fa-times',
        'mod_exescorm:incomplete' => 'fa-pencil-square-o',
        'mod_exescorm:minus' => 'fa-minus',
        'mod_exescorm:notattempted' => 'fa-square-o',
        'mod_exescorm:passed' => 'fa-check',
        'mod_exescorm:plus' => 'fa-plus',
        'mod_exescorm:popdown' => 'fa-window-close-o',
        'mod_exescorm:popup' => 'fa-window-restore',
        'mod_exescorm:suspend' => 'fa-pause',
        'mod_exescorm:wait' => 'fa-clock-o',
    ];
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every exescorm event in the site is checked, else
 * only exescorm events belonging to the course specified are checked.
 *
 * @param int $courseid
 * @param int|stdClass $instance exescorm module instance or ID.
 * @param int|stdClass $cm Course module object or ID.
 * @return bool
 */
function exescorm_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/mod/exescorm/locallib.php');

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('exescorm', array('id' => $instance), '*', MUST_EXIST);
        }
        if (isset($cm)) {
            if (!is_object($cm)) {
                $cm = (object)array('id' => $cm);
            }
        } else {
            $cm = get_coursemodule_from_instance('exescorm', $instance->id);
        }
        exescorm_update_calendar($instance, $cm->id);
        return true;
    }

    if ($courseid) {
        // Make sure that the course id is numeric.
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$exescorms = $DB->get_records('exescorm', array('course' => $courseid))) {
            return false;
        }
    } else {
        if (!$exescorms = $DB->get_records('exescorm')) {
            return false;
        }
    }

    foreach ($exescorms as $exescorm) {
        $cm = get_coursemodule_from_instance('exescorm', $exescorm->id);
        exescorm_update_calendar($exescorm, $cm->id);
    }

    return true;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id override
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_exescorm_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory, $userid = null) {
    global $CFG, $USER;

    require_once($CFG->dirroot . '/mod/exescorm/locallib.php');

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['exescorm'][$event->instance];

    if (has_capability('mod/exescorm:viewreport', $cm->context, $userid)) {
        // Teachers do not need to be reminded to complete a exescorm.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    if (!empty($cm->customdata['timeclose']) && $cm->customdata['timeclose'] < time()) {
        // The exescorm has closed so the user can no longer submit anything.
        return null;
    }

    // Restore exescorm object from cached values in $cm, we only need id, timeclose and timeopen.
    $customdata = $cm->customdata ?: [];
    $customdata['id'] = $cm->instance;
    $exescorm = (object)($customdata + ['timeclose' => 0, 'timeopen' => 0]);

    // Check that the EXESCORM activity is open.
    list($actionable, $warnings) = exescorm_get_availability_status($exescorm, false, null, $userid);

    return $factory->create_instance(
        get_string('enter', 'mod_exescorm'),
        new \moodle_url('/mod/exescorm/view.php', array('id' => $cm->id)),
        1,
        $actionable
    );
}

/**
 * Add a get_coursemodule_info function in case any EXESCORM type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function exescorm_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionstatusrequired, completionscorerequired, completionstatusallscos, '.
        'timeopen, timeclose';
    if (!$exescorm = $DB->get_record('exescorm', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $exescorm->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('exescorm', $exescorm, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionstatusrequired'] = $exescorm->completionstatusrequired;
        $result->customdata['customcompletionrules']['completionscorerequired'] = $exescorm->completionscorerequired;
        $result->customdata['customcompletionrules']['completionstatusallscos'] = $exescorm->completionstatusallscos;
    }
    // Populate some other values that can be used in calendar or on dashboard.
    if ($exescorm->timeopen) {
        $result->customdata['timeopen'] = $exescorm->timeopen;
    }
    if ($exescorm->timeclose) {
        $result->customdata['timeclose'] = $exescorm->timeclose;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_exescorm_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionstatusrequired':
                if (!is_null($val)) {
                    // Determine the selected statuses using a bitwise operation.
                    $cvalues = array();
                    foreach (exescorm_status_options(true) as $bit => $string) {
                        if (($val & $bit) == $bit) {
                            $cvalues[] = $string;
                        }
                    }
                    $statusstring = implode(', ', $cvalues);
                    $descriptions[] = get_string('completionstatusrequireddesc', 'mod_exescorm', $statusstring);
                }
                break;
            case 'completionscorerequired':
                if (!is_null($val)) {
                    $descriptions[] = get_string('completionscorerequireddesc', 'mod_exescorm', $val);
                }
                break;
            case 'completionstatusallscos':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionstatusallscos', 'mod_exescorm');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function will update the exescorm module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the exescorm instance
 * according to the type of event provided.
 *
 * @throws \moodle_exception
 * @param \calendar_event $event
 * @param stdClass $exescorm The module instance to get the range from
 */
function mod_exescorm_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $exescorm) {
    global $DB;

    if (empty($event->instance) || $event->modulename != 'exescorm') {
        return;
    }

    if ($event->instance != $exescorm->id) {
        return;
    }

    if (!in_array($event->eventtype, [EXESCORM_EVENT_TYPE_OPEN, EXESCORM_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == EXESCORM_EVENT_TYPE_OPEN) {
        // If the event is for the exescorm activity opening then we should
        // set the start time of the exescorm activity to be the new start
        // time of the event.
        if ($exescorm->timeopen != $event->timestart) {
            $exescorm->timeopen = $event->timestart;
            $exescorm->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == EXESCORM_EVENT_TYPE_CLOSE) {
        // If the event is for the exescorm activity closing then we should
        // set the end time of the exescorm activity to be the new start
        // time of the event.
        if ($exescorm->timeclose != $event->timestart) {
            $exescorm->timeclose = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $exescorm->timemodified = time();
        $DB->update_record('exescorm', $exescorm);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The date must be after this date'],
 *     [1506741172, 'The date must be before this date']
 * ]
 *
 * @param \calendar_event $event The calendar event to get the time range for
 * @param \stdClass $instance The module instance to get the range from
 * @return array Returns an array with min and max date.
 */
function mod_exescorm_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == EXESCORM_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the exescorm activity.
        if (!empty($instance->timeclose)) {
            $maxdate = [
                $instance->timeclose,
                get_string('openafterclose', 'mod_exescorm')
            ];
        }
    } else if ($event->eventtype == EXESCORM_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the exescorm activity.
        if (!empty($instance->timeopen)) {
            $mindate = [
                $instance->timeopen,
                get_string('closebeforeopen', 'mod_exescorm')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * Given an array with a file path, it returns the itemid and the filepath for the defined filearea.
 *
 * @param  string $filearea The filearea.
 * @param  array  $args The path (the part after the filearea and before the filename).
 * @return array The itemid and the filepath inside the $args path, for the defined filearea.
 */
function mod_exescorm_get_path_from_pluginfile(string $filearea, array $args) : array {
    // EXESCORM never has an itemid (the number represents the revision but it's not stored in database).
    array_shift($args);

    // Get the filepath.
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    return [
        'itemid' => 0,
        'filepath' => $filepath,
    ];
}

/**
 * Callback to fetch the activity event type lang string.
 *
 * @param string $eventtype The event type.
 * @return lang_string The event type lang string.
 */
function mod_exescorm_core_calendar_get_event_action_string(string $eventtype): string {
    $modulename = get_string('modulename', 'mod_exescorm');

    switch ($eventtype) {
        case EXESCORM_EVENT_TYPE_OPEN:
            $identifier = 'calendarstart';
            break;
        case EXESCORM_EVENT_TYPE_CLOSE:
            $identifier = 'calendarend';
            break;
        default:
            return get_string('requiresaction', 'calendar', $modulename);
    }

    return get_string($identifier, 'mod_exescorm', $modulename);
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param settings_navigation $settings navigation_node object.
 * @param navigation_node $exescormnode navigation_node object.
 * @return void
 */
function exescorm_extend_settings_navigation(settings_navigation $settings, navigation_node $exescormnode): void {
    global $PAGE, $CFG;
    // Features from version 4.0. This hack is avoid errors.
    if ($CFG->version < 2022041900) {
        if (isset($PAGE)) {
            $page = $PAGE;
        } else {
            return;
        }
    } else {
        $page = $settings->get_page();
    }

    if (has_capability('mod/exescorm:viewreport', $page->cm->context)) {
        $url = new moodle_url('/mod/exescorm/report.php', ['id' => $page->cm->id]);
        $exescormnode->add(get_string("reports", "mod_exescorm"), $url, navigation_node::TYPE_CUSTOM, null, 'exescormreport');
    }
}
