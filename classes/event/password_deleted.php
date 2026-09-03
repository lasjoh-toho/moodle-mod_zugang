<?php
namespace mod_zugang\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when a student permanently deletes their own revealed password.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class password_deleted extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'zugang_list_entry';
    }

    public static function get_name() {
        return get_string('event_password_deleted', 'mod_zugang');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' deleted their own zugang password entry with id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/zugang/view.php', ['id' => $this->contextinstanceid]);
    }
}
