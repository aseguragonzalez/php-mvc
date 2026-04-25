<?php

declare(strict_types=1);

namespace AlfonsoSG\Mvc\Actions;

use AlfonsoSG\Mvc\Requests\InputNormalizer;

final class ActionParameterBuilder
{
    /** @var array<string, float|int|string> */
    private array $args = [];

    public function __construct() {}

    /**
     * @param array<string, float|int|string> $args
     */
    public function withArgs(array $args): ActionParameterBuilder
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @param class-string $requestType
     */
    public function build(string $requestType): object
    {
        $reflectionClass = new \ReflectionClass($requestType);
        $constructor = $reflectionClass->getConstructor();
        $constructorParameters = $constructor ? $constructor->getParameters() : [];
        $arguments = array_map(
            function (\ReflectionParameter $param) {
                $args = array_filter(
                    $this->args,
                    fn ($key) => str_starts_with($key, $param->getName().'.')
                        || str_starts_with($key, $param->getName().'['),
                    ARRAY_FILTER_USE_KEY
                );

                return (array_key_exists($param->getName(), $this->args) || !empty($args))
                    ? $this->getArgumentValue($param)
                    : $this->getDefaultValue($param);
            },
            $constructorParameters
        );

        return $reflectionClass->newInstanceArgs($arguments);
    }

    private function getArgumentValue(\ReflectionParameter $param): mixed
    {
        /** @var \ReflectionNamedType $type */
        $type = $param->getType();
        $name = $param->getName();

        return match ($type->getName()) {
            'int' => InputNormalizer::toInt($this->args[$name]),
            'float' => InputNormalizer::toFloat($this->args[$name]),
            'string' => InputNormalizer::toString($this->args[$name]),
            'bool' => InputNormalizer::toBool($this->args[$name]),
            \DateTime::class => new \DateTime((string) InputNormalizer::toString($this->args[$name])),
            \DateTimeImmutable::class => new \DateTimeImmutable(
                (string) InputNormalizer::toString($this->args[$name])
            ),
            'array' => $this->getEmbeddedArray($param, $name),
            default => $type->isBuiltin() ? $this->args[$name] : $this->getEmbeddedObject($param, $name),
        };
    }

    private function getEmbeddedArray(\ReflectionParameter $param, string $path): mixed
    {
        $args = array_filter($this->args, fn ($key) => str_starts_with($key, $path.'['), ARRAY_FILTER_USE_KEY);
        $itemType = $this->getArrayItemTypeFromAttribute($param);
        $builtInTypes = ['int', 'float', 'string', 'bool', \DateTime::class, \DateTimeImmutable::class];
        if (class_exists($itemType) && !in_array($itemType, $builtInTypes, true)) {
            return $this->getEmbeddedObjectArray($itemType, $args);
        }

        return array_map(function ($value) use ($itemType) {
            return match ($itemType) {
                'int' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                'bool' => (bool) $value,
                \DateTime::class => new \DateTime((string) $value),
                \DateTimeImmutable::class => new \DateTimeImmutable((string) $value),
                default => $value,
            };
        }, array_values($args));
    }

    private function getArrayItemTypeFromAttribute(\ReflectionParameter $param): string
    {
        $attrs = $param->getAttributes(ArrayOf::class);
        if ([] === $attrs) {
            throw new \RuntimeException("ArrayOf attribute not found for parameter {$param->getName()}");
        }

        return $attrs[0]->newInstance()->type;
    }

    /**
     * @param class-string                    $type
     * @param array<string, float|int|string> $args
     */
    private function getEmbeddedObjectArray(string $type, array $args): mixed
    {
        $groupedArgs = array_unique(array_map(fn ($key) => strstr($key ? $key : '', '.', true), array_keys($args)));
        $embeddedObjects = array_map(function ($group) use ($type, $args) {
            $filteredArgs = array_filter($args, fn ($key) => str_starts_with($key, $group.'.'), ARRAY_FILTER_USE_KEY);
            $embeddedArgs = array_combine(
                array_map(fn ($key) => substr($key, strlen($group ? $group : '') + 1), array_keys($filteredArgs)),
                $filteredArgs
            );

            return new self()->withArgs($embeddedArgs)->build($type);
        }, $groupedArgs);

        return array_values($embeddedObjects);
    }

    private function getEmbeddedObject(\ReflectionParameter $param, string $path): mixed
    {
        $args = array_filter($this->args, fn ($key) => str_starts_with($key, $path.'.'), ARRAY_FILTER_USE_KEY);
        $objectArgs = array_combine(array_map(fn ($key) => substr($key, strlen($path) + 1), array_keys($args)), $args);

        /** @var \ReflectionNamedType $type */
        $type = $param->getType();

        /** @var class-string $typeName */
        $typeName = $type->getName();

        return new self()->withArgs($objectArgs)->build($typeName);
    }

    private function getDefaultValue(\ReflectionParameter $param): mixed
    {
        return $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
    }
}
