# Subcourse Auto-enrolment (local_subcourseenrol)

This local Moodle 5.x plugin automatically enrols users into courses referenced by a `mod_subcourse` activity when the user accesses the subcourse link.

## Features

- **Seamless Auto-enrolment:** When a student clicks a Subcourse link in a master course, they are automatically enrolled in the target course.
- **Expiration Inheritance:** The enrolment expiration date in the target course is inherited from the master course. If the master course enrolment has no expiration, the target course enrolment will also have no expiration.
- **Direct Access Protection:** If a user accesses the target course directly from the course catalog without clicking the subcourse link, they will not be automatically enrolled. Standard Moodle behaviour applies.
- **Auditable Logging:** Every auto-enrolment triggers a custom Moodle event (`\local_subcourseenrol\event\user_autoenrolled`), which is logged and auditable in standard Moodle reports.
- **Admin Settings:** The auto-enrolment behavior can be toggled globally. Only users with the `local/subcourseenrol:manage` capability can change these settings.

## Requirements

- Moodle 5.0+
- The `mod_subcourse` plugin (catalyst/moodle-mod_subcourse) **version 2026021900 or higher**. This specific version is required because it introduces the necessary `course_module_viewed` event.

## Installation

1. Ensure the `mod_subcourse` plugin (version 2026021900+) is installed and configured on your Moodle site.
2. Place this plugin's folder in the `local/` directory of your Moodle installation. The folder name must be `subcourseenrol`.
3. Log in to Moodle as an administrator and go to **Site administration -> Notifications** to complete the installation.
4. Go to **Site administration -> Plugins -> Local plugins -> Subcourse auto-enrolment** to configure settings.

## How it works

The plugin uses Moodle's Event Observer API to listen for the following event:
1. `\mod_subcourse\event\course_module_viewed`: When a subcourse activity is viewed, the plugin checks if it's enabled. If so, it validates the user's enrolment in the master course, retrieves the expiration date, and creates an active `manual` enrolment in the target course with the role of `student`. Finally, it triggers a custom `user_autoenrolled` event for logging.

## Upgrade Notes (v1.x -> v2.0)

Version 2.0 simplifies the enrolment logic from a 2-step pending queue to a 1-step direct enrolment.
- **Database Cleanup:** The table `local_subcourseenrol_pending` is dropped automatically during the upgrade.
- **Removed Settings:** The `session_timeout` and `storage_strategy` settings have been removed as they are no longer necessary.
- **Removed Code:** The cron cleanup task and intermediate storage classes have been deleted.

## Troubleshooting

- **User is not enrolled upon clicking:** 
  1. Verify the plugin is enabled in **Site administration -> Plugins -> Local plugins -> Subcourse auto-enrolment**.
  2. Ensure the `manual` enrolment method is enabled and active in the target course settings.
  3. Check that the user has an active enrolment in the master course.
- **Incompatible version error:** 
  Ensure your `mod_subcourse` version is `2026021900` or higher, as older versions do not trigger the required `course_module_viewed` event.

## Testing

To run the automated PHPUnit test suite:
1. Configure your Moodle PHPUnit environment:
   ```bash
   php admin/tool/phpunit/cli/init.php
   ```
2. Run the test suite for this plugin:
   ```bash
   vendor/bin/phpunit --testsuite local_subcourseenrol_testsuite
   ```

## License

This plugin is licensed under the GNU General Public License v3 or later.
