# Contributing

## Choose a language

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/CONTRIBUTING.md) | **English** | [Español](./CONTRIBUTING_es.md) | [中文](./CONTRIBUTING_zh.md) | [Français](./CONTRIBUTING_fr.md) | [Deutsch](./CONTRIBUTING_de.md) |

Thank you for your interest in Yii2 Book Catalog. This is a small Yii2 web application, so changes should stay bounded, reproducible, and easy to review.

## Before you start

- Report a reproducible bug through a GitHub Issue.
- For an improvement, describe the problem, use case, and expected behavior.
- For a security issue, follow the [security policy](../../.github/SECURITY.md) and do not publish sensitive details.
- Before a large change, make sure it fits the purpose of the project and does not expand scope without a clear reason.

## Application contract

- The project is a Yii2 web application, not a REST API, SPA, or production platform.
- `Book` and `Author` have a many-to-many relationship.
- Guests can browse the catalog, use the public Top-10 report, and subscribe by phone to a specific author.
- Authenticated users can additionally manage books and authors.
- The main flow is `Controller → Form Model / DTO → application service → ActiveRecord / focused query → DB`.
- `BookService` is injected into `BookController` through Yii DI; the controller does not construct application services or external provider/client dependencies itself.
- Database schema changes use migrations only.
- SMSPilot is used only in emulator/test mode.
- Secrets, API keys, and local environment values are provided through environment/config and are not committed.

## Branches

Use a short name that reflects the purpose of the change, for example:

```text
fix/book-validation
docs/update-development-guide
chore/update-ci
```

## Commits

Conventional Commits are preferred. Examples:

```text
fix: correct book validation
docs: clarify local startup
test: cover subscription regression
chore: update CI configuration
```

## Local checks

Project runtime uses Docker-backed Make targets. Do not run PHP, Composer, Yii CLI, PHPUnit, PHPStan, or PHPCS on the host.

First-start instructions are in the [development guide](../development.md).

Before a Pull Request, run:

```shell
make check
```

If application behavior changes, also run:

```shell
make test
```

Coverage is a separate diagnostic and is only needed when relevant:

```shell
make coverage
```

For database schema changes, add a migration and verify the corresponding migration flow. Do not use broad `chmod`, `chown`, or generated-state deletion to hide environment problems.

## Pull Request

In the Pull Request description, include:

- the problem and the change;
- checks that were run;
- tests added or updated when behavior changes;
- database impact and migration details when schema changes;
- impact on documentation, UI, uploads, or external integrations.

Before submitting, verify that:

- secrets, API keys, cookies, session data, and local `.env*` files are not included;
- `vendor/`, `runtime/`, generated assets, uploads, and coverage output are not committed;
- the change is limited to one coherent task;
- unrelated formatting or refactoring is not included;
- documentation matches verified behavior.
