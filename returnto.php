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
 * Landing page used as the eXeLearning return url when the final destination is outside the module.
 *
 * eXeLearning only accepts return urls living under /mod/exescorm, so this script is the entry point
 * that lets the "Edit on eXeLearning and return to course" button reach the course page. All the
 * routing rules live in exescorm_redirector, which is the side that builds this url in the first
 * place; this file is just the web-addressable dispatcher Moodle requires.
 *
 * @package     mod_exescorm
 * @copyright   2026 eXeLearning
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_exescorm\exeonline\exescorm_redirector;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT); // Course module ID.
// PARAM_LOCALURL empties anything pointing outside this Moodle, so this can't become an open redirect.
$returnurl = optional_param(exescorm_redirector::RETURNTO_PARAM, '', PARAM_LOCALURL);

$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/mod/exescorm/returnto.php', ['id' => $cm->id]));

require_login($course, false, $cm);

redirect(exescorm_redirector::resolve_returnto_url($returnurl, $course));
