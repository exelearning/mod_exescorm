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
 * EXESCORM external functions and service definitions.
 *
 * @package    mod_exescorm
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'mod_exescorm_view_exescorm' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'view_exescorm',
        'description' => 'Trigger the course module viewed event.',
        'type' => 'write',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_get_exescorm_attempt_count' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'get_exescorm_attempt_count',
        'description' => 'Return the number of attempts done by a user in the given EXESCORM.',
        'type' => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_get_exescorm_scoes' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'get_exescorm_scoes',
        'description' => 'Returns a list containing all the scoes data related to the given exescorm id',
        'type' => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_get_exescorm_user_data' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'get_exescorm_user_data',
        'description' => 'Retrieves user tracking and SCO data and default EXESCORM values',
        'type' => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_insert_exescorm_tracks' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'insert_exescorm_tracks',
        'description' => 'Saves a exescorm tracking record.
                          It will overwrite any existing tracking data for this attempt.
                          Validation should be performed before running the function to ensure the user will not lose any existing
                          attempt data.',
        'type' => 'write',
        'capabilities' => 'mod/exescorm:savetrack',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_get_exescorm_sco_tracks' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'get_exescorm_sco_tracks',
        'description' => 'Retrieves SCO tracking data for the given user id and attempt number',
        'type' => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_get_exescorms_by_courses' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'get_exescorms_by_courses',
        'description' => 'Returns a list of exescorm instances in a provided set of courses, if
                            no courses are provided then all the exescorm instances the user has access to will be returned.',
        'type' => 'read',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_launch_sco' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'launch_sco',
        'description' => 'Trigger the SCO launched event.',
        'type' => 'write',
        'capabilities' => '',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],

    'mod_exescorm_get_exescorm_access_information' => [
        'classname' => 'mod_exescorm_external',
        'methodname' => 'get_exescorm_access_information',
        'description' => 'Return capabilities information for a given exescorm.',
        'type' => 'read',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
