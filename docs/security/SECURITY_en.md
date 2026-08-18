# Security policy

## Choose a language

| Русский | English | Español | 中文 | Français | Deutsch |
|---|---|---|---|---|---|
| [Русский](../../.github/SECURITY.md) | **English** | [Español](./SECURITY_es.md) | [中文](./SECURITY_zh.md) | [Français](./SECURITY_fr.md) | [Deutsch](./SECURITY_de.md) |

## Supported versions

Security fixes are considered for the current `master` state and the latest published release.

| Version | Supported |
|---|---|
| `master` | Yes |
| Latest published release | Yes |

## What counts as a vulnerability

Security issues include, in particular:

- bypassing authentication, `AccessControl`, or destructive-action restrictions;
- bypassing CSRF protection;
- unsafe handling of uploaded files, file names, or paths;
- SQL injection or bypassing server-side validation;
- disclosure of API keys, passwords, cookies, session data, or other private configuration;
- leaking sensitive data through logs, error messages, or the SMSPilot integration;
- unauthorized access to another user's data or a protected action.

Ordinary bugs, usage questions, and improvement requests can be published in GitHub Issues when they do not contain sensitive data.

## How to report a vulnerability

Prefer GitHub Private Vulnerability Reporting when it is available:

1. Open the repository **Security** tab.
2. Go to **Advisories**.
3. Choose **Report a vulnerability**.
4. Submit the report without publishing sensitive details in a regular Issue.

If Private Vulnerability Reporting is unavailable, create a minimal public Issue without technical vulnerability details and request a private contact channel.

Do not publish before a fix is released:

- API keys or passwords;
- cookies, session data, or CSRF tokens;
- real personal data;
- complete production logs;
- a working exploit or unnecessary details that make the attack reproducible.

## What to include

When possible, include:

- the affected release, branch, or commit;
- impact;
- minimal reproduction steps;
- expected and actual behavior;
- sanitized request/response/log fragments when useful;
- a possible fix if known.

Use only synthetic or anonymized data.

## Report handling

Reports are reviewed as availability permits; no fixed SLA is promised.

Please coordinate disclosure with the maintainer before publishing details. After a vulnerability is confirmed, the fix and affected-version information are published using coordinated disclosure.

The project does not advertise a vulnerability reward program.
