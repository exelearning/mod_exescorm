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
 * Exescorm url manager class.
 *
 * @package     mod_exescorm
 * @category    exescorm
 * @copyright   2023 3&Punt
 * @author      Juan Carrera <juan@treipunt.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\exeonline;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class token_manager {

    /**
     * This function generates a JWT with the received payload plus iat and exp times.
     *
     * @param array $payload
     * @return string A signed JWT.
     */
    public static function get_jwt_token(array $payload = []) {
        $iat = time();
        $exp = get_config('exescorm', 'tokenexpiration');
        $key = get_config('exescorm', 'hmackey1');
        $payload['iat'] = $iat;
        $payload['exp'] = $iat + $exp;
        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * Tries to verify and decode a JWT. Returns a stdClass with
     * payload data or a string with error message.
     *
     * @param string $token
     * @return stdClass|string
     */
    public static function validate_jwt_token(string $token) {
        global $CFG;
        $key = get_config('exescorm', 'hmackey1');
        // Validate JWT.
        try {
            if ($CFG->version < 2022041900) {
                $payload = JWT::decode($token, $key, ['HS256']);
            } else {
                $payload = JWT::decode(
                    $token,
                    new Key($key, 'HS256')
                );
            }
        } catch (\Exception $e) {
            $error = 'Caught exception: ' . $e->getMessage();
            return $error;
        }
        return $payload;
    }
}
