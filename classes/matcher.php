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
 * Matches list-file rows ("Benutzerkennung" + optionally Vorname/Nachname)
 * to Moodle accounts.
 *
 * Exact matches (case/whitespace-insensitive, Benutzerkennung against
 * username or the local part of the email address) are auto-confirmed.
 * Everything else gets the closest candidate — scored using both the
 * identifier AND, when the file provides them, the Vorname/Nachname
 * fields against the Moodle account's real firstname/lastname — as a
 * *suggestion* an admin must approve on the review page. Nothing
 * ambiguous is ever auto-assigned.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class matcher {

    /** Minimum similarity (0-100) before we bother suggesting a candidate. */
    const MIN_SUGGEST_SCORE = 55.0;

    /** @return array<int, \stdClass> active, non-suspended, non-deleted users */
    protected static function get_candidate_users(): array {
        global $DB;
        return $DB->get_records_select(
            'user',
            'deleted = 0 AND suspended = 0 AND id > 1',
            null,
            'id',
            'id, username, email, firstname, lastname'
        );
    }

    /** @param string $ref raw identifier as it appeared in the file */
    public static function normalise(string $ref): string {
        $ref = trim(\core_text::strtolower($ref));
        $ref = trim($ref, " \t\n\r\0\x0B\"';,");
        if (strpos($ref, '@') !== false) {
            $ref = substr($ref, 0, strpos($ref, '@'));
        }
        return $ref;
    }

    /**
     * Best-effort similarity score (0-100) between one file row and one
     * Moodle candidate user, combining the identifier and, when present,
     * the file's Vorname/Nachname.
     */
    protected static function score(array $row, \stdClass $user): float {
        $needle = self::normalise($row['identifier']);
        $unameref = \core_text::strtolower($user->username);
        $emailref = self::normalise($user->email);
        $fullref = \core_text::strtolower($user->firstname . '.' . $user->lastname);

        $best = 0.0;
        if ($needle !== '') {
            foreach ([$unameref, $emailref, $fullref] as $candidateref) {
                if ($candidateref === '') {
                    continue;
                }
                similar_text($needle, $candidateref, $pct);
                $best = max($best, $pct);
            }
        }

        if (!empty($row['firstname']) || !empty($row['lastname'])) {
            $filename = \core_text::strtolower(trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')));
            $moodlename = \core_text::strtolower(trim($user->firstname . ' ' . $user->lastname));
            if ($filename !== '' && $moodlename !== '') {
                similar_text($filename, $moodlename, $pct);
                // Name match is a strong signal on its own — weight it in.
                $best = max($best, $pct);
            }
        }

        return $best;
    }

    protected static function is_exact(array $row, \stdClass $user): bool {
        $needle = self::normalise($row['identifier']);
        if ($needle === '') {
            return false;
        }
        return $needle === \core_text::strtolower($user->username) || $needle === self::normalise($user->email);
    }

    /**
     * Match a whole freshly-imported batch of rows, enforcing that each
     * Moodle user is claimed by at most one row (the "nur ein Account je
     * Liste" requirement). Exact matches are resolved first (in file
     * order) so they always win a contested slot over a fuzzy suggestion.
     *
     * @param array<int, array{identifier:string, firstname:?string, lastname:?string}> $rowsbykey
     * @return array<int, array{status:string, userid:?int, suggesteduserid:?int, score:?float}>
     */
    public static function match_batch(array $rowsbykey): array {
        $candidates = self::get_candidate_users();
        $results = [];
        $assigned = [];

        // Pass 1: exact matches, first-come-first-served.
        foreach ($rowsbykey as $key => $row) {
            foreach ($candidates as $user) {
                if (isset($assigned[$user->id])) {
                    continue;
                }
                if (self::is_exact($row, $user)) {
                    $results[$key] = ['status' => 'confirmed', 'userid' => (int) $user->id,
                        'suggesteduserid' => null, 'score' => 100.0];
                    $assigned[$user->id] = true;
                    break;
                }
            }
        }

        // Pass 2: best-scoring fuzzy suggestion for everything left.
        foreach ($rowsbykey as $key => $row) {
            if (isset($results[$key])) {
                continue;
            }
            $best = null;
            $bestscore = 0.0;
            foreach ($candidates as $user) {
                if (isset($assigned[$user->id])) {
                    continue;
                }
                $s = self::score($row, $user);
                if ($s > $bestscore) {
                    $bestscore = $s;
                    $best = (int) $user->id;
                }
            }
            if ($best !== null && $bestscore >= self::MIN_SUGGEST_SCORE) {
                $results[$key] = ['status' => 'pending', 'userid' => null,
                    'suggesteduserid' => $best, 'score' => round($bestscore, 2)];
                $assigned[$best] = true;
            } else {
                $results[$key] = ['status' => 'unmatched', 'userid' => null,
                    'suggesteduserid' => null, 'score' => null];
            }
        }

        return $results;
    }
}
