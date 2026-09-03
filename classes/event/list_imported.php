<?php
namespace mod_zugang\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Fired when an admin (re-)imports a password list from an uploaded file.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_imported extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'zugang_list';
        $this->context = \context_system::instance();
    }

    public static function get_name() {
        return get_string('event_list_imported', 'mod_zugang');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' imported a password file into zugang list id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/zugang/review.php', ['listid' => $this->objectid]);
    }
}
