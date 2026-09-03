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
     */
    private function setup_scenario() {
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
     */
    private function trigger_subcourse_event($mastercourse, $targetcourse, $student) {
        // We mock the event directly to avoid dependency on mod_subcourse generator.
        global $DB;
        
        $subcourse = new \stdClass();
        $subcourse->course = $mastercourse->id;
        $subcourse->name = 'Mock subcourse';
        $subcourse->refcourse = $targetcourse->id;
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
     */
    public function test_auto_enrolment_workflow() {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test plugin disabled.
     */
    public function test_plugin_disabled_no_enrolment() {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        set_config('enabled', 0, 'local_subcourseenrol');

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test user already enrolled.
     */
    public function test_user_already_enrolled_no_duplicate() {
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        $this->getDataGenerator()->enrol_user($student->id, $targetcourse->id, 'student');
        
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        
        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test missing manual instance.
     */
    public function test_manual_instance_missing_no_enrolment() {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        // Remove manual instance.
        $DB->delete_records('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test disabled manual instance.
     */
    public function test_manual_instance_disabled_no_enrolment() {
        global $DB;
        list($mastercourse, $targetcourse, $student) = $this->setup_scenario();
        
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['courseid' => $targetcourse->id, 'enrol' => 'manual']);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test inherited timeend.
     */
    public function test_timeend_inherited_from_master() {
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
}
