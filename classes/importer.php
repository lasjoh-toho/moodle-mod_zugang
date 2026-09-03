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

namespace mod_zugang;

defined('MOODLE_INTERNAL') || die();

/**
 * Parses an uploaded CSV password list.
 *
 * Native/expected format (as exported by the school's account system):
 *   Vorname;Nachname;Benutzerkennung;Initialkennwort;Klassen (aktiv)
 *
 * "Benutzerkennung" is usually an email-like address (e.g.
 * vorname.nachname@schule.de) that is the matching "prefix" against
 * Moodle accounts, but is not guaranteed to line up exactly — hence the
 * separate matcher::class review step.
 *
 * A minimal 2-column "identifier;password" file is also accepted, for
 * lists that don't carry name/class metadata (e.g. Dock-Kennwörter that
 * aren't per-person). Header row is optional in both cases; delimiter
 * is auto-detected between ";" and ",".
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {

    /** Recognised header names (lowercase) mapped to our internal field. */
    const HEADER_MAP = [
        'vorname'          => 'firstname',
        'nachname'         => 'lastname',
        'benutzerkennung'  => 'identifier',
        'benutzername'     => 'identifier',
        'kennung'          => 'identifier',
        'username'         => 'identifier',
        'account'          => 'identifier',
        'prefix'           => 'identifier',
        'initialkennwort'  => 'password',
        'kennwort'         => 'password',
        'passwort'         => 'password',
        'password'         => 'password',
        'klassen (aktiv)'  => 'classname',
        'klassen'          => 'classname',
        'klasse'           => 'classname',
    ];

    /**
     * @param string $path path to the uploaded file on disk
     * @return array<int, array{identifier:string, password:string,
     *         firstname:?string, lastname:?string, classname:?string}>
     * @throws \moodle_exception if the file cannot be parsed at all
     */
    public static function parse_csv(string $path): array {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \moodle_exception('importfileunreadable', 'mod_zugang');
        }

        // Strip a UTF-8 BOM if present (common from Excel exports).
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) {
            throw new \moodle_exception('importfileempty', 'mod_zugang');
        }

        $delim = (substr_count($lines[0], ';') >= substr_count($lines[0], ',')) ? ';' : ',';

        // Try to recognise a header row and build a column => field map.
        $firstfields = array_map('trim', str_getcsv($lines[0], $delim));
        $colmap = [];
        foreach ($firstfields as $idx => $label) {
            $key = \core_text::strtolower(trim($label));
            if (isset(self::HEADER_MAP[$key])) {
                $colmap[$idx] = self::HEADER_MAP[$key];
            }
        }
        $hasrecognisedheader = in_array('identifier', $colmap, true) && in_array('password', $colmap, true);

        if (!$hasrecognisedheader) {
            // No usable header: fall back to positional defaults.
            // 5+ columns => Vorname;Nachname;Benutzerkennung;Initialkennwort;Klassen
            // else       => identifier;password
            $samplecols = count(str_getcsv($lines[0], $delim));
            $colmap = $samplecols >= 5
                ? [0 => 'firstname', 1 => 'lastname', 2 => 'identifier', 3 => 'password', 4 => 'classname']
                : [0 => 'identifier', 1 => 'password'];
        }

        $rows = [];
        foreach ($lines as $i => $line) {
            if ($i === 0 && $hasrecognisedheader) {
                continue; // Skip the real header row we just parsed.
            }
            $fields = array_map('trim', str_getcsv($line, $delim));

            $row = ['identifier' => '', 'password' => '', 'firstname' => null, 'lastname' => null, 'classname' => null];
            foreach ($colmap as $idx => $field) {
                if (isset($fields[$idx]) && $fields[$idx] !== '') {
                    $row[$field] = $fields[$idx];
                }
            }

            if ($row['identifier'] === '' || $row['password'] === '') {
                continue; // Skip malformed/blank lines rather than failing the whole import.
            }

            $rows[] = $row;
        }

        if (empty($rows)) {
            throw new \moodle_exception('importnovalidrows', 'mod_zugang');
        }

        return $rows;
    }
}
