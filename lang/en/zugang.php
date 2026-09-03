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
 * English (fallback) language strings for mod_zugang.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Zugang';
$string['modulename'] = 'Zugang';
$string['modulenameplural'] = 'Zugang activities';
$string['modulename_help'] = 'The "Zugang" activity shows students their personal WLAN and/or dock password from an admin-uploaded, encrypted-at-rest list.';
$string['pluginadministration'] = 'Zugang administration';

$string['zugangname'] = 'Activity name';
$string['lists'] = 'Password lists';
$string['wlanlist'] = 'WLAN password list';
$string['wlanlist_help'] = 'Pick a WLAN password list previously created under "Manage lists", or "None".';
$string['docklist'] = 'Dock password list';
$string['docklist_help'] = 'Pick a dock password list previously created under "Manage lists", or "None".';
$string['managelists'] = 'Manage lists';
$string['revealseconds'] = 'Reveal duration';
$string['revealseconds_help'] = 'How long the decrypted password stays visible after clicking "Reveal" before it auto-hides (default: 2 minutes).';

$string['nolistsconfigured'] = 'No password list is configured for this activity.';
$string['viewonlynote'] = 'Only students with a matching entry see a button here.';
$string['nopasswordforyou'] = 'There is no password for you in this list.';
$string['revealbutton'] = 'Reveal password';
$string['accountlabel'] = 'Account: {$a}';
$string['deletebutton'] = 'Delete password';
$string['deleteconfirm'] = 'Permanently delete this password? This cannot be undone.';
$string['passworddeletedconfirm'] = 'The password has been deleted.';

$string['noinstances'] = 'There are no Zugang activities in this course yet.';

$string['listname'] = 'Name';
$string['listtype'] = 'Type';
$string['listtypewlan'] = 'WLAN passwords';
$string['listtypedock'] = 'Dock passwords';
$string['entries'] = 'Entries';
$string['pendingentries'] = 'Pending matches';
$string['createlist'] = 'Create new list';
$string['uploadfile'] = 'Upload file';
$string['review'] = 'Review matches';
$string['deletelistconfirm'] = 'Permanently delete this list and all its entries?';
$string['listdeleted'] = 'List deleted.';
$string['backtolists'] = '« Back to lists';
$string['invalidlisttype'] = 'Invalid list type.';

$string['uploadinstructions'] = 'Expected format (semicolon- or comma-separated, header row optional): <code>Vorname;Nachname;Benutzerkennung;Initialkennwort;Klassen (aktiv)</code>. A plain <code>identifier;password</code> list is also accepted. Re-uploading replaces all existing entries in this list.';
$string['importsuccess'] = 'Import complete: {$a->total} rows processed — {$a->confirmed} auto-matched, {$a->pending} suggested for review, {$a->unmatched} with no matching account.';
$string['importfailed'] = 'Import failed: {$a}';
$string['importfiletoobig'] = 'The file is empty or too large (max 5 MB).';
$string['importfileunreadable'] = 'The uploaded file could not be read.';
$string['importfileempty'] = 'The uploaded file is empty.';
$string['importnovalidrows'] = 'No valid rows (identifier + password) were found in the file.';
$string['gotoreview'] = 'Review matches now »';

$string['sourceref'] = 'Identifier (list)';
$string['nameinlist'] = 'Name (list)';
$string['classname'] = 'Class';
$string['matchstatus'] = 'Status';
$string['matchstatus_confirmed'] = 'Matched';
$string['matchstatus_pending'] = 'Suggested';
$string['matchstatus_unmatched'] = 'No match';
$string['matchstatus_rejected'] = 'Rejected';
$string['assignedaccount'] = 'Moodle account';
$string['nosuggestion'] = 'No suggestion available';
$string['scorepercent'] = '{$a}% match';
$string['confirm'] = 'Confirm';
$string['reject'] = 'Reject';
$string['revoke'] = 'Revoke match';
$string['entryconfirmed'] = 'Match confirmed.';
$string['entryrejected'] = 'Match rejected.';
$string['userwithlistalreadyassigned'] = 'That account is already matched to another entry in this list.';

$string['encryptionkeymissing'] = 'The mod_zugang encryption key is missing. Please reinstall the plugin.';
$string['encryptionkeyinvalid'] = 'The stored mod_zugang encryption key is invalid.';
$string['encryptionfailed'] = 'Encryption failed.';
$string['decryptionfailed'] = 'Decryption failed.';
$string['sessionexpired'] = 'Your session has expired. Reload the page now?';
$string['entrygone'] = 'This entry is no longer current, e.g. because the list was re-uploaded in the meantime. Reload the page now?';
$string['cmidnotfound'] = 'No course module with id {$a} found. Please reload the page.';
$string['coursenotfound'] = 'No course with id {$a} found. Please reload the page.';
$string['zugangnotfound'] = 'No Zugang activity with id {$a} found. Please reload the page.';

$string['event_password_revealed'] = 'Password revealed';
$string['event_password_deleted'] = 'Password deleted';
$string['event_list_imported'] = 'Password list imported';

$string['zugang:addinstance'] = 'Add a new Zugang activity';
$string['zugang:view'] = 'View the Zugang activity';
$string['zugang:managelists'] = 'Manage password lists (upload, review matches)';
$string['zugang:reveal'] = 'Reveal own password';
$string['zugang:deleteownpassword'] = 'Delete own password';

$string['privacy:metadata:zugang_list_entry'] = 'A single password entry from an uploaded list, possibly matched to a Moodle account.';
$string['privacy:metadata:zugang_list_entry:userid'] = 'The Moodle account this entry is matched to.';
$string['privacy:metadata:zugang_list_entry:sourceref'] = 'The original identifier from the uploaded list.';
$string['privacy:metadata:zugang_list_entry:firstname'] = 'First name from the uploaded list.';
$string['privacy:metadata:zugang_list_entry:lastname'] = 'Last name from the uploaded list.';
$string['privacy:metadata:zugang_reveal_log'] = 'A log of when a user revealed or deleted their password.';
$string['privacy:metadata:zugang_reveal_log:userid'] = 'The user who performed the action.';
$string['privacy:metadata:zugang_reveal_log:timerevealed'] = 'Time the password was revealed.';
$string['privacy:metadata:zugang_reveal_log:timedeleted'] = 'Time the entry was deleted, if applicable.';
