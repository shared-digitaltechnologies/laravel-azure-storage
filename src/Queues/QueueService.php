<?php

namespace Shrd\Laravel\Azure\Storage\Queues;

use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageOptions;
use MicrosoftAzure\Storage\Queue\Models\CreateMessageResult;
use MicrosoftAzure\Storage\Queue\Models\GetQueueMetadataResult;
use MicrosoftAzure\Storage\Queue\Models\ListMessagesOptions;
use MicrosoftAzure\Storage\Queue\Models\ListQueuesOptions;
use MicrosoftAzure\Storage\Queue\Models\Queue;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use MicrosoftAzure\Storage\Queue\QueueRestProxy;
use MicrosoftAzure\Storage\Queue\QueueSharedAccessSignatureHelper;
use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\Storage\Accounts\StorageAccount;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageException;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;

readonly class QueueService
{
    public function __construct(protected StorageAccount $storageAccount,
                                protected QueueRestProxy $proxy)
    {
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function queueExists(string $queueName): bool
    {
        try {
            $this->getQueueMetadataResult($queueName);
            return true;
        } catch (AzureStorageServiceException $exception) {
            if($exception->Code == 'QueueNotFound') return false;
            throw $exception;
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function deleteQueue(string $queueName): void
    {
        try {
            $this->proxy->deleteQueue($queueName);
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->Code == 'QueueNotFound') return;
            throw $exception;
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function ensureQueueExists(string $queueName): void
    {
        try {
            $this->proxy->createQueue($queueName);
        } catch (ServiceException $exception) {
            if($exception->getCode() === 409)  return;
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @return array<Queue>
     * @throws AzureStorageServiceException
     */
    public function getQueues(?string $prefix = null, bool $includeMetaData = false): array
    {
        $options = new ListQueuesOptions();

        if($prefix) $options->setPrefix($prefix);
        $options->setIncludeMetadata($includeMetaData);

        try {
            return $this->proxy->listQueues()->getQueues();
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getQueueMetadataResult(string $queueName): GetQueueMetadataResult
    {
        try {
            return $this->proxy->getQueueMetadata($queueName);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getApproximateMessageCount(string $queueName): int
    {
        try {
            return $this->getQueueMetadataResult($queueName)->getApproximateMessageCount();
        } catch (AzureStorageServiceException $exception) {
            if($exception->Code == 'QueueNotFound') {
                return 0;
            }
            throw $exception;
        }
    }

    /**
     * @param string $queueName
     * @param ListMessagesOptions|null $options
     * @return QueueMessage[]
     * @throws AzureStorageServiceException
     */
    public function listMessages(string $queueName, ListMessagesOptions|null $options): array
    {
        try {
            return $this->proxy->listMessages($queueName, $options)->getQueueMessages();
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->Code == 'QueueNotFound') {
                return [];
            }
            throw $exception;
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function deleteMessage(string $queueName, string $messageId, string $popReceipt): void
    {
        try {
            $this->proxy->deleteMessage($queueName, $messageId, $popReceipt);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function updateMessage(string $queueName,
                                  string $messageId,
                                  string $popReceipt,
                                  string $messageText,
                                  int    $visibilityTimeout): void
    {
        try {
            $encodeMessageText = base64_encode($messageText);
            $this->proxy->updateMessage($queueName, $messageId, $popReceipt, $encodeMessageText, $visibilityTimeout);
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function createMessage(string $queueName,
                                  string $messageText,
                                  CreateMessageOptions|array $options = []): CreateMessageResult
    {
        if($options instanceof CreateMessageOptions) {
            $msgOptions = $options;
        } else {
            $msgOptions = new CreateMessageOptions();
            if(isset($options['visibilityTimeout'])) $msgOptions->setVisibilityTimeoutInSeconds($options['visibilityTimeout']);
            if(isset($options['timeout'])) $msgOptions->setTimeout($options['timeout']);
            if(isset($options['ttl'])) $msgOptions->setTimeToLiveInSeconds($options['ttl']);
        }

        try {
            $encodedMessageText = base64_encode($messageText);
            return $this->proxy->createMessage($queueName, $encodedMessageText, $msgOptions);
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->Code == 'QueueNotFound') {
                $this->ensureQueueExists($queueName);
                return $this->createMessage($queueName, $messageText, $msgOptions);
            } else {
                throw $exception;
            }
        }
    }

    public function getStorageAccount(): StorageAccount
    {
        return $this->storageAccount;
    }

    public function sasHelper(): QueueSharedAccessSignatureHelper
    {
        return new QueueSharedAccessSignatureHelper(
            $this->storageAccount->getName(),
            $this->storageAccount->getKey()
        );
    }

    /**
     * @throws JsonException
     * @throws AzureStorageServiceException
     */
    public function createJsonMessage(string                          $queueName,
                                      Jsonable|JsonSerializable|array $message,
                                      CreateMessageOptions|array      $options = []): CreateMessageResult
    {
        $messageText = $message instanceof Jsonable
            ? $message->toJson()
            : \Safe\json_encode($message);

        return $this->createMessage($queueName, $messageText, $options);
    }

    public function proxy(): QueueRestProxy
    {
        return $this->proxy;
    }
}
