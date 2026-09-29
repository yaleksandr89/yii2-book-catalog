# 架构

## 选择语言

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./architecture.md) | [English](./architecture_en.md) | [Español](./architecture_es.md) | **已选** | [Français](./architecture_fr.md) | [Deutsch](./architecture_de.md) |

项目围绕 Yii2 的标准能力构建，不为增加层次而增加层次。ActiveRecord 处理常规数据访问，表单模型验证用户输入；复合操作在确实需要独立职责边界时才被抽离。

## 总体结构

```text
HTTP 请求
    ↓
控制器
    ↓
表单模型
    ↓
服务 / ActiveRecord / 独立报表查询
    ↓
MySQL
```

例如，[`BookController`](../../controllers/BookController.php) 接收请求、检查访问权限、获取上传文件并启动验证。[`BookForm`](../../models/BookForm.php) 验证图书字段、所选作者和图片。验证成功后，控制器将图书变更交给 [`BookService`](../../services/BookService.php)。

因此，控制器不负责事务、文件系统或 SMS 发送。

## 数据与关系

主要模型包括：

- [`Book`](../../models/Book.php) — 图书；
- [`Author`](../../models/Author.php) — 作者；
- [`Subscription`](../../models/Subscription.php) — 手机号对作者的订阅；
- [`User`](../../models/User.php) — 可以修改目录的用户。

一本书可以有多位作者，一位作者也可以有多本书。关系保存在 `book_author` 表中。

数据库结构由 [migrations](../../migrations/) 定义。外键保护表间关系，唯一索引防止重复添加同一图书与作者组合，图书出版年份另有索引用于报表。

显示图书列表时，使用 `with('authors')` 预先加载作者，因此不会为每本书单独查询数据库。

## 保存图书

之所以需要 [`BookService`](../../services/BookService.php)，是因为修改图书包含多个动作：保存图书、更新作者关系、处理图片，以及在新建时通知订阅者。

新建时：

1. 新图片以生成的文件名保存；
2. 图书及其作者关系在同一事务内写入；
3. 如果事务失败，则回滚并删除新文件；
4. 事务成功结束后才开始发送通知。

更新时，如果没有上传新文件，旧图片会保留。若更换图片，只有在新的图书状态成功保存后才删除旧文件。

删除时，先提交数据库记录的删除，再删除图片文件。

这样可以避免图书变更未写入数据库、有效图片却已被删除的情况。

## Top-10 报表

[`TopAuthorsReportForm`](../../models/TopAuthorsReportForm.php) 验证所选年份，[`TopAuthorsQuery`](../../models/TopAuthorsQuery.php) 构建报表。

MySQL 通过单个聚合查询完成统计：按年份筛选图书，按作者分组，然后取前十个结果。图书数量相同时，再按作者姓名和标识符排序，保证结果顺序可预测。

该查询独立于控制器，因为聚合统计是一项专门的读取操作，而非普通的 ActiveRecord 模型搜索。

## 输入验证与访问控制

用户输入在服务端验证。例如，[`BookForm`](../../models/BookForm.php) 检查必填字段、出版年份、所选作者是否存在，以及上传图片的扩展名、MIME 类型和不超过 5 MiB 的大小限制。

对于修改目录的操作，控制器使用 `AccessControl`；`VerbFilter` 限定删除只能通过 POST 进行。普通 Web 表单受 Yii 的 CSRF 保护。

数据库查询使用 ActiveRecord、Query Builder 和 Yii 的参数化条件；不会通过字符串拼接将用户值写入 SQL。

包括 `SMSPILOT_API_KEY` 在内的密钥从环境变量和应用配置读取。

## 有意保持简单的设计

没有为每个 ActiveRecord 模型再包一层 repository。在这个应用中，这一层主要会重复 Yii 已提供的功能，无法带来有益的隔离。

保存图书后同步发送 SMS。若负载明显增加，最好将其移入后台任务队列；Yii2 有独立扩展 [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue)。这个测试应用没有增加队列、worker 或配套基础设施。

应用保持为服务端渲染的 Yii2 Web。任务并不需要独立 REST API 或 SPA，因此没有创建它们。
