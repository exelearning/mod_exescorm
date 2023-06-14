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
 * functions used by AICC packages.
 *
 * @package    mod_exescorm
 * @copyright 1999 onwards Roberto Pinna
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function exescorm_add_time($a, $b) {
    $aes = explode(':', $a);
    $bes = explode(':', $b);
    $aseconds = explode('.', $aes[2]);
    $bseconds = explode('.', $bes[2]);
    $change = 0;

    $acents = 0;  // Cents.
    if (count($aseconds) > 1) {
        $acents = $aseconds[1];
    }
    $bcents = 0;
    if (count($bseconds) > 1) {
        $bcents = $bseconds[1];
    }
    $cents = $acents + $bcents;
    $change = floor($cents / 100);
    $cents = $cents - ($change * 100);
    if (floor($cents) < 10) {
        $cents = '0'. $cents;
    }

    $secs = $aseconds[0] + $bseconds[0] + $change;  // Seconds.
    $change = floor($secs / 60);
    $secs = $secs - ($change * 60);
    if (floor($secs) < 10) {
        $secs = '0'. $secs;
    }

    $mins = $aes[1] + $bes[1] + $change;   // Minutes.
    $change = floor($mins / 60);
    $mins = $mins - ($change * 60);
    if ($mins < 10) {
        $mins = '0' .  $mins;
    }

    $hours = $aes[0] + $bes[0] + $change;  // Hours.
    if ($hours < 10) {
        $hours = '0' . $hours;
    }

    if ($cents != '0') {
        return $hours . ":" . $mins . ":" . $secs . '.' . $cents;
    } else {
        return $hours . ":" . $mins . ":" . $secs;
    }
}

/**
 * Take the header row of an AICC definition file
 * and returns sequence of columns and a pointer to
 * the sco identifier column.
 *
 * @param string $row AICC header row
 * @param string $mastername AICC sco identifier column
 * @return mixed
 */
function exescorm_get_aicc_columns($row, $mastername='system_id') {
    $tok = strtok(strtolower($row), "\",\n\r");
    $result = new stdClass();
    $result->columns = array();
    $result->mastercol = 0;
    $i = 0;
    while ($tok) {
        if ($tok != '') {
            $result->columns[] = $tok;
            if ($tok == $mastername) {
                $result->mastercol = $i;
            }
            $i++;
        }
        $tok = strtok("\",\n\r");
    }
    return $result;
}

/**
 * Given a colums array return a string containing the regular
 * expression to match the columns in a text row.
 *
 * @param array $column The header columns
 * @param string $remodule The regular expression module for a single column
 * @return string
 */
function exescorm_forge_cols_regexp($columns, $remodule='(".*")?,') {
    $regexp = '/^';
    foreach ($columns as $column) {
        $regexp .= $remodule;
    }
    $regexp = substr($regexp, 0, -1) . '/';
    return $regexp;
}

/**
 * Sets up AICC packages
 * Called whenever package changes
 * @param object $exescorm instance - fields are updated and changes saved into database
 * @return bool
 */
