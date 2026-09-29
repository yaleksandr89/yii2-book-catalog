# 开发

## 选择语言

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](./development.md) | [English](./development_en.md) | [Español](./development_es.md) | **已选** | [Français](./development_fr.md) | [Deutsch](./development_de.md) |

## 要求

宿主机需要：

- Git；
- Make；
- 支持 Compose 的 Docker；
- 编辑器和常用系统工具。

PHP、Composer、Yii CLI、MySQL 客户端、PHPUnit、PHPStan 和 PHPCS 都通过 [`Makefile`](../../Makefile) 中的命令在容器内运行，无需在宿主机单独安装。

## 初次配置

首次启动：

```bash
make init
make build
make up
make composer-install
make migrate
```

`make init` 根据 [`.env.docker.example`](../../.env.docker.example) 创建 `.env.docker`，并准备应用运行时写入的本地目录。

启动后可通过 `http://localhost:8080` 访问应用。

停止容器：

```bash
make down
```

创建登录用户：

```bash
make yii CMD="user/create <username> <password>"
```

## 环境变量

主要设置位于 `.env.docker`：

- `HOST_UID`、`HOST_GID` — 宿主机用户标识；
- `APP_PORT` — 宿主机访问 Nginx 的端口；
- `MYSQL_DATABASE` — 主数据库；
- `MYSQL_TEST_DATABASE` — 独立的测试数据库；
- `MYSQL_USER`、`MYSQL_PASSWORD`、`MYSQL_ROOT_PASSWORD` — MySQL 设置；
- `DB_HOST`、`DB_PORT`、`DB_FORWARD_PORT` — MySQL 连接参数；
- `COOKIE_VALIDATION_KEY` — cookie 验证密钥；
- `SMSPILOT_API_KEY` — SMSPilot 密钥。

真实密钥不得进入 Git。应在本地替换示例中的 `COOKIE_VALIDATION_KEY`；项目提供 `make cookie-key` 来生成随机值。如果不测试 SMSPilot 集成，`SMSPILOT_API_KEY` 可以留空。

## 容器

Docker Compose 启动三个服务：

- `php` — PHP-FPM 和项目的所有 PHP 工具；
- `nginx` — Web 服务器；
- `mysql` — MySQL 8.4。

PHP 容器以用户 `app` 运行，其 UID 和 GID 与宿主机用户一致，因此应用和开发工具创建的文件不会归 `root` 所有。

常用命令：

```bash
make ps
make restart php
make log nginx
make in php
```

## 数据库与演示数据

数据库结构只能通过 [Yii migrations](../../migrations/) 创建：

```bash
make migrate
```

测试使用独立数据库：

```bash
make test-db-init
make test-db-migrate
```

添加演示图书和作者：

```bash
make demo-data
```

该命令仅用于本地开发和可重复的界面手工检查。

## 测试与检查

主要命令：

```bash
make test
make test-dox
make check
```

- `make test` 运行 PHPUnit；
- `make test-dox` 运行相同测试，并显示易读的场景描述；
- `make check` 依次执行 Composer、PHP 语法、PHPStan 和代码风格检查。

必要时可分别运行各项检查：

```bash
make composer-validate
make php-lint
make phpstan-check
make phpcs-check
```

覆盖率通过单独的命令检查，不会拖慢日常开发：

```bash
make coverage
```

该命令在 `runtime/coverage.xml` 生成 Clover 报告；使用 `make coverage-html` 可在 `runtime/coverage` 生成 HTML 版本。

## GitHub Actions 自动检查

[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) workflow 在干净环境中重复项目的主要检查：

1. 准备并启动 Docker 容器；
2. 从 `composer.lock` 安装依赖并执行 migrations；
3. 创建独立的测试数据库；
4. 运行代码检查和 PHPUnit；
5. 将覆盖率报告发送到 Codecov；
6. 检查 MySQL 连接，以及通过 Nginx 获取的应用 HTTP 响应；
7. 无论前面的步骤结果如何都停止容器。

该 workflow 不负责部署应用。

## 文件所有权

可能在项目内创建文件的命令，都在 PHP 容器中以用户 `app` 运行，并映射到宿主机用户的 UID/GID。这包括 `vendor`、`runtime`、`web/assets` 和 `web/uploads`。

日常工作使用现有 Make 命令即可。无需通过大范围 `chmod`、`chown` 或删除目录来修复权限。
