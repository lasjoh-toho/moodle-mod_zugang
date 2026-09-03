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
 * List all mod_zugang instances in a course (standard module index page).
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_course_login($course);
$context = context_course::instance($course->id);

$event = \mod_zugang\event\course_module_instance_list_viewed::create(['context' => $context]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/zugang/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_zugang'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_zugang'));

$instances = get_all_instances_in_course('zugang', $course);

if (empty($instances)) {
    notice(get_string('noinstances', 'mod_zugang'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->head = [get_string('name')];
$table->attributes['class'] = 'generaltable mod_index';

foreach ($instances as $instance) {
    $cm = get_coursemodule_from_instance('zugang', $instance->id, $course->id);
    $link = html_writer::link(new moodle_url('/mod/zugang/view.php', ['id' => $cm->id]), format_string($instance->name));
    $table->data[] = [$link];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
