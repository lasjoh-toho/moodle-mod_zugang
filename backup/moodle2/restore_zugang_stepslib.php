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
 * Restore step for a single mod_zugang activity instance.
 *
 * wlanlistid/docklistid reference site-wide zugang_list rows that are
 * NOT part of this backup (see backup_zugang_stepslib.php for why).
 * On restore within the SAME site (course duplication, or restoring a
 * backup on the site it came from) those ids still resolve correctly,
 * so we keep them as-is. On restore to a DIFFERENT site — where those
 * list ids are meaningless or point at unrelated data — we defensively
 * null them out rather than risk silently wiring the activity to the
 * wrong list; the restored activity then simply shows "no list
 * configured" (see view.php) instead of erroring or leaking someone
 * else's password list.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class restore_zugang_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $paths[] = new restore_path_element('zugang', '/activity/zugang');
        return $this->prepare_activity_structure($paths);
    }

    protected function process_zugang($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->wlanlistid = $this->resolve_list_reference($data->wlanlistid ?? null);
        $data->docklistid = $this->resolve_list_reference($data->docklistid ?? null);

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }
        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        $newitemid = $DB->insert_record('zugang', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * @param int|null $listid as recorded in the backup file
     * @return int|null the same id if it still resolves on THIS site,
     *         otherwise null
     */
    protected function resolve_list_reference($listid) {
        global $DB;
        if (empty($listid)) {
            return null;
        }
        if ($DB->record_exists('zugang_list', ['id' => (int) $listid])) {
            return (int) $listid;
        }
        // Doesn't resolve here (different site, or the list was deleted
        // since the backup was taken) — drop the reference rather than
        // pointing at whatever unrelated list now happens to have that id.
        return null;
    }

    protected function after_execute() {
        $this->add_related_files('mod_zugang', 'intro', null);
        $this->add_related_files('mod_zugang', 'zipfile', null);
    }
}
