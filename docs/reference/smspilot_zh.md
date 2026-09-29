# SMSPilot

## 选择语言

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./smspilot.md) | [English](./smspilot_en.md) | [Español](./smspilot_es.md) | **已选** | [Français](./smspilot_fr.md) | [Deutsch](./smspilot_de.md) |

创建新书时，应用会向订阅了该书任一作者的访客发送 SMS 通知。集成仅在 SMSPilot 测试模式下运行：请求会经过服务，但不会实际交付给移动运营商。

## 发送流程

```text
BookService
    ↓
SmsSenderInterface
    ↓
SmsPilotSender
    ↓
SMSPilot
```

[`BookService`](../../services/BookService.php) 依赖小型接口 [`SmsSenderInterface`](../../integrations/SmsSenderInterface.php)；具体外部服务的调用由 [`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) 负责。

[`SmsPilotSendResponse`](../../integrations/smspilot/SmsPilotSendResponse.php) 检查 SMSPilot 的成功响应是否具有预期结构。服务密钥由应用配置提供，不存放在源代码中。

`SmsPilotSender` 在每次请求中强制传入 `test=1`，还设置了较短的网络超时，并关闭 HTTP 响应内容的日志记录。

## SMS 何时发送

发送不属于保存图书的事务。

操作顺序如下：

1. 保存图片；
2. 将图书及其作者关系写入数据库；
3. 事务成功结束；
4. 此后才选择订阅者并开始向 SMSPilot 发送请求。

因此，即使 SMSPilot 不可用，也不会撤销已经创建的图书。

如果外部服务对某个号码返回错误，[`BookService`](../../services/BookService.php) 会记录警告，并继续处理其余接收者。

## 如何选择接收者

通过一个按电话号码聚合的查询选择接收者。

如果一个号码订阅了新书的多位作者，查询结果中仍只出现一次，最多进行一次发送尝试。这既避免重复通知，也不需要为每位作者单独查询数据库。

号码经过排序，因此处理顺序可预测。

## 为什么缩短消息

第一个可用版本发送包含书名的消息。功能上它正常工作：手工检查时 SMSPilot 返回 HTTP 200 和成功的测试状态 `0`。模拟器响应中包含以下值：

```text
server_id = 10000
status    = 0
price     = 19.74
cost      = 19.74
balance   = 60.89
```

请求使用 `test=1`，没有真实投递。

这次检查表明，较长的西里尔字母书名会使消息成为分段 SMS。书名长度由用户决定，因此分段数和模拟器计算的费用都可能随之增加。

于是从通知中移除了书名，只保留两个长度受限的版本：

```text
Новая книга у автора: <имя автора>.
```

如果匹配多位作者：

```text
Новая книга у авторов из ваших подписок.
```

再次手工检查得到以下结果：

| 场景 | 每个号码的发送尝试次数 | SMSPilot 状态 | `price` | `cost` |
| --- | ---: | ---: | ---: | ---: |
| 匹配一位作者 | 1 | 0 | 9.87 | 9.87 |
| 匹配两位作者，且均已订阅 | 1 | 0 | 9.87 | 9.87 |

在这个已验证的场景中，模拟器计算的费用从 `19.74` 降至 `9.87`，恰好减半。这只是一次具体测试模式检查的结果，并非对 SMSPilot 资费或实际投递费用的声明。

第二次检查还确认了去重：即使一个号码订阅了新书的两位作者，也只进行一次发送尝试。

## 错误处理

[`SmsPilotSender`](../../integrations/smspilot/SmsPilotSender.php) 将网络错误、失败的 HTTP 响应、无效 JSON 和 SMSPilot 的拒绝转换为带有安全错误消息的 `RuntimeException`。

[`BookService`](../../services/BookService.php) 在图书保存后捕获该错误。日志只记录简短警告，不包含 API 密钥或提供商的原始响应；后续接收者继续处理。

因此，通知失败属于外部集成错误，不会损坏目录状态。

## 如果发送量增加

目前，对 SMSPilot 的请求在创建图书的同一个 HTTP 请求中依次发送。对于小型测试应用，这样无需额外基础设施。

负载增加时，最好将发送移入后台任务队列。Yii2 可使用例如 [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue)。如果还需要重试保障，应在数据库中单独保存待发送通知的状态，并由独立 worker 负责发送。

接收者数量很大时，也可以考虑由 SMS 提供商批量发送，以减少单独的 HTTP 请求数量。
