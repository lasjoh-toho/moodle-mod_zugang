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
 * AJAX endpoint: a student permanently deletes their own password entry
 * after having revealed it (or at any point afterwards). This removes
 * the encrypted row entirely — there is nothing left to leak, and the
 * mapping itself (which account this row belonged to) is intentionally
 * not preserved either, beyond the audit trail in zugang_reveal_log.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/zugang/classes/event/password_deleted.php');

require_login();
require_sesskey();

$cmid = required_param('cmid', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('zugang', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/zugang:deleteownpassword', $context);

$zugang = $DB->get_record('zugang', ['id' => $cm->instance], '*', MUST_EXIST);
$entry = $DB->get_record('zugang_list_entry', ['id' => $entryid], '*', MUST_EXIST);

$validlist = in_array((int) $entry->listid, array_filter([(int) $zugang->wlanlistid, (int) $zugang->docklistid]), true);
if (!$validlist || $entry->matchstatus !== 'confirmed' || (int) $entry->userid !== (int) $USER->id) {
    http_response_code(403);
    die(json_encode(['error' => get_string('nopermissions', 'error', '')]));
}

$DB->set_field_select(
    'zugang_reveal_log',
    'timedeleted',
    time(),
    'entryid = :entryid AND userid = :userid AND timedeleted IS NULL',
    ['entryid' => $entry->id, 'userid' => $USER->id]
);
$DB->delete_records('zugang_list_entry', ['id' => $entry->id]);

$event = \mod_zugang\event\password_deleted::create([
    'objectid' => $entry->id,
    'context'  => $context,
]);
$event->trigger();

header('Content-Type: application/json');
echo json_encode(['deleted' => true]);
