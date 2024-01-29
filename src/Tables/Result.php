<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use ArrayAccess;
use Countable;
use Generator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use OutOfBoundsException;
use RuntimeException;
use Shrd\Laravel\Azure\Storage\Exceptions\AzureStorageServiceException;

/**
 * @template T of Entity
 *
 * @implements ArrayAccess<int, T>
 */
class Result implements IteratorAggregate, ArrayAccess, Countable, Arrayable, JsonSerializable, Jsonable
{
    /**
     * @param array<Result\Page<T>> $pages
     */
    public function __construct(protected array $pages = []) {}

    public function loadedPages(): array
    {
        return $this->pages;
    }

    public function loadedPageCount(): int
    {
        return count($this->pages);
    }

    public function firstLoadedPage(): ?Result\Page
    {
        return $this->pages[0] ?? null;
    }

    public function lastLoadedPage(): ?Result\Page
    {
        $count = $this->loadedPageCount();
        if($count <= 0) return null;
        return $this->pages[$count - 1];
    }

    public function hasNextPage(): bool
    {
        return $this->lastLoadedPage()?->hasNextPage();
    }

    public function append(Result\Page|Result $pageOrResult): static
    {
        if($pageOrResult instanceof Result\Page) {
            return $this->appendPage($pageOrResult);
        } else {
            return $this->appendResult($pageOrResult);
        }
    }

    public function appendPage(Result\Page $page): static
    {
        $this->pages[] = $page;
        return $this;
    }

    public function appendResult(Result $result): static
    {
        foreach ($result->loadedPages() as $page) $this->appendPage($page);
        return $this;
    }

    /**
     * @return Generator<Result\Page<T>>
     */
    public function pages(): Generator
    {
        $page = null;
        foreach ($this->loadedPages() as $page) {
            yield $page;
        }

        while (null !== $page = $page?->nextPage()) {
            $this->appendPage($page);
            yield $page;
        }
    }

    /**
     * @return Generator<Result\Page<T>>
     * @throws AzureStorageServiceException
     */
    public function loadPages(): Generator
    {
        $page = $this->lastLoadedPage();
        while (null !== $page = $page?->nextPage()) {
            $this->appendPage($page);
            yield $page;
        }
    }

    /**
     * @return Generator<T>
     */
    public function loaded(): Generator
    {
        foreach ($this->loadedPages() as $page) {
            yield from $page;
        }
    }

    public function loadedCount(): int
    {
        $result = 0;
        foreach ($this->loadedPages() as $page) {
            $result += $page->count();
        }
        return $result;
    }

    public function items(): Generator
    {
        foreach ($this->pages() as $page) {
            yield from $page;
        }
    }

    public function getIterator(): Generator
    {
        return $this->items();
    }

    public function count(): int
    {
        $result = 0;
        foreach ($this->pages() as $page) {
            $result += $page->count();
        }
        return $result;
    }

    /**
     * @param int $offset
     * @return array{int, int}|null
     */
    public function getOffsetLocation(int $offset): ?array
    {
        if($offset < 0) return null;
        $i = 0;
        foreach ($this->pages() as $page) {
            $count = $page->count();
            if($offset < $count) {
                return [$i, $offset];
            }
            $offset -= $count;
            $i++;
        }
        return null;
    }

    public function offsetExists(mixed $offset): bool
    {
        if(!is_int($offset)) return false;
        return $this->getOffsetLocation($offset) !== null;
    }

    /**
     * @param mixed $offset
     * @return Entity<T>
     */
    public function offsetGet(mixed $offset): Entity
    {
        $res = $this->getOffsetLocation($offset);
        if($res === null) throw new OutOfBoundsException();
        [$pageIndex, $itemIndex] = $res;
        return $this->pages[$pageIndex][$itemIndex];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $res = $this->getOffsetLocation($offset);
        if($res === null) throw new OutOfBoundsException();
        [$pageIndex, $itemIndex] = $res;
        $this->pages[$pageIndex][$itemIndex] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException( static::class."::offsetUnset() not implemented");
    }

    /**
     * @return array<T>
     */
    public function all(): array
    {
        return iterator_to_array($this->pages());
    }

    /**
     * @return array<T>
     */
    public function toArray(): array
    {
        return $this->all();
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
