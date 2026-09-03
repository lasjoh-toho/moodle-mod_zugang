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
 * Admin review page: shows every entry of a list with its match status.
 * Confirmed rows are shown for transparency; pending (fuzzy-suggested)
 * and unmatched rows can be confirmed against a chosen account, or
 * rejected. Enforces one Moodle account per list.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/zugang/classes/list_manager.php');

require_login();
$context = context_system::instance();
require_capability('mod/zugang:managelists', $context);

$listid = required_param('listid', PARAM_INT);
$list = \mod_zugang\list_manager::get_list($listid);

$PAGE->set_url('/mod/zugang/review.php', ['listid' => $listid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('review', 'mod_zugang') . ': ' . format_string($list->name));
$PAGE->set_heading(get_string('review', 'mod_zugang') . ': ' . format_string($list->name));
$PAGE->set_pagelayout('admin');

$action = optional_param('action', '', PARAM_ALPHA);
$entryid = optional_param('entryid', 0, PARAM_INT);

if ($action && $entryid) {
    require_sesskey();
    try {
        if ($action === 'confirm') {
            $chosenuserid = required_param('userid', PARAM_INT);
            \mod_zugang\list_manager::confirm_entry($entryid, $chosenuserid);
            \core\notification::success(get_string('entryconfirmed', 'mod_zugang'));
        } else if ($action === 'reject') {
            \mod_zugang\list_manager::reject_entry($entryid);
            \core\notification::success(get_string('entryrejected', 'mod_zugang'));
        }
    } catch (\Throwable $e) {
        \core\notification::error($e->getMessage());
    }
    redirect(new moodle_url('/mod/zugang/review.php', ['listid' => $listid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('review', 'mod_zugang') . ': ' . format_string($list->name));

global $DB;

// All candidate users, for the manual-override dropdown. Same "active
// users only" pool the matcher itself uses.
$allusers = $DB->get_records_select('user', 'deleted = 0 AND suspended = 0 AND id > 1', null, 'lastname, firstname',
    'id, username, firstname, lastname, email');
$useroptions = [0 => get_string('choosedots')];
foreach ($allusers as $u) {
    $useroptions[$u->id] = fullname($u) . ' (' . $u->username . ')';
}

$entries = $DB->get_records('zugang_list_entry', ['listid' => $listid], 'matchstatus DESC, sourceref ASC');

$statusorder = ['pending' => 0, 'unmatched' => 1, 'confirmed' => 2, 'rejected' => 3];
uasort($entries, fn($a, $b) => ($statusorder[$a->matchstatus] ?? 9) <=> ($statusorder[$b->matchstatus] ?? 9));

$table = new html_table();
$table->head = [
    get_string('sourceref', 'mod_zugang'),
    get_string('nameinlist', 'mod_zugang'),
    get_string('classname', 'mod_zugang'),
    get_string('matchstatus', 'mod_zugang'),
    get_string('assignedaccount', 'mod_zugang'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable';

foreach ($entries as $entry) {
    $statusbadge = [
        'confirmed' => 'badge-success',
        'pending'   => 'badge-warning',
        'unmatched' => 'badge-secondary',
        'rejected'  => 'badge-dark',
    ][$entry->matchstatus] ?? 'badge-light';
    $statustext = html_writer::tag('span', get_string('matchstatus_' . $entry->matchstatus, 'mod_zugang'),
        ['class' => 'badge ' . $statusbadge]);

    $namebits = trim(($entry->firstname ?? '') . ' ' . ($entry->lastname ?? ''));

    if ($entry->matchstatus === 'confirmed' && $entry->userid) {
        $u = $allusers[$entry->userid] ?? null;
        $assigned = $u ? fullname($u) . ' (' . $u->username . ')' : '#' . $entry->userid;
        $actions = html_writer::link(
            new moodle_url('/mod/zugang/review.php', ['listid' => $listid, 'action' => 'reject', 'entryid' => $entry->id, 'sesskey' => sesskey()]),
            get_string('revoke', 'mod_zugang')
        );
    } else {
        $suggested = $entry->suggesteduserid && isset($allusers[$entry->suggesteduserid])
            ? fullname($allusers[$entry->suggesteduserid]) . ' (' . $allusers[$entry->suggesteduserid]->username . ')'
                . ' — ' . get_string('scorepercent', 'mod_zugang', $entry->suggestedscore)
            : get_string('nosuggestion', 'mod_zugang');
        $assigned = $suggested;

        $formid = 'confirmform' . $entry->id;
        $actions = html_writer::start_tag('form', [
            'method' => 'post', 'id' => $formid,
            'action' => new moodle_url('/mod/zugang/review.php', ['listid' => $listid]),
            'class' => 'form-inline',
        ]);
        $actions .= html_writer::input_hidden_params(new moodle_url('', [
            'sesskey' => sesskey(), 'action' => 'confirm', 'entryid' => $entry->id,
        ]));
        $actions .= html_writer::select($useroptions, 'userid', $entry->suggesteduserid ?: 0, null, ['class' => 'form-control form-control-sm mr-2']);
        $actions .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('confirm', 'mod_zugang'), 'class' => 'btn btn-sm btn-success mr-2']);
        $actions .= html_writer::end_tag('form');
        $actions .= html_writer::link(
            new moodle_url('/mod/zugang/review.php', ['listid' => $listid, 'action' => 'reject', 'entryid' => $entry->id, 'sesskey' => sesskey()]),
            get_string('reject', 'mod_zugang'), ['class' => 'small text-muted']
        );
    }

    $table->data[] = [
        s($entry->sourceref),
        s($namebits),
        s($entry->classname ?? ''),
        $statustext,
        $assigned,
        $actions,
    ];
}

echo html_writer::table($table);

echo html_writer::div(
    html_writer::link(new moodle_url('/mod/zugang/managelists.php'), get_string('backtolists', 'mod_zugang')),
    'mt-4'
);

echo $OUTPUT->footer();
