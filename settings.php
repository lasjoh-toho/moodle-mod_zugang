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
 * Adds a link to the shared list-management UI under
 * Site administration > Plugins > Activity modules > Zugang.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = null; // No inline config fields; list management lives on its own admin page.
    $ADMIN->add('modsettings', new admin_externalpage(
        'mod_zugang_managelists',
        get_string('managelists', 'mod_zugang'),
        new moodle_url('/mod/zugang/managelists.php'),
        'mod/zugang:managelists'
    ));
}
