<?php

namespace AIReporter\Laravel;

use AIReporter\Contracts\AiDriver;
use AIReporter\Contracts\CommitProvider;
use AIReporter\Contracts\Notifier;
use AIReporter\Drivers\NullDriver;
use AIReporter\Drivers\OpenAiDriver;
use AIReporter\Events\ReportGenerated;
use AIReporter\Laravel\Console\GenerateReport;
use AIReporter\Laravel\Listeners\SendReportToConfluence;
use AIReporter\Laravel\Listeners\SendReportToEmail;
use AIReporter\Laravel\Listeners\SendReportToSlack;
use AIReporter\Notifiers\EmailNotifier;
use AIReporter\Notifiers\MultiNotifier;
use AIReporter\Notifiers\SlackNotifier;
use AIReporter\Renderer\MarkdownRenderer;
use AIReporter\Services\CommitProviders\GitCommitProvider;
use AIReporter\Services\ReportGenerator;
use AIReporter\Services\TemplateManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

/**
 * Registers AI Reporter bindings, config, templates, and console command
 * for Laravel applications.
 */
class ReportingServiceProvider extends ServiceProvider
{
    /** {@inheritdoc} */
    public function register(): void
    {
        // 1. Merge default config so users can override only what they need
        $this->mergeConfigFrom(__DIR__.'/config/reporting.php', 'reporting');

        $this->app->singleton(CommitProvider::class, fn () => new GitCommitProvider);

        $this->app->singleton(Notifier::class, function () {
            $notifiers = [];

            // Slack
            $slackCfg = config('reporting.notifications.slack');

            if ($slackCfg['enabled']) {
                $notifiers[] = new SlackNotifier(
                    $slackCfg['webhook'],
                    $slackCfg['channel']
                );
            }

            // Email
            $mailCfg = config('reporting.notifications.email');

            if ($mailCfg['enabled']) {
                $mailer = new Mailer(Transport::fromDsn($mailCfg['dsn']));
                $notifiers[] = new EmailNotifier(
                    $mailer,
                    $mailCfg['from'],
                    $mailCfg['recipients']
                );
            }

            // Confluence
            $confluenceCfg = config('reporting.notifications.confluence');

            if ($confluenceCfg['enabled']) {
                $notifiers[] = new \AIReporter\Notifiers\ConfluenceNotifier(
                    baseUrl: $confluenceCfg['base_url'],
                    email: $confluenceCfg['username'],
                    apiToken: $confluenceCfg['token'],
                    spaceKey: $confluenceCfg['space_key'],
                    parentPageId: $confluenceCfg['parent_page_id'],
                    labels: $confluenceCfg['labels'] ? explode(',', $confluenceCfg['labels']) : [],
                    http: HttpClient::create(),
                );
            }

            return new MultiNotifier($notifiers);
        });

        $this->app->singleton(ReportGenerator::class, function ($app) {
            return new ReportGenerator(
                ai: $app->make(AiDriver::class),
                templates: $app->make(TemplateManager::class),
                commits: $app->make(CommitProvider::class),
                renderer: $app->make(MarkdownRenderer::class)
            );
        });

        // 2. Bind the AI driver (OpenAI by default, can swap via config/DI)
        $this->app->singleton(AiDriver::class, function ($app) {
            $driver = config('reporting.driver', 'openai');

            return match ($driver) {
                'openai' => new OpenAiDriver(
                    apiKey: config('services.openai.key')
                ),
                'null', 'offline', 'stub' => new NullDriver,
                default => throw new \InvalidArgumentException(
                    "Unknown AI driver [$driver]"
                ),
            };
        });

        // 3. Template manager — allows project-level overrides
        $this->app->singleton(TemplateManager::class, function () {
            return new TemplateManager(
                templatePath: base_path(config('reporting.template_path')),
                reportStore: storage_path(config('reporting.report_store'))
            );
        });

        // 4. Commit provider — wraps local Git commands
        $this->app->singleton(GitCommitProvider::class);

        // 5. Core ReportGenerator orchestration
        $this->app->singleton(ReportGenerator::class, function ($app) {
            return new ReportGenerator(
                ai: $app->make(AiDriver::class),
                templates: $app->make(TemplateManager::class),
                commits: $app->make(GitCommitProvider::class),
                renderer: $app->make(MarkdownRenderer::class),
            );
        });

        $this->publishes([
            __DIR__.'/../../scripts/git/hooks/client/pre-commit' => $this->app->basePath('.git/hooks/pre-commit'),
            __DIR__.'/../../scripts/git/hooks/prepare-commit-msg' => $this->app->basePath('.git/hooks/prepare-commit-msg'),
        ], 'git-hooks');
    }

    /** {@inheritdoc} */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // 6. Publishable assets: config & template stubs
            $this->publishes([
                __DIR__.'/config/reporting.php' => config_path('reporting.php'),
            ], 'ai-reporter-config');

            $this->publishes([
                // copy default stubs so teams can customise
                __DIR__.'/../Templates' => resource_path('report-templates'),
            ], 'ai-reporter-templates');

            // 7. Console command
            $this->commands([
                GenerateReport::class,
            ]);

            // 8. Event listeners (queued)
            if ($this->app->runningInConsole()) {
                $this->commands([
                    GenerateReport::class,
                ]);

                // Event listeners (queued)
                Event::listen(
                    ReportGenerated::class,
                    SendReportToSlack::class
                );

                Event::listen(
                    ReportGenerated::class,
                    SendReportToEmail::class
                );

                Event::listen(
                    ReportGenerated::class,
                    SendReportToConfluence::class
                );
            }
        }
    }
}
