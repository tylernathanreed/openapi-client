<?php

namespace Reedware\OpenApi\Client\Http;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * @phpstan-type TNonArray Dto|DateTimeInterface|scalar|null
 */
abstract class Dto implements JsonSerializable
{
    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $properties = [];

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $properties[$property->getName()] = $property->getValue($this);
        }

        return static::arrayify($properties);
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @phpstan-template TKey of int|string
     * @phpstan-template TValue of mixed
     *
     * @param TNonArray|array<TKey,TValue> $value
     * @return ($value is Dto ? array<string,mixed> : ($value is array ? array<TKey,TValue> : TNonArray))
     */
    protected static function arrayify($value)
    {
        if ($value instanceof Dto) {
            return static::arrayify($value->toArray());
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->format('c');
        }

        if (is_array($value)) {
            return array_map(function ($v) {
                assert(
                    $v instanceof Dto ||
                    $v instanceof DateTimeInterface ||
                    is_array($v) ||
                    is_scalar($v) ||
                    is_null($v)
                );

                return static::arrayify($v);
            }, $value);
        }

        return $value;
    }
}
