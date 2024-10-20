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
 * EXESCORM module library functions tests
 *
 * @package    mod_exescorm
 * @category   test
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
namespace mod_exescorm;

use mod_exescorm_get_completion_active_rule_descriptions;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/exescorm/lib.php');

/**
 * EXESCORM module library functions tests
 *
 * @package    mod_exescorm
 * @category   test
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class lib_test extends \advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();
        $this->exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $this->course->id]);
        $this->context = \context_module::instance($this->exescorm->cmid);
        $this->cm = get_coursemodule_from_instance('exescorm', $this->exescorm->id);

        // Create users.
        $this->student = self::getDataGenerator()->create_user();
        $this->teacher = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $this->teacherrole->id, 'manual');
    }

    /** Test exescorm_check_mode
     *
     * @return void
     */
    public function test_exescorm_check_mode() {
        global $CFG;

        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        exescorm_check_mode($this->exescorm, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('off', $newattempt);

        $scoes = exescorm_get_scoes($this->exescorm->id);
        $sco = array_pop($scoes);
        exescorm_insert_track($this->student->id, $this->exescorm->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        $newattempt = 'on';
        exescorm_check_mode($this->exescorm, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('on', $newattempt);

        // Now do the same with a SCORM 2004 package.
        $record = new \stdClass();
        $record->course = $this->course->id;
        $record->packagefilepath = $CFG->dirroot.'/mod/exescorm/tests/packages/RuntimeBasicCalls_EXESCORM20043rdEdition.zip';
        $exescorm13 = $this->getDataGenerator()->create_module('exescorm', $record);
        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        exescorm_check_mode($exescorm13, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('off', $newattempt);

        $scoes = exescorm_get_scoes($exescorm13->id);
        $sco = array_pop($scoes);
        exescorm_insert_track($this->student->id, $exescorm13->id, $sco->id, 1, 'cmi.completion_status', 'completed');

        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        exescorm_check_mode($exescorm13, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('on', $newattempt);
    }

    /**
     * Test exescorm_view
     * @return void
     */
    public function test_exescorm_view() {
        global $CFG;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        exescorm_view($this->exescorm, $this->course, $this->cm, $this->context);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_exescorm\event\course_module_viewed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $url = new \moodle_url('/mod/exescorm/view.php', ['id' => $this->cm->id]);
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test exescorm_get_availability_status and exescorm_require_available
     * @return void
     */
    public function test_exescorm_check_and_require_available() {
        global $DB;

        $this->setAdminUser();

        // User override case.
        $this->exescorm->timeopen = time() + DAYSECS;
        $this->exescorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // Now check with a student.
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context, $this->student->id);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);
        $this->assertArrayHasKey('notopenyet', $warnings);
        $this->assertArrayHasKey('expired', $warnings);
        $this->assertEquals(userdate($this->exescorm->timeopen), $warnings['notopenyet']);
        $this->assertEquals(userdate($this->exescorm->timeclose), $warnings['expired']);

        // Reset the exescorm's times.
        $this->exescorm->timeopen = $this->exescorm->timeclose = 0;

        // Set to the student user.
        self::setUser($this->student);

        // Usual case.
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, false);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // EXESCORM not open.
        $this->exescorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // EXESCORM closed.
        $this->exescorm->timeopen = 0;
        $this->exescorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // EXESCORM not open and closed.
        $this->exescorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // Now additional checkings with different parameters values.
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // EXESCORM not open.
        $this->exescorm->timeopen = time() + DAYSECS;
        $this->exescorm->timeclose = 0;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // EXESCORM closed.
        $this->exescorm->timeopen = 0;
        $this->exescorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // EXESCORM not open and closed.
        $this->exescorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // As teacher now.
        self::setUser($this->teacher);

        // EXESCORM not open and closed.
        $this->exescorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // Now, we use the special capability.
        // EXESCORM not open and closed.
        $this->exescorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = exescorm_get_availability_status($this->exescorm, true, $this->context);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // Check exceptions does not broke anything.
        exescorm_require_available($this->exescorm, true, $this->context);
        // Now, expect exceptions.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string("notopenyet", "mod_exescorm", userdate($this->exescorm->timeopen)));

        // Now as student other condition.
        self::setUser($this->student);
        $this->exescorm->timeopen = 0;
        $this->exescorm->timeclose = time() - DAYSECS;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string("expired", "mod_exescorm", userdate($this->exescorm->timeclose)));
        exescorm_require_available($this->exescorm, false);
    }

    /**
     * Test exescorm_get_last_completed_attempt
     *
     * @return void
     */
    public function test_exescorm_get_last_completed_attempt() {
        $this->assertEquals(1, exescorm_get_last_completed_attempt($this->exescorm->id, $this->student->id));
    }

    public function test_exescorm_core_calendar_provide_event_action_open() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a exescorm activity.
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id,
            'timeopen' => time() - DAYSECS, 'timeclose' => time() + DAYSECS]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id, EXESCORM_EVENT_TYPE_OPEN);

        // Only students see exescorm events.
        $this->setUser($this->student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'mod_exescorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_exescorm_core_calendar_provide_event_action_closed() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a exescorm activity.
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id,
            'timeclose' => time() - DAYSECS]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id, EXESCORM_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory);

        // No event on the dashboard if module is closed.
        $this->assertNull($actionevent);
    }

    public function test_exescorm_core_calendar_provide_event_action_open_in_future() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a exescorm activity.
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id,
            'timeopen' => time() + DAYSECS]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id, EXESCORM_EVENT_TYPE_OPEN);

        // Only students see exescorm events.
        $this->setUser($this->student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'mod_exescorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_exescorm_core_calendar_provide_event_action_with_different_user_as_admin() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a exescorm activity.
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id,
            'timeopen' => time() + DAYSECS]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id, EXESCORM_EVENT_TYPE_OPEN);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event override with a passed in user.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory, $this->student->id);
        $actionevent2 = mod_exescorm_core_calendar_provide_event_action($event, $factory);

        // Only students see exescorm events.
        $this->assertNull($actionevent2);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'mod_exescorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertFalse($actionevent->is_actionable());
    }

    public function test_exescorm_core_calendar_provide_event_action_no_time_specified() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create a exescorm activity.
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id]);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id, EXESCORM_EVENT_TYPE_OPEN);

        // Only students see exescorm events.
        $this->setUser($this->student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('enter', 'mod_exescorm'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    public function test_exescorm_core_calendar_provide_event_action_already_completed() {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id],
            ['completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS]);

        // Get some additional data.
        $cm = get_coursemodule_from_instance('exescorm', $exescorm->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    public function test_exescorm_core_calendar_provide_event_action_already_completed_for_user() {
        $this->resetAfterTest();
        set_config('enablecompletion', 1);
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $exescorm = $this->getDataGenerator()->create_module('exescorm', ['course' => $course->id],
            ['completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS]);

        // Enrol a student in the course.
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');

        // Get some additional data.
        $cm = get_coursemodule_from_instance('exescorm', $exescorm->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $exescorm->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed for the student.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm, $student->id);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_exescorm_core_calendar_provide_event_action($event, $factory, $student->id);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid
     * @param int $instanceid The data id.
     * @param string $eventtype The event type. eg. DATA_EVENT_TYPE_OPEN.
     * @param int|null $timestart The start timestamp for the event
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype, $timestart = null) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename = 'exescorm';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->eventtype = $eventtype;

        if ($timestart) {
            $event->timestart = $timestart;
        } else {
            $event->timestart = time();
        }

        return \calendar_event::create($event);
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     */
    public function test_mod_exescorm_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 2]);
        $exescorm1 = $this->getDataGenerator()->create_module('exescorm', [
            'course' => $course->id,
            'completion' => 2,
            'completionstatusrequired' => 6,
            'completionscorerequired' => 5,
            'completionstatusallscos' => 1,
        ]);
        $exescorm2 = $this->getDataGenerator()->create_module('exescorm', [
            'course' => $course->id,
            'completion' => 2,
            'completionstatusrequired' => null,
            'completionscorerequired' => null,
            'completionstatusallscos' => null,
        ]);
        $cm1 = \cm_info::create(get_coursemodule_from_instance('exescorm', $exescorm1->id));
        $cm2 = \cm_info::create(get_coursemodule_from_instance('exescorm', $exescorm2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new \stdClass();
        $moddefaults->customdata = ['customcompletionrules' => [
            'completionstatusrequired' => 6,
            'completionscorerequired' => 5,
            'completionstatusallscos' => 1,
        ]];
        $moddefaults->completion = 2;

        // Determine the selected statuses using a bitwise operation.
        $cvalues = [];
        foreach (exescorm_status_options(true) as $key => $value) {
            if (($exescorm1->completionstatusrequired & $key) == $key) {
                $cvalues[] = $value;
            }
        }
        $statusstring = implode(', ', $cvalues);

        $activeruledescriptions = [
            get_string('completionstatusrequireddesc', 'mod_exescorm', $statusstring),
            get_string('completionscorerequireddesc', 'mod_exescorm', $exescorm1->completionscorerequired),
            get_string('completionstatusallscos', 'mod_exescorm'),
        ];
        $this->assertEquals(mod_exescorm_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_exescorm_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_exescorm_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_exescorm_get_completion_active_rule_descriptions(new \stdClass()), []);
    }

    /**
     * An unkown event type should not change the exescorm instance.
     */
    public function test_mod_exescorm_core_calendar_event_timestart_updated_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $exescormgenerator = $generator->get_plugin_generator('mod_exescorm');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $exescorm = $exescormgenerator->create_instance(['course' => $course->id]);
        $exescorm->timeopen = $timeopen;
        $exescorm->timeclose = $timeclose;
        $DB->update_record('exescorm', $exescorm);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'exescorm',
            'instance' => $exescorm->id,
            'eventtype' => EXESCORM_EVENT_TYPE_OPEN . "SOMETHING ELSE",
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1,
        ]);

        mod_exescorm_core_calendar_event_timestart_updated($event, $exescorm);

        $exescorm = $DB->get_record('exescorm', ['id' => $exescorm->id]);
        $this->assertEquals($timeopen, $exescorm->timeopen);
        $this->assertEquals($timeclose, $exescorm->timeclose);
    }

    /**
     * A EXESCORM_EVENT_TYPE_OPEN event should update the timeopen property of
     * the exescorm activity.
     */
    public function test_mod_exescorm_core_calendar_event_timestart_updated_open_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $exescormgenerator = $generator->get_plugin_generator('mod_exescorm');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $timemodified = 1;
        $newtimeopen = $timeopen - DAYSECS;
        $exescorm = $exescormgenerator->create_instance(['course' => $course->id]);
        $exescorm->timeopen = $timeopen;
        $exescorm->timeclose = $timeclose;
        $exescorm->timemodified = $timemodified;
        $DB->update_record('exescorm', $exescorm);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'exescorm',
            'instance' => $exescorm->id,
            'eventtype' => EXESCORM_EVENT_TYPE_OPEN,
            'timestart' => $newtimeopen,
            'timeduration' => 86400,
            'visible' => 1,
        ]);

        // Trigger and capture the event when adding a contact.
        $sink = $this->redirectEvents();

        mod_exescorm_core_calendar_event_timestart_updated($event, $exescorm);

        $triggeredevents = $sink->get_events();
        $moduleupdatedevents = array_filter($triggeredevents, function($e) {
            return is_a($e, 'core\event\course_module_updated');
        });

        $exescorm = $DB->get_record('exescorm', ['id' => $exescorm->id]);
        // Ensure the timeopen property matches the event timestart.
        $this->assertEquals($newtimeopen, $exescorm->timeopen);
        // Ensure the timeclose isn't changed.
        $this->assertEquals($timeclose, $exescorm->timeclose);
        // Ensure the timemodified property has been changed.
        $this->assertNotEquals($timemodified, $exescorm->timemodified);
        // Confirm that a module updated event is fired when the module
        // is changed.
        $this->assertNotEmpty($moduleupdatedevents);
    }

    /**
     * A EXESCORM_EVENT_TYPE_CLOSE event should update the timeclose property of
     * the exescorm activity.
     */
    public function test_mod_exescorm_core_calendar_event_timestart_updated_close_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $exescormgenerator = $generator->get_plugin_generator('mod_exescorm');
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $timemodified = 1;
        $newtimeclose = $timeclose + DAYSECS;
        $exescorm = $exescormgenerator->create_instance(['course' => $course->id]);
        $exescorm->timeopen = $timeopen;
        $exescorm->timeclose = $timeclose;
        $exescorm->timemodified = $timemodified;
        $DB->update_record('exescorm', $exescorm);

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'exescorm',
            'instance' => $exescorm->id,
            'eventtype' => EXESCORM_EVENT_TYPE_CLOSE,
            'timestart' => $newtimeclose,
            'timeduration' => 86400,
            'visible' => 1,
        ]);

        // Trigger and capture the event when adding a contact.
        $sink = $this->redirectEvents();

        mod_exescorm_core_calendar_event_timestart_updated($event, $exescorm);

        $triggeredevents = $sink->get_events();
        $moduleupdatedevents = array_filter($triggeredevents, function($e) {
            return is_a($e, 'core\event\course_module_updated');
        });

        $exescorm = $DB->get_record('exescorm', ['id' => $exescorm->id]);
        // Ensure the timeclose property matches the event timestart.
        $this->assertEquals($newtimeclose, $exescorm->timeclose);
        // Ensure the timeopen isn't changed.
        $this->assertEquals($timeopen, $exescorm->timeopen);
        // Ensure the timemodified property has been changed.
        $this->assertNotEquals($timemodified, $exescorm->timemodified);
        // Confirm that a module updated event is fired when the module
        // is changed.
        $this->assertNotEmpty($moduleupdatedevents);
    }

    /**
     * An unkown event type should not have any limits
     */
    public function test_mod_exescorm_core_calendar_get_valid_event_timestart_range_unknown_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $exescorm = new \stdClass();
        $exescorm->timeopen = $timeopen;
        $exescorm->timeclose = $timeclose;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'exescorm',
            'instance' => 1,
            'eventtype' => EXESCORM_EVENT_TYPE_OPEN . "SOMETHING ELSE",
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1,
        ]);

        list ($min, $max) = mod_exescorm_core_calendar_get_valid_event_timestart_range($event, $exescorm);
        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * The open event should be limited by the exescorm's timeclose property, if it's set.
     */
    public function test_mod_exescorm_core_calendar_get_valid_event_timestart_range_open_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $exescorm = new \stdClass();
        $exescorm->timeopen = $timeopen;
        $exescorm->timeclose = $timeclose;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'exescorm',
            'instance' => 1,
            'eventtype' => EXESCORM_EVENT_TYPE_OPEN,
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1,
        ]);

        // The max limit should be bounded by the timeclose value.
        list ($min, $max) = mod_exescorm_core_calendar_get_valid_event_timestart_range($event, $exescorm);

        $this->assertNull($min);
        $this->assertEquals($timeclose, $max[0]);

        // No timeclose value should result in no upper limit.
        $exescorm->timeclose = 0;
        list ($min, $max) = mod_exescorm_core_calendar_get_valid_event_timestart_range($event, $exescorm);

        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * The close event should be limited by the exescorm's timeopen property, if it's set.
     */
    public function test_mod_exescorm_core_calendar_get_valid_event_timestart_range_close_event() {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/calendar/lib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $timeopen = time();
        $timeclose = $timeopen + DAYSECS;
        $exescorm = new \stdClass();
        $exescorm->timeopen = $timeopen;
        $exescorm->timeclose = $timeclose;

        // Create a valid event.
        $event = new \calendar_event([
            'name' => 'Test event',
            'description' => '',
            'format' => 1,
            'courseid' => $course->id,
            'groupid' => 0,
            'userid' => 2,
            'modulename' => 'exescorm',
            'instance' => 1,
            'eventtype' => EXESCORM_EVENT_TYPE_CLOSE,
            'timestart' => 1,
            'timeduration' => 86400,
            'visible' => 1,
        ]);

        // The max limit should be bounded by the timeclose value.
        list ($min, $max) = mod_exescorm_core_calendar_get_valid_event_timestart_range($event, $exescorm);

        $this->assertEquals($timeopen, $min[0]);
        $this->assertNull($max);

        // No timeclose value should result in no upper limit.
        $exescorm->timeopen = 0;
        list ($min, $max) = mod_exescorm_core_calendar_get_valid_event_timestart_range($event, $exescorm);

        $this->assertNull($min);
        $this->assertNull($max);
    }

    /**
     * A user who does not have capabilities to add events to the calendar should be able to create a EXESCORM.
     */
    public function test_creation_with_no_calendar_capabilities() {
        $this->resetAfterTest();
        $course = self::getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $user = self::getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $roleid = self::getDataGenerator()->create_role();
        self::getDataGenerator()->role_assign($roleid, $user->id, $context->id);
        assign_capability('moodle/calendar:manageentries', CAP_PROHIBIT, $roleid, $context, true);
        $generator = self::getDataGenerator()->get_plugin_generator('mod_exescorm');
        // Create an instance as a user without the calendar capabilities.
        $this->setUser($user);
        $time = time();
        $params = [
            'course' => $course->id,
            'timeopen' => $time + 200,
            'timeclose' => $time + 2000,
        ];
        $generator->create_instance($params);
    }
}
