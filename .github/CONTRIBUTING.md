# Участие в разработке

## Выберите язык

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| **Русский** | [English](../docs/contributing/CONTRIBUTING_en.md) | [Español](../docs/contributing/CONTRIBUTING_es.md) | [中文](../docs/contributing/CONTRIBUTING_zh.md) | [Français](../docs/contributing/CONTRIBUTING_fr.md) | [Deutsch](../docs/contributing/CONTRIBUTING_de.md) |

Спасибо за интерес к Yii2 Book Catalog. Это небольшое Yii2 web-приложение, поэтому изменения лучше держать ограниченными, воспроизводимыми и простыми для проверки.

## Перед началом

- О воспроизводимой ошибке сообщите через GitHub Issue.
- Для улучшения опишите проблему, сценарий использования и ожидаемое поведение.
- При проблеме безопасности следуйте [политике безопасности](SECURITY.md) и не публикуйте чувствительные детали.
- Перед крупным изменением сначала убедитесь, что оно соответствует назначению проекта и не расширяет scope без явной причины.

## Контракт приложения

- Проект является Yii2 web application, а не REST API, SPA или production platform.
- `Book` и `Author` связаны отношением many-to-many.
- Гости могут просматривать каталог, использовать публичный Top-10 и подписываться по телефону на конкретного автора.
- Аутентифицированные пользователи дополнительно управляют книгами и авторами.
- Основной flow: `Controller → Form Model / DTO → application service → ActiveRecord / focused query → DB`.
- `BookService` передаётся в `BookController` через Yii DI; controller не собирает application services и внешние provider/client dependencies самостоятельно.
- Изменения схемы БД выполняются только migrations.
- SMSPilot используется только в emulator/test mode.
- Secrets, API keys и локальные значения окружения передаются через environment/config и не коммитятся.

## Ветки

Используйте короткое имя, отражающее назначение изменения, например:

```text
fix/book-validation
docs/update-development-guide
chore/update-ci
```

## Коммиты

Предпочтителен формат Conventional Commits. Примеры:

```text
fix: исправить валидацию книги
docs: уточнить локальный запуск
test: покрыть регрессию подписки
chore: обновить конфигурацию CI
```

## Локальная проверка

Runtime проекта выполняется через Docker-backed Make targets. PHP, Composer, Yii CLI, PHPUnit, PHPStan и PHPCS на хосте не запускаются.

Инструкции по первому запуску находятся в [руководстве по разработке](../docs/development.md).

Перед pull request выполните:

```shell
make check
```

Если меняется поведение приложения, дополнительно выполните:

```shell
make test
```

Покрытие запускается отдельно как диагностический отчёт, когда оно действительно нужно:

```shell
make coverage
```

При изменении схемы БД добавьте migration и проверьте соответствующий migration flow. Не используйте broad `chmod`, `chown` или удаление generated state для маскировки проблем окружения.

## Pull Request

В описании Pull Request укажите:

- проблему и внесённое изменение;
- выполненные проверки;
- добавленные или обновлённые tests, если меняется поведение;
- влияние на БД и наличие migration, если меняется schema;
- влияние на документацию, UI, uploads или внешние интеграции.

Перед отправкой убедитесь:

- secrets, API keys, cookie, session data и локальные `.env*` не добавлены;
- `vendor/`, `runtime/`, generated assets, uploads и coverage output не попали в commit;
- изменение ограничено одной понятной задачей;
- unrelated formatting/refactoring не добавлены;
- документация соответствует фактически проверенному поведению.
