<?php

namespace Shrd\Laravel\Azure\Storage\Tables;

use InvalidArgumentException;
use MicrosoftAzure\Storage\Table\Models\EdmType as Edm;
use MicrosoftAzure\Storage\Table\Models\Property;

/**
 * @method static Property DATETIME($value)
 * @method static Property BINARY($value)
 * @method static Property BOOLEAN($value)
 * @method static Property DOUBLE($value)
 * @method static Property GUID($value)
 * @method static Property INT32($value)
 * @method static Property INT64($value)
 * @method static Property STRING($value)
 */
enum EdmType: string
{
    case DATETIME = 'Edm.DateTime';
    case BINARY   = 'Edm.Binary';
    case BOOLEAN  = 'Edm.Boolean';
    case DOUBLE   = 'Edm.Double';
    case GUID     = 'Edm.Guid';
    case INT32    = 'Edm.Int32';
    case INT64    = 'Edm.Int64';
    case STRING   = 'Edm.String';

    public function __invoke($value): Property
    {
        $property = new Property();
        $property->setValue($value);
        $property->setRawValue('');
        $property->setEdmType($this->value);
        return $property;
    }

    public static function __callStatic(string $name, array $arguments): Property
    {
        return self::from($name)(...$arguments);
    }

    public function typeRequired(): bool
    {
        return Edm::typeRequired($this->value);
    }

    public static function fromValue($value): self
    {
        return self::from(Edm::propertyType($value));
    }

    public static function fromName(string $name): self
    {
        return constant(self::class.'::'.$name);
    }

    public static function coerce(mixed $value): ?self
    {
        if($value instanceof self) return $value;
        if(!$value) return null;

        if(is_string($value)) {
            $result = self::tryFrom($value);
            if($result !== null) return $result;
            return self::fromName($value);
        }

        throw new InvalidArgumentException("Could not convert ".get_debug_type($value)." to ".self::class);
    }
}
