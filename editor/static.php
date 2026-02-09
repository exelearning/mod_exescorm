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

$file = required_param('file', PARAM_PATH);
$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);

// Sanitize the file path to prevent directory traversal.
$file = clean_param($file, PARAM_PATH);
$file = ltrim($file, '/');

// Prevent directory traversal.
if (strpos($file, '..') !== false) {
    send_header_404();
    die('File not found');
}

$staticdir = $CFG->dirroot . '/mod/exescorm/dist/static';
$filepath = realpath($staticdir . '/' . $file);

// Ensure the resolved path is within the static directory.
if ($filepath === false || strpos($filepath, realpath($staticdir)) !== 0) {
    send_header_404();
    die('File not found');
}

if (!is_file($filepath)) {
    send_header_404();
    die('File not found');
}

// Determine content type.
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
];

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
$contenttype = isset($mimetypes[$ext]) ? $mimetypes[$ext] : 'application/octet-stream';

// Send the file with appropriate headers.
header('Content-Type: ' . $contenttype);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=604800'); // Cache for 1 week.
header('X-Frame-Options: SAMEORIGIN');

readfile($filepath);
