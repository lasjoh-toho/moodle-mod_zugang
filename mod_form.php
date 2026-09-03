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
 * The main mod_zugang activity settings form.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/zugang/classes/list_manager.php');

class mod_zugang_mod_form extends moodleform_mod {

    public function definition() {
        global $PAGE;

        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('zugangname', 'mod_zugang'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'zuganglists', get_string('lists', 'mod_zugang'));

        $wlanoptions = [0 => get_string('none')] + $this->list_options(\mod_zugang\list_manager::TYPE_WLAN);
        $mform->addElement('select', 'wlanlistid', get_string('wlanlist', 'mod_zugang'), $wlanoptions);
        $mform->addHelpButton('wlanlistid', 'wlanlist', 'mod_zugang');

        $dockoptions = [0 => get_string('none')] + $this->list_options(\mod_zugang\list_manager::TYPE_DOCK);
        $mform->addElement('select', 'docklistid', get_string('docklist', 'mod_zugang'), $dockoptions);
        $mform->addHelpButton('docklistid', 'docklist', 'mod_zugang');

        if (has_capability('mod/zugang:managelists', context_system::instance())) {
            $managelink = new moodle_url('/mod/zugang/managelists.php');
            $mform->addElement('static', 'managelistslink', '',
                html_writer::link($managelink, get_string('managelists', 'mod_zugang'), ['target' => '_blank']));
        }

        $mform->addElement('duration', 'revealseconds', get_string('revealseconds', 'mod_zugang'),
            ['optional' => false, 'defaultunit' => 1]);
        $mform->setDefault('revealseconds', 120);
        $mform->addHelpButton('revealseconds', 'revealseconds', 'mod_zugang');
        $mform->setType('revealseconds', PARAM_INT);

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    protected function list_options(string $type): array {
        $options = [];
        foreach (\mod_zugang\list_manager::get_lists($type) as $list) {
            $options[$list->id] = $list->name;
        }
        return $options;
    }

    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        if (empty($defaultvalues['wlanlistid'])) {
            $defaultvalues['wlanlistid'] = 0;
        }
        if (empty($defaultvalues['docklistid'])) {
            $defaultvalues['docklistid'] = 0;
        }
    }
}
