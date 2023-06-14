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
 * This file doesn't use login. It's an open callback (webhook) for Docusign Connect events.
 *
 * It uses AccountId verification (low security as it is not a secret) and, if set in plugin
 * configuration, HMAC verification through share secret HMAC keys.
 *
 * @package     mod_docusign
 * @copyright   2022 3&Punt
 * @author      Juan Carrera <juan@treipunt.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_exescorm\exeonline\token_manager;

// @codingStandardsIgnoreLine as we validate via account id, HMAC keys, etc.
require_once(__DIR__ . '/../../config.php');
global $DB, $CFG;

require_once($CFG->dirroot.'/mod/exescorm/locallib.php');

// TODO: Add a security measure to prevent unauthorized access adding ip whitelist.

// Get ode_data param sended by eXeLearning Online by POST and
// x-www-form-urlencoded we'll use Moodle's params functions.
$odedata = required_param('ode_data', PARAM_RAW);
// We'll set generic error here. We'll change elements as needed.
$resultmsg = [
    'status' => '1',
    'description' => 'KO. Not defined',
    'ode_id' => $data->ode_id ?? '',
    'ode_uri' => '',
];
header('Content-Type: application/json; charset=utf-8');

$data = json_decode($odedata);
if (is_null($data)) {
    $resultmsg['description'] = 'KO. Couldn\'t decode JSON.';
    echo json_encode($resultmsg);
    exit(1);
}

// Validate JWT.
$payload = token_manager::validate_jwt_token($data->jwt_token ?? '');
if (is_string($payload)) {
    // Error decoding.
    $resultmsg['description'] = 'KO. ' .  $payload;
    echo json_encode($resultmsg);
    exit(1);
}

if (! $cm = get_coursemodule_from_id('exescorm', $payload->cmid, 0, true)) {
    $resultmsg['description'] = 'KO. Invalid ode_id.';
    echo json_encode($resultmsg);
    exit(1);
}

if (! $exescorm = $DB->get_record('exescorm', ['id' => $cm->instance])) {
    $resultmsg['description'] = 'KO. Invalid ode_id.';
    echo json_encode($resultmsg);
    exit(1);
}
// Check user and permission.
$user = $DB->get_record('user',
    [
        'id' => $payload->userid,
        'confirmed' => 1,
        'suspended' => 0,
        'deleted' => 0,
    ]
);
if (! $user) {
    $resultmsg['description'] = 'KO. Invalid user.';
    echo json_encode($resultmsg);
    exit(1);
}
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);
if (! has_capability('mod/exescorm:addinstance', $context, $user)) {
    $resultmsg['description'] = 'KO. Forbidden.';
    echo json_encode($resultmsg);
    exit(1);
}
$content = base64_decode($data->ode_file);
if ($content === false) {
    $resultmsg['description'] = 'KO. Error decoding file content.';
    echo json_encode($resultmsg);
    exit(1);
}

// Check size of content.
$maxbytes = get_max_upload_file_size($CFG->maxbytes, $course->maxbytes);
if (strlen($content) > $maxbytes) {
    $resultmsg['status'] = '2';
    $resultmsg['description'] = 'KO. Exceeds the maximum size in Moodle.';
    echo json_encode($resultmsg);
    exit(1);
}

$fs = get_file_storage();
$fs->delete_area_files($context->id, 'mod_exescorm', 'temppackage');
// Create new file from content. We'll use a temporary area to avoid collisions until package is validated.
$fileinfo = [
    'contextid' => $context->id,
    'component' => 'mod_exescorm',
    'filearea' => 'temppackage',
    'itemid' => 0,
    'filepath' => '/',
    'filename' => $data->ode_filename,
    'userid' => $user->id,
    'source' => $data->ode_filename,
    'author' => fullname($user),
    'license' => 'unknown',
];
$tmpfile = $fs->create_file_from_string($fileinfo, $content);
// Validate package.
$errors = exescorm_validate_package($tmpfile);
if (!empty($errors)) {
    $tmpfile->delete();
    $resultmsg['description'] = 'KO. Invalid package.';
    echo json_encode($resultmsg);
    exit(1);
}
// Package is valid so delete files from package area and move the new one.
$fs->delete_area_files($context->id, 'mod_exescorm', 'package');
$fileinfo['filearea'] = 'package';
$file = $fs->create_file_from_storedfile($fileinfo, $tmpfile);
$fs->delete_area_files($context->id, 'mod_exescorm', 'temppackage');
// Set filename as new instance reference.
$exescorm->reference = $file->get_filename();
$DB->update_record('exescorm', $exescorm);

exescorm_parse($exescorm, true);

// Prepare OK response.
$resultmsg['status'] = '0';
$resultmsg['description'] = 'OK';
$resultmsg['ode_uri'] = $payload->returnurl;

$response = json_encode($resultmsg);
header('Content-Length: ' . strlen($response));
echo $response;
exit(0);
