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
 * Unit tests for the streamed get_ode.php response.
 *
 * @package    mod_exescorm
 * @copyright  2026 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\exeonline;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../fixtures/short_read_stream.php');

/**
 * Tests for mod_exescorm\exeonline\ode_response.
 *
 * @covers \mod_exescorm\exeonline\ode_response
 */
final class ode_response_test extends \basic_testcase {
    /** @var array Envelope fields used by every test; they include characters JSON has to escape. */
    const FIELDS = [
        'status' => '0',
        'ode_id' => 42,
        'ode_filename' => 'my "package".zip',
        'ode_uri' => 'https://moodle.example.com/mod/exescorm/view.php?id=42&forceview=1',
        'ode_user' => '2',
    ];

    /**
     * Returns a readable handle over the given bytes.
     *
     * @param string $content
     * @return resource
     */
    protected function handle_for(string $content) {
        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, $content);
        rewind($handle);

        return $handle;
    }

    /**
     * Streams a package and returns what was sent.
     *
     * @param resource $package
     * @return string
     */
    protected function send($package): string {
        $out = fopen('php://temp', 'w+b');
        ode_response::send(self::FIELDS, $package, $out);
        rewind($out);

        return stream_get_contents($out);
    }

    /**
     * Package sizes around the base64 group and read chunk boundaries.
     *
     * @return array
     */
    public static function package_size_provider(): array {
        $chunk = ode_response::CHUNK_SIZE;

        return [
            'empty' => [0],
            'one byte' => [1],
            'two bytes' => [2],
            'three bytes' => [3],
            'one chunk' => [$chunk],
            'one chunk plus one byte' => [$chunk + 1],
            'two chunks plus two bytes' => [2 * $chunk + 2],
        ];
    }

    /**
     * The response is the JSON document eXeLearning expects, with the package intact.
     *
     * @dataProvider package_size_provider
     * @param int $size
     */
    public function test_send_writes_json_with_the_base64_package(int $size): void {
        $content = $size > 0 ? random_bytes($size) : '';

        $json = $this->send($this->handle_for($content));

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded, 'Response is not valid JSON: ' . json_last_error_msg());
        $this->assertSame(base64_encode($content), $decoded['ode_file']);
        unset($decoded['ode_file']);
        $this->assertSame(self::FIELDS, $decoded);
        $this->assertSame(strlen($json), ode_response::content_length(self::FIELDS, $size));
    }

    /**
     * A stream that returns fewer bytes than requested must not break the base64 output.
     */
    public function test_send_supports_streams_with_short_reads(): void {
        $content = random_bytes(100000);
        short_read_stream::$content = $content;
        stream_wrapper_register('exescormshortread', short_read_stream::class);
        $handle = fopen('exescormshortread://package', 'rb');

        try {
            $decoded = json_decode($this->send($handle), true);
        } finally {
            fclose($handle);
            stream_wrapper_unregister('exescormshortread');
        }

        $this->assertSame(base64_encode($content), $decoded['ode_file']);
    }

    /**
     * Memory use must not grow with the package size, which is what made large packages fail.
     */
    public function test_send_does_not_load_the_package_in_memory(): void {
        $size = 32 * 1024 * 1024;
        $path = tempnam(sys_get_temp_dir(), 'ode');
        $package = fopen($path, 'w+b');
        $block = random_bytes(1024 * 1024);
        for ($i = 0; $i < $size / strlen($block); $i++) {
            fwrite($package, $block);
        }
        rewind($package);
        $out = fopen('/dev/null', 'wb');
        unset($block);

        if (!function_exists('memory_reset_peak_usage')) {
            $this->markTestSkipped('Peak memory cannot be reset before PHP 8.2.');
        }
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        ode_response::send(self::FIELDS, $package, $out);
        $used = memory_get_peak_usage() - $before;

        fclose($package);
        unlink($path);
        $this->assertLessThan(8 * 1024 * 1024, $used, 'Sending a 32 MiB package used ' . $used . ' bytes of memory');
    }
}
