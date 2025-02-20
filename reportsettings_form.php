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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
class mod_exescorm_report_settings extends moodleform {

    public function definition() {
        global $COURSE;
        $mform =& $this->_form;
        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'preferencespage', get_string('preferencespage', 'mod_exescorm'));

        $options = [];
        if ($COURSE->id != SITEID) {
            $options[EXESCORM_REPORT_ATTEMPTS_ALL_STUDENTS] = get_string('optallstudents', 'mod_exescorm');
            $options[EXESCORM_REPORT_ATTEMPTS_STUDENTS_WITH] = get_string('optattemptsonly', 'mod_exescorm');
            $options[EXESCORM_REPORT_ATTEMPTS_STUDENTS_WITH_NO] = get_string('optnoattemptsonly', 'mod_exescorm');
        }
        $mform->addElement('select', 'attemptsmode', get_string('show', 'mod_exescorm'), $options);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'preferencesuser', get_string('preferencesuser', 'mod_exescorm'));

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'mod_exescorm'));
        $mform->setType('pagesize', PARAM_INT);

        $mform->addElement('selectyesno', 'detailedrep', get_string('details', 'mod_exescorm'));

        $this->add_action_buttons(false, get_string('savepreferences'));
    }
}
