<?php

namespace Shrd\Laravel\Azure\Storage\Queues\Arrays;

use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;

class ArrayCreateMessageResult extends CreateMessageResult
{
    public function __construct(protected QueueMessage $queueMessage)
    {
    }

    public function getQueueMessage(): QueueMessage
    {
        return $this->queueMessage;
    }
}
