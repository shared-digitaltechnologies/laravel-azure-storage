<?php

namespace Shrd\Laravel\Azure\Storage\Queues;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use Illuminate\Support\Str;
use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;

class AzureStorageQueue extends Queue implements QueueContract
{

    public function __construct(protected QueueService $queueService,
                                protected string $default,
                                protected int $visibilityTimeout,
                                protected string $prefix = '')
    {
    }

    /**
     * Checks whether the provided queue-name already has the prefix.
     *
     * @param string $queueName
     * @return bool
     */
    protected function queueNameIsPrefixed(string $queueName): bool
    {
        if(!$this->prefix) return true;
        return Str::startsWith($queueName, $this->prefix);
    }

    /**
     * Gives the queue-name without the prefix.
     *
     * @param string|null $queue
     * @return string
     */
    public function getQueueBaseName(?string $queue = null): string
    {
        if(!$queue) return $this->default;
        if($this->queueNameIsPrefixed($queue)) {
            return Str::substr($queue, strlen($this->prefix));
        }
        return $queue;
    }

    /**
     * Gives the queue-name with the prefix.
     *
     * @param string|null $queue
     * @return string
     */
    public function getQueueName(?string $queue = null): string
    {
        $baseName = $queue?: $this->default;
        if(!$this->queueNameIsPrefixed($queue)) return $this->prefix.$baseName;
        return $baseName;
    }

    /**
     * @return \MicrosoftAzure\Storage\Queue\Models\Queue[]
     * @throws AzureStorageServiceException
     */
    public function getQueues(): array
    {
        return $this->queueService->getQueues($this->prefix);
    }

    /**
     * Gives the visibility timeout.
     *
     * @return int
     */
    public function getVisibilityTimeout(): int
    {
        return $this->visibilityTimeout;
    }

    public function getQueueService(): QueueService
    {
        return $this->queueService;
    }

    /**
     * Gives the underlying queue proxy.
     *
     * @return IQueue
     */
    public function getQueueProxy(): IQueue
    {
        return $this->queueService->proxy();
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function size($queue = null): int
    {
        return $this->queueService->getApproximateMessageCount($this->getQueueName($queue));
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function push($job, $data = '', $queue = null): CreateMessageResult
    {
        return $this->pushRaw($this->createPayload($job, $queue, $data), $queue);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function pushRaw($payload, $queue = null, array $options = []): CreateMessageResult
    {
        return $this->queueService->createMessage($this->getQueueName($queue), $payload, $options);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function later($delay, $job, $data = '', $queue = null): CreateMessageResult
    {
        $payload = $this->createPayload($job, $queue, $data);

        $options = new CreateMessageOptions();
        $options->setVisibilityTimeoutInSeconds($this->secondsUntil($delay));

        return $this->queueService->createMessage($this->getQueueName($queue), $payload, $options);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function pop($queue = null): ?AzureStorageJob
    {
        $queue = $this->getQueueName($queue);

        // As recommended in the API docs, first call listMessages to hide message from other code.
        $listMessagesOptions = new ListMessagesOptions();
        $listMessagesOptions->setVisibilityTimeoutInSeconds($this->getVisibilityTimeout());
        $listMessagesOptions->setNumberOfMessages(1);

        $messages = $this->getQueueService()->listMessages($queue, $listMessagesOptions);

        if (count($messages) > 0) {
            return new AzureStorageJob(
                $this->container,
                $this->queueService,
                $messages[0],
                $this->connectionName,
                $queue
            );
        }

        return null;
    }

}
