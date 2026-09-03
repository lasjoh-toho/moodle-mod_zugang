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
 * German language strings for mod_zugang.
 *
 * @package mod_zugang
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Zugang';
$string['modulename'] = 'Zugang';
$string['modulenameplural'] = 'Zugänge';
$string['modulename_help'] = 'Die Aktivität "Zugang" zeigt Schülerinnen und Schülern ihr persönliches WLAN- und/oder Dock-Kennwort aus einer vom Admin hochgeladenen, verschlüsselt gespeicherten Liste an.';
$string['pluginadministration'] = 'Zugang-Administration';

$string['zugangname'] = 'Name der Aktivität';
$string['lists'] = 'Kennwortlisten';
$string['wlanlist'] = 'WLAN-Kennwortliste';
$string['wlanlist_help'] = 'Wählen Sie eine zuvor unter "Listen verwalten" angelegte WLAN-Kennwortliste aus, oder "Keine".';
$string['docklist'] = 'Dock-Kennwortliste';
$string['docklist_help'] = 'Wählen Sie eine zuvor unter "Listen verwalten" angelegte Dock-Kennwortliste aus, oder "Keine".';
$string['managelists'] = 'Listen verwalten';
$string['revealseconds'] = 'Anzeigedauer';
$string['revealseconds_help'] = 'Wie lange das entschlüsselte Kennwort nach dem Klick auf "Anzeigen" sichtbar bleibt, bevor die Anzeige automatisch schließt (Standard: 2 Minuten).';

$string['nolistsconfigured'] = 'Für diese Aktivität ist keine Kennwortliste hinterlegt.';
$string['viewonlynote'] = 'Nur Schülerinnen und Schüler mit einem passenden Eintrag sehen hier einen Button.';
$string['nopasswordforyou'] = 'Für Sie liegt in dieser Liste kein Kennwort vor.';
$string['revealbutton'] = 'Kennwort anzeigen';
$string['deletebutton'] = 'Kennwort löschen';
$string['deleteconfirm'] = 'Kennwort endgültig löschen? Dies kann nicht rückgängig gemacht werden.';
$string['passworddeletedconfirm'] = 'Das Kennwort wurde gelöscht.';

$string['noinstances'] = 'Es gibt noch keine Zugang-Aktivität in diesem Kurs.';

$string['listname'] = 'Bezeichnung';
$string['listtype'] = 'Typ';
$string['listtypewlan'] = 'WLAN-Kennwörter';
$string['listtypedock'] = 'Dock-Kennwörter';
$string['entries'] = 'Einträge';
$string['pendingentries'] = 'Offene Zuordnungen';
$string['createlist'] = 'Neue Liste anlegen';
$string['uploadfile'] = 'Datei hochladen';
$string['review'] = 'Zuordnungen prüfen';
$string['deletelistconfirm'] = 'Diese Liste inklusive aller Einträge unwiderruflich löschen?';
$string['listdeleted'] = 'Liste wurde gelöscht.';
$string['backtolists'] = '« Zurück zur Listenübersicht';
$string['invalidlisttype'] = 'Ungültiger Listentyp.';

$string['uploadinstructions'] = 'Erwartetes Format (Semikolon- oder Komma-getrennt, mit oder ohne Kopfzeile): <code>Vorname;Nachname;Benutzerkennung;Initialkennwort;Klassen (aktiv)</code>. Alternativ genügt eine einfache Liste <code>Kennung;Kennwort</code>. Ein erneuter Upload ersetzt alle bisherigen Einträge dieser Liste.';
$string['importsuccess'] = 'Import abgeschlossen: {$a->total} Zeilen verarbeitet — {$a->confirmed} automatisch zugeordnet, {$a->pending} mit Vorschlag zur Prüfung, {$a->unmatched} ohne passenden Account.';
$string['importfailed'] = 'Import fehlgeschlagen: {$a}';
$string['importfiletoobig'] = 'Die Datei ist leer oder zu groß (max. 5 MB).';
$string['importfileunreadable'] = 'Die hochgeladene Datei konnte nicht gelesen werden.';
$string['importfileempty'] = 'Die hochgeladene Datei ist leer.';
$string['importnovalidrows'] = 'Es konnten keine gültigen Zeilen (Kennung + Kennwort) in der Datei gefunden werden.';
$string['gotoreview'] = 'Zuordnungen jetzt prüfen »';

