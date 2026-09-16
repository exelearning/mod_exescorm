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
 * Receives an uploaded SCORM ZIP file, checks it is a valid SCORM package that
 * carries the eXeLearning source the editor needs to re-open it, saves it to the
 * package filearea and calls exescorm_parse() to extract content and parse the
 * manifest.
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
    if (empty($_FILES['package'])) {
        throw new moodle_exception('nofile', 'error');
    }

    $uploadedfile = $_FILES['package'];
    if ((int)$uploadedfile['error'] !== UPLOAD_ERR_OK) {
        throw new moodle_exception('uploadproblem', 'error');
    }

    if (empty($uploadedfile['tmp_name']) || !is_uploaded_file($uploadedfile['tmp_name'])) {
        throw new moodle_exception('uploadproblem', 'error');
    }

    $filename = clean_filename($uploadedfile['name'] ?? 'package.zip');
    if ($filename === '') {
        $filename = 'package.zip';
    }
    if (core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'zip') {
        throw new moodle_exception('uploadproblem', 'error', '', null, 'Uploaded file must be a ZIP package');
    }

    $fs = get_file_storage();

    // Stage the upload in the temppackage area, as set_ode.php does, so the
    // package the activity already has is untouched if this one is refused.
    $fs->delete_area_files($context->id, 'mod_exescorm', 'temppackage');
    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'mod_exescorm',
        'filearea' => 'temppackage',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $filename,
        'userid' => $USER->id,
        'source' => $filename,
        'author' => fullname($USER),
        'license' => 'unknown',
    ];
    $tmpfile = $fs->create_file_from_pathname($fileinfo, $uploadedfile['tmp_name']);

    // The editor re-opens this exact package on the next edit (bridge.js,
    // importPackageFromMoodle), so besides being a valid SCORM package it must
    // carry the eXeLearning source. mod_form.php skips validation for the
    // embedded type, so this is the only gate on that path. The editor exports
    // with forceEditableSource so the source check never fires; if it ever
    // does, refusing the save is what keeps the activity editable.
    // See https://github.com/exelearning/exelearning/issues/2415.
    $errors = exescorm_validate_package($tmpfile);
    if (empty($errors)) {
        $filelist = $tmpfile->list_files(get_file_packer('application/zip'));
        if (!\mod_exescorm\exescorm_package::has_editable_source($filelist)) {
            $errors['packagefile'] = get_string('nosourcetosave', 'mod_exescorm');
        }
    }
    if (!empty($errors)) {
        $tmpfile->delete();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => reset($errors)]);
        exit;
    }

    // Overwrite current package.
    $fs->delete_area_files($context->id, 'mod_exescorm', 'package');
    $fileinfo['filearea'] = 'package';
    $fs->create_file_from_storedfile($fileinfo, $tmpfile);
    $fs->delete_area_files($context->id, 'mod_exescorm', 'temppackage');

    // Keep package name in SCORM reference and trigger re-parse.
    $exescorm->timemodified = time();
    $exescorm->reference = $filename;
    $DB->update_record('exescorm', $exescorm);
    exescorm_parse($exescorm, true);

    $updated = $DB->get_record('exescorm', ['id' => $exescorm->id], 'id,timemodified,version', MUST_EXIST);

    echo json_encode([
        'success' => true,
        'revision' => (int)$updated->timemodified,
        'version' => $updated->version,
    ]);
} catch (Exception $e) {
    debugging('mod_exescorm editor save failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => get_string('error'),
    ]);
}
