# AGENTS.md

## Scope and application contract

- This repository is a server-rendered Yii2 web application, not a reusable framework or a production service. Preserve its routes, public catalog, authenticated book/author CRUD, guest subscriptions, Top-10 report, image handling and test-mode SMS integration. Published releases and persisted data require an explicit owner decision before changing external behavior, schema, setup or compatibility.
- Use the existing Yii2 MVC, ActiveRecord, form models and Query Builder. `web/index.php` and `yii` bootstrap the web and console applications; `config/web.php` defines the DI composition root. `BookController` receives `BookService`; the service receives `SmsSenderInterface`. Keep provider creation in configuration, not controllers. Do not add layers, queues, REST API, SPA or extra infrastructure merely for uniformity.
- Read applicable source, config and tests before editing. Use Yii-supported mechanisms for typical framework tasks; preserve inherited signatures, magic attributes, aliases and framework discovery. Global timestamp-prefixed migration class names and bootstrap scripts with intentional side effects are required Yii conventions, not opportunities for renaming or moving files.

## Runtime and dependencies

- Development and CI target PHP 8.5 (`~8.5.0`), Yii2 2.0.55 and MySQL 8.4 through Docker Compose. PHP 8.6 is not supported by the selected toolchain; do not expand platform support without evidence and approval. Keep `composer.lock` committed; use targeted dependency updates, preserve runtime dependencies and trusted asset repository/plugin configuration. Check necessity, maintenance, compatibility and security before adding dependencies.
- PHP, Composer and project tools run through Docker-backed Make targets as `app` mapped to the host UID/GID. Inspect the target, effective user and write side effects first. Preserve `runtime/cache/`, `runtime/coverage.xml`, `runtime/coverage/`, `vendor/`, `web/assets/` and `web/uploads/` conventions. Never use broad permission changes or deletion to fix generated state.

## PHP style

- PER Coding Style 3.1 and PSR-1 are the normative baseline, with PHP CS Fixer `@PER-CS` as executable enforcement of supported rules. Use UTF-8, LF, one final newline, four spaces, `declare(strict_types=1);`, native types where contracts allow, explicit visibility, English identifiers, short arrays, strict comparisons and braces. Preserve framework-required signatures, validation semantics and view markup.
- Prefer compact `{}` for empty bodies, trailing commas in multiline lists, direct `new Foo()->method()`, ordered imports and global class imports where appropriate. Use `final` and `readonly` semantically, not on framework extension points that require otherwise.
- In mixed PHP/HTML templates keep the initial `declare(strict_types=1);`, imports, PHPDoc and other statements in a single PHP block. If the declaration is the only statement before markup, `<?php declare(strict_types=1); ?>` is permitted. Declare strict types exactly once. Do not split the block artificially or change rendered HTML whitespace without cause. This is the approved PER-CS 3.1 section 3 exception. Follow the formatter for whitespace within the block.
- Meaningful first-party comments and PHPDoc prose use `RU:` then `EN:` with equal meaning; group all RU lines before EN. Write machine-readable tags and type shapes once. Do not comment obvious code or duplicate native signatures. Preserve IDE metadata in `config/__autocomplete.php`; it is not application runtime code.
- Align formatter and static analysis with the normative contract and explicit exceptions. Do not suppress warnings or change runtime behavior just to satisfy tooling. Resolve genuine conflicts explicitly. Do not reformat third-party assets or unrelated code outside an approved style batch.

## Security and data

- Treat HTTP inputs, uploaded files, session state and external provider responses as untrusted. Preserve Yii `AccessControl`, CSRF, POST-only deletion, server-side validation and escaping; do not weaken authentication or route restrictions. Keep database queries parameterized.
- `BookService` owns book/author transaction and image lifecycle; clean up newly uploaded files on failure, preserve old files until commit and notify subscribers only after successful creation. Keep deduplication and test-mode SMS semantics. Never send real SMS during checks. Do not expose credentials, cookies, phone numbers, personal data or sensitive logs. Do not run production operations.
- Tests delete data from the configured test tables. Before initialization, migrations or tests, verify a disposable local MySQL target, distinct test and application database names, isolated test upload paths and synthetic credentials. Run integration tests and test migrations only through the guarded Make targets; do not bypass the local `mysql:3306` and distinct-schema checks by invoking Composer, PHPUnit or Yii test mode directly. Never run tests against working or shared data; never invoke `make mysql-reinit` for routine checks.
- Security-sensitive changes require focused risk review, meaningful abuse/failure regression tests and owner approval for new product/security decisions.

## Checks and documentation

- `make composer-install` installs locked dependencies; `make composer-validate`, `make composer-audit`, `make php-lint`, `make cs-check`, `make phpstan-check` and `make test` are the individual gates; `make check` runs all of them except install. `make cs-fix` changes first-party source. Prepare the isolated test DB with `make test-db-init` and `make test-db-migrate` first. `make coverage` and `make coverage-html` are local diagnostic commands. `make db-check` and `make smoke` verify the local DB and HTTP path. Keep tests deterministic; use stubs for external calls. Never claim PASS for an unperformed check.
- Maintain root Russian README, `.github/CONTRIBUTING.md`, `.github/SECURITY.md`, and complete localized documentation under `docs/langs/` (ru/en/es/zh/fr/de). Preserve `docs/assets/`, link navigation, code examples, actual commands and meaningful product facts; update all affected languages together. Architecture diagrams are Mermaid. Keep coverage diagnostic only, without external upload services or public coverage badges.

## Git and review

- Remote policy `READ_ONLY_FOR_AI`: owner performs commits, pushes, PRs, merges, settings, signed tags, releases. No autonomous commit/push/rebase/reset/restore/clean/stash/force, branch/tag deletion, remote mutation or production operations. Preserve unrelated changes and stay inside exact task allowlist.
- Report full diff, changed paths, relevant checks, `git diff --check` / `git diff --cached --check`, final Git state and runtime ownership. Stop for unsafe environment, missing evidence, expanded scope or new dependency/architecture/security/compatibility choices. Review is binary ACCEPTED / NOT ACCEPTED; consolidate all in-scope corrections before acceptance.
