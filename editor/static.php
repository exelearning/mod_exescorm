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
 * Serve static files from the embedded eXeLearning editor (dist/static/).
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot . '/mod/exescorm/lib.php');

// Support both slash arguments (PATH_INFO) and query params.
// Slash arguments: /static.php/{cmid}/{filepath}
// Query params: /static.php?id={cmid}&file={filepath}
$pathinfo = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO']
    : (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '');

// Fallback: parse from REQUEST_URI when PATH_INFO is not available.
if (empty($pathinfo) && !empty($_SERVER['REQUEST_URI'])) {
    $requesturi = $_SERVER['REQUEST_URI'];
    $qpos = strpos($requesturi, '?');
    if ($qpos !== false) {
        $requesturi = substr($requesturi, 0, $qpos);
    }
    $marker = 'static.php/';
    $mpos = strpos($requesturi, $marker);
    if ($mpos !== false) {
        $pathinfo = '/' . substr($requesturi, $mpos + strlen($marker));
    }
}

if (!empty($pathinfo)) {
    $parts = explode('/', ltrim($pathinfo, '/'), 2);
    if (count($parts) < 2 || !is_numeric($parts[0]) || empty($parts[1])) {
        send_header_404();
        die('Invalid path');
    }
    $id = (int)$parts[0];
    $file = $parts[1];
} else {
    $file = required_param('file', PARAM_PATH);
    $id = required_param('id', PARAM_INT);
}

$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

// Sanitize the file path to prevent directory traversal.
$file = clean_param($file, PARAM_PATH);
$file = ltrim($file, '/');

if (strpos($file, '..') !== false) {
    send_header_404();
    die('File not found');
}

$mimetypes = [
    'html' => 'text/html',
    'htm' => 'text/html',
    'css' => 'text/css',
    'js' => 'application/javascript',
    'mjs' => 'application/javascript',
    'json' => 'application/json',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'webp' => 'image/webp',
    'mp3' => 'audio/mpeg',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'ogg' => 'audio/ogg',
    'wav' => 'audio/wav',
    'pdf' => 'application/pdf',
    'xml' => 'application/xml',
    'wasm' => 'application/wasm',
    'zip' => 'application/zip',
    'md' => 'text/plain',
];

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$contenttype = isset($mimetypes[$ext]) ? $mimetypes[$ext] : 'application/octet-stream';

// Release session lock early so parallel requests are not blocked.
\core\session\manager::write_close();

if (exescorm_embedded_editor_uses_local_assets()) {
    $staticdir = exescorm_get_embedded_editor_local_static_dir();
    $filepath = realpath($staticdir . '/' . $file);
    $staticroot = realpath($staticdir);

    // Ensure the resolved path is within the static directory.
    if ($filepath === false || $staticroot === false || strpos($filepath, $staticroot) !== 0) {
        send_header_404();
        die('File not found');
    }

    if (!is_file($filepath)) {
        send_header_404();
        die('File not found');
    }

    header('Content-Type: ' . $contenttype);
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: public, max-age=604800'); // Cache for 1 week.
    header('X-Frame-Options: SAMEORIGIN');

    if (basename($file) === 'preview-sw.js') {
        header('Service-Worker-Allowed: /');
    }

    readfile($filepath);
    exit;
}

$remoteurl = exescorm_get_embedded_editor_remote_asset_url($file);
$content = download_file_content($remoteurl);
if ($content === false || $content === null) {
    send_header_404();
    die('Could not retrieve editor asset: ' . $file);
}

header('Content-Type: ' . $contenttype);
header('Content-Length: ' . strlen($content));
header('Cache-Control: public, max-age=604800'); // Cache for 1 week.
header('X-Frame-Options: SAMEORIGIN');

if (basename($file) === 'preview-sw.js') {
    header('Service-Worker-Allowed: /');
}

echo $content;
