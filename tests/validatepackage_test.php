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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/exescorm/locallib.php');

/**
 * Unit tests for {@link mod_exescorm}.
 *
 * @package    mod_exescorm
 * @category   test
 * @copyright  2013 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validatepackage_test extends \advanced_testcase {

    /**
     * Convenience to take a fixture test file and create a stored_file.
     *
     * @param string $filepath
     * @return stored_file
     */
    protected function create_stored_file_from_path($filepath) {
        $syscontext = \context_system::instance();
        $filerecord = [
            'contextid' => $syscontext->id,
            'component' => 'mod_exescorm',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => basename($filepath),
        ];

        $fs = get_file_storage();
        return $fs->create_file_from_pathname($filerecord, $filepath);
    }


    public function test_validate_package() {
        global $CFG;

        $this->resetAfterTest(true);

        $filename = "validexescorm.zip";
        $file = $this->create_stored_file_from_path($CFG->dirroot.'/mod/exescorm/tests/packages/'.$filename, \file_archive::OPEN);
        $errors = exescorm_validate_package($file);
        $this->assertEmpty($errors);

        $filename = "validaicc.zip";
        $file = $this->create_stored_file_from_path($CFG->dirroot.'/mod/exescorm/tests/packages/'.$filename, \file_archive::OPEN);
        $errors = exescorm_validate_package($file);
        $this->assertEmpty($errors);

        $filename = "invalid.zip";
        $file = $this->create_stored_file_from_path($CFG->dirroot.'/mod/exescorm/tests/packages/'.$filename, \file_archive::OPEN);
        $errors = exescorm_validate_package($file);
        $this->assertArrayHasKey('packagefile', $errors);
        if (isset($errors['packagefile'])) {
            $this->assertEquals(get_string('nomanifest', 'mod_exescorm'), $errors['packagefile']);
        }

        $filename = "badexescorm.zip";
        $file = $this->create_stored_file_from_path($CFG->dirroot.'/mod/exescorm/tests/packages/'.$filename, \file_archive::OPEN);
        $errors = exescorm_validate_package($file);
        $this->assertArrayHasKey('packagefile', $errors);
        if (isset($errors['packagefile'])) {
            $this->assertEquals(get_string('badimsmanifestlocation', 'mod_exescorm'), $errors['packagefile']);
        }
    }
}

