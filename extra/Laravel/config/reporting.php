<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Driver
    |--------------------------------------------------------------------------
    |
    | This defines which AI driver to use.
    | - "openai" = OpenAI API (via openai-php/laravel or sdk)
    | - You may implement your own and bind AiDriver to another concrete class.
    |
    */
    'driver' => env('REPORTING_AI_DRIVER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | The directory for:
    | - report templates (weekly.md.stub, monthly.md.stub)
    | - saved reports (as .md files)
    |
    | These default to /resources/reporting/...
    |
    */
    'paths' => [
        'templates' => resource_path('reporting/templates'),
        'output' => resource_path('reporting/reports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Types
    |--------------------------------------------------------------------------
    |
    | Define available report types and their individual settings.
    | Each type can specify:
    | - default model to use
    | - max tree depth
    | - fallback behaviour
    |
    */
    'types' => [

        'weekly' => [
            'model' => 'gpt-4o-mini',
            'treeDepth' => 3,
        ],

        'monthly' => [
            'model' => 'gpt-4',
            'treeDepth' => 4,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Options
    |--------------------------------------------------------------------------
    |
    | Global prompt modifiers — could be tone, perspective, or formatting rules
    | that get merged into the AI prompt (via your stub or prompt builder).
    |
    */
    'prompt' => [
        'tone' => 'non-technical, executive summary',
        'style' => 'concise, bullet-pointed, markdown',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Dispatching
    |--------------------------------------------------------------------------
    |
    | If true, ReportGenerator will emit a ReportGenerated event.
    | Disable if running in a minimal or console-only context.
    |
    */
    'emit_events' => true,

    /*|--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure notification channels for report generation events.
    | Currently supports Slack via webhook.
    | Set enabled to false to disable notifications.
    |*/
    'notifications' => [
        'slack' => [
            'enabled' => env('REPORTING_NOTIFY_SLACK', false),
            'webhook' => env('REPORTING_SLACK_WEBHOOK'),
            'channel' => env('REPORTING_SLACK_CHANNEL', ''),
        ],

        'email' => [
            'enabled' => env('REPORTING_NOTIFY_EMAIL', false),
            'dsn' => env('REPORTING_MAIL_DSN'), // SMTP, sendmail, or any supported DSN
            'from' => env('REPORTING_MAIL_FROM', 'noreply@localhost'),
            'recipients' => explode(',', env('REPORTING_MAIL_TO', '')),
        ],

        'confluence' => [
            'enabled' => env('REPORTING_NOTIFY_CONFLUENCE', false),
            'baseUrl' => env('REPORTING_CONFLUENCE_BASE_URL', ''),
            'email' => env('REPORTING_CONFLUENCE_EMAIL', ''),
            'token' => env('REPORTING_CONFLUENCE_API_TOKEN', ''),
            'spaceKey' => env('REPORTING_CONFLUENCE_SPACE_KEY', ''),
            'parentPageId' => env('REPORTING_CONFLUENCE_PARENT_PAGE_ID', ''),
            'labels' => explode(',', env('REPORTING_CONFLUENCE_LABELS', '')),
        ],
    ],
];
