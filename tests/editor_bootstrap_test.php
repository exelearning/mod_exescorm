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

namespace mod_exescorm;

/**
 * Regression test for the embedded editor bootstrap.
 *
 * @package    mod_exescorm
 * @category   test
 * @copyright  2026 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class editor_bootstrap_test extends \basic_testcase {
    /**
     * The editor serves its new-tab (fullscreen) preview only through its preview
     * Service Worker, so the bootstrap must not replace the registration
     * (exelearning/exelearning issue 2774).
     */
    public function test_bootstrap_does_not_override_service_worker_registration(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/mod/exescorm/editor/index.php');

        $this->assertIsString($source);
        $this->assertDoesNotMatchRegularExpression('/serviceWorker\s*\.\s*register\s*=/', $source);
    }
}
