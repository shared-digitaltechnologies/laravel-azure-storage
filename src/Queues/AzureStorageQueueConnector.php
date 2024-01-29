<?php

namespace Shrd\Laravel\Azure\Storage\Queues;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Shrd\Laravel\Azure\Storage\AzureStorageService;

class AzureStorageQueueConnector implements ConnectorInterface
{
    public function __construct(protected AzureStorageService $service)
    {
    }

    public function connect(array $config): AzureStorageQueue
    {
        $account           = $this->service->account($config['connection'] ?? null);
        $queueService      = $account->queue();

        $default           = $config['queue'] ?? 'default';
        $prefix            = $config['prefix'] ?? '';
        $visibilityTimeout = $config['timeout'] ?? 60;

        return new AzureStorageQueue(
            queueService: $queueService,
            default: $default,
            visibilityTimeout: $visibilityTimeout,
            prefix: $prefix
        );
    }
}