$string['sourceref'] = 'Benutzerkennung (Liste)';
$string['nameinlist'] = 'Name (Liste)';
$string['classname'] = 'Klasse';
$string['matchstatus'] = 'Status';
$string['matchstatus_confirmed'] = 'Zugeordnet';
$string['matchstatus_pending'] = 'Vorschlag';
$string['matchstatus_unmatched'] = 'Kein Treffer';
$string['matchstatus_rejected'] = 'Abgelehnt';
$string['assignedaccount'] = 'Moodle-Account';
$string['nosuggestion'] = 'Kein Vorschlag verfügbar';
$string['scorepercent'] = '{$a}% Übereinstimmung';
$string['confirm'] = 'Übernehmen';
$string['reject'] = 'Ablehnen';
$string['revoke'] = 'Zuordnung aufheben';
$string['entryconfirmed'] = 'Zuordnung übernommen.';
$string['entryrejected'] = 'Zuordnung abgelehnt.';
$string['userwithlistalreadyassigned'] = 'Dieser Account ist in dieser Liste bereits einem anderen Eintrag zugeordnet.';

$string['encryptionkeymissing'] = 'Der Verschlüsselungsschlüssel für mod_zugang fehlt. Bitte das Plugin neu installieren.';
$string['encryptionkeyinvalid'] = 'Der gespeicherte Verschlüsselungsschlüssel für mod_zugang ist ungültig.';
$string['encryptionfailed'] = 'Verschlüsselung fehlgeschlagen.';
$string['decryptionfailed'] = 'Entschlüsselung fehlgeschlagen.';
$string['sessionexpired'] = 'Ihre Sitzung ist abgelaufen. Seite jetzt neu laden?';
$string['entrygone'] = 'Dieser Eintrag ist nicht mehr aktuell, z. B. weil die Liste zwischenzeitlich neu hochgeladen wurde. Seite jetzt neu laden?';

$string['event_password_revealed'] = 'Kennwort angezeigt';
$string['event_password_deleted'] = 'Kennwort gelöscht';
$string['event_list_imported'] = 'Kennwortliste importiert';

$string['zugang:addinstance'] = 'Neue Zugang-Aktivität hinzufügen';
$string['zugang:view'] = 'Zugang-Aktivität ansehen';
$string['zugang:managelists'] = 'Kennwortlisten verwalten (hochladen, Zuordnungen prüfen)';
$string['zugang:reveal'] = 'Eigenes Kennwort anzeigen';
$string['zugang:deleteownpassword'] = 'Eigenes Kennwort löschen';

$string['privacy:metadata:zugang_list_entry'] = 'Ein einzelner Kennwort-Eintrag aus einer hochgeladenen Liste, ggf. einem Moodle-Account zugeordnet.';
$string['privacy:metadata:zugang_list_entry:userid'] = 'Der Moodle-Account, dem dieser Eintrag zugeordnet ist.';
$string['privacy:metadata:zugang_list_entry:sourceref'] = 'Die Original-Benutzerkennung aus der hochgeladenen Liste.';
$string['privacy:metadata:zugang_list_entry:firstname'] = 'Vorname aus der hochgeladenen Liste.';
$string['privacy:metadata:zugang_list_entry:lastname'] = 'Nachname aus der hochgeladenen Liste.';
$string['privacy:metadata:zugang_reveal_log'] = 'Protokoll darüber, wann ein Nutzer sein Kennwort angezeigt oder gelöscht hat.';
$string['privacy:metadata:zugang_reveal_log:userid'] = 'Der Nutzer, der die Aktion ausgeführt hat.';
$string['privacy:metadata:zugang_reveal_log:timerevealed'] = 'Zeitpunkt der Anzeige.';
$string['privacy:metadata:zugang_reveal_log:timedeleted'] = 'Zeitpunkt der Löschung, falls erfolgt.';
