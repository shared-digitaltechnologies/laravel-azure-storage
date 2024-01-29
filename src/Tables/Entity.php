<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use JsonSerializable;
use MicrosoftAzure\Storage\Table\Models\Entity as BaseEntity;
use MicrosoftAzure\Storage\Table\Models\Property;
use OutOfBoundsException;

/**
 * @property string $partition_key
 * @property string $row_key
 * @property string $etag
 * @property Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read string $key
 */
class Entity extends BaseEntity implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{

    public function __construct(BaseEntity|iterable|Arrayable|null $entityOrProperties = null, string $etag = '')
    {
        if($entityOrProperties instanceof BaseEntity) {
            $this->setETag($entityOrProperties->getETag());
            $this->setProperties($entityOrProperties->getProperties());
        } else {
            $this->setETag($etag);
            $this->setAttributes($entityOrProperties ?? []);
        }
    }

    public final function fill(iterable|Arrayable $attributes): static
    {
        return $this->setAttributes($attributes);
    }

    public function load(BaseEntity $other): static
    {
        $this->setProperties(array_merge($this->getProperties(), $other->getProperties()));
        return $this;
    }

    public static function make(iterable|Arrayable $attributes = [], string $etag = ''): static
    {
        $res = new static(null, $etag);
        $res->setAttributes($attributes);
        return $res;
    }

    public static function from(BaseEntity|iterable|Arrayable|null $entityOrProperties = null, string $etag = ''): static
    {
        return new static($entityOrProperties, $etag);
    }

    public static function coerce(BaseEntity|EntityInterface|iterable|Arrayable|null $value = null): ?static
    {
        if(!$value) return null;
        if($value instanceof EntityInterface) $value = $value->getEntity();
        if($value instanceof self) return $value;
        return static::from($value);
    }

    public function getTimestamp(): ?Carbon
    {
        return Carbon::make(parent::getTimestamp());
    }

    public function getCursor(?string $nextTableName = null, ?string $location = null): Cursor
    {
        return new Cursor(
            nextTableName: $nextTableName ?? '',
            nextPartitionKey: base64_encode($this->getPartitionKey()),
            nextRowKey: base64_encode($this->getRowKey()),
            location: $location ?? 'PrimaryOnly',
        );
    }

    public function getKey(): string
    {
        $partitionKey = $this->getPartitionKey();
        $rowKey = $this->getRowKey();
        return "$partitionKey/$rowKey";
    }

    public function hasProperty(string $propertyName): string
    {
        return array_key_exists($propertyName, $this->getProperties());
    }

    public static function getStandardName(string $name): string
    {
        return Str::studly($name);
    }

    public function getProperties(): array
    {
        return parent::getProperties() ?? [];
    }

    public function getPropertyName(string $name): string
    {
        if($this->hasProperty($name)) return $name;
        return static::getStandardName($name);
    }

    public function getPropertyRawValue(string $name): ?string
    {
        return $this->getProperty($name)?->getRawValue();
    }

    public function getPropertyNames(): array
    {
        return array_keys($this->getProperties());
    }

    public function deleteProperty(string $name): static
    {
        $properties = $this->getProperties();
        unset($properties[$name]);
        $this->setProperties($properties);
        return $this;
    }

    public function addProperty($name, $edmType, $value, $rawValue = ''): static
    {
        if($edmType instanceof EdmType) $edmType = $edmType->value;
        parent::addProperty($name, $edmType, $value, $rawValue);
        return $this;
    }

    public function getPropertyType(string $name): ?EdmType
    {
        $property = $this->getProperty($name);
        if($property === null) return null;
        return EdmType::from($property->getEdmType());
    }

    public function getAttributeType(string $name): ?EdmType
    {
        return $this->getPropertyType($this->getPropertyName($name));
    }

    public function hasAttribute(string $name): bool
    {
        if($this->hasProperty($name)) return true;
        return array_key_exists(static::getStandardName($name), $this->getProperties());
    }

    public function addAttribute(string $name, $value, ?EdmType $type = null, $rawValue = ''): static
    {
        $type ??= EdmType::fromValue($value);
        return $this->addProperty($this->getPropertyName($name), $type->value, $value, $rawValue);
    }

    public function setAttribute(string $name, $value, ?EdmType $type = null, $rawValue = ''): static
    {
        $name = $this->getPropertyName($name);

        if($value instanceof Property) {
            $this->setProperty($name, $value);
            return $this;
        }

        if($this->hasProperty($name)) {
            $this->setPropertyValue($name, $value);
        } else {
            $this->addAttribute($name, $value, $type, $rawValue);
        }
        return $this;
    }

    public function offsetExists($offset): bool
    {
        if(!is_string($offset)) return false;
        return true;
    }

    const ETAG_NAMES = ['etag','ETag','eTag','e_tag'];

    public function getAttribute(string $name): mixed
    {
        if(in_array($name, self::ETAG_NAMES)) {
            return $this->getETag();
        }

        $name = $this->getPropertyName($name);

        if($name === 'Key') return $this->getKey();
        if($name === 'UpdatedAt') return $this->getTimestamp();

        $value = $this->getPropertyValue($name);
        if($value === null) {
            if($name === 'CreatedAt') return $this->getTimestamp();
            return null;
        }

        return match ($this->getPropertyType($name)) {
            EdmType::DATETIME => Carbon::make($value),
            default => $value,
        };
    }

    public function getAttributes(): array
    {
        return collect($this->getPropertyNames())
            ->mapWithKeys(fn($prop) => [$prop => $this->getAttribute($prop)])
            ->all();
    }

    public function setAttributes(iterable|Arrayable $attributes): static
    {
        if($attributes instanceof Arrayable) $attributes = $attributes->toArray();
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }

    public function deleteAttribute(string $name): static
    {
        return $this->deleteProperty($this->getPropertyValue($name));
    }

    public function __get(string $name)
    {
        return $this->getAttribute($name);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if(!is_string($offset)) throw new OutOfBoundsException("Offset must be a string.");
        return $this->getAttribute($offset);
    }

    public function __set(string $name, $value): void
    {
        $this->setAttribute($name, $value);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if(!is_string($offset)) throw new OutOfBoundsException("Offset must be a string.");
        $this->setAttribute($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if(!is_string($offset)) throw new OutOfBoundsException("Offset must be a string.");
        $this->deleteAttribute($offset);
    }

    public function __debugInfo(): ?array
    {
        return [
            "attributes" => $this->getAttributes(),
            "etag" => $this->getETag(),
        ];
    }

    public function __unset(string $name): void
    {
        $this->deleteAttribute($name);
    }

    public function toArray(): array
    {
        return $this->getAttributes();
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->getAttributes(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->getAttributes();
    }
}
