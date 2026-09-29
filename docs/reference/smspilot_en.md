# SMSPilot

## Choose language

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./smspilot.md) | **Selected** | [Español](./smspilot_es.md) | [中文](./smspilot_zh.md) | [Français](./smspilot_fr.md) | [Deutsch](./smspilot_de.md) |

When a new book is created, the application sends SMS notifications to guests subscribed to one of its authors. The integration operates only in SMSPilot test mode: requests go through the service, but no actual delivery to a mobile operator occurs.

## How sending works

```text
BookService
    ↓
SmsSenderInterface
    ↓
SmsPilotSender
    ↓
SMSPilot
```

[`BookService`](../../services/BookService.php) depends on the small [`SmsSenderInterface`](../../integrations/SmsSenderInterface.php) interface, while the specific external service is handled by [`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php).

[`SmsPilotSendResponse`](../../integrations/smspilot/SmsPilotSendResponse.php) checks that a successful SMSPilot response has the expected structure. The service key is provided through application configuration and is not stored in source code.

`SmsPilotSender` forces `test=1` on every request. It also sets a short network timeout and disables logging of the HTTP response body.

## When an SMS is sent

Sending is outside the transaction that saves the book.

The sequence is:

1. the image is saved;
2. the book and its author relationships are written to the database;
3. the transaction completes successfully;
4. only then are subscribers selected and requests to SMSPilot started.

Thus, SMSPilot being unavailable cannot undo a book that has already been created.

If the external service returns an error for one number, [`BookService`](../../services/BookService.php) logs a warning and continues with the remaining recipients.

## How recipients are selected

Recipients are selected with one aggregate query over phone numbers.

If a number is subscribed to several authors of a new book, it still appears only once in the result and receives no more than one send attempt. This prevents duplicate notifications and avoids a separate database query for each author.

The numbers are sorted, keeping the processing order predictable.

## Why the message was shortened

The first working version sent the book title in the message. It worked functionally: in a manual check, SMSPilot returned HTTP 200 and successful test status `0`. The emulator response included these values:

```text
server_id = 10000
status    = 0
price     = 19.74
cost      = 19.74
balance   = 60.89
```

There was no real delivery: the request used `test=1`.

That check showed that a long Cyrillic book title made the text a multipart SMS. The title length is set by the user, so both the number of segments and the cost calculated by the emulator could increase with it.

The title was then removed from the notification, leaving two bounded variants:

```text
Новая книга у автора: <имя автора>.
```

When several authors match:

```text
Новая книга у авторов из ваших подписок.
```

A second manual check produced these results:

| Scenario | Send attempts per number | SMSPilot status | `price` | `cost` |
| --- | ---: | ---: | ---: | ---: |
| One matching author | 1 | 0 | 9.87 | 9.87 |
| Two matching authors, both subscribed | 1 | 0 | 9.87 | 9.87 |

In this verified scenario, the cost calculated by the emulator fell from `19.74` to `9.87` — exactly by half. This is the result of a specific test-mode check, not a statement about SMSPilot pricing or actual delivery cost.

The second check also confirmed deduplication: even when one number subscribed to two authors of the new book, there was one send attempt.

## Error handling

[`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) converts network errors, unsuccessful HTTP responses, invalid JSON, and rejection by SMSPilot into a `RuntimeException` with a safe message.

[`BookService`](../../services/BookService.php) catches that error after the book has been saved. A short warning without the API key or raw provider response is logged; subsequent recipients continue to be processed.

A notification failure therefore remains an external integration error and does not damage the catalog state.

## If sending volume grows

Requests to SMSPilot are currently sent sequentially in the same HTTP request that creates the book. For a small test application, this avoids separate infrastructure.

With higher load, sending would be better moved to a background task queue. In Yii2, one option is [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue). If retry guarantees are also needed, outgoing notification state should be stored separately in the database and sending handled by an independent worker.

For large numbers of recipients, batch sending through the SMS provider is also worth considering to reduce the number of individual HTTP requests.
