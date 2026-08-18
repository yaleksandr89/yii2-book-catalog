# Yii2 图书目录

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

## 选择语言

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../README.md) | [English](./README_en.md) | [Español](./README_es.md) | **已选择** | [Français](./README_fr.md) | [Deutsch](./README_de.md) |

这是一个基于 Yii2 和 MySQL 的测试 Web 应用：提供图书与作者目录、封面上传、多对多关系、公开作者排行、手机号订阅，以及 SMSPilot 测试集成。

实现重点是职责分离：控制器负责 HTTP 请求和访问检查，输入数据在服务端验证，复杂的图书操作交给独立服务处理，报表通过单个聚合查询生成。项目运行在 Docker 中，宿主机不需要 PHP 或 Composer。

## 功能

- 公开浏览图书和作者目录；
- 登录后创建、编辑和删除图书与作者；
- 上传图书主图；
- 一本书可关联多个作者，一个作者也可关联多本书；
- 按所选年份统计图书数量的公开 Top-10 作者；
- 游客可通过手机号订阅指定作者；
- 通过 SMSPilot 发送新书测试短信通知。

## 快速开始

```bash
make init
make build
make up
make composer-install
make migrate
```

启动后，应用可通过 [http://localhost:8080](http://localhost:8080) 访问。

可使用以下控制台命令创建登录用户：

```bash
make yii CMD="user/create <username> <password>"
```

使用 `make demo-data` 可向目录填充演示数据。环境、测试数据库和质量检查的其他命令见[开发指南](../development.md)。

## 访问权限

| 用户 | 权限 |
| --- | --- |
| 游客 | 浏览图书和作者、查看所选年份的 Top-10、通过手机号订阅作者 |
| 已登录用户 | 拥有游客全部权限，并可创建、编辑和删除图书与作者 |

## 应用结构

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

控制器保持精简，主要负责 Web 场景：接收请求、检查访问权限、执行验证并将工作交给后续层。图书数据由 [`BookForm`](../../models/BookForm.php) 验证；[`BookService`](../../services/BookService.php) 负责保存图书、作者关系和图片。

Top-10 使用独立的 [`TopAuthorsQuery`](../../models/TopAuthorsQuery.php)：统计直接在数据库中通过单个查询完成，而不是在 PHP 中从已加载模型拼装。

这些设计、图片处理方式和职责边界详见[架构说明](../architecture.md)。

## SMSPilot

图书成功创建后，应用会查找其作者的订阅者，并通过 SMSPilot 测试模式发送通知。只有在图书及其关系成功写入数据库后才开始发送，因此外部服务失败不会回滚已经创建的图书。如果同一手机号订阅了新书的多个作者，该号码只会进行一次发送尝试。

手工验证发现，包含较长西里尔字母书名的消息会被模拟器按更昂贵的分段短信计算：缩短文本前为 `19.74`，缩短后为 `9.87`。因此通知中移除了书名，并将消息限制为两个简短版本，分别用于一个或多个匹配作者。

SMSPilot 响应、发送顺序和错误处理见[集成说明](../smspilot.md)。

## 有意保持简单的部分

- 图书保存后同步发送 SMS。对于有明显负载的应用，通常应把这项工作移出 HTTP 请求，交给后台任务队列，例如 [`yiisoft/yii2-queue`](https://github.com/yiisoft/yii2-queue)。本测试任务未增加独立队列 worker 和额外基础设施。
- 当 Yii 已经足以处理常规读写时，直接使用 ActiveRecord。没有为每个模型再增加 repository 层，因为在这个规模下它主要会重复现有的数据访问层。
- 未增加独立 REST API 或客户端 SPA：应用按普通的服务端渲染 Yii2 Web 应用实现。
