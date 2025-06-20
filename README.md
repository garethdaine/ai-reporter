# AI Reporter

_Fully-automated dev-progress reporting for mono-repo & micro-service projects_

* Generates **weekly** and **monthly** Markdown reports from Git commits + directory snapshots
* Summarises the work with **OpenAI (GPT-4o-mini)** or **NullDriver** for offline mode
* Publishes reports to **Slack, Email, Confluence** (pluggable notifier system)
* Works **framework-agnostically** via a CLI, with first-class **Laravel** integration
* Ships with Pint + PHPStan + Pest and a pre-commit hook for zero-regression commits

---

## Table of Contents
1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Configuration](#configuration)
5. [CLI Usage](#cli-usage)
6. [Laravel Usage](#laravel-usage)
7. [Notifications](#notifications)
8. [Developer Guide](#developer-guide)
9. [Testing](#testing)
10. [Contributing](#contributing)
11. [License](#license)

---

## Requirements
| Tool / Library | Version |
|----------------|---------|
| PHP            | **^8.2** |
| Composer       | 2.x     |
| Git            | installed |
| tree (command) | optional (automatically falls back) |
| OpenAI account | only if you use `OpenAiDriver` |

---

## Installation

```bash
composer require garethdaine/ai-reporter         # core package
composer require --dev laravel/pint phpstan/phpstan pestphp/pest
```

> **Laravel projects** only need to run the single `require` line—our service-provider auto-registers.

Clone hooks (done automatically by composer):

```bash
scripts/git/hooks/local/pre-commit  # Pint + PHPStan + Pest
```

---

## Quick Start

```bash
# Weekly summary for past 7 days, saved + published
./bin/report --type=weekly
```

_No flags?_ —> the CLI asks for type / date ranges via Laravel Prompts.

---

## Configuration

### Global ENV

| Variable | Description |
|----------|-------------|
| `OPENAI_API_KEY` | Key for GPT-4o-mini |
| `OFFLINE` | `true` ➜ use `NullDriver` |
| `REPORTING_TEMPLATE_PATH` | Folder with custom stubs |
| `REPORTING_REPORT_STORE` | Where finished reports live |

### Notification ENV

| Channel | Key(s) |
|---------|--------|
| **Slack** | `REPORTING_SLACK_WEBHOOK`, `REPORTING_SLACK_CHANNEL`, `REPORTING_NOTIFY_SLACK` |
| **Email** | `REPORTING_MAIL_DSN`, `REPORTING_MAIL_FROM`, `REPORTING_MAIL_TO`, `REPORTING_NOTIFY_EMAIL` |
| **Confluence** | `CONFLUENCE_BASE_URL`, `CONFLUENCE_EMAIL`, `CONFLUENCE_API_TOKEN`, `CONFLUENCE_SPACE_KEY`, `CONFLUENCE_PARENT_ID`, `REPORTING_NOTIFY_CONFLUENCE` |

### Laravel Config

```php
// config/reporting.php
return [
    'template_path' => 'resources/report-templates',
    'report_store'  => 'storage/app/reports',

    'notifications' => [
        'slack' => [ 'enabled' => env('REPORTING_NOTIFY_SLACK', true), /* … */ ],
        'email' => [ 'enabled' => env('REPORTING_NOTIFY_EMAIL', false), /* … */ ],
        'confluence' => [ 'enabled' => env('REPORTING_NOTIFY_CONFLUENCE', false), /* … */ ],
    ],
];
```

Publish + edit:

```bash
php artisan vendor:publish --tag=reporting-config
php artisan vendor:publish --tag=reporting-templates
```

---

## CLI Usage

```bash
./bin/report                                    # interactive
./bin/report --type=monthly --start=2025-06-01 \
             --end=2025-06-30                   # non-interactive
./bin/report --dry                              # prints to stdout
```

All options:

| Flag         | Description                        |
|--------------|------------------------------------|
| `--type`     | `weekly` (default) or `monthly`    |
| `--start`    | `YYYY-MM-DD`                       |
| `--end`      | `YYYY-MM-DD`                       |
| `--dry`      | Skip saving + notifications        |

---

## Laravel Usage

```bash
php artisan report:generate         # interactive
php artisan report:generate \
    --type=weekly --start=...       # scripted
```

### Service Provider

`extra/Laravel/ReportingServiceProvider.php` registers:

* Config merge + stub publishing
* Bound `ReportGenerator`, `TemplateManager`, `Notifier`
* Queued listeners:
  * `SendReportToSlack`
  * `SendReportToEmail`
  * `SendReportToConfluence`

---

## Notifications

* **SlackNotifier** – posts Markdown to a webhook
* **EmailNotifier** – uses Symfony Mailer
* **ConfluenceNotifier** – creates or updates a Confluence page
* **MultiNotifier** – fan-out to any combo of the above
* **NotifierListener** – PSR-14 + Laravel event listener wrapper

All notifications fire when `ReportGenerated` is dispatched.

---

## Developer Guide

### Core Flow

```
GitCommitProvider   → ReportGenerator  → MarkdownRenderer
                   ↘ TemplateManager ↗ → Template stubs
SimpleEventDispatcher → NotifierListener → MultiNotifier → Slack/Email/Confluence
```

### Extending

* **Custom AI Driver** – implement `Contracts\AiDriver`
* **Custom Notifier** – implement `Contracts\Notifier`
* **Different commit backend** – implement `Contracts\CommitProvider`

Register via DI or `SimpleEventDispatcher`.

---

## Testing

Unit & feature suites in `/tests` (Pest):

```bash
composer test      # runs ./vendor/bin/pest
```

Coverage:

```bash
composer test -- --coverage
```

Pre-commit hook automatically runs:

* `composer lint` → Pint `--test` + PHPStan
* `composer test`

---

## Contributing

1. Fork + create topic branch
2. Run `composer lint && composer test` locally
3. Submit PR with passing CI

---

## License

[MIT](LICENSE) © 2025 AI Reporter Team
