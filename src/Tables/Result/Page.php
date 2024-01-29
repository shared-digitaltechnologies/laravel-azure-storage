<?php

namespace Shrd\Laravel\Azure\Storage\Tables\Result;


use MicrosoftAzure\Storage\Table\Models\QueryEntitiesResult;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;
use Shrd\Laravel\Azure\Storage\Tables\Builder;
use Shrd\Laravel\Azure\Storage\Tables\Collection;
use Shrd\Laravel\Azure\Storage\Tables\Concerns\HasCursor;
use Shrd\Laravel\Azure\Storage\Tables\Cursor;
use Shrd\Laravel\Azure\Storage\Tables\Entity;

/**
 * @template T of Entity
 * @extends Collection<T>
 */
class Page extends Collection
{
    use HasCursor;

    public function __construct(protected Builder   $builder,
                                QueryEntitiesResult $result
    ) {
        parent::__construct($result->getEntities(), $this->builder->getEntityClass());
        $this->setContinuationToken($result->getContinuationToken());
    }

    public function edges(): \Illuminate\Support\Collection
    {
        return $this->map(fn(Entity $item) => [
            "cursor" => $item->getCursor($this->getNextTableName(), $this->getLocation()),
            "node" => $item,
        ]);
    }

    public function pageInfo(): array
    {
        return [
            "hasNextPage" => $this->hasNextPage(),
            "hasPreviousPage" => $this->hasPreviousPage(),
            "startCursor" => $this->prevCursor(),
            "endCursor" => $this->nextCursor(),
            "count" => $this->count(),
        ];
    }

    public function prevQuery(): Builder
    {
        return $this->builder;
    }

    public function prevCursor(): ?Cursor
    {
        return $this->builder->getCursor();
    }

    public function nextCursor(): ?Cursor
    {
        return $this->getCursor();
    }

    public function nextQuery(): ?Builder
    {
        if($this->nextCursor() === null) return null;

        $builder = clone $this->builder;
        $builder->setContinuationToken($this->nextCursor());
        return $builder;
    }

    /**
     * @throws AzureStorageServiceException
     */
    public function nextPage(): ?self
    {
        return $this->nextQuery()?->getPage();
    }

    public function hasPreviousPage(): bool
    {
        return $this->prevCursor() !== null;
    }

    public function hasNextPage(): bool
    {
        return $this->nextCursor() !== null;
    }
}
