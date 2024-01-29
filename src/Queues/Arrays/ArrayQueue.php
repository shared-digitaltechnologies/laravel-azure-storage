<?php

namespace Shrd\Laravel\Azure\Storage\Queues\Arrays;

use MicrosoftAzure\Storage\Queue\Models\Queue;
use MicrosoftAzure\Storage\Queue\Models\QueueACL;
use MicrosoftAzure\Storage\Queue\Models\QueueMessage;
use Ramsey\Uuid\Uuid;

class ArrayQueue extends Queue
{

    protected array $messages = [];

    public function __construct(string $name, protected QueueACL $acl) {
        parent::__construct($name, "internal://$name");
    }

    public function queue(string $messageText): QueueMessage
    {
        $message =  new QueueMessage();
        $message->setMessageId(Uuid::uuid4());
        $message->setMessageText($messageText);
        $this->messages[] = $message;
        return $message;
    }

    public function dequeue(): QueueMessage
    {
        return array_shift($this->messages);
    }

    public function getAcl(): QueueACL
    {
        return $this->acl;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function clear(): array
    {
        $messages = $this->messages;
        $this->messages = [];
        return $messages;
    }

    public function setAcl(QueueACL $acl): static
    {
        $this->acl = $acl;
        return $this;
    }
}
