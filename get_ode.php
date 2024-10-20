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

 use mod_exescorm\exeonline\token_manager;

// @codingStandardsIgnoreLine as we validate via account id, HMAC keys, etc.
require_once(__DIR__ . '/../../config.php');

// TODO: Add a security measure to prevent unauthorized access adding ip whitelist.

// Get ode_data param sended by eXeLearning Online by POST and
// x-www-form-urlencoded we'll use Moodle's params functions.
$odedata = required_param('ode_data', PARAM_RAW);

// We'll set generic error here. We'll change elements as needed.
$errormsg = [
    'status' => '1',
    'description' => 'KO. Not defined',
    'ode_id' => $data->ode_id ?? '',
    'ode_uri' => '',
];
header('Content-Type: application/json; charset=utf-8');

$data = json_decode($odedata);
if (is_null($data)) {
    $resultmsg['description'] = 'KO. Couldn\'t decode JSON.';
    echo json_encode($errormsg);
    exit(1);
}

$payload = token_manager::validate_jwt_token($data->jwt_token ?? '');

if (is_string($payload)) {
    // Error decoding.
    $errormsg['description'] = 'KO. ' .  $payload;
    echo json_encode($errormsg);
    exit(1);
}

if ($payload->pkgtype !== 'scorm') {
    $resultmsg['description'] = 'KO. Invalid package type. Scorm required.';
    echo json_encode($resultmsg);
    exit(1);
}

// Validate payload course module and userid.
if (! $cm = get_coursemodule_from_id('exescorm', $payload->cmid, 0, true)) {
    $resultmsg['description'] = 'KO. Invalid ode_id.';
    echo json_encode($errormsg);
    exit(1);
}

if (! $exescorm = $DB->get_record('exescorm', ['id' => $cm->instance])) {
    $resultmsg['description'] = 'KO. Invalid ode_id.';
    echo json_encode($errormsg);
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
    echo json_encode($errormsg);
    exit(1);
}

$context = context_module::instance($cm->id);
if (! has_capability('mod/exescorm:addinstance', $context, $user)) {
    $resultmsg['description'] = 'KO. Forbidden.';
    echo json_encode($errormsg);
    exit(1);
}

// Lookup area files.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, '', false);
$file = reset($files);
if ($file) {
    $contents = $file->get_content();
} else {
    $resultmsg['description'] = 'KO. Not found.';
    echo json_encode($errormsg);
    exit(1);
}
// Prepare response OK.
$response = [
    'status' => '0',
    'ode_id' => $payload->cmid,
    'ode_filename' => $file->get_filename(),
    'ode_file' => base64_encode($contents),
    'ode_uri' => $payload->returnurl,
    'ode_user' => $payload->userid,
];

// Send it.
$response = json_encode($response);
if ($response !== false) {
    header('Content-Length: ' . strlen($response));
    echo $response;
    exit(0);
}

// Error encoding json.
echo json_encode($errormsg);
exit(1);
