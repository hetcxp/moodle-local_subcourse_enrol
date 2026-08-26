<?php
/**
 * English strings for local_subcourseenrol
 *
 * @package    local_subcourseenrol
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Subcourse auto-enrolment';
$string['enabled'] = 'Enable auto-enrolment';
$string['enabled_desc'] = 'When enabled, users clicking a subcourse activity link will be automatically enrolled in the target course if they are not already.';

$string['howto_heading'] = 'How it works';
$string['howto_desc'] = '<p>When a user clicks on a Subcourse activity inside a master course, this plugin automatically enrols them into the referenced (target) course as a student.</p>
<ul>
<li>The enrolment expiration date in the target course is inherited from the master course.</li>
<li>If the master course has no expiration date, the enrolment in the target course will also not expire.</li>
<li>If the user accesses the target course directly from the course catalog (without going through the subcourse link), the standard Moodle behavior applies: the user will not be able to access unless enrolled or self-enrolment is enabled.</li>
</ul>
<p><strong>Requirements:</strong></p>
<ul>
<li>The <code>mod_subcourse</code> plugin must be installed.</li>
<li>The user must be enrolled and active in the master course.</li>
<li>The <code>manual</code> enrolment method must be enabled and available in the target course.</li>
</ul>';
$string['privacy:metadata'] = 'This plugin does not store personal data.';
$string['event_user_autoenrolled'] = 'User auto-enrolled via subcourse';
$string['event_user_autoenrolled_desc'] = 'The user with id "{$a->userid}" was auto-enrolled in the course with id "{$a->courseid}" because they accessed a subcourse activity in the master course with id "{$a->other[\'mastercourseid\']}".';
