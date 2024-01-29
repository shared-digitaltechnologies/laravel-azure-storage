<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use MicrosoftAzure\Storage\Table\Models\BatchOperations;

class Batch extends BatchOperations
{
    public function __construct(protected TableConnection $connection, protected string $table)
    {
        parent::__construct();
    }

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function insert($entity, ?string $table = null): static
    {
        $this->addInsertEntity($table ?? $this->table, Entity::coerce($entity));
        return $this;
    }

    public function insertMany($entities, ?string $table = null): static
    {
        foreach ($entities as $entity) {
            $this->insert($entity, $table);
        }
        return $this;
    }

    public function update($entity, ?string $table = null): static
    {
        $this->addInsertEntity($table ?? $this->table, Entity::coerce($entity));
        return $this;
    }

    public function updateMany($entities, ?string $table = null): static
    {
        foreach ($entities as $entity) {
            $this->update($entity, $table);
        }
        return $this;
    }

    public function merge($entity, ?string $table = null): static
    {
        $this->addMergeEntity($table ?? $this->table, Entity::coerce($entity));
        return $this;
    }

    public function mergeMany($entities, ?string $table = null): static
    {
        foreach ($entities as $entity) {
            $this->merge($entity, $table);
        }
        return $this;
    }

    public function upsert($entity, ?string $table = null): static
    {
        $this->addInsertOrReplaceEntity($table ?? $this->table, Entity::coerce($entity));
        return $this;
    }

    public function upsertMany($entities, ?string $table = null): static
    {
        foreach ($entities as $entity) {
            $this->upsert($entity, $table);
        }
        return $this;
    }

    public function save($entity, ?string $table = null): static
    {
        $this->addInsertOrMergeEntity($table ?? $this->table, Entity::coerce($entity));
        return $this;
    }

    public function saveMany($entities, ?string $table = null): static
    {
        foreach ($entities as $entity) {
            $this->save($entity, $table);
        }
        return $this;
    }

    public function delete($entity, ?string $table = null): static
    {
        $entity = Entity::coerce($entity);
        $partitionKey = $entity->getPartitionKey();
        $rowKey = $entity->getRowKey();
        $this->addDeleteEntity($table ?? $this->table, $partitionKey, $rowKey);
        return $this;
    }

    public function deleteMany($entities, ?string $table = null): static
    {
        foreach ($entities as $entity) {
            $this->delete($entity, $table);
        }
        return $this;
    }

    public function append(self $other): static
    {
        $this->setOperations(array_merge($this->getOperations(), $other->getOperations()));
        return $this;
    }

    public function run(): Collection
    {
        return Collection::make(
            collect($this->connection->getProxy()->batch($this)->getEntries())
                ->map(fn($e) => Entity::coerce($e))
        );
    }
}
