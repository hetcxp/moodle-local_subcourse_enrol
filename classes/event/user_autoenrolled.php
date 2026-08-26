<?php
/**
 * Event for when a user is auto-enrolled via subcourse.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event for user_autoenrolled.
 */
class user_autoenrolled extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'user_enrolments';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_user_autoenrolled', 'local_subcourseenrol');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $mastercourseid = $this->other['mastercourseid'] ?? 'unknown';
        return "The user with id '$this->userid' was auto-enrolled in the course with id '$this->courseid' " .
               "because they accessed a subcourse activity in the master course with id '$mastercourseid'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/enrol/users.php', ['id' => $this->courseid]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['mastercourseid'])) {
            throw new \coding_exception('The \'mastercourseid\' value must be set in other.');
        }
    }
}
