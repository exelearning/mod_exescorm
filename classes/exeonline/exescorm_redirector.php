<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * eXeLearning Online url manager class.
 *
 * @package     mod_exescorm
 * @category    exescorm
 * @copyright   2023 3&Punt
 * @author      Juan Carrera <juan@treipunt.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\exeonline;

use coding_exception;
use dml_exception;
use mod_exescorm\exeonline\token_manager;
use moodle_url;

class exescorm_redirector {

    /**
     * Keeps if editing or adding instance.
     *
     * @var string
     */
    private static $action = 'edit';

    /**
     * Keeps the return objective.
     *
     * @var moodle_url
     */
    private static $returnto = null;

    public function __construct(string $action = 'edit', moodle_url $returnto = null) {
        self::$action = $action;
        self::$returnto = $returnto;
    }

    /**
     * Builds moodle url object needed to redirect to eXeLearnig Online.
     *
     * @param integer $cmid
     * @param string|null $action
     * @param moodle_url|null $returnto
     *
     * @return moodle_url
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function get_redirection_url(int $cmid, moodle_url $returnto = null, string $action = null) {
        global $CFG, $USER;
        $action = $action ?? self::$action;
        $target = $action === 'add' ? '/new_ode' : '/edit_ode';
        $returnto = $returnto ?? self::$returnto ?? new moodle_url($CFG->wwwroot);
        // Ensure return url has a valid cmid if it is a module view url.
        if (strpos($returnto->get_path(), 'mod/exescorm') !== false) {
            $returnto->params(['id' => $cmid]);
        }
        // Get remote URL from config.
        $exeonlineurl = get_config('exescorm', 'exeonlinebaseuri');
        $payload = [
            'action' => $target,
            'cmid' => $cmid,
            'pkgtype' => 'scorm',
            'returnurl' => $returnto->out(false),
            'userid' => $USER->id,
            'provider' => [
                'name' => get_config('exescorm', 'providername'),
                'version' => get_config('exescorm', 'providerversion'),
            ],
        ];
        $jwttoken = token_manager::get_jwt_token($payload);
        $params = [
            'user' => $USER->id,
            'jwt_token' => $jwttoken,
        ];
        if ($target === '/edit_ode') {
            $params['ode_id'] = $cmid;
        }
        $url = $exeonlineurl . $target;

        return new moodle_url($url, $params);
    }


    /**
     * Hack to get redirected to eXeLearning Online by core's modedit.
     *
     * @param moodle_url $url URL of module view.
     * @return moodle_url
     */
    public function get_management_url(moodle_url $modediturl = null) {
        // When adding an instance, this object is contructed prior to be created in moodle, so we need to get id on this stage.
        // Get the cmid from module view url params.
        $params = $modediturl->params();

        return $this->get_redirection_url($params['id']);
    }
}