function exescorm_parse_aicc(&$exescorm) {
    global $DB;

    if ($exescorm->exescormtype == EXESCORM_TYPE_AICCURL) {
        return exescorm_aicc_generate_simple_sco($exescorm);
    }
    if (!isset($exescorm->cmid)) {
        $cm = get_coursemodule_from_instance('exescorm', $exescorm->id);
        $exescorm->cmid = $cm->id;
    }
    $context = context_module::instance($exescorm->cmid);

    $fs = get_file_storage();

    $files = $fs->get_area_files($context->id, 'mod_exescorm', 'content', 0, 'sortorder, itemid, filepath, filename', false);

    $version = 'AICC';
    $ids = array();
    $courses = array();
    $extaiccfiles = array('crs', 'des', 'au', 'cst', 'ort', 'pre', 'cmp');

    foreach ($files as $file) {
        $filename = $file->get_filename();
        $ext = substr($filename, strrpos($filename, '.'));
        $extension = strtolower(substr($ext, 1));
        if (in_array($extension, $extaiccfiles)) {
            $id = strtolower(basename($filename, $ext));
            if (!isset($ids[$id])) {
                $ids[$id] = new stdClass();
            }
            $ids[$id]->$extension = $file;
        }
    }

    foreach ($ids as $courseid => $id) {
        if (!isset($courses[$courseid])) {
            $courses[$courseid] = new stdClass();
        }
        if (isset($id->crs)) {
            $contents = $id->crs->get_content();
            $rows = explode("\r\n", $contents);
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (preg_match("/^(.+)=(.+)$/", $row, $matches)) {
                        switch (strtolower(trim($matches[1]))) {
                            case 'course_id':
                                $courses[$courseid]->id = trim($matches[2]);
                            break;
                            case 'course_title':
                                $courses[$courseid]->title = trim($matches[2]);
                            break;
                            case 'version':
                                $courses[$courseid]->version = 'AICC_'.trim($matches[2]);
                            break;
                        }
                    }
                }
            }
        }
        if (isset($id->des)) {
            $contents = $id->des->get_content();
            $rows = explode("\r\n", $contents);
            $columns = exescorm_get_aicc_columns($rows[0]);
            $regexp = exescorm_forge_cols_regexp($columns->columns);
            for ($i = 1; $i < count($rows); $i++) {
                if (preg_match($regexp, $rows[$i], $matches)) {
                    for ($j = 0; $j < count($columns->columns); $j++) {
                        $column = $columns->columns[$j];
                        if (!isset($courses[$courseid]->elements[substr(trim($matches[$columns->mastercol + 1]), 1 , -1)])) {
                            $courses[$courseid]->elements[substr(trim($matches[$columns->mastercol + 1]), 1 , -1)] = new stdClass();
                        }
                        $courses[$courseid]
                            ->elements[substr(trim($matches[$columns->mastercol + 1]), 1 , -1)]
                            ->$column = substr(trim($matches[$j + 1]), 1, -1);
                    }
                }
            }
        }
        if (isset($id->au)) {
            $contents = $id->au->get_content();
            $rows = explode("\r\n", $contents);
            $columns = exescorm_get_aicc_columns($rows[0]);
            $regexp = exescorm_forge_cols_regexp($columns->columns);
            for ($i = 1; $i < count($rows); $i++) {
                if (preg_match($regexp, $rows[$i], $matches)) {
                    for ($j = 0; $j < count($columns->columns); $j++) {
                        $column = $columns->columns[$j];
                        $courses[$courseid]
                            ->elements[substr(trim($matches[$columns->mastercol + 1]), 1, -1)]
                            ->$column = substr(trim($matches[$j + 1]), 1, -1);
                    }
                }
            }
        }
        if (isset($id->cst)) {
            $contents = $id->cst->get_content();
            $rows = explode("\r\n", $contents);
            $columns = exescorm_get_aicc_columns($rows[0], 'block');
            $regexp = exescorm_forge_cols_regexp($columns->columns, '(.+)?,');
            for ($i = 1; $i < count($rows); $i++) {
                if (preg_match($regexp, $rows[$i], $matches)) {
                    for ($j = 0; $j < count($columns->columns); $j++) {
                        if ($j != $columns->mastercol) {
                            $element = substr(trim($matches[$j + 1]), 1 , -1);
                            if (!empty($element)) {
                                $courses[$courseid]
                                    ->elements[$element]
                                    ->parent = substr(trim($matches[$columns->mastercol + 1]), 1, -1);
                            }
                        }
                    }
                }
            }
        }
        if (isset($id->ort)) {
            $contents = $id->ort->get_content();
            $rows = explode("\r\n", $contents);
            $columns = exescorm_get_aicc_columns($rows[0], 'course_element');
            $regexp = exescorm_forge_cols_regexp($columns->columns, '(.+)?,');
            for ($i = 1; $i < count($rows); $i++) {
                if (preg_match($regexp, $rows[$i], $matches)) {
                    for ($j = 0; $j < count($matches) - 1; $j++) {
                        if ($j != $columns->mastercol) {
                            $courses[$courseid]
                                ->elements[substr(trim($matches[$j + 1]), 1, -1)]
                                ->parent = substr(trim($matches[$columns->mastercol + 1]), 1, -1);
                        }
                    }
                }
            }
        }
        if (isset($id->pre)) {
            $contents = $id->pre->get_content();
            $rows = explode("\r\n", $contents);
            $columns = exescorm_get_aicc_columns($rows[0], 'structure_element');
            $regexp = exescorm_forge_cols_regexp($columns->columns, '(.+),');
            for ($i = 1; $i < count($rows); $i++) {
                if (preg_match($regexp, $rows[$i], $matches)) {
                    $elementid = trim($matches[$columns->mastercol + 1]);
                    $elementid = trim(trim($elementid, '"'), "'"); // Remove any quotes.

                    $prereq = trim($matches[2 - $columns->mastercol]);
                    $prereq = trim(trim($prereq, '"'), "'"); // Remove any quotes.

                    $courses[$courseid]->elements[$elementid]->prerequisites = $prereq;
                }
            }
        }
        if (isset($id->cmp)) {
            $contents = $id->cmp->get_content();
            $rows = explode("\r\n", $contents);
        }
    }

    $oldscoes = $DB->get_records('exescorm_scoes', array('exescorm' => $exescorm->id));
    $sortorder = 0;
    $launch = 0;
    if (isset($courses)) {
        foreach ($courses as $course) {
            $sortorder++;
            $sco = new stdClass();
            $sco->identifier = $course->id;
            $sco->exescorm = $exescorm->id;
            $sco->organization = '';
            $sco->title = $course->title;
            $sco->parent = '/';
            $sco->launch = '';
            $sco->exescormtype = '';
            $sco->sortorder = $sortorder;

            if ($ss = $DB->get_record('exescorm_scoes', array('exescorm' => $exescorm->id,
                                                           'identifier' => $sco->identifier))) {
                $id = $ss->id;
                $sco->id = $id;
                $DB->update_record('exescorm_scoes', $sco);
                unset($oldscoes[$id]);
            } else {
                $id = $DB->insert_record('exescorm_scoes', $sco);
            }

            if ($launch == 0) {
                $launch = $id;
            }
            if (isset($course->elements)) {
                foreach ($course->elements as $element) {
                    unset($sco);
                    $sco = new stdClass();
                    $sco->identifier = $element->system_id;
                    $sco->exescorm = $exescorm->id;
                    $sco->organization = $course->id;
                    $sco->title = $element->title;

                    if (!isset($element->parent)) {
                        $sco->parent = '/';
                    } else if (strtolower($element->parent) == 'root') {
                        $sco->parent = $course->id;
                    } else {
                        $sco->parent = $element->parent;
                    }
                    $sco->launch = '';
                    $sco->exescormtype = '';
                    $sco->previous = 0;
                    $sco->next = 0;
                    $id = null;
                    // Is it an Assignable Unit (AU)?
                    if (isset($element->file_name)) {
                        $sco->launch = $element->file_name;
                        $sco->exescormtype = 'sco';
                    }
                    if ($oldscoid = exescorm_array_search('identifier', $sco->identifier, $oldscoes)) {
                        $sco->id = $oldscoid;
                        $DB->update_record('exescorm_scoes', $sco);
                        $id = $oldscoid;
                        $DB->delete_records('exescorm_scoes_data', array('scoid' => $oldscoid));
                        unset($oldscoes[$oldscoid]);
                    } else {
                        $id = $DB->insert_record('exescorm_scoes', $sco);
                    }
                    if (!empty($id)) {
                        $scodata = new stdClass();
                        $scodata->scoid = $id;
                        if (isset($element->web_launch)) {
                            $scodata->name = 'parameters';
                            $scodata->value = $element->web_launch;
                            $dataid = $DB->insert_record('exescorm_scoes_data', $scodata);
                        }
                        if (isset($element->prerequisites)) {
                            $scodata->name = 'prerequisites';
                            $scodata->value = $element->prerequisites;
                            $dataid = $DB->insert_record('exescorm_scoes_data', $scodata);
                        }
                        if (isset($element->max_time_allowed)) {
                            $scodata->name = 'max_time_allowed';
                            $scodata->value = $element->max_time_allowed;
                            $dataid = $DB->insert_record('exescorm_scoes_data', $scodata);
                        }
                        if (isset($element->time_limit_action)) {
                            $scodata->name = 'time_limit_action';
                            $scodata->value = $element->time_limit_action;
                            $dataid = $DB->insert_record('exescorm_scoes_data', $scodata);
                        }
                        if (isset($element->mastery_score)) {
                            $scodata->name = 'mastery_score';
                            $scodata->value = $element->mastery_score;
                            $dataid = $DB->insert_record('exescorm_scoes_data', $scodata);
                        }
                        if (isset($element->core_vendor)) {
                            $scodata->name = 'datafromlms';
                            $scodata->value = preg_replace('/<cr>/i', "\r\n", $element->core_vendor);
                            $dataid = $DB->insert_record('exescorm_scoes_data', $scodata);
                        }
                    }
                    if ($launch == 0) {
                        $launch = $id;
                    }
                }
            }
        }
    }
    if (!empty($oldscoes)) {
        foreach ($oldscoes as $oldsco) {
            $DB->delete_records('exescorm_scoes', array('id' => $oldsco->id));
            $DB->delete_records('exescorm_scoes_track', array('scoid' => $oldsco->id));
        }
    }

    // Find first launchable object.
    $sqlselect = 'exescorm = ? AND '.$DB->sql_isnotempty('exescorm_scoes', 'launch', false, true);
    // We use get_records here as we need to pass a limit in the query that works cross db.
    $scoes = $DB->get_records_select('exescorm_scoes', $sqlselect, array($exescorm->id), 'sortorder', 'id', 0, 1);
    if (!empty($scoes)) {
        $sco = reset($scoes); // We only care about the first record - the above query only returns one.
        $exescorm->launch = $sco->id;
    } else {
        $exescorm->launch = $launch;
    }

    $exescorm->version = 'AICC';

    return true;
}

