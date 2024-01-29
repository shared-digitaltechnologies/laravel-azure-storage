<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Model;

use Illuminate\Contracts\Container\BindingResolutionException;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\Builder;
use Shrd\Laravel\Azure\Storage\Tables\TableConnection;

/**
 * @method static where(string|array|callable $propertyOrValues, ?string $operand = null, $value = null, bool $boolean = true, bool $or = false): Builder<static>
 * @method static notWhere(string|array|callable $propertyOrValues, ?string $operand = null, $value = null, bool $or = false): Builder<static>
 * @method static whereRaw(string $filter, bool $boolean = true, bool $or = false): Builder<static>
 * @method static get(string|array ...$fields): Page<static>
 * @method static select(string|array ...$fields): Builder<static>
 * @method static first(string|array ...$fields): Builder<static>
 */
trait HasTableConnection
{
    protected ?string $connection = null;

    private TableConnection $_connection;

    /**
     * @throws BindingResolutionException
     */
    protected function initConnection(): void
    {
        $this->_connection = app()->make(TableConnection::class, [
            'connection' => $this->connection
        ]);
    }

    /**
     * @throws BindingResolutionException
     */
    public function getConnection(): TableConnection
    {
        if(!isset($this->_connection)) {
            $this->initConnection();
        }
        return $this->_connection;
    }

    public static function createTable(): string
    {
        $instance = static::make();
        $instance->getConnection()->createTable($instance->getTable());
        return $instance->getTable();
    }

    public static function ensureTableExists(): string
    {
        $instance = static::make();
        $instance->getConnection()->ensureTableExists($instance->getTable());
        return $instance->getTable();
    }

    /**
     * @throws AzureStorageServiceException
     */
    public static function create($attributes): static
    {
        /** @var static $result */
        $result = static::make($attributes);
        $result->insert();
        return $result;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public static function createOrMerge($attributes): static
    {
        /** @var static $result */
        $result = static::make($attributes);
        $result->save();
        return $result;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public static function createOrReplace($attributes): static
    {
        /** @var static $result */
        $result = static::make($attributes);
        $result->upsert();
        return $result;
    }

    protected function beforeInsert(): void
    {
    }

    protected function beforeMerge(): void
    {
    }

    protected function beforeUpdate(): void
    {
    }

    protected function beforeDelete(): void
    {
    }

    protected function afterDelete(): void
    {
    }

    protected function afterUpdate(): void
    {
    }

    protected function afterMerge(): void
    {
    }

    protected function afterInsert(): void
    {
    }

    /**
     * @throws AzureStorageServiceException
     * @throws BindingResolutionException
     */
    public function insert(): void
    {
        $this->beforeInsert();
        $this->getConnection()->insertEntity($this->getTable(), $this);
        $this->afterInsert();
    }

    /**
     * @throws AzureStorageServiceException
     * @throws BindingResolutionException
     */
    public function save(): void
    {
        $this->beforeInsert();
        $this->beforeMerge();
        $this->getConnection()->saveEntity($this->getTable(), $this);
        $this->afterMerge();
        $this->afterInsert();
    }

    /**
     * @throws BindingResolutionException
     * @throws AzureStorageServiceException
     */
    public function merge(): void
    {
        $this->beforeMerge();
        $this->getConnection()->mergeEntity($this->getTable(), $this);
        $this->afterMerge();
    }

    /**
     * @throws BindingResolutionException
     * @throws AzureStorageServiceException
     */
    public function upsert(): void
    {
        $this->beforeInsert();
        $this->beforeUpdate();
        $this->getConnection()->upsertEntity($this->getTable(), $this);
        $this->afterUpdate();
        $this->afterInsert();
    }

    /**
     * @throws BindingResolutionException
     * @throws AzureStorageServiceException
     */
    public function update(): void
    {
        $this->beforeUpdate();
        $this->getConnection()->updateEntity($this->getTable(), $this);
        $this->afterUpdate();
    }

    /**
     * @throws BindingResolutionException
     * @throws AzureStorageServiceException
     */
    public function delete(): void
    {
        $this->beforeDelete();
        $this->getConnection()->deleteEntity($this->getTable(), $this);
        $this->afterDelete();
    }

    public static function findOrFail(string $keyOrPartitionKey, ?string $rowKey = null): static
    {
        if($rowKey === null) {
            $parts = explode('/', $keyOrPartitionKey);
            $partitionKey = $parts[count($parts) - 2];
            $rowKey = $parts[count($parts) - 1];
        } else {
            $partitionKey = $keyOrPartitionKey;
        }
        $instance = static::make();
        $entity = $instance->getConnection()->getEntity($instance->getTable(), $partitionKey, $rowKey);
        return new static($entity);
    }

    public static function find(string $keyOrPartitionKey, ?string $rowKey = null): ?static
    {
        try {
            return static::findOrFail($keyOrPartitionKey, $rowKey);
        } catch (ServiceException $exception) {
            if($exception->getCode() === 404) {
                return null;
            }
            throw $exception;
        }
    }

    /**
     * @throws BindingResolutionException
     */
    public function newQuery(): Builder
    {
        return $this->getConnection()->query($this->getTable(), static::class);
    }

    public static function query(): Builder
    {
        return static::make()->newQuery();
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        $instance = static::make();
        $query = $instance->newQuery();
        return call_user_func_array([$query, $name], $arguments);
    }
}
