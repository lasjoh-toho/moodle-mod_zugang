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
 * At-rest encryption for password list entries.
 *
 * AES-256-GCM with a random 12-byte nonce per entry and a per-site key
 * generated once at install time (db/install.php) and stored as plugin
 * config. The GCM authentication tag is stored alongside the ciphertext,
 * so any tampering with a stored row is detected on decrypt rather than
 * silently returning garbage.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class crypto {

    /** @var string AES-256-GCM. */
    const CIPHER = 'aes-256-gcm';

    /**
     * @return string raw 32-byte key
     */
    protected static function get_key(): string {
        $encoded = get_config('mod_zugang', 'encryptionkey');
        if (empty($encoded)) {
            // Should only happen if install.php was skipped (e.g. plugin
            // files copied in manually rather than installed properly).
            throw new \moodle_exception('encryptionkeymissing', 'mod_zugang');
        }
        $key = base64_decode($encoded, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \moodle_exception('encryptionkeyinvalid', 'mod_zugang');
        }
        return $key;
    }

    /**
     * Encrypt a plaintext password.
     *
     * @param string $plaintext
     * @return array{ciphertext: string, iv: string} both base64-encoded;
     *         the GCM tag is appended to the ciphertext.
     */
    public static function encrypt(string $plaintext): array {
        $key = self::get_key();
        $iv = random_bytes(12);
        $tag = '';
        $raw = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($raw === false) {
            throw new \moodle_exception('encryptionfailed', 'mod_zugang');
        }
        return [
            'ciphertext' => base64_encode($raw . $tag),
            'iv'         => base64_encode($iv),
        ];
    }

    /**
     * Decrypt a stored entry.
     *
     * @param string $ciphertextb64
     * @param string $ivb64
     * @return string plaintext password
     */
    public static function decrypt(string $ciphertextb64, string $ivb64): string {
        $key = self::get_key();
        $iv = base64_decode($ivb64, true);
        $combined = base64_decode($ciphertextb64, true);
        if ($iv === false || $combined === false || strlen($combined) < 16) {
            throw new \moodle_exception('decryptionfailed', 'mod_zugang');
        }
        $tag = substr($combined, -16);
        $raw = substr($combined, 0, -16);
        $plaintext = openssl_decrypt($raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            // Wrong key or tampered data — the auth tag didn't verify.
            throw new \moodle_exception('decryptionfailed', 'mod_zugang');
        }
        return $plaintext;
    }
}
