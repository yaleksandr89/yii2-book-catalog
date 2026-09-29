# Architecture

## Choose language

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./architecture.md) | **Selected** | [Español](./architecture_es.md) | [中文](./architecture_zh.md) | [Français](./architecture_fr.md) | [Deutsch](./architecture_de.md) |

The project uses standard Yii2 features without adding layers for their own sake. ActiveRecord handles ordinary data access, form models validate user input, and compound operations are moved behind a separate responsibility boundary where needed.

## Overview

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

For example, [`BookController`](../../controllers/BookController.php) receives the request, checks access rights, obtains the uploaded file, and starts validation. [`BookForm`](../../models/BookForm.php) validates the book fields, selected authors, and image. After validation succeeds, the controller passes the book change to [`BookService`](../../services/BookService.php).

The controller thus does not handle transactions, the file system, or SMS sending.

## Data and relationships

The main models are:

- [`Book`](../../models/Book.php) — a book;
- [`Author`](../../models/Author.php) — an author;
- [`Subscription`](../../models/Subscription.php) — a phone number's subscription to an author;
- [`User`](../../models/User.php) — a user who can change the catalog.

A book can have multiple authors, and an author can have multiple books. The relationship is stored in the `book_author` table.

The database structure is defined by [migrations](../../migrations/). Foreign keys protect table relationships, a unique index prevents adding the same book–author pair twice, and a separate index on the book's release year supports the report.

When listing books, authors are eager loaded with `with('authors')`, so the database is not queried separately for each book.

## Saving a book

[`BookService`](../../services/BookService.php) is needed because a book change involves several actions: saving the book, updating its authors, handling the image, and notifying subscribers on creation.

On creation:

1. a new image is saved under a generated name;
2. the book and its author relationships are written in one transaction;
3. if the transaction fails, it is rolled back and the new file is deleted;
4. notifications begin after the transaction completes successfully.

On update, the old image stays in place if no new file is uploaded. If the image is replaced, the old file is deleted only after the new book state is saved successfully.

On deletion, removal of the database record is committed first; the image file is deleted afterward.

This prevents a valid image from being removed when a book change fails to persist in the database.

## Top-10 report

[`TopAuthorsReportForm`](../../models/TopAuthorsReportForm.php) validates the selected year, and [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php) builds the report.

MySQL performs the count in one aggregate query: books are filtered by year, grouped by author, and the first ten results are selected. For equal book counts, additional sorting by author name and identifier keeps the order predictable.

The query is separate from the controller because aggregation is a distinct read operation rather than an ordinary ActiveRecord model search.

## Input validation and access

User input is validated on the server. For example, [`BookForm`](../../models/BookForm.php) checks required fields, release year, existence of selected authors, and the uploaded image: allowed extensions, MIME type, and size up to 5 MiB.

Controllers use `AccessControl` for actions that change the catalog; `VerbFilter` restricts deletion to POST. Yii's CSRF protection applies to ordinary web forms.

Database queries use ActiveRecord, Query Builder, and Yii's parameterized conditions; user values are not interpolated into SQL through string concatenation.

Secrets, including `SMSPILOT_API_KEY`, are read from the environment and application configuration.

## Deliberately simple choices

ActiveRecord is not wrapped in a separate repository for every model. In this application, that layer would mainly repeat existing Yii features without providing useful isolation.

SMS is sent synchronously after a book is saved. Under significant load, it would be better moved to a background task queue; Yii2 has the separate [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue) extension for that. This test application does not add a queue, worker, or supporting infrastructure.

The application remains server-rendered Yii2 Web. No separate REST API or SPA was built because the task does not require them.
