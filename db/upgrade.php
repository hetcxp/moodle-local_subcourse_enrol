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
 * Upgrade code for the plugin.
 *
 * @package    local_subcourseenrol
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_subcourseenrol_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // The table 'local_subcourseenrol_pending' was introduced in an early iteration
    // for scheduled queue processing, and dropped in 2026082602 when transitioning
    // to synchronous event observer auto-enrolment. Kept for upgrade path compatibility.
    if ($oldversion < 2026082601) {
        $table = new xmldb_table('local_subcourseenrol_pending');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetcourseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mastercourseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('userid_targetcourseid', XMLDB_INDEX_UNIQUE, ['userid', 'targetcourseid']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2026082601, 'local', 'subcourseenrol');
    }
    if ($oldversion < 2026082602) {
        $table = new xmldb_table('local_subcourseenrol_pending');
        
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2026082602, 'local', 'subcourseenrol');
    }

    return true;
}
