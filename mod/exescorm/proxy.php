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

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

$endpoint = required_param('endpoint', PARAM_TEXT);
$exeonlinebaseuri = get_config('exescorm', 'exeonlinebaseuri');

if (!$exeonlinebaseuri) {
    throw new moodle_exception('missingconfig', 'mod_exescorm', '', 'exeonlinebaseuri');
}

$url = $exeonlinebaseuri . '/' . ltrim($endpoint, '/');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . get_config('exescorm', 'hmackey1')
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    throw new moodle_exception('proxyerror', 'mod_exescorm', '', $error_msg);
}

curl_close($ch);

http_response_code($httpcode);
header('Content-Type: application/json');
echo $response;
