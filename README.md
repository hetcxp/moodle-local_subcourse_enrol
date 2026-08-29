# 🎓 Subcourse Auto-Enrolment

**Subcourse Auto-Enrolment** bridges the gap between structured learning pathways and administrative simplicity. It automatically enrols students into target courses the moment they click on a Subcourse activity, syncing access rules effortlessly.

## ✨ Why Subcourse Auto-Enrolment?
Managing complex training programs across multiple Moodle courses can be an administrative nightmare. This plugin automates the busywork.
- **Seamless Learner Journey**: Students click a subcourse link and gain instant access. No frustrating "You cannot enrol yourself in this course" errors.
- **Smart Access Expiration**: Enrolment duration in the target course automatically matches the main (master) course.
- **Admin Relief**: Say goodbye to manual enrolments or complex cohort sync setups for subcourses.

## 🛠️ Technical Overview
- **Plugin Type**: Local plugin (`local_subcourseenrol`).
- **Dependencies**: Requires the `mod_subcourse` activity plugin to be installed.
- **Event Observer**: Listens to subcourse view events via Moodle's Events API (`classes/observer.php`).
- **Enrolment Logic**:
  - Intercepts the click, automatically identifying the target course.
  - Verifies that the `manual` enrolment method is available in the target course.
  - Enrols the user as a student dynamically.
  - Inherits the `timeend` (expiration timestamp) from the master course enrolment instance.
- **Privacy**: Fully GDPR compliant. Implements the privacy provider interface and does not store personal data.

### Installation
1. Clone or copy this repository into your Moodle `local/subcourseenrol` directory.
2. Run the Moodle upgrade script (`php admin/cli/upgrade.php`).
3. **Requirement:** Ensure the `manual` enrolment method is enabled on your target courses, as the plugin relies on it to create the enrolment instances.
