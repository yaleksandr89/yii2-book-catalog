# Yii2 Book Catalog

[![Source Code](https://img.shields.io/badge/source-yaleksandr89%2Fyii2--book--catalog-blue.svg?style=flat-square)](https://github.com/yaleksandr89/yii2-book-catalog)
[![CI](https://img.shields.io/github/actions/workflow/status/yaleksandr89/yii2-book-catalog/ci.yml?style=flat-square&label=CI)](https://github.com/yaleksandr89/yii2-book-catalog/actions/workflows/ci.yml)
[![Codecov](https://codecov.io/gh/yaleksandr89/yii2-book-catalog/graph/badge.svg)](https://codecov.io/gh/yaleksandr89/yii2-book-catalog)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Yii](https://img.shields.io/badge/Yii-2.0.55-40B3D8.svg?style=flat-square)](https://www.yiiframework.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1.svg?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED.svg?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](../../LICENSE)

<p align="center">
  <img
    src="../assets/yii2-book-catalog-readme-cover.png"
    alt="Yii2 Book Catalog — web catalog with authors, subscriptions, Top-10 report and SMSPilot"
    width="100%"
  >
</p>

## Choose a language

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../README.md) | **Selected** | [Español](./README_es.md) | [中文](./README_zh.md) | [Français](./README_fr.md) | [Deutsch](./README_de.md) |

A Yii2 and MySQL test web application: a catalog of books and authors with cover uploads, a many-to-many relationship, a public author ranking, phone subscriptions, and a test SMSPilot integration.

The implementation focuses on separation of responsibilities: controllers handle HTTP requests and access checks, input is validated on the server, compound book operations are delegated to a dedicated service, and the report is built with a single aggregate query. The project runs in Docker and does not require PHP or Composer on the host.

## Features

- public catalog of books and authors;
- create, edit, and delete books and authors after signing in;
- upload a main image for a book;
- multiple authors per book and multiple books per author;
- public Top-10 authors by number of books for a selected year;
- guest subscription by phone number to a specific author;
- test SMS notifications about new books through SMSPilot.

## Quick start

```bash
make init
make build
make up
make composer-install
make migrate
```

After startup, the application is available at [http://localhost:8080](http://localhost:8080).

Create a user for sign-in with the console command:

```bash
make yii CMD="user/create <username> <password>"
```

Use `make demo-data` to populate the catalog with demo data. Other commands for the environment, test database, and checks are collected in the [development guide](../development.md).

## Access

| User | Capabilities |
| --- | --- |
| Guest | Browse books and authors, view the Top-10 for a selected year, subscribe by phone to an author |
| Signed-in user | Everything available to a guest, plus create, edit, and delete books and authors |

## Application structure

```text
HTTP request
    ↓
controller
    ↓
form model
    ↓
service / ActiveRecord / dedicated report query
    ↓
MySQL
```

Controllers stay small and mainly handle the web scenario: receive the request, check access, run validation, and delegate the work. Book data is validated by [`BookForm`](../../models/BookForm.php), while [`BookService`](../../services/BookService.php) saves the book, its author relations, and the image.

The Top-10 uses a dedicated [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php): counting is performed directly in the database with one query instead of being assembled from loaded models in PHP.

These decisions, image handling, and responsibility boundaries are described in detail in the [architecture guide](../architecture.md).

## SMSPilot

After a book is created successfully, the application finds subscribers of its authors and sends notifications through SMSPilot in test mode. Sending starts only after the book and its relations have been saved to the database, so an external service failure does not roll back an already created book. If the same phone number is subscribed to several authors of the new book, only one send attempt is made for that number.

Manual verification showed that a message containing a long Cyrillic book title was priced by the emulator as a more expensive multipart SMS: `19.74` versus `9.87` after shortening the text. The book title was therefore removed from the notification, and the message was limited to two short variants for one or several matching authors.

SMSPilot responses, send order, and error handling are documented in the [integration guide](../smspilot.md).

## What is intentionally kept simple

- SMS is sent synchronously after the book is saved. For an application with meaningful load, this work would normally move out of the HTTP request into a background queue such as [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). A separate queue worker and additional infrastructure were intentionally not added for this test assignment.
- ActiveRecord is used directly where Yii already provides enough functionality for ordinary reads and writes. A repository layer around every model was not introduced because, at this application size, it would mostly duplicate the existing database access layer.
- No separate REST API or client-side SPA was added: the application is implemented as a regular server-rendered Yii2 Web application.
