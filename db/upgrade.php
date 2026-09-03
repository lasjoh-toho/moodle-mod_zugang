<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_zugang.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_zugang_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // 2026090301: self-healing step. On some installs the initial
    // db/install.xml did not fully apply (e.g. an install that was
    // interrupted, or plugin files were replaced without a clean
    // uninstall first), leaving one or more tables missing columns that
    // the code already expects — most commonly zugang_list.listtype.
    // This step checks every table/column against the full schema and
    // adds whatever is missing, so upgrading to this version repairs a
    // partially-installed site without requiring a manual DB fix or a
    // full plugin uninstall/reinstall (which would drop existing data).
    if ($oldversion < 2026090301) {

        if ($dbman->table_exists('zugang')) {
            $table = new xmldb_table('zugang');
            $fields = [
                new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null),
                new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1'),
                new xmldb_field('wlanlistid', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
                new xmldb_field('docklistid', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
                new xmldb_field('revealseconds', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '120'),
                new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        if ($dbman->table_exists('zugang_list')) {
            $table = new xmldb_table('zugang_list');
            $fields = [
                new xmldb_field('listtype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null),
                new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        if ($dbman->table_exists('zugang_list_entry')) {
            $table = new xmldb_table('zugang_list_entry');
            $fields = [
                new xmldb_field('listid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('sourceref', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('firstname', XMLDB_TYPE_CHAR, '255', null, null, null, null),
                new xmldb_field('lastname', XMLDB_TYPE_CHAR, '255', null, null, null, null),
                new xmldb_field('classname', XMLDB_TYPE_CHAR, '255', null, null, null, null),
                new xmldb_field('ciphertext', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null),
                new xmldb_field('cipheriv', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('matchstatus', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'unmatched'),
                new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
                new xmldb_field('suggesteduserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
                new xmldb_field('suggestedscore', XMLDB_TYPE_NUMBER, '5, 2', null, null, null, null),
                new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        if ($dbman->table_exists('zugang_reveal_log')) {
            $table = new xmldb_table('zugang_reveal_log');
            $fields = [
                new xmldb_field('entryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('timerevealed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null),
                new xmldb_field('timedeleted', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
            ];
            foreach ($fields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        upgrade_mod_savepoint(true, 2026090301, 'zugang');
    }

    // 2026090302: the 2026090301 step above only added columns that were
    // completely MISSING. On sites where a column existed but with the
    // wrong attributes from the original partial install (seen in
    // practice: zugang.wlanlistid / zugang.docklistid created as NOT
    // NULL even though install.xml always declared them nullable), that
    // step was a no-op and the bad attribute survived. This step
    // reconciles nullability for every field the schema declares
    // nullable, on every table, regardless of whether the column already
    // existed — so it also fixes columns add_field() skipped last time.
    if ($oldversion < 2026090302) {

        // [table => [fieldname => [type, precision, notnull]]] for every
        // NULLABLE field in db/install.xml. NOTNULL fields are left alone:
        // relaxing nullability is always safe, tightening it is not (would
        // require a fill value for existing NULL rows), so this only ever
        // loosens constraints to match the schema, never the reverse.
        $nullablefields = [
            'zugang' => [
                'wlanlistid' => [XMLDB_TYPE_INTEGER, '10'],
                'docklistid' => [XMLDB_TYPE_INTEGER, '10'],
            ],
            'zugang_list' => [
                'description' => [XMLDB_TYPE_TEXT, null],
            ],
            'zugang_list_entry' => [
                'firstname'       => [XMLDB_TYPE_CHAR, '255'],
                'lastname'        => [XMLDB_TYPE_CHAR, '255'],
                'classname'       => [XMLDB_TYPE_CHAR, '255'],
                'userid'          => [XMLDB_TYPE_INTEGER, '10'],
                'suggesteduserid' => [XMLDB_TYPE_INTEGER, '10'],
                'suggestedscore'  => [XMLDB_TYPE_NUMBER, '5, 2'],
            ],
            'zugang_reveal_log' => [
                'timedeleted' => [XMLDB_TYPE_INTEGER, '10'],
            ],
        ];

        foreach ($nullablefields as $tablename => $fields) {
            if (!$dbman->table_exists($tablename)) {
                continue;
            }
            $table = new xmldb_table($tablename);
            foreach ($fields as $fieldname => [$type, $precision]) {
                $field = new xmldb_field($fieldname, $type, $precision, null, false, null, null);
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                } else {
                    // Column already there — align its nullability with
                    // the schema even if everything else about it matches.
                    $dbman->change_field_notnull($table, $field);
                }
            }
        }

        upgrade_mod_savepoint(true, 2026090302, 'zugang');
    }

    return true;
}
