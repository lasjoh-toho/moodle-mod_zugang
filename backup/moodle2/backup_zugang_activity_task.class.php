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

require_once($CFG->dirroot . '/mod/zugang/backup/moodle2/backup_zugang_stepslib.php');
require_once($CFG->dirroot . '/mod/zugang/backup/moodle2/backup_zugang_settingslib.php');

class backup_zugang_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // No activity-specific settings beyond the standard ones.
    }

    protected function define_my_steps() {
        $this->add_step(new backup_zugang_activity_structure_step('zugang_structure', 'zugang.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;
        $base = preg_quote($CFG->wwwroot, '/');

        $content = preg_replace(
            "/(" . $base . "\/mod\/zugang\/view\.php\?id=)([0-9]+)/",
            '$@ZUGANGVIEWBYID*$2@$',
            $content
        );

        return $content;
    }
}
