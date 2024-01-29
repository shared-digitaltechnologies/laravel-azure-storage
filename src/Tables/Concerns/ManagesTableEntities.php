<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Concerns;

use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesOptions;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesResult;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\Entity;

trait ManagesTableEntities
{
    /**
     * @throws AzureStorageServiceException
     */
    public function getEntity(string $table, string $partitionKey, string $rowKey): Entity
    {
        try {
            return Entity::from($this->proxy->getEntity($table, $partitionKey, $rowKey)->getEntity());
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function insertEntity(string $table, $entity): Entity
    {
        try {
            $entity = Entity::coerce($entity);
            return $entity->load(Entity::from($this->proxy->insertEntity($table, $entity)->getEntity()));
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) {
                $this->ensureTableExists($table);
                return $this->insertEntity($table, $entity);
            }
            throw $exception;
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function saveEntity(string $table, $entity): string
    {
        try {
            return $this->proxy->insertOrMergeEntity($table, Entity::coerce($entity))->getETag();
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) {
                $this->ensureTableExists($table);
                return $this->insertEntity($table, $entity)->getETag();
            }
            throw $exception;
        }

    }

    /**
     * @throws AzureStorageServiceException
     */
    public function upsertEntity(string $table, $entity): string
    {
        try {
            return $this->proxy->insertOrReplaceEntity($table, Entity::coerce($entity))->getETag();
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) {
                $this->ensureTableExists($table);
                return $this->insertEntity($table, $entity)->getETag();
            }
            throw $exception;
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function mergeEntity(string $table, $entity): string
    {
        try {
            return $this->proxy->mergeEntity($table, Entity::coerce($entity))->getETag();
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function updateEntity(string $table, $entity): string
    {
        try {
            return $this->proxy->updateEntity($table, Entity::coerce($entity))->getETag();
        } catch (ServiceException $exception) {
            throw new AzureStorageServiceException($exception);
        }
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function deleteEntity(string $table, $entityOrPartitionKey, $rowKey = null): void
    {
        if(is_string($rowKey) && is_string($entityOrPartitionKey)) {
            $partitionKey = $entityOrPartitionKey;
        } else {
            $entity = Entity::coerce($entityOrPartitionKey);
            $partitionKey = $entity->getPartitionKey();
            $rowKey = $entity->getRowKey();
        }

        try {
            $this->proxy->deleteEntity($table, $partitionKey, $rowKey);
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) {
                return;
            }
            throw $exception;
        }
    }

    private function createEmptyEntitiesResult(): QueryEntitiesResult
    {
        return QueryEntitiesResult::create([], []);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function queryEntities(string $table, QueryEntitiesOptions|null $options = null): QueryEntitiesResult
    {
        try {
            return $this->proxy->queryEntities($table, $options);
        } catch (ServiceException $exception) {
            $exception = new AzureStorageServiceException($exception);
            if($exception->hasErrorCode('TableNotFound')) {
                return $this->createEmptyEntitiesResult();
            }
            throw $exception;
        }
    }

}
