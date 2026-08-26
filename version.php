<?php
/**
 * Version details.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_subcourseenrol';
$plugin->version   = 2026082603;
$plugin->requires  = 2026041300; // Moodle 5.0+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '2.0.0';
$plugin->dependencies = [
    'mod_subcourse' => 2026021900 // Requires 2026021900 which introduces the course_module_viewed event.
];
