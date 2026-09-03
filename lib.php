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
 * Standard library functions for mod_zugang.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @param string $feature FEATURE_xx constant
 * @return mixed
 */
function zugang_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_MOD_ARCHETYPE:
            return false;
        case FEATURE_MOD_PURPOSE:
            return defined('MOD_PURPOSE_ADMINISTRATION') ? MOD_PURPOSE_ADMINISTRATION : MOD_PURPOSE_OTHER;
        default:
            return null;
    }
}

function zugang_add_instance($data, $mform = null) {
    global $DB;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->wlanlistid = !empty($data->wlanlistid) ? (int) $data->wlanlistid : null;
    $data->docklistid = !empty($data->docklistid) ? (int) $data->docklistid : null;
    $data->id = $DB->insert_record('zugang', $data);
    zugang_save_zipfile($data);
    return $data->id;
}

function zugang_update_instance($data, $mform = null) {
    global $DB;
    $data->timemodified = time();
    $data->id = $data->instance;
    $data->wlanlistid = !empty($data->wlanlistid) ? (int) $data->wlanlistid : null;
    $data->docklistid = !empty($data->docklistid) ? (int) $data->docklistid : null;
    $DB->update_record('zugang', $data);
    zugang_save_zipfile($data);
    return true;
}

/**
 * Persists the optional per-activity zip attachment (course/modlib.php
 * sets $data->coursemodule to the just-created/existing course module id
 * before calling add_instance()/update_instance(), so a valid module
 * context is already resolvable at this point even for a brand new
 * activity).
 */
function zugang_save_zipfile($data) {
    if (empty($data->coursemodule) || !isset($data->zipfile_filemanager)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    file_save_draft_area_files(
        $data->zipfile_filemanager,
        $context->id,
        'mod_zugang',
        'zipfile',
        0,
        ['subdirs' => 0, 'maxfiles' => 1]
    );
}

function zugang_delete_instance($id) {
    global $DB;
    if (!$DB->get_record('zugang', ['id' => $id])) {
        return false;
    }
    // The instance only *references* shared lists — deleting the activity
    // must never delete the lists themselves or other courses' access to
    // them, so there is nothing else to clean up here.
    $DB->delete_records('zugang', ['id' => $id]);
    return true;
}

/**
 * Used by course/moodleform when rendering activity chooser summaries etc.
 */
function zugang_get_coursemodule_info($coursemodule) {
    global $DB;
    $zugang = $DB->get_record('zugang', ['id' => $coursemodule->instance], 'id, name, intro, introformat');
    if (!$zugang) {
        return null;
    }
    $info = new cached_cm_info();
    $info->name = $zugang->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('zugang', $zugang, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Serves the optional per-activity zip attachment. No other file areas
 * are exposed by this plugin.
 */
function zugang_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    require_course_login($course, true, $cm);
    if (!has_capability('mod/zugang:view', $context)) {
        return false;
    }
    if ($filearea !== 'zipfile') {
        return false;
    }

    $itemid = (int) array_shift($args);
    if ($itemid !== 0) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_zugang', 'zipfile', 0, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
