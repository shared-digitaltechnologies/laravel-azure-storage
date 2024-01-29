<?php

namespace Shrd\Laravel\Azure\Storage\Queues\Arrays;

use GuzzleHttp\Promise\Promise;
use MicrosoftAzure\Storage\Common\Models\ServiceOptions;
use MicrosoftAzure\Storage\Common\Models\ServiceProperties;
use MicrosoftAzure\Storage\Queue\Internal\IQueue;
use MicrosoftAzure\Storage\Queue\Models as QueueModels;
use RuntimeException;

/**
 * A queue proxy that stores the queue items in an in-memory array. This is primarily useful for testing purposes.
 */
class ArrayQueueProxy implements IQueue
{

    public function __construct(protected ArrayQueueManager $queues)
    {
    }

    public function getServiceProperties(ServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::getServiceProperties() not implemented');
    }

    public function getServicePropertiesAsync(ServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::getServicePropertiesAsync() not implemented');
    }

    public function setServiceProperties(ServiceProperties $serviceProperties, ServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::setServiceProperties() not implemented');
    }

    public function setServicePropertiesAsync(ServiceProperties $serviceProperties, ServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::setServicePropertiesAsync() not implemented');
    }

    public function getServiceStats(ServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::getServiceStats() not implemented');
    }

    public function getServiceStatsAsync(ServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::getServiceStatsAsync() not implemented');
    }

    public function createQueue($queueName, QueueModels\CreateQueueOptions $options = null): void
    {
        $this->queues->ensureQueueExists($queueName);
    }

    public function createQueueAsync($queueName, QueueModels\CreateQueueOptions $options = null): Promise
    {
        $this->createQueue($queueName, $options);
        return new Promise();
    }

    public function deleteQueue($queueName, QueueModels\QueueServiceOptions $options): void
    {
        $this->queues->unset($queueName);
    }

    public function deleteQueueAsync($queueName, QueueModels\QueueServiceOptions $options = null): Promise
    {
        $this->deleteQueue($queueName, $options);
        return new Promise();
    }

    public function listQueues(QueueModels\ListQueuesOptions $options = null): ArrayListQueuesResult
    {
        return new ArrayListQueuesResult($this->queues->getQueues());
    }

    public function listQueuesAsync(QueueModels\ListQueuesOptions $options = null)
    {
        throw new RuntimeException(static::class.'::listQueuesAsync() not implemented');
    }

    public function getQueueMetadata($queueName, QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::getQueueMetadata() not implemented');
    }

    public function getQueueMetadataAsync($queueName, QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::getQueueMetadataAsync() not implemented');
    }

    public function setQueueMetadata($queueName,
                                     array $metadata = null,
                                     QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::setQueueMetadata() not implemented');
    }

    public function setQueueMetadataAsync($queueName,
                                          array $metadata = null,
                                          QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::setQueueMetadataAsync() not implemented');
    }

    public function createMessage($queueName,
                                  $messageText,
                                  QueueModels\CreateMessageOptions $options = null): QueueModels\CreateMessageResult
    {
        return new ArrayCreateMessageResult($this->queues->get($queueName)->queue($messageText));
    }

    public function createMessageAsync($queueName,
                                       $messageText,
                                       QueueModels\CreateMessageOptions $options = null)
    {
        throw new RuntimeException(static::class.'::createMessageAsync() not implemented');
    }

    public function updateMessage($queueName,
                                  $messageId,
                                  $popReceipt,
                                  $messageText,
                                  $visibilityTimeoutInSeconds,
                                  QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::updateMessage() not implemented');
    }

    public function updateMessageAsync($queueName,
                                       $messageId,
                                       $popReceipt,
                                       $messageText,
                                       $visibilityTimeoutInSeconds,
                                       QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::updateMessageAsync() not implemented');
    }

    public function deleteMessage($queueName, $messageId, $popReceipt, QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::deleteMessage() not implemented');
    }

    public function deleteMessageAsync($queueName, $messageId, $popReceipt, QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::deleteMessageAsync() not implemented');
    }

    public function listMessages($queueName, QueueModels\ListMessagesOptions $options = null): ArrayListMessagesResult
    {
        $messages = $this->queues->get($queueName)->getMessages();

        return new ArrayListMessagesResult($messages);
    }

    public function listMessagesAsync($queueName, QueueModels\ListMessagesOptions $options = null)
    {
        throw new RuntimeException(static::class.'::listMessagesAsync() not implemented');
    }

    public function peekMessages($queueName, QueueModels\PeekMessagesOptions $options = null)
    {
        throw new RuntimeException(static::class.'::peekMessages() not implemented');
    }

    public function peekMessagesAsync($queueName, QueueModels\PeekMessagesOptions $options = null)
    {
        throw new RuntimeException(static::class.'::peekMessagesAsync() not implemented');
    }

    public function clearMessages($queueName, QueueModels\QueueServiceOptions $options = null): void
    {
        $this->queues->get($queueName)->clear();
    }

    public function clearMessagesAsync($queueName, QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::clearMessagesAsync() not implemented');
    }

    public function getQueueAcl($queue,
                                QueueModels\QueueServiceOptions $options = null): QueueModels\QueueACL
    {
        return $this->queues->get($queue)->getAcl();
    }

    public function getQueueAclAsync($queue,
                                     QueueModels\QueueServiceOptions $options = null)
    {
        throw new RuntimeException(static::class.'::clearQueueAclAsync() not implemented');
    }

    public function setQueueAcl($queue,
                                QueueModels\QueueACL $acl,
                                QueueModels\QueueServiceOptions $options = null): void
    {
        $this->queues->get($queue)->setAcl($acl);
    }

    public function setQueueAclAsync($queue,
                                     QueueModels\QueueACL $acl,
                                     QueueModels\QueueServiceOptions $options = null): Promise
    {
        $this->setQueueAcl($queue, $acl);
        return new Promise();
    }
}
