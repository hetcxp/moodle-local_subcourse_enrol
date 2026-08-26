<?php
/**
 * Privacy provider.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider class.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Get the language string identifier with the component's language file to explain why this plugin stores no data.
     * 
     * Note: This plugin does not persistently store any personal data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
