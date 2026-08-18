# 安全策略

## 选择语言

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/SECURITY.md) | [English](./SECURITY_en.md) | [Español](./SECURITY_es.md) | **中文** | [Français](./SECURITY_fr.md) | [Deutsch](./SECURITY_de.md) |

## 支持的版本

安全修复针对当前 `master` 状态和最新已发布版本进行评估。

| 版本 | 支持 |
|---|---|
| `master` | 是 |
| 最新已发布版本 | 是 |

## 什么属于安全漏洞

安全问题包括但不限于：

- 绕过身份验证、`AccessControl` 或 destructive actions 的限制；
- 绕过 CSRF 防护；
- 对上传文件、文件名或路径的不安全处理；
- SQL injection 或绕过服务端验证；
- 泄露 API keys、密码、cookies、session data 或其他私有配置；
- 通过 logs、错误消息或 SMSPilot 集成泄露敏感数据；
- 未授权访问其他用户的数据或受保护操作。

普通错误、使用问题和改进建议可以发布到 GitHub Issues，只要其中不包含敏感数据。

## 如何报告漏洞

如果 GitHub Private Vulnerability Reporting 可用，请优先使用：

1. 打开仓库的 **Security** 标签页。
2. 进入 **Advisories**。
3. 选择 **Report a vulnerability**。
4. 提交报告，不要在普通 Issue 中公开敏感细节。

如果 Private Vulnerability Reporting 不可用，请创建一个最小化的公开 Issue，不包含漏洞技术细节，并请求建立私下联系渠道。

修复发布前请勿公开：

- API keys 或密码；
- cookies、session data 或 CSRF tokens；
- 真实个人数据；
- 完整 production logs；
- 可工作的 exploit 或不必要的可复现攻击细节。

## 报告中应包含什么

如有可能，请提供：

- 受影响的 release、branch 或 commit；
- 影响说明；
- 最小复现步骤；
- 预期与实际行为；
- 有帮助时提供清理后的 request/response/log 片段；
- 已知的可能修复方案。

只使用合成或匿名化数据。

## 报告处理

报告会在可用时间内进行检查；不承诺固定 SLA。

在公开细节前，请先与维护者协调披露。漏洞确认后，将通过 coordinated disclosure 发布修复和受影响版本信息。

本项目未声明漏洞奖励计划。
