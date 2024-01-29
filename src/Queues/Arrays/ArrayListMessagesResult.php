<?php

namespace Shrd\Laravel\Azure\Storage\Queues\Arrays;

use MicrosoftAzure\Storage\Queue\Models\ListMessagesResult;

class ArrayListMessagesResult extends ListMessagesResult
{
    public function __construct(protected array $queueMessages) {
    }

    public function getQueueMessages(): array
    {
        return $this->queueMessages;
    }
}
