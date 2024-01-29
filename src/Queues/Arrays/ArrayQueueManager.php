<?php

namespace Shrd\Laravel\Azure\Storage\Queues\Arrays;

use MicrosoftAzure\Storage\Queue\Models\QueueACL;

class ArrayQueueManager
{
    /** @var array<string, ArrayQueue> */
    protected array $queues = [];

    public function __construct() {}

    protected function getDefaultAcl(): QueueACL
    {
        return new QueueACL();
    }
    public function ensureQueueExists(string $queueName): static
    {
        if(!array_key_exists($queueName, $this->queues)) {
            $this->queues[$queueName] = new ArrayQueue($queueName, $this->getDefaultAcl());
        }
        return $this;
    }

    public function getQueues(): array
    {
        return $this->queues;
    }

    public function get(string $queueName): ArrayQueue
    {
        $this->ensureQueueExists($queueName);
        return $this->queues[$queueName];
    }

    public function unset(string $queueName): static
    {
        unset($this->queues[$queueName]);
        return $this;
    }

}
