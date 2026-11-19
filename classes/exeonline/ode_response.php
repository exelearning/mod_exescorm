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
 * Streamed response of get_ode.php.
 *
 * @package     mod_exescorm
 * @copyright   2026 eXeLearning
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\exeonline;

/**
 * Sends the activity package to eXeLearning as a JSON document without loading it in memory.
 *
 * eXeLearning expects one JSON object whose ode_file member is the base64 encoded package. Building
 * it with base64_encode() and json_encode() keeps about 3.7 times the package size in memory, which
 * exceeds the PHP memory limit for large packages. This class writes the same document in pieces.
 */
class ode_response {
    /** @var int Bytes read per iteration. A multiple of 3, so every piece is a whole number of base64 groups. */
    const CHUNK_SIZE = 3 * 256 * 1024;

    /** @var string End of the document: closes the ode_file string and the object. */
    private const TAIL = '"}';

    /**
     * Returns the exact size in bytes of the document written by {@see self::send()}.
     *
     * @param array $fields Members of the JSON object, other than ode_file.
     * @param int $packagesize Size of the package in bytes.
     * @return int
     */
    public static function content_length(array $fields, int $packagesize): int {
        return strlen(self::head($fields)) + 4 * intdiv($packagesize + 2, 3) + strlen(self::TAIL);
    }

    /**
     * Writes the JSON document, reading the package piece by piece.
     *
     * @param array $fields Members of the JSON object, other than ode_file. Must not be empty.
     * @param resource $package Readable handle positioned at the start of the package.
     * @param resource $out Writable handle that receives the document.
     */
    public static function send(array $fields, $package, $out): void {
        fwrite($out, self::head($fields));

        // Base64 can only be cut every 3 bytes. A read may return fewer bytes than requested,
        // so the bytes that do not complete a group wait for the next read.
        $pending = '';
        while (!feof($package)) {
            $read = fread($package, self::CHUNK_SIZE);
            if ($read === false) {
                break;
            }
            $pending .= $read;
            $complete = strlen($pending) - strlen($pending) % 3;
            if ($complete > 0) {
                fwrite($out, base64_encode(substr($pending, 0, $complete)));
                $pending = substr($pending, $complete);
            }
        }
        if ($pending !== '') {
            fwrite($out, base64_encode($pending));
        }

        fwrite($out, self::TAIL);
    }

    /**
     * Returns the start of the document, up to the opening quote of the ode_file string.
     *
     * @param array $fields
     * @return string
     */
    private static function head(array $fields): string {
        return substr(json_encode($fields), 0, -1) . ',"ode_file":"';
    }
}
