<?php
/**
 * Settings for the plugin.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && has_capability('local/subcourseenrol:manage', context_system::instance())) {
    $settings = new admin_settingpage('local_subcourseenrol', get_string('pluginname', 'local_subcourseenrol'));
    
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_subcourseenrol/enabled',
        get_string('enabled', 'local_subcourseenrol'),
        get_string('enabled_desc', 'local_subcourseenrol'),
        1
    ));

    $settings->add(new admin_setting_heading(
        'local_subcourseenrol/howto_heading',
        get_string('howto_heading', 'local_subcourseenrol'),
        get_string('howto_desc', 'local_subcourseenrol')
    ));
}
