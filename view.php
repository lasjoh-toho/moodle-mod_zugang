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
 * View page: shows one reveal button per password list (WLAN/Dock) that
 * this activity references AND that has a confirmed entry for $USER.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/zugang/classes/list_manager.php');

$id = required_param('id', PARAM_INT); // Course module id.

$cm = get_coursemodule_from_id('zugang', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$zugang = $DB->get_record('zugang', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/zugang:view', $context);

$event = \mod_zugang\event\course_module_viewed::create([
    'objectid' => $zugang->id,
    'context'  => $context,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('zugang', $zugang);
$event->trigger();

$PAGE->set_url('/mod/zugang/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($zugang->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/zugang/styles.css');

$canreveal = has_capability('mod/zugang:reveal', $context);
$candelete = has_capability('mod/zugang:deleteownpassword', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($zugang->name));

if ($zugang->intro) {
    echo $OUTPUT->box(format_module_intro('zugang', $zugang, $cm->id), 'generalbox mod_introbox', 'zugangintro');
}

$panels = [];
foreach ([
    'wlan' => ['listid' => $zugang->wlanlistid, 'labelkey' => 'wlanlist'],
    'dock' => ['listid' => $zugang->docklistid, 'labelkey' => 'docklist'],
] as $type => $info) {
    if (empty($info['listid'])) {
        continue;
    }
    $list = $DB->get_record('zugang_list', ['id' => $info['listid']]);
    if (!$list) {
        continue; // Referenced list no longer exists (e.g. deleted, or a stale cross-site backup reference).
    }
    $entry = $canreveal ? \mod_zugang\list_manager::get_confirmed_entry_for_user((int) $info['listid'], (int) $USER->id) : false;
    $panels[] = (object) [
        'type'     => $type,
        'label'    => get_string($info['labelkey'], 'mod_zugang'),
        'listname' => format_string($list->name),
        'entryid'  => $entry ? (int) $entry->id : 0,
        'hasentry' => (bool) $entry,
    ];
}

if (empty($panels)) {
    echo $OUTPUT->notification(get_string('nolistsconfigured', 'mod_zugang'), 'info');
} else {
    echo html_writer::start_div('zugang-panels');
    foreach ($panels as $panel) {
        echo html_writer::start_div('zugang-panel', ['data-zugang-type' => $panel->type]);
        echo html_writer::tag('h4', $panel->label . ' — ' . $panel->listname);
        if (!$canreveal) {
            // Nothing to do here for non-students (e.g. viewing teachers).
            echo html_writer::div(get_string('viewonlynote', 'mod_zugang'), 'text-muted');
        } else if (!$panel->hasentry) {
            echo html_writer::div(get_string('nopasswordforyou', 'mod_zugang'), 'text-muted');
        } else {
            echo html_writer::start_div('zugang-reveal-widget', ['data-entryid' => $panel->entryid]);
            echo html_writer::tag('button', get_string('revealbutton', 'mod_zugang'),
                ['class' => 'btn btn-primary zugang-reveal-btn', 'type' => 'button']);
            echo html_writer::div('', 'zugang-password-display', ['style' => 'display:none']);
            echo html_writer::div('', 'zugang-countdown text-muted', ['style' => 'display:none']);
            if ($candelete) {
                echo html_writer::tag('button', get_string('deletebutton', 'mod_zugang'),
                    ['class' => 'btn btn-outline-danger btn-sm zugang-delete-btn', 'type' => 'button',
                     'style' => 'display:none', 'data-confirm' => get_string('deleteconfirm', 'mod_zugang')]);
            }
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}

$PAGE->requires->js('/mod/zugang/javascript/reveal.js');
$PAGE->requires->strings_for_js(['passworddeletedconfirm'], 'mod_zugang');
$PAGE->requires->js_init_call('M.mod_zugang.init', [
    $cm->id,
    (int) $zugang->revealseconds,
    sesskey(),
], true);

echo $OUTPUT->footer();
