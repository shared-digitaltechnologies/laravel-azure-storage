<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use MicrosoftAzure\Storage\Table\Models\TableContinuationToken;
use RuntimeException;
use Safe\Exceptions\UrlException;
use UnexpectedValueException;

final class Cursor extends TableContinuationToken implements JsonSerializable, Jsonable
{

    public function toString(): string
    {
        $table = $this->getNextTableName() ?? '';
        $partitionKey = $this->getNextPartitionKey() ?? '';
        $rowKey = $this->getNextRowKey() ?? '';
        $location = $this->getLocation() ?? '';
        $str = "$table/$partitionKey/$rowKey/$location";
        return base64_encode($str);
    }

    /**
     * @throws UrlException
     */
    public static function encode($continuationToken): string
    {
        return self::from($continuationToken)->toString();
    }

    /**
     * @throws UrlException
     */
    public static function decode(string $cursor): self
    {
        $str = \Safe\base64_decode($cursor);
        $parts = explode('/', $str);
        if(count($parts) !== 4) {
            throw new UnexpectedValueException("Invalid cursor");
        }
        [$table, $partitionKey, $rowKey, $location] = $parts;

        return new self(
            nextTableName: $table,
            nextPartitionKey: $partitionKey,
            nextRowKey: $rowKey,
            location: $location,
        );
    }

    public static function fromContinuationToken(TableContinuationToken $continuationToken): self
    {
        return new self(
            nextTableName: $continuationToken->getNextTableName(),
            nextPartitionKey: $continuationToken->getNextPartitionKey(),
            nextRowKey: $continuationToken->getNextRowKey(),
            location: $continuationToken->getLocation(),
        );
    }

    /**
     * @throws UrlException
     */
    public static function coerce($value): ?self
    {
        if(!$value) return null;
        return self::from($value);
    }

    /**
     * @throws UrlException
     */
    public static function from($value): self
    {
        if(!$value) return new self();

        if(is_string($value)) return self::decode($value);

        if($value instanceof self) return $value;

        if(method_exists($value, 'getContinuationToken')) $value = $value->getContinuationToken();

        if($value instanceof TableContinuationToken) return self::fromContinuationToken($value);

        throw new RuntimeException("Could not convert ".get_debug_type($value)." to ".self::class);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function __debugInfo(): ?array
    {
        return [
            "nextTableName" => $this->getNextTableName(),
            "nextPartitionKey" => $this->getNextPartitionKey(),
            "nextRowKey" => $this->getNextRowKey(),
            "location" => $this->getLocation(),
        ];
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