/**
 * Given a exescormid creates an AICC Session record to allow HACP
 *
 * @param int $exescormid - id from exescorm table
 * @return string hacpsession
 */
function exescorm_aicc_get_hacp_session($exescormid) {
    global $USER, $DB, $SESSION;
    $cfgexescorm = get_config('exescorm');
    if (empty($cfgexescorm->allowaicchacp)) {
        return false;
    }
    $now = time();

    $hacpsession = $SESSION->exescorm;
    $hacpsession->exescormid = $exescormid;
    $hacpsession->hacpsession = random_string(20);
    $hacpsession->userid = $USER->id;
    $hacpsession->timecreated = $now;
    $hacpsession->timemodified = $now;
    $DB->insert_record('exescorm_aicc_session', $hacpsession);

    return $hacpsession->hacpsession;
}

/**
 * Check the hacp_session for whether it is valid.
 *
 * @param string $hacpsession The hacpsession value to check (optional). Normally leave this blank
 *      and this function will do required_param('sesskey', ...).
 * @return mixed - false if invalid, otherwise returns record from exescorm_aicc_session table.
 */
function exescorm_aicc_confirm_hacp_session($hacpsession) {
    global $DB;
    $cfgexescorm = get_config('exescorm');
    if (empty($cfgexescorm->allowaicchacp)) {
        return false;
    }
    $time = time() - ($cfgexescorm->aicchacptimeout * 60);
    $sql = "hacpsession = ? AND timemodified > ?";
    $hacpsession = $DB->get_record_select('exescorm_aicc_session', $sql, array($hacpsession, $time));
    if (!empty($hacpsession)) { // Update timemodified as this is still an active session - resets the timeout.
        $hacpsession->timemodified = time();
        $DB->update_record('exescorm_aicc_session', $hacpsession);
    }
    return $hacpsession;
}

