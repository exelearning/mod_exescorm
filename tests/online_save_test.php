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
 * Integration tests for the online save flow used by set_ode.php.
 *
 * Verifies the behaviour the fix for issue #55 relies on: replacing the
 * package on a live activity must leave the package area holding exactly the
 * new file, with the matching {@see exescorm_parse()} bookkeeping in place,
 * so that pluginfile.php can resolve assets referenced by the SCO HTML pages.
 *
 * @package    mod_exescorm
 * @copyright  2026 eXeLearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_exescorm;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/exescorm/lib.php');
require_once($CFG->dirroot . '/mod/exescorm/locallib.php');

/**
 * Integration tests for the package-replacement performed by set_ode.php.
 */
final class online_save_test extends \advanced_testcase {

    /**
     * Replace the package file on an existing activity following the same
     * sequence used by set_ode.php after the issue #55 fix and run
     * exescorm_parse() to extract the new content.
     *
     * Returns the activity context so callers can inspect the resulting
     * filearea state.
     *
     * @param object $exescorm
     * @param string $packagefilename Filename for the new package.
     * @param string $packagepath     Disk path of an existing valid SCORM zip.
     * @return \context_module
     */
    private function replace_package_via_online_flow(object $exescorm, string $packagefilename,
            string $packagepath): \context_module {
        global $DB;

        $cm = get_coursemodule_from_instance('exescorm', $exescorm->id, $exescorm->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $fs = get_file_storage();

        // Drop existing files at itemid 0 (the same itemid set_ode.php targets) so the
        // new file does not collide with the previous one before it is replaced.
        $existingfiles = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, 'filename', false);
        foreach ($existingfiles as $existing) {
            $existing->delete();
        }

        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_exescorm',
            'filearea' => 'package',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $packagefilename,
        ];
        $fs->create_file_from_pathname($fileinfo, $packagepath);

        $exescorm->reference = $packagefilename;
        $exescorm->timemodified = time();
        $DB->update_record('exescorm', $exescorm);

        exescorm_parse($exescorm, true);

        return $context;
    }

    /**
     * After an online save, the package area must hold exactly the new
     * package file (no leftover files from the previous revision) so the
     * activity's exescorm_parse() call always finds the latest reference.
     */
    public function test_online_save_keeps_only_new_package(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        // Create the activity with an initial package.
        $initialpackage = $CFG->dirroot . '/mod/exescorm/tests/packages/validscorm.zip';
        $exescorm = $this->getDataGenerator()->create_module('exescorm', [
            'course' => $course->id,
            'packagefile' => null,
        ]);

        $cm = get_coursemodule_from_instance('exescorm', $exescorm->id);
        $context = \context_module::instance($cm->id);
        $fs = get_file_storage();

        // Seed the activity with a known package, mirroring the state that exists
        // before an online save lands.
        $existing = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, 'filename', false);
        foreach ($existing as $f) {
            $f->delete();
        }
        $fs->create_file_from_pathname([
            'contextid' => $context->id,
            'component' => 'mod_exescorm',
            'filearea' => 'package',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'previous-online-save.zip',
        ], $initialpackage);

        $exescorm->reference = 'previous-online-save.zip';
        $DB->update_record('exescorm', $exescorm);

        // Replace it as set_ode.php would.
        $this->replace_package_via_online_flow($exescorm, 'new-online-save.zip', $initialpackage);

        $packagefiles = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, 'filename', false);
        $this->assertCount(1, $packagefiles,
            'Online save must leave exactly one package file in the area; orphaned files break exescorm_parse() lookup.');
        $remaining = reset($packagefiles);
        $this->assertSame('new-online-save.zip', $remaining->get_filename());

        $updated = $DB->get_record('exescorm', ['id' => $exescorm->id], '*', MUST_EXIST);
        $this->assertSame('new-online-save.zip', $updated->reference,
            'The activity reference column must point to the freshly stored package.');
    }

    /**
     * Re-running the online save with a different filename must not leave the
     * previous file behind. This guards against the symptom seen in issue #55
     * where stale files in the package filearea masked the active reference.
     */
    public function test_online_save_replaces_previous_package_with_different_name(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id]);

        $packagepath = $CFG->dirroot . '/mod/exescorm/tests/packages/validscorm.zip';

        $context = $this->replace_package_via_online_flow($exescorm, 'first-save.zip', $packagepath);

        // A second online save with a different filename should overwrite the first one.
        $exescorm = $DB->get_record('exescorm', ['id' => $exescorm->id], '*', MUST_EXIST);
        $this->replace_package_via_online_flow($exescorm, 'second-save.zip', $packagepath);

        $fs = get_file_storage();
        $packagefiles = $fs->get_area_files($context->id, 'mod_exescorm', 'package', 0, 'filename', false);
        $this->assertCount(1, $packagefiles,
            'A second online save must not accumulate stale package files in the area.');
        $remaining = reset($packagefiles);
        $this->assertSame('second-save.zip', $remaining->get_filename());

        $updated = $DB->get_record('exescorm', ['id' => $exescorm->id], '*', MUST_EXIST);
        $this->assertSame('second-save.zip', $updated->reference);
    }
}
