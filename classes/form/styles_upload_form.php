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
 * Style upload form for the mod_exescorm admin page.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use mod_exescorm\local\styles_service;

/**
 * Moodle filemanager form accepting one or more eXeLearning style ZIPs.
 */
class styles_upload_form extends \moodleform {

    /**
     * Internal form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filemanager', 'styles_zip',
            get_string('stylesupload_label', 'mod_exescorm'),
            null,
            [
                'subdirs'        => 0,
                'maxbytes'       => styles_service::get_max_zip_size(),
                'areamaxbytes'   => styles_service::get_max_zip_size() * 10,
                'maxfiles'       => -1,
                'accepted_types' => ['.zip'],
            ]
        );
        $mform->addRule('styles_zip',
            get_string('stylesupload_failed', 'mod_exescorm'), 'required');
        $mform->addElement('static', 'styles_zip_help', '',
            get_string('stylesupload_hint', 'mod_exescorm',
                display_size(styles_service::get_max_zip_size())));

        $this->add_action_buttons(false, get_string('stylesupload_submit', 'mod_exescorm'));
    }
}
