<?php
/**
 * PHPUnit tests for the event observer.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer tests.
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
        
        $cm = new \stdClass();
        $cm->id = 999;
        $cm->course = $mastercourse->id;
        $cm->instance = $subcourse->id;
        
        $context = \context_course::instance($mastercourse->id);
        
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
        
        $sink = $this->redirectEvents();
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        $events = $sink->get_events();
        $sink->close();

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        
        // Check that autoenrolled event was triggered.
        $autoenrolled_events = array_filter($events, function($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });
        $this->assertCount(1, $autoenrolled_events);
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
        
        $sink = $this->redirectEvents();
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        $events = $sink->get_events();
        $sink->close();

        // Check that no new autoenrolled event was triggered.
        $autoenrolled_events = array_filter($events, function($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });
        $this->assertCount(0, $autoenrolled_events);
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
