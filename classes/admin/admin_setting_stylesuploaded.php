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
 * Admin setting that renders uploaded styles as enable/disable checkboxes
 * and per-row delete links.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\admin;

defined('MOODLE_INTERNAL') || die();

use mod_exescorm\local\styles_service;

/**
 * Per-uploaded-style enable/disable checkboxes + delete buttons.
 *
 * Follows the same source-of-truth-is-the-registry pattern as
 * {@see admin_setting_stylesbuiltins}. Deletes are handled via a
 * separate redirect-only endpoint so they survive nested-form
 * constraints and preserve sesskey validation.
 */
class admin_setting_stylesuploaded extends \admin_setting {

    public function __construct(string $name) {
        parent::__construct(
            $name,
            get_string('stylesuploaded', 'mod_exescorm'),
            get_string('stylesuploaded_hint', 'mod_exescorm'),
            []
        );
    }

    public function get_setting() {
        return [];
    }

    public function get_defaultsetting() {
        return [];
    }

    public function write_setting($data) {
        if (!is_array($data)) {
            $data = [];
        }
        $registry = styles_service::get_registry();
        foreach (array_keys($registry['uploaded']) as $slug) {
            $enabled = !empty($data[$slug]);
            styles_service::set_uploaded_enabled($slug, $enabled);
        }
        return '';
    }

    public function output_html($data, $query = '') {
        $uploaded = styles_service::list_uploaded_styles();
        if (empty($uploaded)) {
            $html = \html_writer::tag('p',
                get_string('stylesuploaded_empty', 'mod_exescorm'),
                ['class' => 'text-muted']
            );
            return format_admin_setting($this, $this->visiblename, $html, $this->description);
        }
        $rows = '';
        foreach ($uploaded as $style) {
            $slug = $style['id'];
            $checked = !empty($style['enabled']) ? 'checked' : '';
            $inputname = $this->get_full_name() . '[' . $slug . ']';
            $version = !empty($style['version'])
                ? ' <span class="text-muted small">v' . s($style['version']) . '</span>'
                : '';
            $deleteurl = new \moodle_url('/mod/exescorm/admin/styles.php', [
                'action'  => 'delete',
                'slug'    => $slug,
                'sesskey' => sesskey(),
            ]);
            $deletelink = \html_writer::link(
                $deleteurl,
                get_string('stylesdelete', 'mod_exescorm'),
                [
                    'class' => 'btn btn-link text-danger p-0 ml-2',
                    'style' => 'margin-left:.75em;',
                    'onclick' => "return confirm('"
                        . addslashes_js(get_string('stylesdelete_confirm', 'mod_exescorm'))
                        . "');",
                ]
            );
            $rows .= '<li style="margin-bottom:.25em;">'
                . '<label>'
                . '<input type="checkbox" name="' . s($inputname) . '" value="1" ' . $checked . '> '
                . s($style['title'] ?? $slug)
                . $version
                . ' <code class="text-muted small">' . s($slug) . '</code>'
                . '</label>'
                . $deletelink
                . '</li>';
        }
        $html = '<ul class="mod_exescorm-styles-uploaded list-unstyled" '
            . 'style="list-style:none;padding:0;margin:0;">' . $rows . '</ul>';
        return format_admin_setting($this, $this->visiblename, $html, $this->description);
    }
}
