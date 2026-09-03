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

namespace mod_zugang;

defined('MOODLE_INTERNAL') || die();

/**
 * CRUD + import/matching orchestration for zugang_list and its entries.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_manager {

    const TYPE_WLAN = 'wlan';
    const TYPE_DOCK = 'dock';

    /** @return array<string,string> valid list types => language string key */
    public static function get_types(): array {
        return [self::TYPE_WLAN => 'listtypewlan', self::TYPE_DOCK => 'listtypedock'];
    }

    public static function create_list(string $listtype, string $name, ?string $description, int $userid): int {
        global $DB;
        if (!array_key_exists($listtype, self::get_types())) {
            throw new \coding_exception('invalid listtype');
        }
        $now = time();
        return (int) $DB->insert_record('zugang_list', (object) [
            'listtype'     => $listtype,
            'name'         => $name,
            'description'  => $description,
            'usermodified' => $userid,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    public static function get_list(int $listid): \stdClass {
        global $DB;
        return $DB->get_record('zugang_list', ['id' => $listid], '*', MUST_EXIST);
    }

    public static function get_lists(?string $listtype = null): array {
        global $DB;
        $conditions = $listtype !== null ? ['listtype' => $listtype] : [];
        return $DB->get_records('zugang_list', $conditions, 'name ASC');
    }

    public static function delete_list(int $listid): void {
        global $DB;
        $DB->delete_records('zugang_reveal_log', [
            'entryid' => array_keys($DB->get_records('zugang_list_entry', ['listid' => $listid], '', 'id')),
        ]);
        $DB->delete_records('zugang_list_entry', ['listid' => $listid]);
        $DB->delete_records('zugang_list', ['id' => $listid]);
        // Activities referencing this list keep the now-dangling id; view.php
        // and mod_form.php both handle a missing list gracefully.
    }

    /**
     * Import parsed rows into a list: encrypts each password, runs
     * matching against Moodle accounts, and persists everything —
     * confirmed, pending AND unmatched rows are all saved, per the
     * requirement that discrepancies are kept for admin review rather
     * than dropped.
     *
     * Existing entries for this list are replaced entirely by the new
     * import (a fresh upload is treated as the new source of truth).
     *
     * @param int $listid
     * @param array<int, array{identifier:string,password:string,firstname:?string,lastname:?string,classname:?string}> $rows
     * @return array{confirmed:int, pending:int, unmatched:int}
     */
    public static function import_rows(int $listid, array $rows): array {
        global $DB;

        $matches = matcher::match_batch($rows);

        $transaction = $DB->start_delegated_transaction();

        // Replace old entries for this list.
        $DB->delete_records('zugang_reveal_log', [
            'entryid' => array_keys($DB->get_records('zugang_list_entry', ['listid' => $listid], '', 'id')),
        ]);
        $DB->delete_records('zugang_list_entry', ['listid' => $listid]);

        $counts = ['confirmed' => 0, 'pending' => 0, 'unmatched' => 0];
        $now = time();

        foreach ($rows as $key => $row) {
            $enc = crypto::encrypt($row['password']);
            $m = $matches[$key];

            $DB->insert_record('zugang_list_entry', (object) [
                'listid'          => $listid,
                'sourceref'       => $row['identifier'],
                'firstname'       => $row['firstname'] ?? null,
                'lastname'        => $row['lastname'] ?? null,
                'classname'       => $row['classname'] ?? null,
                'ciphertext'      => $enc['ciphertext'],
                'cipheriv'        => $enc['iv'],
                'matchstatus'     => $m['status'],
                'userid'          => $m['userid'],
                'suggesteduserid' => $m['suggesteduserid'],
                'suggestedscore'  => $m['score'],
                'timecreated'     => $now,
                'timemodified'    => $now,
            ]);
            $counts[$m['status'] === 'confirmed' ? 'confirmed' : ($m['status'] === 'pending' ? 'pending' : 'unmatched')]++;
        }

        $DB->update_record('zugang_list', (object) ['id' => $listid, 'timemodified' => $now]);

        $transaction->allow_commit();
        return $counts;
    }

    /**
     * Admin approves a suggested (or manually chosen) match.
     * Enforces the one-account-per-list rule.
     */
    public static function confirm_entry(int $entryid, int $userid): void {
        global $DB;
        $entry = $DB->get_record('zugang_list_entry', ['id' => $entryid], '*', MUST_EXIST);

        $clash = $DB->record_exists_select(
            'zugang_list_entry',
            'listid = :listid AND userid = :userid AND id != :id',
            ['listid' => $entry->listid, 'userid' => $userid, 'id' => $entryid]
        );
        if ($clash) {
            throw new \moodle_exception('userwithlistalreadyassigned', 'mod_zugang');
        }

        $DB->update_record('zugang_list_entry', (object) [
            'id'              => $entryid,
            'matchstatus'     => 'confirmed',
            'userid'          => $userid,
            'suggesteduserid' => null,
            'timemodified'    => time(),
        ]);
    }

    public static function reject_entry(int $entryid): void {
        global $DB;
        $DB->update_record('zugang_list_entry', (object) [
            'id'              => $entryid,
            'matchstatus'     => 'rejected',
            'userid'          => null,
            'suggesteduserid' => null,
            'timemodified'    => time(),
        ]);
    }

    /**
     * @return \stdClass|false the confirmed entry for this user in this
     *         list, or false if none exists / it was deleted
     */
    public static function get_confirmed_entry_for_user(int $listid, int $userid) {
        global $DB;
        return $DB->get_record('zugang_list_entry', [
            'listid'      => $listid,
            'userid'      => $userid,
            'matchstatus' => 'confirmed',
        ]);
    }
}
