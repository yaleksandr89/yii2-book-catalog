# Entwicklung

## Sprache wählen

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./development.md) | [English](./development_en.md) | [Español](./development_es.md) | [中文](./development_zh.md) | [Français](./development_fr.md) | **Ausgewählt** |

## Anforderungen

Auf dem Host werden benötigt:

- Git;
- Make;
- Docker mit Compose-Unterstützung;
- ein Editor und übliche Systemwerkzeuge.

PHP, Composer, Yii CLI, der MySQL-Client, PHPUnit, PHPStan und PHPCS laufen in Containern über die Befehle im [`Makefile`](../../Makefile). Sie müssen nicht separat auf dem Host installiert werden.

## Ersteinrichtung

Für den ersten Start:

```bash
make init
make build
make up
make composer-install
make migrate
```

`make init` erstellt `.env.docker` aus [`.env.docker.example`](../../.env.docker.example) und bereitet lokale Verzeichnisse vor, in die die Anwendung während des Betriebs schreibt.

Nach dem Start ist die Anwendung unter `http://localhost:8080` erreichbar.

Container stoppen:

```bash
make down
```

Einen Benutzer für die Anmeldung erstellen:

```bash
make yii CMD="user/create <username> <password>"
```

## Umgebungsvariablen

Die wichtigsten Einstellungen stehen in `.env.docker`:

- `HOST_UID`, `HOST_GID` — Kennungen des Host-Benutzers;
- `APP_PORT` — Port, über den Nginx vom Host aus erreichbar ist;
- `MYSQL_DATABASE` — Hauptdatenbank;
- `MYSQL_TEST_DATABASE` — separate Testdatenbank;
- `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` — MySQL-Einstellungen;
- `DB_HOST`, `DB_PORT`, `DB_FORWARD_PORT` — MySQL-Verbindungsparameter;
- `COOKIE_VALIDATION_KEY` — Schlüssel zur Cookie-Validierung;
- `SMSPILOT_API_KEY` — SMSPilot-Schlüssel.

Echte Geheimnisse dürfen nicht in Git gelangen. Der Beispielwert für `COOKIE_VALIDATION_KEY` muss lokal ersetzt werden; das Projekt bietet `make cookie-key`, um einen Zufallswert zu erzeugen. `SMSPILOT_API_KEY` kann leer bleiben, wenn die SMSPilot-Integration nicht geprüft wird.

## Container

Docker Compose startet drei Dienste:

- `php` — PHP-FPM und alle PHP-Werkzeuge des Projekts;
- `nginx` — Webserver;
- `mysql` — MySQL 8.4.

Der PHP-Container läuft als Benutzer `app`. Seine UID und GID stimmen mit denen des Host-Benutzers überein. Dadurch gehören Dateien, die Anwendung und Entwicklungswerkzeuge erstellen, nicht `root`.

Nützliche Befehle:

```bash
make ps
make restart php
make log nginx
make in php
```

## Datenbank und Demodaten

Das Datenbankschema wird ausschließlich durch [Yii-Migrationen](../../migrations/) erstellt:

```bash
make migrate
```

Für Tests wird eine separate Datenbank verwendet:

```bash
make test-db-init
make test-db-migrate
```

Demobücher und -autoren hinzufügen:

```bash
make demo-data
```

Dieser Befehl dient nur der lokalen Entwicklung und wiederholbaren manuellen Prüfung der Oberfläche.

## Tests und Prüfungen

Die wichtigsten Befehle:

```bash
make test
make test-dox
make check
```

- `make test` startet PHPUnit;
- `make test-dox` startet dieselben Tests mit lesbaren Szenariobeschreibungen;
- `make check` prüft nacheinander Composer, PHP-Syntax, PHPStan und Codestil.

Bei Bedarf lassen sich die Prüfungen einzeln starten:

```bash
make composer-validate
make php-lint
make phpstan-check
make phpcs-check
```

Die Testabdeckung wird separat geprüft und verlangsamt den normalen Entwicklungszyklus nicht:

```bash
make coverage
```

Dabei entsteht ein Clover-Bericht in `runtime/coverage.xml`; eine HTML-Version kann mit `make coverage-html` in `runtime/coverage` erstellt werden.

## Automatische Prüfungen in GitHub Actions

Der Workflow [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) wiederholt die wichtigsten Projektprüfungen in einer sauberen Umgebung:

1. bereitet die Docker-Container vor und startet sie;
2. installiert Abhängigkeiten aus `composer.lock` und führt Migrationen aus;
3. erstellt eine separate Testdatenbank;
4. führt Codeprüfungen und PHPUnit aus;
5. übermittelt den Abdeckungsbericht an Codecov;
6. prüft die MySQL-Verbindung und die HTTP-Antwort der Anwendung über Nginx;
7. stoppt die Container unabhängig vom Ergebnis der vorherigen Schritte.

Dieser Workflow stellt die Anwendung nicht bereit.

## Dateibesitz

Befehle, die Dateien im Projekt erstellen können, laufen im PHP-Container als Benutzer `app`, dessen UID/GID dem Host-Benutzer entsprechen. Das gilt unter anderem für `vendor`, `runtime`, `web/assets` und `web/uploads`.

Für gewöhnliche Arbeiten reichen die vorhandenen Make-Befehle aus. Großflächige `chmod`- oder `chown`-Aktionen sowie das Löschen von Verzeichnissen sind zur Korrektur von Berechtigungen nicht nötig.