/**
 * generate a simple single activity AICC object
 * structure to wrap around and externally linked
 * AICC package URL
 *
 * @param object $exescorm package record
 */
function exescorm_aicc_generate_simple_sco($exescorm) {
    global $DB;
    // Find the oldest one.
    $scos = $DB->get_records('exescorm_scoes', array('exescorm' => $exescorm->id), 'id');
    if (!empty($scos)) {
        $sco = array_shift($scos);
    } else {
        $sco = new stdClass();
    }
    // Get rid of old ones.
    foreach ($scos as $oldsco) {
        $DB->delete_records('exescorm_scoes', array('id' => $oldsco->id));
        $DB->delete_records('exescorm_scoes_track', array('scoid' => $oldsco->id));
    }

    $sco->identifier = 'A1';
    $sco->exescorm = $exescorm->id;
    $sco->organization = '';
    $sco->title = $exescorm->name;
    $sco->parent = '/';
    // Add the HACP signal to the activity launcher.
    if (preg_match('/\?/', $exescorm->reference)) {
        $sco->launch = $exescorm->reference.'&CMI=HACP';
    } else {
        $sco->launch = $exescorm->reference.'?CMI=HACP';
    }
    $sco->exescormtype = 'sco';
    if (isset($sco->id)) {
        $DB->update_record('exescorm_scoes', $sco);
        $id = $sco->id;
    } else {
        $id = $DB->insert_record('exescorm_scoes', $sco);
    }
    return $id;
}

