# 参与开发

## 选择语言

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/CONTRIBUTING.md) | [English](./CONTRIBUTING_en.md) | [Español](./CONTRIBUTING_es.md) | **中文** | [Français](./CONTRIBUTING_fr.md) | [Deutsch](./CONTRIBUTING_de.md) |

感谢你关注 Yii2 Book Catalog。这是一个小型 Yii2 Web 应用，因此改动应保持范围明确、可复现并便于审查。

## 开始之前

- 可复现的错误请通过 GitHub Issue 报告。
- 改进建议请说明问题、使用场景和预期行为。
- 安全问题请遵循[安全策略](../../.github/SECURITY.md)，不要公开敏感细节。
- 大型改动开始前，请确认它符合项目用途，并且不会在没有明确理由的情况下扩大范围。

## 应用契约

- 本项目是 Yii2 Web 应用，不是 REST API、SPA 或生产平台。
- `Book` 与 `Author` 是多对多关系。
- 游客可以浏览目录、使用公开 Top-10 报表，并通过手机号订阅指定作者。
- 已认证用户还可以管理图书和作者。
- 主要流程为 `Controller → Form Model / DTO → application service → ActiveRecord / focused query → DB`。
- `BookService` 通过 Yii DI 注入 `BookController`；控制器不会自行构建应用服务或外部 provider/client 依赖。
- 数据库 schema 变更仅通过 migrations 完成。
- SMSPilot 仅使用 emulator/test 模式。
- secrets、API keys 和本地环境值通过 environment/config 提供，不提交到仓库。

## 分支

使用能反映改动目的的简短名称，例如：

```text
fix/book-validation
docs/update-development-guide
chore/update-ci
```

## 提交

推荐使用 Conventional Commits。例如：

```text
fix: correct book validation
docs: clarify local startup
test: cover subscription regression
chore: update CI configuration
```

## 本地检查

项目 runtime 通过 Docker-backed Make targets 执行。不要在宿主机运行 PHP、Composer、Yii CLI、PHPUnit、PHPStan 或 PHPCS。

首次启动说明见[开发指南](../development.md)。

提交 Pull Request 前运行：

```shell
make check
```

如果应用行为发生变化，还应运行：

```shell
make test
```

覆盖率是独立诊断，仅在确有需要时运行：

```shell
make coverage
```

数据库 schema 变化必须增加 migration，并验证相应 migration 流程。不要用大范围 `chmod`、`chown` 或删除 generated state 来掩盖环境问题。

## Pull Request

Pull Request 描述中请说明：

- 问题和具体改动；
- 已执行的检查；
- 行为变化时新增或更新的 tests；
- schema 变化时对数据库的影响和 migration；
- 对文档、UI、uploads 或外部集成的影响。

提交前确认：

- 未包含 secrets、API keys、cookies、session data 或本地 `.env*`；
- `vendor/`、`runtime/`、generated assets、uploads 和 coverage output 未进入 commit；
- 改动仅覆盖一个连贯任务；
- 未加入无关 formatting 或 refactoring；
- 文档与已验证行为一致。
