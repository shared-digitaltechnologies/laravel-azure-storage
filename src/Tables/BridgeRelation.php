<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\Model as TableModel;
use MicrosoftAzure\Storage\Table\Models\QueryEntitiesResult;

/**
 * @template T of TableModel
 *
 * @extends Builder<T>
 */
class BridgeRelation extends Builder
{

    /**
     * @var callable|Closure
     */
    protected $localKey;

    /**
     * @param EloquentModel $parent
     * @param class-string<T> $child
     * @param string|callable $localKey
     * @throws BindingResolutionException
     */
    public function __construct(protected EloquentModel $parent,
                                string $child,
                                string|callable $localKey)
    {

        if(is_string($localKey)) {
            $this->localKey = function (EloquentModel $model) use ($localKey) {
                return $model->getAttribute($localKey);
            };
        } else {
            $this->localKey = $localKey;
        }

        $childInstance = $child::make();
        parent::__construct($childInstance->getConnection(), $childInstance->getTable(), $child);
    }

    public function getPartitionKey(?EloquentModel $model = null)
    {
        return call_user_func($this->localKey, $model ?? $this->parent);
    }

    public function addConstraints(): void
    {
        $this->where('PartitionKey', '=', $this->getPartitionKey($this->parent));
    }

    public function addEagerConstraints(array $models): void
    {
        foreach ($models as $model) {
            $this->orWhere('PartitionKey', '=', $this->getPartitionKey($model));
        }
    }

    public function getRaw(string|array ...$fields): QueryEntitiesResult
    {
        $this->addConstraints();
        return parent::getRaw($fields);
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function getResults(): Result
    {
        return $this->get();
    }

    /**
     * @param $entity
     * @return T&TableModel
     */
    public function make($entity = null): TableModel
    {
        $result = $this->initEntity($entity);
        $result->setPartitionKey($this->getPartitionKey());
        return $result;
    }

    /**
     * @param array $entities
     * @return Collection<array-key, Collection>
     */
    public function makeMany(array $entities = []): Collection
    {
        $result = [];
        foreach ($entities as $rowKey => $entityInput) {
            $entity = $this->make($entityInput);
            if(is_string($rowKey)) $entity->setRowKey($rowKey);
            $result[$rowKey] = $entity;
        }
        return Collection::make($result);
    }

    /**
     * @param $entity
     * @return T&TableModel
     * @throws AzureStorageServiceException
     * @throws BindingResolutionException
     */
    public function create($entity = null): TableModel
    {
        $result = $this->make($entity);
        $result->insert();
        return $result;
    }

    /**
     * @param array $entities
     * @return Collection<T>
     */
    public function createMany(array $entities = []): Collection
    {
        return $this->makeMany($entities)->insert();
    }

    /**
     * @param $entity
     * @return T&TableModel
     * @throws AzureStorageServiceException
     * @throws BindingResolutionException
     */
    public function createOrMerge($entity = null): TableModel
    {
        $result = $this->make($entity);
        $result->save();
        return $result;
    }

    /**
     * @param array $entities
     * @return Collection<T>
     */
    public function createOrMergeMany(array $entities = []): Collection
    {
        return $this->makeMany($entities)->save();
    }

    /**
     * @param $entity
     * @return T&TableModel
     * @throws AzureStorageServiceException
     * @throws BindingResolutionException
     */
    public function createOrReplace($entity = null): TableModel
    {
        $result = $this->make($entity);
        $result->upsert();
        return $result;
    }

    /**
     * @param array $entities
     * @return Collection<T>
     */
    public function createOrReplaceMany(array $entities = []): Collection
    {
        return $this->makeMany($entities)->upsert();
    }
}