/**
 * Sets up $userdata array and default values for AICC package.
 *
 * @param stdClass $userdata an empty stdClass variable that should be set up with user values
 * @param object $exescorm package record
 * @param string $scoid SCO Id
 * @param string $attempt attempt number for the user
 * @param string $mode exescorm display mode type
 * @return array The default values that should be used for AICC package
 */
function get_exescorm_default (&$userdata, $exescorm, $scoid, $attempt, $mode) {
    global $USER;
    $aiccuserid = get_config('exescorm', 'aiccuserid');
    if (!empty($aiccuserid)) {
        $userdata->student_id = $USER->id;
    } else {
        $userdata->student_id = $USER->username;
    }
    $userdata->student_name = $USER->lastname .', '. $USER->firstname;

    if ($usertrack = exescorm_get_tracks($scoid, $USER->id, $attempt)) {
        foreach ($usertrack as $key => $value) {
            $userdata->$key = $value;
        }
    } else {
        $userdata->status = '';
        $userdata->score_raw = '';
    }

    if ($scodatas = exescorm_get_sco($scoid, EXESCORM_SCO_DATA)) {
        foreach ($scodatas as $key => $value) {
            $userdata->$key = $value;
        }
    } else {
        throw new \moodle_exception('cannotfindsco', 'exescorm');
    }
    if (!$sco = exescorm_get_sco($scoid)) {
        throw new \moodle_exception('cannotfindsco', 'exescorm');
    }

    $userdata->mode = 'normal';
    if (!empty($mode)) {
        $userdata->mode = $mode;
    }
    if ($userdata->mode == 'normal') {
        $userdata->credit = 'credit';
    } else {
        $userdata->credit = 'no-credit';
    }

    if (isset($userdata->status)) {
        if ($userdata->status == '') {
            $userdata->entry = 'ab-initio';
        } else {
            if (isset($userdata->{'cmi.core.exit'}) && ($userdata->{'cmi.core.exit'} == 'suspend')) {
                $userdata->entry = 'resume';
            } else {
                $userdata->entry = '';
            }
        }
    }

    $def = array();
    $def['cmi.core.student_id'] = $userdata->student_id;
    $def['cmi.core.student_name'] = $userdata->student_name;
    $def['cmi.core.credit'] = $userdata->credit;
    $def['cmi.core.entry'] = $userdata->entry;
    $def['cmi.launch_data'] = exescorm_isset($userdata, 'datafromlms');
    $def['cmi.core.lesson_mode'] = $userdata->mode;
    $def['cmi.student_data.attempt_number'] = exescorm_isset($userdata, 'cmi.student_data.attempt_number');
    $def['cmi.student_data.mastery_score'] = exescorm_isset($userdata, 'mastery_score');
    $def['cmi.student_data.max_time_allowed'] = exescorm_isset($userdata, 'max_time_allowed');
    $def['cmi.student_data.time_limit_action'] = exescorm_isset($userdata, 'time_limit_action');
    $def['cmi.student_data.tries_during_lesson'] = exescorm_isset($userdata, 'cmi.student_data.tries_during_lesson');

    $def['cmi.core.lesson_location'] = exescorm_isset($userdata, 'cmi.core.lesson_location');
    $def['cmi.core.lesson_status'] = exescorm_isset($userdata, 'cmi.core.lesson_status');
    $def['cmi.core.exit'] = exescorm_isset($userdata, 'cmi.core.exit');
    $def['cmi.core.score.raw'] = exescorm_isset($userdata, 'cmi.core.score.raw');
    $def['cmi.core.score.max'] = exescorm_isset($userdata, 'cmi.core.score.max');
    $def['cmi.core.score.min'] = exescorm_isset($userdata, 'cmi.core.score.min');
    $def['cmi.core.total_time'] = exescorm_isset($userdata, 'cmi.core.total_time', '00:00:00');
    $def['cmi.suspend_data'] = exescorm_isset($userdata, 'cmi.suspend_data');
    $def['cmi.comments'] = exescorm_isset($userdata, 'cmi.comments');

    return $def;
}
