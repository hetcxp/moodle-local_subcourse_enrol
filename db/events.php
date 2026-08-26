<?php
/**
 * Events definition for local_subcourseenrol.
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\mod_subcourse\event\course_module_viewed',
        'callback'    => '\local_subcourseenrol\observer::subcourse_viewed',
        'internal'    => false,
    ],
];
