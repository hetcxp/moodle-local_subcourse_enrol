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
 * Event observer for local_subcourseenrol.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Check if the plugin is enabled.
     *
     * @return bool
     */
    private static function is_enabled(): bool {
        return (bool) get_config('local_subcourseenrol', 'enabled');
    }

    /**
     * Get the active enrolment in the master course.
     *
     * @param int $courseid Master course ID.
     * @param int $userid User ID.
     * @return \stdClass|null
     */
    private static function get_master_enrolment(int $courseid, int $userid): ?\stdClass {
        global $DB;
        
        $now = time();
        $sql = "SELECT ue.timeend
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON ue.enrolid = e.id
                 WHERE e.courseid = :courseid
                   AND ue.userid = :userid
                   AND ue.status = :status
                   AND (ue.timeend = 0 OR ue.timeend > :now)
              ORDER BY ue.timeend DESC";
        $params = [
            'courseid' => $courseid,
            'userid'   => $userid,
            'status'   => ENROL_USER_ACTIVE,
            'now'      => $now
        ];

        $enrolment = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        return $enrolment ?: null;
    }

    /**
     * Resolve the manual enrolment instance for the course.
     *
     * @param int $courseid Target course ID.
     * @return \stdClass|null
     */
    private static function resolve_enrol_instance(int $courseid): ?\stdClass {
        global $DB;
        
        $enrolplugin = enrol_get_plugin('manual');
        if (!$enrolplugin) {
            return null;
        }

        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', IGNORE_MISSING);
        
        if (!$instance) {
            debugging("local_subcourseenrol: Manual enrolment instance not found in course {$courseid}. Skipping auto-enrolment.", DEBUG_DEVELOPER);
            return null;
        }

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            debugging("local_subcourseenrol: Manual enrolment instance is disabled in course {$courseid}. Skipping auto-enrolment.", DEBUG_DEVELOPER);
            return null;
        }
        
        return $instance;
    }

    /**
     * Resolve the student role.
     *
     * @return \stdClass|null
     */
    private static function resolve_student_role(): ?\stdClass {
        global $DB;
        
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], 'id', IGNORE_MISSING);
        if ($studentrole) {
            return $studentrole;
        }
        
        $roles = get_archetype_roles('student');
        if (empty($roles)) {
            return null;
        }
        
        return reset($roles);
    }

    /**
     * Observer for when a subcourse activity is viewed.
     *
     * @param \mod_subcourse\event\course_module_viewed $event
     */
    public static function subcourse_viewed(\mod_subcourse\event\course_module_viewed $event) {
        if (!self::is_enabled()) {
            return;
        }

        $subcourse = $event->get_record_snapshot('subcourse', $event->objectid);
        if (!$subcourse || empty($subcourse->refcourse)) {
            return;
        }

        $userid = $event->userid;
        $mastercourseid = $event->courseid;
        $targetcourseid = (int)$subcourse->refcourse;
        
        $coursecontext = \context_course::instance($targetcourseid);

        if (is_enrolled($coursecontext, $userid)) {
            return;
        }

        $masterenrolment = self::get_master_enrolment($mastercourseid, $userid);
        if (!$masterenrolment) {
            return;
        }

        $instance = self::resolve_enrol_instance($targetcourseid);
        if (!$instance) {
            return;
        }

        $studentrole = self::resolve_student_role();
        if (!$studentrole) {
            return;
        }

        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->enrol_user($instance, $userid, $studentrole->id, time(), (int)$masterenrolment->timeend);
        
        // Fetch the user_enrolments ID for the event objectid.
        global $DB;
        $ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid], 'id', IGNORE_MISSING);
        $objectid = $ue ? $ue->id : 0;
        
        // Log the auto-enrolment.
        $autoenrol_event = \local_subcourseenrol\event\user_autoenrolled::create([
            'objectid' => $objectid,
            'userid' => $userid,
            'courseid' => $targetcourseid,
            'relateduserid' => $userid,
            'context' => $coursecontext,
            'other' => [
                'mastercourseid' => $mastercourseid
            ]
        ]);
        $autoenrol_event->trigger();

    }
}
