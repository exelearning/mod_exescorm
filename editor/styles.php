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
 * Serve files from an admin-uploaded eXeLearning style package.
 *
 * URL layout: `/mod/exescorm/editor/styles.php/{slug}/{filepath}`.
 *
 * Files are stored outside the plugin source tree at
 * `{dataroot}/mod_exescorm/styles/{slug}/`; this endpoint is the only way
 * the embedded editor reaches them. Access requires `mod/exescorm:view`
 * so teachers/students/admins can load the CSS/assets the editor
 * references after an admin has approved the style.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState

require('../../../config.php');

use mod_exescorm\local\styles_service;

$pathinfo = !empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO']
    : (!empty($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '');

if (empty($pathinfo) && !empty($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    $qpos = strpos($uri, '?');
    if ($qpos !== false) {
        $uri = substr($uri, 0, $qpos);
    }
    $marker = 'styles.php/';
    $mpos = strpos($uri, $marker);
    if ($mpos !== false) {
        $pathinfo = '/' . substr($uri, $mpos + strlen($marker));
    }
}

if (empty($pathinfo)) {
    send_header_404();
    die('Not found');
}

$parts = explode('/', ltrim($pathinfo, '/'), 2);
if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
    send_header_404();
    die('Invalid path');
}

$slug = clean_param($parts[0], PARAM_PATH);
$file = clean_param($parts[1], PARAM_PATH);
$file = ltrim($file, '/');

if (strpos($file, '..') !== false || strpos($slug, '..') !== false) {
    send_header_404();
    die('File not found');
}

// Require a real Moodle session. The embedded editor is loaded inside an
// authenticated admin/teacher context; we still gate on mod/exescorm:view
// via the system context since admin-approved styles are site-wide.
require_login(null, false);

$context = \context_system::instance();
// Any user who can view at least one exescorm activity should be able to
// load the CSS. Check the module capability at system level so the
// admin preview on /mod/exescorm/admin/styles.php also works.
if (!has_capability('mod/exescorm:view', $context)
    && !has_capability('mod/exescorm:manageembeddededitor', $context)) {
    send_header_404();
    die('Forbidden');
}

// Registry gate: refuse serving slugs that were never installed.
$registry = styles_service::get_registry();
if (!isset($registry['uploaded'][styles_service::normalize_slug($slug)])) {
    send_header_404();
    die('Style not registered');
}

$styledir = styles_service::get_style_dir($slug);
$fullpath = realpath($styledir . '/' . $file);
$baseprefix = realpath($styledir);

if ($baseprefix === false || $fullpath === false
    || strpos($fullpath, $baseprefix . DIRECTORY_SEPARATOR) !== 0) {
    send_header_404();
    die('File not found');
}

if (!is_file($fullpath) || !is_readable($fullpath)) {
    send_header_404();
    die('File not found');
}

send_file($fullpath, basename($fullpath), null, 0, false, false, '', false);
