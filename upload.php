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
 * Upload (or replace) the CSV contents of one password list.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/zugang/classes/list_manager.php');
require_once($CFG->dirroot . '/mod/zugang/classes/importer.php');
require_once($CFG->dirroot . '/mod/zugang/classes/event/list_imported.php');

require_login();
$context = context_system::instance();
require_capability('mod/zugang:managelists', $context);

$listid = required_param('listid', PARAM_INT);
$list = \mod_zugang\list_manager::get_list($listid);

$PAGE->set_url('/mod/zugang/upload.php', ['listid' => $listid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('uploadfile', 'mod_zugang') . ': ' . format_string($list->name));
$PAGE->set_heading(get_string('uploadfile', 'mod_zugang') . ': ' . format_string($list->name));
$PAGE->set_pagelayout('admin');

$result = null;
if (data_submitted() && !empty($_FILES['csvfile']['tmp_name'])) {
    require_sesskey();

    $tmpname = $_FILES['csvfile']['tmp_name'];
    $size = (int) $_FILES['csvfile']['size'];
    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        \core\notification::error(get_string('importfiletoobig', 'mod_zugang'));
    } else {
        try {
            $rows = \mod_zugang\importer::parse_csv($tmpname);
            $result = \mod_zugang\list_manager::import_rows($listid, $rows);

            $event = \mod_zugang\event\list_imported::create(['objectid' => $listid]);
            $event->trigger();

            \core\notification::success(get_string('importsuccess', 'mod_zugang', (object) [
                'total'     => count($rows),
                'confirmed' => $result['confirmed'],
                'pending'   => $result['pending'],
                'unmatched' => $result['unmatched'],
            ]));
        } catch (\Throwable $e) {
            \core\notification::error(get_string('importfailed', 'mod_zugang', $e->getMessage()));
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadfile', 'mod_zugang') . ': ' . format_string($list->name));

echo html_writer::tag('p', get_string('uploadinstructions', 'mod_zugang'));

echo html_writer::start_tag('form', [
    'method' => 'post', 'enctype' => 'multipart/form-data',
    'action' => new moodle_url('/mod/zugang/upload.php', ['listid' => $listid]),
]);
echo html_writer::input_hidden_params(new moodle_url('', ['sesskey' => sesskey()]));
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'csvfile', 'accept' => '.csv,.txt', 'class' => 'form-control-file mb-3']);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('uploadfile', 'mod_zugang'), 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

if ($result) {
    echo html_writer::link(
        new moodle_url('/mod/zugang/review.php', ['listid' => $listid]),
        get_string('gotoreview', 'mod_zugang'),
        ['class' => 'btn btn-secondary mt-3']
    );
}

echo html_writer::div(
    html_writer::link(new moodle_url('/mod/zugang/managelists.php'), get_string('backtolists', 'mod_zugang')),
    'mt-4'
);

echo $OUTPUT->footer();
