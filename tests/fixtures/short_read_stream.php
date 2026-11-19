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
 * Stream wrapper fixture for the get_ode.php response tests.
 *
 * @package    mod_exescorm
 * @copyright  2026 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\exeonline;

/**
 * Read-only stream that never returns more than 1000 bytes per read, like a remote file system would.
 */
final class short_read_stream {
    /** @var string Bytes served by the stream. */
    public static $content = '';

    /** @var resource|null Stream context, set by PHP. */
    public $context;

    /** @var int Current position. */
    private $position = 0;

    /**
     * Opens the stream.
     *
     * @return bool
     */
    public function stream_open(): bool {
        $this->position = 0;

        return true;
    }

    /**
     * Reads at most 1000 bytes, which is not a multiple of a base64 group.
     *
     * @param int $count
     * @return string
     */
    public function stream_read(int $count): string {
        $data = substr(self::$content, $this->position, min($count, 1000));
        $this->position += strlen($data);

        return $data;
    }

    /**
     * Tells whether the whole content has been read.
     *
     * @return bool
     */
    public function stream_eof(): bool {
        return $this->position >= strlen(self::$content);
    }
}
