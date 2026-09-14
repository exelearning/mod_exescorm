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
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_exescorm',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => basename($filepath)
        );

        $fs = get_file_storage();
        return $fs->create_file_from_pathname($filerecord, $filepath);
    }


    public function test_validate_package() {
        global $CFG;

        $this->resetAfterTest(true);

        $filename = "validscorm.zip";
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

        $filename = "badscorm.zip";
        $file = $this->create_stored_file_from_path($CFG->dirroot.'/mod/exescorm/tests/packages/'.$filename, \file_archive::OPEN);
        $errors = exescorm_validate_package($file);
        $this->assertArrayHasKey('packagefile', $errors);
        if (isset($errors['packagefile'])) {
            $this->assertEquals(get_string('badimsmanifestlocation', 'mod_exescorm'), $errors['packagefile']);
        }
    }

    /**
     * A plain SCORM package, with no eXeLearning source in it, is accepted.
     *
     * The mandatory-files rule used to default to requiring a root content.xml,
     * which rejected every third-party SCORM package and, once eXeLearning
     * honoured its own "Editable export" property, eXeLearning's own output too
     * (exelearning/exelearning#2415). Such a package is played, never edited,
     * so it has nothing to be rejected for.
     *
     * @covers ::exescorm_validate_package
     */
    public function test_validate_package_without_exelearning_source(): void {
        global $CFG;

        $this->resetAfterTest(true);

        // The shipped fixture is a stock SCORM package: imsmanifest.xml, no content.xml.
        $file = $this->create_stored_file_from_path($CFG->dirroot . '/mod/exescorm/tests/packages/validscorm.zip');
        $this->assertFalse(
            exescorm_package::has_editable_source($file->list_files(get_file_packer('application/zip'))),
            'Fixture precondition: validscorm.zip must not carry an eXeLearning source.'
        );

        set_config('mandatoryfileslist', '', 'exescorm');
        $this->assertEmpty(exescorm_validate_package($file));
    }

    /**
     * A site may still restrict uploads to eXeLearning-authored packages.
     *
     * @covers ::exescorm_validate_package
     */
    public function test_validate_package_honours_a_site_mandatory_rule(): void {
        global $CFG;

        $this->resetAfterTest(true);

        set_config('mandatoryfileslist', '/^content(v\d+)?\.xml$/', 'exescorm');

        $file = $this->create_stored_file_from_path($CFG->dirroot . '/mod/exescorm/tests/packages/validscorm.zip');
        $errors = exescorm_validate_package($file);

        $this->assertArrayHasKey('packagefile', $errors);
        $this->assertEquals(get_string('badexelearningpackage', 'mod_exescorm'), $errors['packagefile']);
    }

    /**
     * has_editable_source() finds the source the embedded editor needs.
     *
     * set_ode.php refuses a save without one, because the editor re-opens the
     * package it wrote. Only a root file counts: an editor that reads the
     * archive root would not find one nested in a directory.
     *
     * @covers \mod_exescorm\exescorm_package::has_editable_source
     */
    public function test_has_editable_source(): void {
        $entry = function (string $pathname): \stdClass {
            $info = new \stdClass();
            $info->pathname = $pathname;
            $info->is_directory = false;
            return $info;
        };

        $this->assertTrue(exescorm_package::has_editable_source([$entry('content.xml')]));
        $this->assertTrue(exescorm_package::has_editable_source([$entry('contentv3.xml')]));
        $this->assertTrue(
            exescorm_package::has_editable_source([$entry('imsmanifest.xml'), $entry('content.xml')])
        );

        $this->assertFalse(exescorm_package::has_editable_source([$entry('imsmanifest.xml')]));
        $this->assertFalse(exescorm_package::has_editable_source([$entry('sub/content.xml')]));
        $this->assertFalse(exescorm_package::has_editable_source([$entry('content.xml.bak')]));
        $this->assertFalse(exescorm_package::has_editable_source([]));
        $this->assertFalse(exescorm_package::has_editable_source(null));
    }
}

