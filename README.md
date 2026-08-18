# Каталог книг на Yii2

[![Source Code](https://img.shields.io/badge/source-yaleksandr89%2Fyii2--book--catalog-blue.svg?style=flat-square)](https://github.com/yaleksandr89/yii2-book-catalog)
[![CI](https://img.shields.io/github/actions/workflow/status/yaleksandr89/yii2-book-catalog/ci.yml?style=flat-square&label=CI)](https://github.com/yaleksandr89/yii2-book-catalog/actions/workflows/ci.yml)
[![Codecov](https://codecov.io/gh/yaleksandr89/yii2-book-catalog/graph/badge.svg)](https://codecov.io/gh/yaleksandr89/yii2-book-catalog)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Yii](https://img.shields.io/badge/Yii-2.0.55-40B3D8.svg?style=flat-square)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1.svg?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

<p align="center">
  <img
    src="docs/assets/yii2-book-catalog-readme-cover.png"
    alt="Yii2 Book Catalog — web catalog with authors, subscriptions, Top-10 report and SMSPilot"
    width="100%"
  >
</p>

## Выберите язык

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| **Выбран** | [English](./docs/readme/README_en.md) | [Español](./docs/readme/README_es.md) | [中文](./docs/readme/README_zh.md) | [Français](./docs/readme/README_fr.md) | [Deutsch](./docs/readme/README_de.md) |

Тестовое веб-приложение на Yii2 и MySQL: каталог книг и авторов с загрузкой обложек, связью «многие ко многим», публичным рейтингом авторов, подписками по телефону и тестовой интеграцией SMSPilot.

При реализации основной упор сделан на разделение ответственности: контроллеры занимаются обработкой HTTP-запросов и проверкой доступа, входные данные валидируются на сервере, составные операции с книгой вынесены в отдельный сервис, а отчёт строится одним агрегатным запросом. Проект запускается в Docker и не требует PHP или Composer на хосте.

## Возможности

- публичный каталог книг и авторов;
- создание, редактирование и удаление книг и авторов после входа в систему;
- загрузка основного изображения книги;
- несколько авторов у одной книги и несколько книг у одного автора;
- публичный Top-10 авторов по количеству книг за выбранный год;
- подписка гостя по номеру телефона на конкретного автора;
- тестовые SMS-уведомления о новых книгах через SMSPilot.

## Быстрый старт

```bash
make init
make build
make up
make composer-install
make migrate
```

После запуска приложение доступно по адресу [http://localhost:8080](http://localhost:8080).

Пользователя для входа можно создать через консольную команду:

```bash
make yii CMD="user/create <username> <password>"
```

Для заполнения каталога демонстрационными данными доступна команда `make demo-data`. Остальные команды для работы с окружением, тестовой базой и проверками собраны в [руководстве по разработке](docs/development.md).

## Доступ

| Пользователь | Возможности |
| --- | --- |
| Гость | Просмотр книг и авторов, Top-10 за выбранный год, подписка по телефону на автора |
| Пользователь после входа | Всё, что доступно гостю, а также создание, редактирование и удаление книг и авторов |

## Как устроено приложение

```text
HTTP-запрос
    ↓
контроллер
    ↓
модель формы
    ↓
сервис / ActiveRecord / отдельный запрос отчёта
    ↓
MySQL
```

Контроллеры остаются небольшими и отвечают в основном за веб-сценарий: получить запрос, проверить доступ, запустить валидацию и передать работу дальше. Данные книги проверяет [`BookForm`](models/BookForm.php), а сохранение книги, её связей с авторами и изображения выполняет [`BookService`](services/BookService.php).

Для Top-10 выделен [`TopAuthorsQuery`](models/TopAuthorsQuery.php): подсчёт выполняется сразу в базе данных одним запросом, а не собирается из загруженных моделей в PHP.

Подробно эти решения, работа с изображениями и границы ответственности разобраны в [описании архитектуры](docs/architecture.md).

## SMSPilot

После успешного создания книги приложение находит подписчиков её авторов и отправляет уведомления через SMSPilot в тестовом режиме. Отправка начинается только после сохранения книги и её связей в базе данных, поэтому ошибка внешнего сервиса не отменяет уже выполненное создание книги. Если один номер подписан сразу на нескольких авторов новой книги, для него выполняется только одна попытка отправки.

Во время ручной проверки выяснилось, что сообщение с длинным кириллическим названием книги в эмуляторе рассчитывалось как более дорогое составное SMS: `19.74` против `9.87` после сокращения текста. Поэтому название книги из уведомления убрано, а текст ограничен двумя короткими вариантами для одного или нескольких подходящих авторов.

Ответы SMSPilot, последовательность отправки и обработка ошибок приведены в [описании интеграции](docs/smspilot.md).

## Что намеренно оставлено простым

- SMS отправляются синхронно после сохранения книги. Для приложения с заметной нагрузкой эту работу стоило бы вынести из HTTP-запроса в очередь фоновых задач, например через [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). Для тестового задания отдельный обработчик очереди и дополнительная инфраструктура не добавлялись.
- ActiveRecord используется напрямую там, где возможностей Yii достаточно для обычного чтения и записи данных. Дополнительный слой репозиториев вокруг каждой модели не вводился, потому что в приложении такого размера он только дублировал бы уже существующий слой работы с базой данных.
- Отдельный REST API и клиентское SPA не добавлялись: приложение реализовано как обычный серверный Yii2 Web.
