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
 * The mod_exescorm tracks viewed event.
 *
 * @package    mod_exescorm
 * @copyright  2013 onwards Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\event;

/**
 * The mod_exescorm tracks viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event properties.
 *
 *      - int attemptid: Attempt id.
 *      - int instanceid: Instance id of the exescorm activity.
 *      - int scoid: Sco Id for which the trackes are viewed.
 * }
 *
 * @package    mod_exescorm
 * @since      Moodle 2.7
 * @copyright  2013 onwards Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracks_viewed extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the tracks for the user with id '$this->relateduserid' " .
            "for the exescorm activity with course module id '$this->contextinstanceid'.";
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtracksviewed', 'mod_exescorm');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = [
            'id' => $this->contextinstanceid,
            'user' => $this->relateduserid,
            'attempt' => $this->other['attemptid'],
            'scoid' => $this->other['scoid'],
        ];
        return new \moodle_url('/mod/exescorm/userreporttracks.php', $params);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return [
            $this->courseid, 'exescorm', 'userreporttracks', 'report/userreporttracks.php?id=' . $this->contextinstanceid
            . '&user=' . $this->relateduserid . '&attempt=' . $this->other['attemptid'] . '&scoid=' . $this->other['scoid']
            . '&mode=' . $this->other['mode'], $this->other['instanceid'], $this->contextinstanceid,
        ];
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }
        if (empty($this->other['attemptid'])) {
            throw new \coding_exception('The \'attemptid\' value must be set in other.');
        }
        if (empty($this->other['instanceid'])) {
            throw new \coding_exception('The \'instanceid\' value must be set in other.');
        }
        if (empty($this->other['scoid'])) {
            throw new \coding_exception('The \'scoid\' value must be set in other.');
        }
    }

    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['instanceid'] = ['db' => 'exescorm', 'restore' => 'exescorm'];
        $othermapped['scoid'] = ['db' => 'exescorm_scoes', 'restore' => 'exescorm_scoe'];

        return $othermapped;
    }
}
