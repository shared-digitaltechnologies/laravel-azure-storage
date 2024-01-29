<?php

namespace Shrd\Laravel\Azure\Storage\Queues\Arrays;

use MicrosoftAzure\Storage\Queue\Models\ListQueuesResult;

class ArrayListQueuesResult extends ListQueuesResult
{
    public function __construct(protected array $queues)
    {
    }

    public function getQueues(): array
    {
        return $this->queues;
    }
}
