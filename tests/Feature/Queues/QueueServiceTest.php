<?php

namespace Shrd\Laravel\Azure\Storage\Tests\Feature\Queues;

use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageException;
use Shrd\Laravel\Azure\Storage\Tests\TestCase;

class QueueServiceTest extends TestCase
{
    /**
     * @throws AzureStorageException
     */
    public function test_createMessage_creates_queue_if_it_does_not_exist_yet(): void
    {
        $queueName = $this->fake()->bothify('???????????????????');

        $queueService = $this->makeStorageAccount()->queue();

        $this->assertFalse($queueService->queueExists($queueName));

        $message = 'MyTestMessage';

        try {
            $queueService->createMessage($queueName, $message);
            $this->assertTrue($queueService->queueExists($queueName));
        } finally {
            $queueService->deleteQueue($queueName);
        }
    }
}
