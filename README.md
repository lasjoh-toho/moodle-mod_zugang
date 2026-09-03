# mod_zugang

Moodle-Aktivität, mit der Admins zwei getrennte, verschlüsselt gespeicherte
Kennwortlisten pflegen (**WLAN-Kennwörter** und **Dock-Kennwörter**) und
Schülerinnen/Schüler ihr eigenes Kennwort per Klick anzeigen (und danach
löschen) können.

## Funktionsweise

- **Listen sind site-weit**, nicht kursgebunden — mehrere Zugang-Aktivitäten
  aus verschiedenen Kursen können dieselbe Liste referenzieren
  (Site administration → Plugins → Activity modules → Zugang →
  *Listen verwalten*).
- **Upload-Format** (Semikolon- oder Komma-getrennt, Kopfzeile optional):
  ```
  Vorname;Nachname;Benutzerkennung;Initialkennwort;Klassen (aktiv)
  ```
  Eine einfache `Kennung;Kennwort`-Liste wird ebenfalls akzeptiert (z. B.
  für nicht personenbezogene Dock-Kennwörter).
- **Abgleich mit Moodle-Accounts:** exakte Treffer (Benutzerkennung ==
  Username oder E-Mail) werden automatisch übernommen. Bei Abweichungen
  wird der wahrscheinlichste Account als Vorschlag angezeigt (Kombination
  aus Kennungs- und Namensähnlichkeit) und muss unter *Zuordnungen prüfen*
  von einem Admin bestätigt werden. Jeder Moodle-Account kann pro Liste nur
  einem Eintrag zugeordnet werden.
- **Verschlüsselung:** AES-256-GCM, ein zufälliger Schlüssel pro Moodle-
  Installation (erzeugt bei der Plugin-Installation, gespeichert als
  Plugin-Konfiguration, nie exportiert oder in Backups enthalten).
  Klartext-Kennwörter werden nur serverseitig entschlüsselt und per AJAX
  an den anfragenden, berechtigten Nutzer ausgeliefert.
- **Anzeige für Schüler:** Button „Kennwort anzeigen“ pro passendem
  Eintrag. Die Anzeige schließt automatisch nach der konfigurierten Dauer
  (Standard 2 Minuten). Danach kann der Eintrag über „Kennwort löschen“
  dauerhaft entfernt werden.
- **Backup/Restore (Moodle2):** Aktivitäten sichern nur die *Referenz*
  auf eine Liste, nicht die Liste selbst (die Listen sind geteilte,
  site-weite Ressourcen mit verschlüsseltem Inhalt — sie gehören nicht in
  ein portables Kurs-Backup). Beim Restore auf derselben Instanz bleibt
  die Referenz gültig; beim Restore auf einer anderen Moodle-Instanz (wo
  die Listen-ID nichts bedeutet) wird die Referenz automatisch entfernt,
  damit die Aktivität sauber als „keine Liste hinterlegt“ erscheint statt
  fälschlich auf eine fremde Liste zu zeigen oder einen Fehler zu werfen.

## Installation

1. Neuestes Release von der [Releases-Seite](../../releases/latest)
   herunterladen (`mod_zugang.zip`).
2. Site administration → Plugins → Install plugins → Zip hochladen.
3. Nach der Installation: Listen anlegen und befüllen unter
   *Site administration → Plugins → Activity modules → Zugang →
   Listen verwalten*.

## Berechtigungen

| Capability | Zweck | Standardmäßig für |
|---|---|---|
| `mod/zugang:addinstance` | Aktivität in Kurs einfügen | Trainer, Manager |
| `mod/zugang:managelists` | Listen hochladen/verwalten, Zuordnungen prüfen | Manager |
| `mod/zugang:view` | Aktivitätsseite ansehen | Alle Kursrollen |
| `mod/zugang:reveal` | Eigenes Kennwort anzeigen | Student, Trainer, Manager |
| `mod/zugang:deleteownpassword` | Eigenes Kennwort löschen | Student, Trainer, Manager |
