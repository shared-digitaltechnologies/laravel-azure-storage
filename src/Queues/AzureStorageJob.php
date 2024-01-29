<?php

namespace Shrd\Laravel\Azure\Storage\Queues;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Safe\Exceptions\UrlException;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;

/**
 * @mixin QueueMessage
 */
class AzureStorageJob extends Job implements JobContract
{
    public function __construct(
        Container $container,
        protected QueueService $queueService,
        protected QueueMessage $job,
        string $connectionName,
        string $queue,
    ) {
        $this->container = $container;
        $this->queue = $queue;
        $this->connectionName = $connectionName;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function delete(): void
    {
        parent::delete();
        $this->queueService->deleteMessage($this->queue, $this->job->getMessageId(), $this->job->getPopReceipt());
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function release($delay = 0): void
    {
        parent::release($delay);
        $this->queueService->updateMessage(
            queueName: $this->queue,
            messageId: $this->job->getMessageId(),
            popReceipt: $this->job->getPopReceipt(),
            messageText: $this->job->getMessageText(),
            visibilityTimeout: $delay,
        );
    }

    public function getJobId(): string
    {
        return $this->job->getMessageId();
    }

    /**
     * @throws UrlException
     */
    public function getRawBody(): string
    {
        $encodedMessageText = $this->job->getMessageText();
        return \Safe\base64_decode($encodedMessageText);
    }

    /**
     * @throws UrlException
     */
    public function attempts(): int|string
    {
        return $this->getRawBody();
    }

    public function getQueueMessage(): QueueMessage
    {
        return $this->job;
    }

    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this->job, $name], $arguments);
    }
}
