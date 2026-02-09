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
 * Embedded eXeLearning editor bootstrap page.
 *
 * Loads the static editor and injects Moodle configuration so the editor
 * can communicate with Moodle (load/save packages).
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot . '/mod/exescorm/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID.

$cm = get_coursemodule_from_id('exescorm', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$exescorm = $DB->get_record('exescorm', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $context);
require_sesskey();

// Verify the embedded editor is available.
$editorpath = $CFG->dirroot . '/mod/exescorm/dist/static/index.html';
if (!file_exists($editorpath)) {
    throw new moodle_exception('editormissing', 'mod_exescorm');
}

// Build the package URL for the editor to import.
$packageurl = exescorm_get_package_url($exescorm, $context);

// Build the save endpoint URL.
$saveurl = new moodle_url('/mod/exescorm/editor/save.php');

// Base URL pointing directly to the static editor directory (web-accessible).
$editorbaseurl = $CFG->wwwroot . '/mod/exescorm/dist/static';

// Read the editor template.
$html = file_get_contents($editorpath);

// Inject <base> tag pointing directly to the static directory.
$basetag = '<base href="' . htmlspecialchars($editorbaseurl, ENT_QUOTES, 'UTF-8') . '/">';
$html = preg_replace('/(<head[^>]*>)/i', '$1' . $basetag, $html);

// Fix explicit "./" relative paths in attributes (same pattern used by WP and Omeka-S).
$html = preg_replace(
    '/(?<=["\'])\.\//',
    htmlspecialchars($editorbaseurl, ENT_QUOTES, 'UTF-8') . '/',
    $html
);

// Build Moodle configuration for the bridge script.
$moodleconfig = json_encode([
    'cmid' => $cm->id,
    'contextid' => $context->id,
    'sesskey' => sesskey(),
    'packageUrl' => $packageurl ? $packageurl->out(false) : '',
    'saveUrl' => $saveurl->out(false),
    'activityName' => format_string($exescorm->name),
    'wwwroot' => $CFG->wwwroot,
    'editorBaseUrl' => $editorbaseurl,
]);

$embeddingconfig = json_encode([
    'basePath' => $editorbaseurl,
    'parentOrigin' => $CFG->wwwroot,
    'trustedOrigins' => [$CFG->wwwroot],
    'hideUI' => [
        'fileMenu' => true,
        'saveButton' => true,
        'userMenu' => true,
    ],
    'platform' => 'moodle',
    'pluginVersion' => get_config('mod_exescorm', 'version'),
]);

// Inject configuration scripts before </head>.
$configscript = <<<EOT
<script>
    window.__MOODLE_EXE_CONFIG__ = $moodleconfig;
    window.__EXE_EMBEDDING_CONFIG__ = $embeddingconfig;
</script>
EOT;

// Inject bridge script before </body>.
$bridgescript = '<script src="' . $CFG->wwwroot . '/mod/exescorm/amd/src/moodle_exe_bridge.js"></script>';

$html = str_replace('</head>', $configscript . "\n" . '</head>', $html);
$html = str_replace('</body>', $bridgescript . "\n" . '</body>', $html);

// Output the processed HTML.
header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: SAMEORIGIN');
echo $html;
