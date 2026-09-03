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

namespace mod_zugang\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for mod_zugang.
 *
 * Personal data stored: which list entry (if any) is matched to a user
 * (zugang_list_entry.userid), and the reveal/delete audit trail
 * (zugang_reveal_log). The password itself is not exported for a data
 * request (it is credential material, not the kind of personal data a
 * SAR export is meant to surface) — only the fact of the match and the
 * access log are exported. Deleting a user's data un-matches their list
 * entries (reverting them to "unmatched" for an admin to re-triage) and
 * removes their reveal-log rows; it does not delete other users' data
 * from the shared list.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('zugang_list_entry', [
            'userid'    => 'privacy:metadata:zugang_list_entry:userid',
            'sourceref' => 'privacy:metadata:zugang_list_entry:sourceref',
            'firstname' => 'privacy:metadata:zugang_list_entry:firstname',
            'lastname'  => 'privacy:metadata:zugang_list_entry:lastname',
        ], 'privacy:metadata:zugang_list_entry');

        $collection->add_database_table('zugang_reveal_log', [
            'userid'       => 'privacy:metadata:zugang_reveal_log:userid',
            'timerevealed' => 'privacy:metadata:zugang_reveal_log:timerevealed',
            'timedeleted'  => 'privacy:metadata:zugang_reveal_log:timedeleted',
        ], 'privacy:metadata:zugang_reveal_log');

        return $collection;
    }

    /**
     * Lists (and thus their entries) are site-wide, not course contexts,
     * so the only meaningful context here is the module context of each
     * activity through which a reveal/delete action was logged.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {zugang_reveal_log} rl ON rl.cmid = cm.id
                 WHERE rl.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_MODULE, 'userid' => $userid]);
        return $contextlist;
    }

    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        global $DB;
        $cm = get_coursemodule_from_id('zugang', $context->instanceid);
        if (!$cm) {
            return;
        }
        $sql = "SELECT userid FROM {zugang_reveal_log} WHERE cmid = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $cm->id]);
    }

    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('zugang', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $logs = $DB->get_records('zugang_reveal_log', ['cmid' => $cm->id, 'userid' => $userid]);
            if (empty($logs)) {
                continue;
            }
            $data = array_map(fn($l) => (object) [
                'timerevealed' => \core_privacy\local\request\transform::datetime($l->timerevealed),
                'timedeleted'  => $l->timedeleted ? \core_privacy\local\request\transform::datetime($l->timedeleted) : null,
            ], array_values($logs));
            writer::with_context($context)->export_data(['mod_zugang'], (object) ['reveal_log' => $data]);
        }
    }

    public static function delete_data_for_all_users_in_context(\core_privacy\local\request\context $context): void {
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        global $DB;
        $cm = get_coursemodule_from_id('zugang', $context->instanceid);
        if (!$cm) {
            return;
        }
        $DB->delete_records('zugang_reveal_log', ['cmid' => $cm->id]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('zugang', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('zugang_reveal_log', ['cmid' => $cm->id, 'userid' => $userid]);
        }

        // Un-match this user from any list entries site-wide (their
        // password stays encrypted in storage but is no longer linked to
        // their identity; an admin can re-triage it via the review page).
        $DB->set_field('zugang_list_entry', 'matchstatus', 'unmatched', ['userid' => $userid]);
        $DB->set_field('zugang_list_entry', 'userid', null, ['userid' => $userid]);
    }

    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        global $DB;
        $cm = get_coursemodule_from_id('zugang', $context->instanceid);
        if (!$cm) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('zugang_reveal_log', ['cmid' => $cm->id, 'userid' => $userid]);
        }
    }
}
