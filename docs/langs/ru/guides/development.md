# Разработка

## Выберите язык

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| **Выбран** | [English](../../en/guides/development.md) | [Español](../../es/guides/development.md) | [中文](../../zh/guides/development.md) | [Français](../../fr/guides/development.md) | [Deutsch](../../de/guides/development.md) |

## Требования

Для работы на хосте нужны:

- Git;
- Make;
- Docker с поддержкой Compose;
- редактор и обычные системные утилиты.

PHP, Composer, Yii CLI, клиент MySQL, PHPUnit, PHPStan и PHP CS Fixer запускаются внутри контейнеров через команды из [`Makefile`](../../../../Makefile). Устанавливать их на хост отдельно не требуется.

## Первичная настройка

Для первого запуска:

```bash
make init
make build
make up
make composer-install
make migrate
```

`make init` создаёт `.env.docker` на основе [`.env.docker.example`](../../../../.env.docker.example) и подготавливает локальные каталоги, в которые приложение пишет во время работы.

После запуска приложение доступно на `http://localhost:8080`.

Остановить контейнеры:

```bash
make down
```

Создать пользователя для входа:

```bash
make yii CMD="user/create <username> <password>"
```

## Переменные окружения

Основные настройки находятся в `.env.docker`:

- `HOST_UID`, `HOST_GID` — идентификаторы пользователя хоста;
- `APP_PORT` — порт, на котором Nginx доступен с хоста;
- `MYSQL_DATABASE` — основная база данных;
- `MYSQL_TEST_DATABASE` — отдельная база для тестов;
- `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD` — настройки MySQL;
- `DB_HOST`, `DB_PORT`, `DB_FORWARD_PORT` — параметры подключения к MySQL;
- `COOKIE_VALIDATION_KEY` — ключ проверки cookie;
- `SMSPILOT_API_KEY` — ключ SMSPilot.

Реальные секреты не должны попадать в Git. Значение `COOKIE_VALIDATION_KEY` из примера нужно заменить локально; для генерации случайного значения в проекте есть команда `make cookie-key`. `SMSPILOT_API_KEY` можно оставить пустым, если интеграция SMSPilot не проверяется.

## Контейнеры

Docker Compose поднимает три сервиса:

- `php` — PHP 8.5 FPM и все PHP-инструменты проекта;
- `nginx` — веб-сервер;
- `mysql` — MySQL 8.4.

PHP-контейнер работает от пользователя `app`. Его UID и GID совпадают с пользователем хоста, поэтому файлы, создаваемые приложением и инструментами разработки, не становятся принадлежащими `root`.

Полезные команды:

```bash
make ps
make restart php
make log nginx
make in php
```

## База данных и демонстрационные данные

Схема базы данных создаётся только [миграциями Yii](../../../../migrations/):

```bash
make migrate
```

Для тестов используется отдельная база:

```bash
make test-db-init
make test-db-migrate
```

Перед запуском `make check`, `make test` или покрытия подготовьте отдельную тестовую базу командами выше. Тесты удаляют данные из её таблиц. Используйте только изолированную локальную БД с именем, отличным от `MYSQL_DATABASE`.

Тестовые команды запускайте через Make: они проверяют локальный endpoint `mysql:3306` и отдельное имя тестовой базы перед миграциями и тестами. Не запускайте интеграционный PHPUnit напрямую через Composer или `vendor/bin/phpunit`, обходя эту проверку.

Демонстрационные книги и авторов можно добавить командой:

```bash
make demo-data
```

Она предназначена только для локальной разработки и повторяемой ручной проверки интерфейса.

## Тесты и проверки

Основные команды:

```bash
make test
make test-dox
make check
```

- `make test` запускает PHPUnit;
- `make test-dox` запускает те же тесты с читаемыми описаниями сценариев;
- `make check` последовательно проверяет Composer (`validate --strict --no-check-publish` и `audit --locked`), синтаксис PHP, стиль через PHP CS Fixer, PHPStan и запускает PHPUnit на отдельной тестовой базе.

При необходимости каждую проверку можно запустить отдельно:

```bash
make composer-validate
make composer-audit
make php-lint
make cs-check
make phpstan-check
```

`make cs-fix` форматирует PHP-код и изменяет исходники.

Покрытие тестами проверяется отдельной командой и не замедляет обычный цикл разработки:

```bash
make coverage
```

Она создаёт отчёт Clover в `runtime/coverage.xml`; HTML-версию можно получить через `make coverage-html`, результат будет записан в `runtime/coverage`.

## Автоматические проверки в GitHub Actions

Сценарий [`.github/workflows/ci.yml`](../../../../.github/workflows/ci.yml) повторяет основные проверки проекта в чистом окружении:

1. подготавливает и запускает Docker-контейнеры;
2. устанавливает зависимости из `composer.lock` и применяет миграции;
3. создаёт отдельную тестовую базу;
4. запускает проверки кода и PHPUnit;
5. создаёт диагностический отчёт покрытия внутри CI;
6. проверяет подключение к MySQL и HTTP-ответ приложения через Nginx;
7. останавливает контейнеры независимо от результата предыдущих шагов.

Развёртывание приложения этим сценарием не выполняется.

## Права на файлы

Команды, которые могут создавать файлы внутри проекта, запускаются в PHP-контейнере от пользователя `app`, связанного с UID/GID пользователя хоста. Это касается в том числе `vendor`, `runtime`, `web/assets` и `web/uploads`.

Для обычной работы достаточно существующих Make-команд. Массовые `chmod`, `chown` и удаление каталогов для исправления прав не требуются.
