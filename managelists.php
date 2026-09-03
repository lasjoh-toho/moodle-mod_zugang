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
 * Admin overview of all site-wide password lists: create new ones,
 * jump to upload/review, delete.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/zugang/classes/list_manager.php');

require_login();
$context = context_system::instance();
require_capability('mod/zugang:managelists', $context);

$PAGE->set_url('/mod/zugang/managelists.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('managelists', 'mod_zugang'));
$PAGE->set_heading(get_string('managelists', 'mod_zugang'));
$PAGE->set_pagelayout('admin');

$deleteid = optional_param('delete', 0, PARAM_INT);
if ($deleteid) {
    require_sesskey();
    \mod_zugang\list_manager::delete_list($deleteid);
    redirect(new moodle_url('/mod/zugang/managelists.php'), get_string('listdeleted', 'mod_zugang'));
}

// Create-new-list form (deliberately plain HTML+require_sesskey rather
// than moodleform, to keep this single-purpose page lightweight).
$createname = optional_param('createname', '', PARAM_TEXT);
$createtype = optional_param('createtype', '', PARAM_ALPHA);
if ($createname !== '' && $createtype !== '' && data_submitted()) {
    require_sesskey();
    if (!array_key_exists($createtype, \mod_zugang\list_manager::get_types())) {
        throw new moodle_exception('invalidlisttype', 'mod_zugang');
    }
    $newid = \mod_zugang\list_manager::create_list($createtype, $createname, null, (int) $USER->id);
    redirect(new moodle_url('/mod/zugang/upload.php', ['listid' => $newid]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managelists', 'mod_zugang'));

global $DB;

$table = new html_table();
$table->head = [
    get_string('listname', 'mod_zugang'),
    get_string('listtype', 'mod_zugang'),
    get_string('entries', 'mod_zugang'),
    get_string('pendingentries', 'mod_zugang'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable';

foreach (\mod_zugang\list_manager::get_lists() as $list) {
    $total = $DB->count_records('zugang_list_entry', ['listid' => $list->id]);
    $pending = $DB->count_records('zugang_list_entry', ['listid' => $list->id, 'matchstatus' => 'pending']);

    $uploadurl = new moodle_url('/mod/zugang/upload.php', ['listid' => $list->id]);
    $reviewurl = new moodle_url('/mod/zugang/review.php', ['listid' => $list->id]);
    $deleteurl = new moodle_url('/mod/zugang/managelists.php', ['delete' => $list->id, 'sesskey' => sesskey()]);

    $actions = html_writer::link($uploadurl, get_string('uploadfile', 'mod_zugang')) . ' | '
        . html_writer::link($reviewurl, get_string('review', 'mod_zugang')) . ' | '
        . html_writer::link($deleteurl, get_string('delete'), [
            'onclick' => "return confirm('" . get_string('deletelistconfirm', 'mod_zugang') . "');",
        ]);

    $typelabel = get_string(\mod_zugang\list_manager::get_types()[$list->listtype], 'mod_zugang');
    $pendingcell = $pending > 0
        ? html_writer::tag('span', $pending, ['class' => 'badge badge-warning'])
        : '0';

    $table->data[] = [format_string($list->name), $typelabel, $total, $pendingcell, $actions];
}

echo html_writer::table($table);

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mt-4']);
echo html_writer::input_hidden_params(new moodle_url('', ['sesskey' => sesskey()]));
echo html_writer::tag('h4', get_string('createlist', 'mod_zugang'));
echo html_writer::start_div('form-inline');
echo html_writer::label(get_string('listname', 'mod_zugang'), 'createname', false, ['class' => 'mr-2']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'createname', 'id' => 'createname', 'class' => 'form-control mr-3', 'required' => 'required']);
echo html_writer::label(get_string('listtype', 'mod_zugang'), 'createtype', false, ['class' => 'mr-2']);
echo html_writer::select(
    array_map(fn($k) => get_string($k, 'mod_zugang'), \mod_zugang\list_manager::get_types()),
    'createtype', '', ['' => get_string('choosedots')], ['class' => 'mr-3']
);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('createlist', 'mod_zugang'), 'class' => 'btn btn-primary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
