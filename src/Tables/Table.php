<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;

class Table
{
    public function __construct(protected TableConnection $connection, protected string $name)
    {
    }

    public function getConnection(): TableConnection
    {
        return $this->connection;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function exists(): bool
    {
        return $this->connection->tableExists($this->name);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function ensureExists(): static
    {
        $this->connection->ensureTableExists($this->name);
        return $this;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function delete(): void
    {
        $this->connection->deleteTable($this->name);
    }
}
