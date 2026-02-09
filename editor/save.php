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
 * AJAX endpoint for saving SCORM packages from the embedded eXeLearning editor.
 *
 * Receives an uploaded SCORM ZIP file, saves it to the package filearea,
 * and calls exescorm_parse() to extract content and parse the manifest.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require('../../../config.php');
require_once($CFG->dirroot . '/mod/exescorm/lib.php');
require_once($CFG->dirroot . '/mod/exescorm/locallib.php');

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('exescorm', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$exescorm = $DB->get_record('exescorm', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
require_sesskey();
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

header('Content-Type: application/json; charset=utf-8');

try {
    // Check that a file was uploaded.
    if (empty($_FILES['package'])) {
        throw new moodle_exception('nofile', 'error');
    }

    $uploadedfile = $_FILES['package'];
    if ($uploadedfile['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('uploadproblem', 'error');
    }

    $fs = get_file_storage();

    // Update revision and timestamp.
    $exescorm->timemodified = time();

    // Clean old package files.
    $fs->delete_area_files($context->id, 'mod_exescorm', 'package');

    // Save the uploaded file as the new package (itemid=0 as exescorm expects).
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'mod_exescorm',
        'filearea' => 'package',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => clean_filename($uploadedfile['name']),
        'userid' => $USER->id,
        'source' => clean_filename($uploadedfile['name']),
        'author' => fullname($USER),
        'license' => 'unknown',
    ];

    $package = $fs->create_file_from_pathname($fileinfo, $uploadedfile['tmp_name']);

    // Store filename as reference.
    $exescorm->reference = clean_filename($uploadedfile['name']);
    $DB->update_record('exescorm', $exescorm);

    // Parse the SCORM package: extracts ZIP to content, finds imsmanifest.xml,
    // parses SCOs, and sets $exescorm->version.
    exescorm_parse($exescorm, true);

    // Re-read to get updated version/data after parse.
    $exescorm = $DB->get_record('exescorm', ['id' => $exescorm->id]);

    echo json_encode([
        'success' => true,
        'revision' => $exescorm->timemodified,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
