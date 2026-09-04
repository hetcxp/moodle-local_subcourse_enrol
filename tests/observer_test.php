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
 * PHPUnit tests for the event observer.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer tests.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_subcourseenrol\observer
 */
class observer_test extends \advanced_testcase {

    /**
     * Setup before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Helper to set up the scenario.
     *
     * @return array
     */
    private function setup_scenario(): array {
        $mastercourse = $this->getDataGenerator()->create_course();
        $targetcourse = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        
        $this->getDataGenerator()->enrol_user($student->id, $mastercourse->id, 'student');
        
        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->add_default_instance($targetcourse);
        
        set_config('enabled', 1, 'local_subcourseenrol');
        
        $this->setUser($student);
        
        return [$mastercourse, $targetcourse, $student];
    }
    
    /**
     * Helper to mock the course_module_viewed event.
     *
     * @param \stdClass $mastercourse
     * @param \stdClass $targetcourse
     * @param \stdClass $student
     * @param int|null $refcourse Optional override for subcourse refcourse.
     */
    private function trigger_subcourse_event($mastercourse, $targetcourse, $student, ?int $refcourse = null): void {
        // We mock the event directly to avoid dependency on mod_subcourse generator.
        global $DB;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('subcourse')) {
            $this->markTestSkipped('mod_subcourse is not installed or subcourse table does not exist.');
        }
        
        $subcourse = new \stdClass();
        $subcourse->course = $mastercourse->id;
        $subcourse->name = 'Mock subcourse';
        $subcourse->refcourse = ($refcourse !== null) ? $refcourse : $targetcourse->id;
        $subcourse->timecreated = time();
        $subcourse->timemodified = time();
        
        $subcourse->id = $DB->insert_record('subcourse', $subcourse);
        
        $module = $DB->get_record('modules', ['name' => 'subcourse'], 'id', IGNORE_MISSING);
        $moduleid = $module ? $module->id : 1;
        
        $cm = new \stdClass();
        $cm->course = $mastercourse->id;
        $cm->module = $moduleid;
        $cm->instance = $subcourse->id;
        $cm->section = 1;
        $cm->id = $DB->insert_record('course_modules', $cm);
        
        $context = \context_module::instance($cm->id);
        
        $event = \mod_subcourse\event\course_module_viewed::create([
            'objectid' => $subcourse->id,
            'context'  => $context,
            'courseid' => $mastercourse->id,
            'userid'   => $student->id
        ]);
        
        $event->add_record_snapshot('subcourse', $subcourse);
        $event->trigger();
    }

    /**
     * Test the full auto-enrolment workflow.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_auto_enrolment_workflow(): void {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test plugin disabled.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_plugin_disabled_no_enrolment(): void {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        set_config('enabled', 0, 'local_subcourseenrol');

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test user already enrolled.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_user_already_enrolled_no_duplicate(): void {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        $this->getDataGenerator()->enrol_user($student->id, $targetcourse->id, 'student');
        
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        
        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test missing manual instance.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_manual_instance_missing_no_enrolment(): void {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        // Remove manual instance.
        $DB->delete_records('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test disabled manual instance.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_manual_instance_disabled_no_enrolment(): void {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['courseid' => $targetcourse->id, 'enrol' => 'manual']);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test inherited timeend.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_timeend_inherited_from_master(): void {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        $timeend = time() + 3600;
        // Update master enrolment to have a specific timeend.
        $instance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'timeend', $timeend, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $targetenrolment = $DB->get_record('user_enrolments', ['enrolid' => $targetinstance->id, 'userid' => $student->id]);
        
        $this->assertEquals($timeend, $targetenrolment->timeend);
    }

    /**
     * Test subcourse without refcourse does not trigger enrolment.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_subcourse_without_refcourse_no_enrolment(): void {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student, 0);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test fallback to archetype roles when 'student' shortname role does not exist.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_student_role_fallback_via_archetype(): void {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        // Change shortname of 'student' role to force archetype fallback.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $DB->set_field('role', 'shortname', 'student_custom', ['id' => $studentrole->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test user_autoenrolled event is triggered on enrolment.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_autoenrolled_event_is_fired(): void {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        $sink = $this->redirectEvents();

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $events = $sink->get_events();
        $sink->close();

        $autoenrolevents = array_filter($events, function($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });

        $this->assertCount(1, $autoenrolevents);
        $event = reset($autoenrolevents);
        $this->assertEquals($student->id, $event->userid);
        $this->assertEquals($targetcourse->id, $event->courseid);
        $this->assertEquals($mastercourse->id, $event->other['mastercourseid']);
    }

    /**
     * Test user_autoenrolled event throws exception when mastercourseid is missing.
     *
     * @covers \local_subcourseenrol\event\user_autoenrolled::validate_data
     */
    public function test_validate_data_throws_without_mastercourseid(): void {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        $this->expectException(\coding_exception::class);
        $this->expectExceptionMessage("The 'mastercourseid' value must be set in other.");

        \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => 1,
            'userid' => $student->id,
            'courseid' => $targetcourse->id,
            'context' => \context_course::instance($targetcourse->id),
        ]);
    }

    /**
     * Test master enrolment with timeend = 0 results in target enrolment without expiry.
     *
     * @covers \local_subcourseenrol\observer::subcourse_viewed
     */
    public function test_master_enrolment_timeend_zero_no_expiry(): void {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        // Ensure master enrolment has timeend = 0.
        $instance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'timeend', 0, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $targetenrolment = $DB->get_record('user_enrolments', ['enrolid' => $targetinstance->id, 'userid' => $student->id]);

        $this->assertEquals(0, $targetenrolment->timeend);
    }
}
