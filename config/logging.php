<?php

declare(strict_types=1);

use MinVWS\Logging\Laravel\Models\AuditLog;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stderr'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

    /**
     * RDO Audit logs (minvws/laravel-logging, see ../packages/nl-rdo-laravel-logging)
     */
    'dblog_enabled' => env('AUDIT_LOG_DBLOG_ENABLED', false),
    'dblog_encrypt' => env('AUDIT_LOG_DBLOG_THEIR_PUB_KEY') !== null,
    'dblog_pubkey' => env('AUDIT_LOG_DBLOG_THEIR_PUB_KEY'),
    'dblog_secret' => env('AUDIT_LOG_DBLOG_OUR_PRIV_KEY'),
    'syslog_enabled' => env('AUDIT_LOG_SYSLOG_ENABLED', true),
    'rabbitmq_enabled' => env('AUDIT_LOG_RABBITMQ_ENABLED', false),

    // The model we use to write data to the database via the dblogger
    'auditlog_model' => env('AUDIT_LOG_MODEL', AuditLog::class),

    // Automatically logs the complete HTTP request
    'log_full_request' => env('AUDIT_LOG_FULL_REQUEST', false),

    // Keys for encrypted logging
    'syslog_encrypt' => env('AUDIT_LOG_SYSLOG_THEIR_PUB_KEY') !== null,
    'syslog_pubkey' => env('AUDIT_LOG_SYSLOG_THEIR_PUB_KEY'),
    'syslog_secret' => env('AUDIT_LOG_SYSLOG_OUR_PRIV_KEY'),
    'syslog_channel' => env('AUDIT_LOG_SYSLOG_CHANNEL'),

    'rabbitmq_additional_allowed_events' => [],
    'rabbitmq_log_pii' => env('AUDIT_LOG_RABBITMQ_LOG_PII', false),

];
