<?php

namespace Reedware\OpenApi\Client\Http;

use DateTimeImmutable;
use Reedware\OpenApi\Client\Http\Attributes\PolymorphicList;
use Reedware\OpenApi\Client\Http\Exceptions\DeserializationException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use TypeError;

class Deserializer
{
    /**
     * @phpstan-template TDto of Dto
     * @param ($array is true ? list<array<string,mixed>> : array<string,mixed>) $data
     * @param class-string<TDto> $class
     * @return ($array is true ? (TDto is PolymorphicDto ? list<Dto> : list<TDto>) : (TDto is PolymorphicDto ? Dto : TDto))
     */
    public function deserialize(array $data, string $class, bool $array = false)
    {
        if ($array) {
            $values = [];

            foreach ($data as $value) {
                $values[] = self::deserialize($value, $class);
            }

            return $values;
        }

        if (is_subclass_of($class, PolymorphicDto::class)) {
            $class = $class::discriminateFromData($data);
        }

        return $this->from($class, $data);
    }

    /**
     * @phpstan-template T of Dto
     * @param class-string<T> $class
     * @param array<string,mixed> $data
     * @return T
     */
    public function from(string $class, array $data): Dto
    {
        try {
            return $this->resolve($class, $data);
        } catch (TypeError $e) {
            throw new DeserializationException(sprintf(
                'Failed to deserialize [%s]: %s (Data: %s)',
                $class,
                substr($e->getMessage(), strlen($class . '::__construct(): ')),
                json_encode($data),
            ), previous: $e);
        }
    }

    /**
     * @phpstan-template T of Dto
     * @param class-string<T> $class
     * @param array<string,mixed> $data
     * @return T
     */
    protected function resolve(string $class, array $data): Dto
    {
        $reflector = new ReflectionClass($class);

        $parameters = $reflector->getConstructor()?->getParameters() ?? [];

        $args = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $key = str_starts_with($name, '_')
                ? substr($name, 1)
                : $name;

            $property = $reflector->getProperty($name);

            $value = array_key_exists($key, $data)
                ? $data[$key]
                : (
                    $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : null
                );

            $type = $parameter->getType();

            if (is_null($value) && $type instanceof ReflectionNamedType && ! $type->allowsNull()) {
                if ($type->getName() === 'array') {
                    $value = [];
                }
            }

            if (is_null($value)) {
                $args[] = $value;
            } elseif (is_null($type)) {
                $args[] = $value;
            } elseif (! $type instanceof ReflectionNamedType) {
                $args[] = $value;
            } elseif (is_array($value) && empty($value) && $type->allowsNull()) {
                $args[] = null;
            } elseif ($type->getName() === 'array' && is_array($value)) {
                $args[] = $this->fromArray($class, $property, $value);
            } elseif ($type->getName() === DateTimeImmutable::class && is_string($value)) {
                $args[] = new DateTimeImmutable($value);
            } elseif ($type->getName() === DateTimeImmutable::class && is_int($value)) {
                $args[] = (new DateTimeImmutable())->setTimestamp($value);
            } elseif (! $type->isBuiltin() && is_subclass_of($type->getName(), Dto::class) && is_array($value)) {
                // @phpstan-ignore argument.type
                $args[] = $this->from($type->getName(), $value);
            } else {
                $args[] = $value;
            }
        }

        return $reflector->newInstanceArgs($args);
    }

    /**
     * @param class-string<Dto> $class
     * @param array<mixed,mixed> $array
     * @return array<mixed,mixed>
     */
    protected function fromArray(string $class, ReflectionProperty $property, array $array): array
    {
        $doc = $property->getDocComment();

        if (! $doc) {
            return $array;
        }

        $var = preg_match('/@var \??([^ ]+)(?:\n| \*)/', $doc, $matches)
            ? $matches[1]
            : null;

        if (! $var) {
            return $array;
        }

        if (str_starts_with($var, 'list')) {
            $type = substr($var, strlen('list<'), -strlen('>'));
        } else {
            if (! preg_match('/^array<(?<key>[^>]+), ?(?<type>.+)>$/', $var, $matches)) {
                return $array;
            }

            $type = $matches['type'];
        }

        if ($type === 'mixed' || str_starts_with($type, 'list')) {
            return $array;
        }

        if (in_array($type, ['int', 'float', 'string', 'boolean', 'bool'])) {
            return array_map(function ($v) use ($type) {
                settype($v, $type);
                return $v;
            }, $array);
        }

        if (! empty($attributes = $property->getAttributes(PolymorphicList::class))) {
            /** @var class-string<PolymorphicDto> $polymorph */
            $polymorph = $attributes[0]->getArguments()[0];

            // @phpstan-ignore argument.type
            return $this->deserialize($array, $polymorph, array: true);
        }

        if (class_exists($subclass = 'Reedware\OpenApi\Client\Schema\\' . $type) && is_subclass_of($subclass, Dto::class)) {
            // @phpstan-ignore argument.type
            return $this->deserialize($array, $subclass, array: true);
        }

        throw new DeserializationException("Unknown class [{$subclass}] when deserializing [{$class}].");
    }
}
