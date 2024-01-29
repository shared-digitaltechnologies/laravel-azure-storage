<?php

namespace Shrd\Laravel\Azure\Storage\Blobs\SharedAccessSignatures;

use InvalidArgumentException;

enum BlobStorageSignedPermission: string
{
    case READ = 'r';
    case ADD = 'a';
    case CREATE = 'c';
    case WRITE = 'w';
    case DELETE = 'd';
    case DELETE_VERSION = 'x';
    case PERMANENT_DELETE = 'y';
    case LIST = 'l';
    case TAGS = 't';
    case FIND = 'f';
    case MOVE = 'm';
    case EXECUTE = 'e';
    case OWNERSHIP = 'o';
    case PERMISSIONS = 'p';
    case SET_IMMUTABILITY_POLICY = 'i';

    public function getIndex(): int
    {
        return match ($this) {
            self::READ => 0,
            self::ADD => 1,
            self::CREATE => 2,
            self::WRITE => 3,
            self::DELETE => 4,
            self::DELETE_VERSION => 5,
            self::PERMANENT_DELETE => 7,
            self::LIST => 8,
            self::TAGS => 9,
            self::FIND => 10,
            self::MOVE => 11,
            self::EXECUTE => 12,
            self::OWNERSHIP => 13,
            self::PERMISSIONS => 14,
            self::SET_IMMUTABILITY_POLICY => 15
        };
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

    public static function normalize(iterable $permissions): array
    {
        $parsed = [];
        foreach ($permissions as $permission) {
            $parsed[] = self::coerce($permission);
        }

        $parsed = array_unique($parsed, SORT_REGULAR);
        usort($parsed, fn(self $lhs, self $rhs) => $lhs->getIndex() < $rhs->getIndex() ? -1 : 1);

        return $parsed;
    }

    public static function toSignedPermissionString(iterable $permissions): string
    {
        return implode(
            '',
            array_map(
                fn(self $permission) => $permission->value,
                self::normalize($permissions)
            )
        );
    }
}
