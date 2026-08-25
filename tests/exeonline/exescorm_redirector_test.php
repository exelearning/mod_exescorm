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
 * Unit tests for the eXeLearning redirector.
 *
 * @package    mod_exescorm
 * @copyright  2026 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_exescorm\exeonline;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for mod_exescorm\exeonline\exescorm_redirector.
 *
 * @covers \mod_exescorm\exeonline\exescorm_redirector
 */
class exescorm_redirector_test extends \advanced_testcase {

    /** @var int Arbitrary course module id; the redirector only forwards it, it never looks it up. */
    const CMID = 42;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        set_config('exeonlinebaseuri', 'https://exelearning.example.com', 'exescorm');
        set_config('hmackey1', 'testkey', 'exescorm');
        set_config('tokenexpiration', 300, 'exescorm');
    }

    /**
     * Returns the returnurl claim of the JWT carried by a redirection url.
     *
     * @param moodle_url $url
     * @return string
     */
    protected function get_payload_returnurl(moodle_url $url): string {
        $payload = token_manager::validate_jwt_token($url->param('jwt_token'));
        $this->assertIsObject($payload, 'JWT could not be decoded: ' . (is_string($payload) ? $payload : ''));

        return $payload->returnurl;
    }

    public function test_module_return_url_is_kept_and_gets_the_cmid(): void {
        $url = exescorm_redirector::get_redirection_url(
            self::CMID,
            new moodle_url('/mod/exescorm/view.php', ['id' => 1, 'forceview' => 1])
        );

        $returnurl = $this->get_payload_returnurl($url);
        $this->assertStringContainsString('/mod/exescorm/view.php', $returnurl);
        $this->assertStringContainsString('id=' . self::CMID, $returnurl);
    }

    public function test_course_return_url_is_routed_through_the_module(): void {
        global $CFG;

        $courseurl = new moodle_url('/course/view.php', ['id' => 7]);
        $url = exescorm_redirector::get_redirection_url(self::CMID, $courseurl);

        // eXeLearning only accepts return urls under the module path, so the course page
        // must be reached through returnto.php instead of being sent as is.
        $returnurl = $this->get_payload_returnurl($url);
        $this->assertStringStartsWith($CFG->wwwroot . '/mod/exescorm/returnto.php', $returnurl);
        $this->assertStringContainsString('id=' . self::CMID, $returnurl);
        $this->assertStringContainsString(rawurlencode('/course/view.php?id=7'), str_replace('&amp;', '&', $returnurl));
    }

    public function test_default_return_url_is_routed_through_the_module(): void {
        global $CFG;

        // No explicit destination: the redirector defaults to the site home, which is
        // still outside the module and would break the eXeLearning callback.
        $returnurl = $this->get_payload_returnurl(exescorm_redirector::get_redirection_url(self::CMID));
        $this->assertStringStartsWith($CFG->wwwroot . '/mod/exescorm/returnto.php', $returnurl);
    }

    public function test_resolve_returnto_url_restores_the_carried_destination(): void {
        global $CFG;

        $course = $this->getDataGenerator()->create_course();
        $resolved = exescorm_redirector::resolve_returnto_url('/course/view.php?id=' . $course->id, $course);

        $this->assertSame($CFG->wwwroot . '/course/view.php?id=' . $course->id, $resolved->out(false));
    }

    public function test_resolve_returnto_url_falls_back_to_the_course(): void {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $course = $this->getDataGenerator()->create_course();
        $resolved = exescorm_redirector::resolve_returnto_url('', $course);

        $this->assertSame(course_get_url($course)->out(false), $resolved->out(false));
    }
}
