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
 * Backup step for a single mod_zugang activity instance.
 *
 * IMPORTANT: the actual password lists (zugang_list / zugang_list_entry)
 * are deliberately NOT backed up here. They are site-wide, shared across
 * courses, and contain encrypted credential material — pulling them into
 * a per-course backup file would (a) duplicate secrets into a portable
 * .mbz archive that can leave the server, and (b) make no sense on
 * restore, since the whole point is that many activities across many
 * courses point at the SAME list. Only the *reference* (which list ids
 * this instance points to) is backed up; see restore_zugang_stepslib.php
 * for how that reference is handled if it no longer resolves on restore.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class backup_zugang_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $zugang = new backup_nested_element('zugang', ['id'], [
            'name', 'intro', 'introformat', 'wlanlistid', 'docklistid',
            'revealseconds', 'timecreated', 'timemodified',
        ]);

        $zugang->set_source_table('zugang', ['id' => backup::VAR_ACTIVITYID]);
        $zugang->annotate_files('mod_zugang', 'intro', null);
        $zugang->annotate_files('mod_zugang', 'zipfile', null);

        // No userinfo-dependent data is included: reveal logs are an
        // audit trail tied to a specific site's users and are not
        // meaningful to carry across a course backup/restore/duplicate.

        return $this->prepare_activity_structure($zugang);
    }
}
