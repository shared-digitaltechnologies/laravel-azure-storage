<?php

namespace Shrd\Laravel\Azure\Storage\Tables;


use Shrd\Laravel\Azure\Storage\Tables\Concerns\MakesEntities;
use Illuminate\Support\Collection as BaseCollection;

/**
 *
 * @template TKey of array-key
 * @template TValue of Entity
 *
 * @extends BaseCollection<TKey, TValue>
 */
class Collection extends BaseCollection
{
    use MakesEntities;

    /**
     * @param array $items
     * @param class-string<TValue> $entityClass
     */
    public function __construct($items = [], protected string $entityClass = Entity::class)
    {
        parent::__construct(
            array_map(fn($i) => $this->makeEntity($i), $this->getArrayableItems($items))
        );
    }

    public static function from($items = []): static
    {
        return new static($items);
    }

    public function map(callable $callback): BaseCollection
    {
        return $this->toBase()->map($callback);
    }

    public function mapWithKeys(callable $callback): BaseCollection
    {
        return $this->toBase()->mapWithKeys($callback);
    }

    public function pluck($value, $key = null): BaseCollection
    {
        return $this->toBase()->pluck($value, $key);
    }

    public function keys(): BaseCollection
    {
        return $this->toBase()->keys();
    }

    public function zip(...$items): BaseCollection
    {
        return $this->toBase()->zip(...$items);
    }

    public function collapse(): BaseCollection
    {
        return $this->toBase()->collapse();
    }

    public function flatten($depth = INF): BaseCollection
    {
        return $this->toBase()->flatten($depth);
    }

    public function flip(): BaseCollection
    {
        return $this->toBase()->flip();
    }

    public function pad($size, $value): BaseCollection
    {
        return $this->toBase()->pad($size, $value);
    }

    /**
     * @param $groupBy
     * @param bool $preserveKeys
     * @return BaseCollection<TKey, static<int, TValue>>
     */
    public function groupBy($groupBy, $preserveKeys = false): BaseCollection
    {
        return $this->toBase()
            ->groupBy($groupBy, $preserveKeys)
            ->map(fn(Collection $group) => new static($group->all()));
    }

        /**
     * @return class-string<TValue>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function insert(): static
    {
        return $this->each(function ($entity) {
            $entity->insert();
        });
    }

    public function save(): static
    {
        return $this->each(function ($entity) {
            $entity->save();
        });
    }

    public function upsert(): static
    {
        return $this->each(function ($entity) {
            $entity->upsert();
        });
    }

    public function mergeOperation(): static
    {
        return $this->each(function ($entity) {
            $entity->merge();
        });
    }

    public function delete(): static
    {
        return $this->each(function ($entity) {
            $entity->delete();
        });
    }
}
