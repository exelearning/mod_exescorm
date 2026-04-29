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
 * Admin page for managing eXeLearning styles exposed to the embedded editor.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use mod_exescorm\local\styles_service;

admin_externalpage_setup('mod_exescorm_styles');

$context = \context_system::instance();
require_capability('moodle/site:config', $context);
require_capability('mod/exescorm:manageembeddededitor', $context);

$action = optional_param('action', '', PARAM_ALPHA);
$returnurl = new moodle_url('/mod/exescorm/admin/styles.php');
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'modsettingexescorm']);

// --------------------------------------------------------------------
// Toggle/delete actions use GET + sesskey (simple URL handlers).
// Upload happens inline in the plugin settings page.
// --------------------------------------------------------------------
if ($action !== '') {
    require_sesskey();
    switch ($action) {
        case 'toggleuploaded':
            $slug = required_param('slug', PARAM_TEXT);
            $enabled = (bool) required_param('enabled', PARAM_INT);
            styles_service::set_uploaded_enabled($slug, $enabled);
            redirect($returnurl);
            break;

        case 'togglebuiltin':
            $id = required_param('id', PARAM_TEXT);
            $enabled = (bool) required_param('enabled', PARAM_INT);
            styles_service::set_builtin_enabled($id, $enabled);
            redirect($returnurl);
            break;

        case 'delete':
            $slug = required_param('slug', PARAM_TEXT);
            styles_service::delete_uploaded($slug);
            redirect($returnurl,
                get_string('stylesdelete_success', 'mod_exescorm'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
            break;
    }
}

// --------------------------------------------------------------------
// Render.
// --------------------------------------------------------------------
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('stylesmanager', 'mod_exescorm'));

if (get_config('exescorm', 'editormode') !== 'embedded') {
    echo $OUTPUT->notification(get_string('stylesonlywhenembedded', 'mod_exescorm'),
        \core\output\notification::NOTIFY_WARNING);
}

echo html_writer::tag('p', get_string('stylesmanager_intro', 'mod_exescorm'));

// Point admins at the inline uploader on the plugin settings page —
// the filemanager there is the single entry point for uploading styles.
echo html_writer::tag('p',
    html_writer::link(
        $settingsurl,
        get_string('stylesupload_goto_settings', 'mod_exescorm'),
        ['class' => 'btn btn-secondary']
    )
);

// Uploaded styles table.
$uploaded = styles_service::list_uploaded_styles();
echo $OUTPUT->heading(get_string('stylesuploaded', 'mod_exescorm'), 3);
if (empty($uploaded)) {
    echo html_writer::tag('p', get_string('stylesuploaded_empty', 'mod_exescorm'), ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('stylestable_title', 'mod_exescorm'),
        get_string('stylestable_id', 'mod_exescorm'),
        get_string('stylestable_version', 'mod_exescorm'),
        get_string('stylestable_installed', 'mod_exescorm'),
        get_string('stylestable_enabled', 'mod_exescorm'),
        get_string('stylestable_actions', 'mod_exescorm'),
    ];
    foreach ($uploaded as $style) {
        $toggleurl = new moodle_url('/mod/exescorm/admin/styles.php', [
            'action' => 'toggleuploaded',
            'slug' => $style['id'],
            'enabled' => empty($style['enabled']) ? 1 : 0,
            'sesskey' => sesskey(),
        ]);
        $togglelabel = empty($style['enabled'])
            ? get_string('stylesenable', 'mod_exescorm')
            : get_string('stylesdisable', 'mod_exescorm');
        $deleteurl = new moodle_url('/mod/exescorm/admin/styles.php', [
            'action' => 'delete',
            'slug' => $style['id'],
            'sesskey' => sesskey(),
        ]);
        $table->data[] = [
            s($style['title'] ?? $style['id']),
            html_writer::tag('code', s($style['id'])),
            s($style['version'] ?? ''),
            s($style['installed_at'] ?? ''),
            html_writer::link($toggleurl, $togglelabel, ['class' => 'btn btn-secondary btn-sm']),
            html_writer::link(
                $deleteurl,
                get_string('stylesdelete', 'mod_exescorm'),
                [
                    'class' => 'btn btn-danger btn-sm',
                    'onclick' => "return confirm('"
                        . addslashes_js(get_string('stylesdelete_confirm', 'mod_exescorm'))
                        . "');",
                ]
            ),
        ];
    }
    echo html_writer::table($table);
}

// Built-in styles table.
$builtins = styles_service::list_builtin_themes();
echo $OUTPUT->heading(get_string('stylesbuiltin', 'mod_exescorm'), 3);
if (empty($builtins)) {
    echo html_writer::tag('p', get_string('stylesbuiltin_empty', 'mod_exescorm'), ['class' => 'text-muted']);
} else {
    $registry = styles_service::get_registry();
    $disabledlist = $registry['disabled_builtins'];
    $table = new html_table();
    $table->head = [
        get_string('stylestable_title', 'mod_exescorm'),
        get_string('stylestable_id', 'mod_exescorm'),
        get_string('stylestable_version', 'mod_exescorm'),
        get_string('stylestable_enabled', 'mod_exescorm'),
    ];
    foreach ($builtins as $style) {
        $isdisabled = in_array($style['id'], $disabledlist, true);
        $toggleurl = new moodle_url('/mod/exescorm/admin/styles.php', [
            'action' => 'togglebuiltin',
            'id' => $style['id'],
            'enabled' => $isdisabled ? 1 : 0,
            'sesskey' => sesskey(),
        ]);
        $togglelabel = $isdisabled
            ? get_string('stylesenable', 'mod_exescorm')
            : get_string('stylesdisable', 'mod_exescorm');
        $table->data[] = [
            s($style['title']),
            html_writer::tag('code', s($style['id'])),
            s($style['version']),
            html_writer::link($toggleurl, $togglelabel, ['class' => 'btn btn-secondary btn-sm']),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
