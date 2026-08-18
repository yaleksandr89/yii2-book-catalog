## What changed / Что изменено

<!-- Describe the problem and the change that solves it. / Опишите проблему и изменение, которое её решает. -->

## Why / Зачем

<!-- Explain why this change is needed. / Объясните, зачем нужно это изменение. -->

## How to verify / Как проверить

<!-- List the checks, tests, and manual verification you ran. / Перечислите выполненные проверки, тесты и ручную проверку. -->

## Database, security and UI / База данных, безопасность и интерфейс

<!-- Describe migrations, security-sensitive changes, uploads, integrations, or UI impact when relevant. / Опишите миграции, изменения безопасности, uploads, интеграции или влияние на интерфейс, если это применимо. -->

## Checklist / Чек-лист

- [ ] `make check` passes when code, configuration, or quality tooling is affected. / `make check` проходит, если затронуты код, конфигурация или quality tooling.
- [ ] `make test` passes when application behavior changes. / `make test` проходит при изменении поведения приложения.
- [ ] Relevant tests were added or updated when behavior changed. / При изменении поведения добавлены или обновлены релевантные тесты.
- [ ] Database schema changes use Yii migrations. / Изменения схемы БД оформлены Yii migrations.
- [ ] No secrets, local `.env*`, personal data, or sensitive logs are included. / Секреты, локальные `.env*`, персональные данные и чувствительные логи не добавлены.
- [ ] UI changes include screenshots or a described manual check. / Для изменений интерфейса приложены скриншоты или описана ручная проверка.
- [ ] Documentation was updated when commands or behavior changed. / Документация обновлена при изменении команд или поведения.
- [ ] The Pull Request is limited to one coherent task and contains no unrelated generated files or formatting. / Pull Request ограничен одной связной задачей и не содержит несвязанных generated files или форматирования.
- [ ] README, CONTRIBUTING and SECURITY translations were synchronized when their source text changed. / Переводы README, CONTRIBUTING и SECURITY синхронизированы при изменении исходного текста.
