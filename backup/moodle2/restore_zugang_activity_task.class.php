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
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/zugang/backup/moodle2/restore_zugang_stepslib.php');

class restore_zugang_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // No activity-specific settings.
    }

    protected function define_my_steps() {
        $this->add_step(new restore_zugang_activity_structure_step('zugang_structure', 'zugang.xml'));
    }

    static public function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('zugang', ['intro'], 'zugang');
        return $contents;
    }

    static public function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('ZUGANGVIEWBYID', '/mod/zugang/view.php?id=$1', 'course_module');
        return $rules;
    }

    public function get_fileareas() {
        return ['intro'];
    }

    public function get_configdata_encoded_attributes() {
        return [];
    }

    static public function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('zugang', 'view', 'view.php?id={course_module}', '{zugang}');
        return $rules;
    }
}
