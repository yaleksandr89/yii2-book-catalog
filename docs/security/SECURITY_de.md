# Sicherheitsrichtlinie

## Sprache auswählen

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/SECURITY.md) | [English](./SECURITY_en.md) | [Español](./SECURITY_es.md) | [中文](./SECURITY_zh.md) | [Français](./SECURITY_fr.md) | **Deutsch** |

## Unterstützte Versionen

Sicherheitskorrekturen werden für den aktuellen Stand von `master` und die zuletzt veröffentlichte Version betrachtet.

| Version | Unterstützung |
|---|---|
| `master` | Ja |
| Letzte veröffentlichte Version | Ja |

## Was als Sicherheitslücke gilt

Zu Sicherheitsproblemen zählen insbesondere:

- Umgehung von Authentifizierung, `AccessControl` oder Einschränkungen für destructive actions;
- Umgehung des CSRF-Schutzes;
- unsichere Verarbeitung hochgeladener Dateien, Dateinamen oder Pfade;
- SQL injection oder Umgehung serverseitiger Validierung;
- Offenlegung von API keys, Passwörtern, cookies, session data oder anderer privater Konfiguration;
- Verlust sensibler Daten über logs, Fehlermeldungen oder die SMSPilot-Integration;
- unbefugter Zugriff auf Daten eines anderen Benutzers oder auf eine geschützte Aktion.

Normale Fehler, Nutzungsfragen und Verbesserungsvorschläge können in GitHub Issues veröffentlicht werden, sofern sie keine sensiblen Daten enthalten.

## So melden Sie eine Sicherheitslücke

Verwenden Sie bevorzugt GitHub Private Vulnerability Reporting, wenn es verfügbar ist:

1. Öffnen Sie den Tab **Security** des Repositorys.
2. Wechseln Sie zu **Advisories**.
3. Wählen Sie **Report a vulnerability**.
4. Senden Sie den Bericht, ohne sensible Details in einem normalen Issue zu veröffentlichen.

Wenn Private Vulnerability Reporting nicht verfügbar ist, erstellen Sie ein minimales öffentliches Issue ohne technische Details der Sicherheitslücke und bitten Sie um einen privaten Kontaktkanal.

Vor Veröffentlichung eines Fixes nicht veröffentlichen:

- API keys oder Passwörter;
- cookies, session data oder CSRF tokens;
- echte personenbezogene Daten;
- vollständige production logs;
- einen funktionsfähigen exploit oder unnötige Details, mit denen der Angriff reproduziert werden kann.

## Angaben im Bericht

Wenn möglich, geben Sie an:

- betroffene release, branch oder commit;
- Auswirkungen;
- minimale Reproduktionsschritte;
- erwartetes und tatsächliches Verhalten;
- bereinigte request/response/log-Ausschnitte, wenn hilfreich;
- einen möglichen Fix, falls bekannt.

Verwenden Sie ausschließlich synthetische oder anonymisierte Daten.

## Bearbeitung des Berichts

Berichte werden nach Verfügbarkeit geprüft; ein fester SLA wird nicht zugesagt.

Bitte koordinieren Sie die Offenlegung mit dem Maintainer, bevor Details veröffentlicht werden. Nach Bestätigung einer Sicherheitslücke werden Fix und Informationen zu betroffenen Versionen im Rahmen von coordinated disclosure veröffentlicht.

Das Projekt weist kein Bug-Bounty-Programm aus.
