# Mitwirken

## Sprache auswählen

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/CONTRIBUTING.md) | [English](./CONTRIBUTING_en.md) | [Español](./CONTRIBUTING_es.md) | [中文](./CONTRIBUTING_zh.md) | [Français](./CONTRIBUTING_fr.md) | **Deutsch** |

Vielen Dank für Ihr Interesse an Yii2 Book Catalog. Es handelt sich um eine kleine Yii2-Webanwendung; Änderungen sollten daher klar begrenzt, reproduzierbar und leicht prüfbar bleiben.

## Vor dem Start

- Einen reproduzierbaren Fehler bitte über ein GitHub Issue melden.
- Bei einer Verbesserung Problem, Anwendungsfall und erwartetes Verhalten beschreiben.
- Bei einem Sicherheitsproblem die [Sicherheitsrichtlinie](../../.github/SECURITY.md) beachten und keine sensiblen Details veröffentlichen.
- Vor einer größeren Änderung prüfen, ob sie zum Zweck des Projekts passt und den Scope nicht ohne klaren Grund erweitert.

## Anwendungsvertrag

- Das Projekt ist eine Yii2-Webanwendung, keine REST API, SPA oder Produktionsplattform.
- `Book` und `Author` stehen in einer Viele-zu-viele-Beziehung.
- Gäste können den Katalog durchsuchen, den öffentlichen Top-10-Bericht verwenden und einen bestimmten Autor per Telefonnummer abonnieren.
- Authentifizierte Benutzer können zusätzlich Bücher und Autoren verwalten.
- Der Hauptfluss lautet `Controller → Form Model / DTO → application service → ActiveRecord / focused query → DB`.
- `BookService` wird über Yii DI in `BookController` injiziert; der Controller erstellt application services oder externe provider/client dependencies nicht selbst.
- Änderungen am Datenbankschema erfolgen ausschließlich über migrations.
- SMSPilot wird nur im emulator/test mode verwendet.
- Secrets, API keys und lokale Umgebungswerte werden über environment/config bereitgestellt und nicht committed.

## Branches

Verwenden Sie einen kurzen Namen, der den Zweck der Änderung beschreibt, zum Beispiel:

```text
fix/book-validation
docs/update-development-guide
chore/update-ci
```

## Commits

Conventional Commits werden empfohlen. Beispiele:

```text
fix: correct book validation
docs: clarify local startup
test: cover subscription regression
chore: update CI configuration
```

## Lokale Prüfungen

Der Runtime-Betrieb erfolgt über Docker-backed Make targets. PHP, Composer, Yii CLI, PHPUnit, PHPStan und PHPCS dürfen nicht auf dem Host ausgeführt werden.

Anweisungen für den ersten Start stehen im [Entwicklungsleitfaden](../development.md).

Vor einem Pull Request ausführen:

```shell
make check
```

Wenn sich das Anwendungsverhalten ändert, zusätzlich:

```shell
make test
```

Coverage ist eine separate Diagnose und wird nur bei Bedarf ausgeführt:

```shell
make coverage
```

Bei Änderungen am Datenbankschema eine migration hinzufügen und den entsprechenden Migrationsablauf prüfen. Keine breiten `chmod`-/`chown`-Operationen oder das Löschen von generated state verwenden, um Umgebungsprobleme zu verdecken.

## Pull Request

In der Pull-Request-Beschreibung angeben:

- Problem und Änderung;
- ausgeführte Prüfungen;
- hinzugefügte oder aktualisierte Tests bei Verhaltensänderungen;
- Datenbankauswirkung und migration bei Schemaänderungen;
- Auswirkungen auf Dokumentation, UI, uploads oder externe Integrationen.

Vor dem Absenden prüfen:

- keine Secrets, API keys, cookies, session data oder lokale `.env*` enthalten;
- `vendor/`, `runtime/`, generated assets, uploads und coverage output nicht committed;
- Änderung auf eine zusammenhängende Aufgabe begrenzt;
- kein unrelated formatting/refactoring enthalten;
- Dokumentation entspricht dem verifizierten Verhalten.
