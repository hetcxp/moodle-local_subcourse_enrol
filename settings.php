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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Settings for the plugin.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
