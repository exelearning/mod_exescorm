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
 * Defines the version of exescorm_interactions
 * @package   exescormreport
 * @subpackage interactions
 * @author    Dan Marsden and Ankit Kumar Agarwal
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
class mod_exescorm_report_interactions_settings extends moodleform {

    public function definition() {
        global $COURSE;
        $mform    =& $this->_form;
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'preferencespage', get_string('preferencespage', 'mod_exescorm'));

        $options = [];
        if ($COURSE->id != SITEID) {
            $options[EXESCORM_REPORT_ATTEMPTS_ALL_STUDENTS] = get_string('optallstudents', 'mod_exescorm');
            $options[EXESCORM_REPORT_ATTEMPTS_STUDENTS_WITH] = get_string('optattemptsonly', 'mod_exescorm');
            $options[EXESCORM_REPORT_ATTEMPTS_STUDENTS_WITH_NO] = get_string('optnoattemptsonly', 'mod_exescorm');
        }
        $mform->addElement('select', 'attemptsmode', get_string('show', 'mod_exescorm'), $options);
        $mform->addElement('advcheckbox', 'qtext', '', get_string('summaryofquestiontext', 'exescormreport_interactions'));
        $mform->addElement('advcheckbox', 'resp', '', get_string('summaryofresponse', 'exescormreport_interactions'));
        $mform->addElement('advcheckbox', 'right', '', get_string('summaryofrightanswer', 'exescormreport_interactions'));
        $mform->addElement('advcheckbox', 'result', '', get_string('summaryofresult', 'exescormreport_interactions'));

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'preferencesuser', get_string('preferencesuser', 'mod_exescorm'));

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'mod_exescorm'));
        $mform->setType('pagesize', PARAM_INT);

        $this->add_action_buttons(false, get_string('savepreferences'));
    }
}
