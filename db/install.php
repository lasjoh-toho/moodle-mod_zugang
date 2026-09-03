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
 * Post-install: generate a per-site encryption key for mod_zugang.
 *
 * The key never leaves the server, is never shown in the UI, and is not
 * included in course backups. It is stored as plugin config, which
 * benefits from Moodle's standard DB access controls. Losing this key
 * (e.g. restoring $CFG->dataroot/DB from different points in time) makes
 * all previously stored passwords permanently unrecoverable — this is
 * intentional: it is the same trade-off any at-rest encryption makes.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_zugang_install() {
    if (get_config('mod_zugang', 'encryptionkey') === false) {
        // 256-bit key for AES-256-GCM, stored base64-encoded.
        set_config('encryptionkey', base64_encode(random_bytes(32)), 'mod_zugang');
    }
    return true;
}
