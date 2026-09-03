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
 * AJAX endpoint: decrypt and return the caller's own password for one
 * entry, if (and only if) it is confirmed to belong to them and is
 * reachable via a list attached to the given course module.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/zugang/classes/crypto.php');
require_once($CFG->dirroot . '/mod/zugang/classes/event/password_revealed.php');

require_login();
require_sesskey();

$cmid = required_param('cmid', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('zugang', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/zugang:reveal', $context);

$zugang = $DB->get_record('zugang', ['id' => $cm->instance], '*', MUST_EXIST);

$entry = $DB->get_record('zugang_list_entry', ['id' => $entryid], '*', MUST_EXIST);

// The entry must belong to a list this specific activity instance
// actually references, and must be confirmed for THIS user.
$validlist = in_array((int) $entry->listid, array_filter([(int) $zugang->wlanlistid, (int) $zugang->docklistid]), true);
if (!$validlist || $entry->matchstatus !== 'confirmed' || (int) $entry->userid !== (int) $USER->id) {
    http_response_code(403);
    die(json_encode(['error' => get_string('nopermissions', 'error', '')]));
}

try {
    $password = \mod_zugang\crypto::decrypt($entry->ciphertext, $entry->cipheriv);
} catch (\Throwable $e) {
    http_response_code(500);
    die(json_encode(['error' => get_string('decryptionfailed', 'mod_zugang')]));
}

$DB->insert_record('zugang_reveal_log', (object) [
    'entryid'      => $entry->id,
    'cmid'         => $cm->id,
    'userid'       => $USER->id,
    'timerevealed' => time(),
    'timedeleted'  => null,
]);

$event = \mod_zugang\event\password_revealed::create([
    'objectid' => $entry->id,
    'context'  => $context,
]);
$event->trigger();

header('Content-Type: application/json');
echo json_encode(['password' => $password]);
