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
 * The mod_exescorm sco launched event.
 *
 * @package    mod_exescorm
 * @copyright  2013 onwards Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\event;

/**
 * The mod_exescorm sco launched event class.
 *
 * @property-read array $other {
 *      Extra information about event properties.
 *
 *      - string loadedcontent: A reference to the content loaded.
 *      - int instanceid: (optional) Instance id of the exescorm activity.
 * }
 *
 * @package    mod_exescorm
 * @since      Moodle 2.7
 * @copyright  2013 onwards Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sco_launched extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'exescorm_scoes';
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' launched the sco with id '$this->objectid' for the exescorm with " .
            "course module id '$this->contextinstanceid'.";
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventscolaunched', 'mod_exescorm');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/exescorm/player.php', ['cm' => $this->contextinstanceid, 'scoid' => $this->objectid]);
    }

    /**
     * Replace add_to_log() statement.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        return [$this->courseid, 'exescorm', 'launch', 'view.php?id=' . $this->contextinstanceid,
                $this->other['loadedcontent'], $this->contextinstanceid];
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (empty($this->other['loadedcontent'])) {
            throw new \coding_exception('The \'loadedcontent\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return ['db' => 'exescorm_scoes', 'restore' => 'exescorm_sco'];
    }

    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['instanceid'] = ['db' => 'exescorm', 'restore' => 'exescorm'];

        return $othermapped;
    }
}
