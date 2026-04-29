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
 * Admin setting that renders built-in styles as a list of checkboxes.
 *
 * @package    mod_exescorm
 * @copyright  2025 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\admin;

defined('MOODLE_INTERNAL') || die();

use mod_exescorm\local\styles_service;

/**
 * Per-built-in style enable/disable checkboxes, inline in the plugin
 * settings page. Source of truth stays in the styles registry
 * (`config_plugin(exescorm).styles_registry`), so the widget reads state
 * from it at render time and writes back through the service.
 */
class admin_setting_stylesbuiltins extends \admin_setting {

    /**
     * @param string $name Setting key (used for the HTML input name).
     */
    public function __construct(string $name) {
        parent::__construct(
            $name,
            get_string('stylesbuiltin', 'mod_exescorm'),
            get_string('stylesbuiltin_hint', 'mod_exescorm'),
            []
        );
    }

    /**
     * This setting does not live in $CFG; the registry owns the state.
     *
     * @return array
     */
    public function get_setting() {
        return [];
    }

    /**
     * @return array
     */
    public function get_defaultsetting() {
        return [];
    }

    /**
     * Persist checkbox state back into the registry.
     *
     * @param array $data Posted value map (slug => '1' when checked).
     * @return string Empty on success.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            $data = [];
        }
        foreach (styles_service::list_builtin_themes() as $theme) {
            $id = $theme['id'];
            $enabled = !empty($data[$id]);
            styles_service::set_builtin_enabled($id, $enabled);
        }
        return '';
    }

    /**
     * Render the checkbox list.
     *
     * @param mixed  $data  Unused — current state comes from the registry.
     * @param string $query Current admin search query.
     * @return string
     */
    public function output_html($data, $query = '') {
        $registry = styles_service::get_registry();
        $disabled = $registry['disabled_builtins'];
        $builtins = styles_service::list_builtin_themes();
        if (empty($builtins)) {
            $html = \html_writer::tag('p',
                get_string('stylesbuiltin_empty', 'mod_exescorm'),
                ['class' => 'text-muted']
            );
            return format_admin_setting($this, $this->visiblename, $html, $this->description);
        }
        $rows = '';
        foreach ($builtins as $theme) {
            $id = $theme['id'];
            $checked = in_array($id, $disabled, true) ? '' : 'checked';
            $inputname = $this->get_full_name() . '[' . $id . ']';
            $version = !empty($theme['version'])
                ? ' <span class="text-muted small">v' . s($theme['version']) . '</span>'
                : '';
            $rows .= '<li style="margin-bottom:.25em;">'
                . '<label>'
                . '<input type="checkbox" name="' . s($inputname) . '" value="1" ' . $checked . '> '
                . s($theme['title'])
                . $version
                . ' <code class="text-muted small">' . s($id) . '</code>'
                . '</label>'
                . '</li>';
        }
        // Hidden sentinel so the form always posts the parent name. Without
        // it admin_find_write_settings() skips write_setting() when every
        // checkbox is cleared, so disabling all builtins from the form
        // would leave them silently re-enabled.
        $sentinel = '<input type="hidden" name="' . s($this->get_full_name())
            . '[__sentinel]" value="1">';
        $html = $sentinel
            . '<ul class="mod_exescorm-styles-builtins list-unstyled" '
            . 'style="list-style:none;padding:0;margin:0;">' . $rows . '</ul>';
        return format_admin_setting($this, $this->visiblename, $html, $this->description);
    }
}
