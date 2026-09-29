# Development

## Choose language

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./development.md) | **Selected** | [Español](./development_es.md) | [中文](./development_zh.md) | [Français](./development_fr.md) | [Deutsch](./development_de.md) |

## Requirements

On the host, you need:

- Git;
- Make;
- Docker with Compose support;
- an editor and standard system utilities.

PHP, Composer, Yii CLI, the MySQL client, PHPUnit, PHPStan, and PHPCS run inside containers through commands in [`Makefile`](../../Makefile). They do not need to be installed separately on the host.

## Initial setup

For the first start:

```bash
make init
make build
make up
make composer-install
make migrate
```

`make init` creates `.env.docker` from [`.env.docker.example`](../../.env.docker.example) and prepares local directories that the application writes to at runtime.

After startup, the application is available at `http://localhost:8080`.

To stop the containers:

```bash
make down
```

To create a user for sign-in:

```bash
make yii CMD="user/create <username> <password>"
```

## Environment variables

The main settings are in `.env.docker`:

- `HOST_UID`, `HOST_GID` — host user identifiers;
- `APP_PORT` — port on which Nginx is available from the host;
- `MYSQL_DATABASE` — main database;
- `MYSQL_TEST_DATABASE` — separate test database;
- `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` — MySQL settings;
- `DB_HOST`, `DB_PORT`, `DB_FORWARD_PORT` — MySQL connection settings;
- `COOKIE_VALIDATION_KEY` — cookie validation key;
- `SMSPILOT_API_KEY` — SMSPilot key.

Real secrets must not enter Git. Replace the example `COOKIE_VALIDATION_KEY` value locally; the project provides `make cookie-key` to generate a random value. `SMSPILOT_API_KEY` may be left empty if the SMSPilot integration is not being tested.

## Containers

Docker Compose starts three services:

- `php` — PHP-FPM and all project PHP tools;
- `nginx` — web server;
- `mysql` — MySQL 8.4.

The PHP container runs as user `app`. Its UID and GID match the host user, so files created by the application and development tools do not become owned by `root`.

Useful commands:

```bash
make ps
make restart php
make log nginx
make in php
```

## Database and demo data

The database schema is created only through [Yii migrations](../../migrations/):

```bash
make migrate
```

Tests use a separate database:

```bash
make test-db-init
make test-db-migrate
```

Add demo books and authors with:

```bash
make demo-data
```

This command is intended only for local development and repeatable manual interface checks.

## Tests and checks

Main commands:

```bash
make test
make test-dox
make check
```

- `make test` runs PHPUnit;
- `make test-dox` runs the same tests with readable scenario descriptions;
- `make check` runs Composer checks, PHP syntax checks, PHPStan, and code style checks in sequence.

Run individual checks when needed:

```bash
make composer-validate
make php-lint
make phpstan-check
make phpcs-check
```

Coverage is checked with a separate command and does not slow the ordinary development cycle:

```bash
make coverage
```

It creates a Clover report in `runtime/coverage.xml`; an HTML version can be generated with `make coverage-html` at `runtime/coverage`.

## Automated checks in GitHub Actions

The [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) workflow repeats the project's main checks in a clean environment:

1. prepares and starts Docker containers;
2. installs dependencies from `composer.lock` and applies migrations;
3. creates a separate test database;
4. runs code checks and PHPUnit;
5. sends the coverage report to Codecov;
6. checks the MySQL connection and the application's HTTP response through Nginx;
7. stops the containers regardless of previous step results.

This workflow does not deploy the application.

## File ownership

Commands that may create files inside the project run in the PHP container as user `app`, mapped to the host user's UID/GID. This includes `vendor`, `runtime`, `web/assets`, and `web/uploads`.

The existing Make commands are sufficient for ordinary work. Broad `chmod`, `chown`, or directory deletion is not needed to fix permissions.
