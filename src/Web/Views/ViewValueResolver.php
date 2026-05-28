<?php

declare(strict_types=1);

namespace PhpMvc\Views;

/**
 * Resolves a path expression (e.g. "model->customer->address->street", "items[0]->name")
 * against a view model (object or array). Supports property access, array indices, and method calls.
 */
final readonly class ViewValueResolver
{
    /**
     * @param null|array<string, mixed>|object $model
     */
    public function resolve(array|object|null $model, string $path): mixed
    {
        if (null === $model) {
            return null;
        }
        $path = trim($path);
        if ('' === $path) {
            return $model;
        }
        $segments = explode('->', $path);
        $current = $model;
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ('' === $segment) {
                continue;
            }
            $current = $this->resolveSegment($current, $segment);
            if (null === $current) {
                return null;
            }
        }

        return $current;
    }

    /**
     * Returns true if the path exists in the model (every segment present). Used to distinguish
     * "path exists, value may be null" (replace with formatted value) from "path missing" (leave for i18n).
     *
     * @param null|array<string, mixed>|object $model
     */
    public function pathExists(array|object|null $model, string $path): bool
    {
        if (null === $model) {
            return false;
        }
        $path = trim($path);
        if ('' === $path) {
            return true;
        }
        $segments = explode('->', $path);
        $current = $model;
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ('' === $segment) {
                continue;
            }
            if (!is_array($current) && !is_object($current)) {
                return false;
            }
            if (!$this->segmentExists($current, $segment)) {
                return false;
            }
            $current = $this->resolveSegment($current, $segment);
        }

        return true;
    }

    /**
     * Returns true if the resolved value is truthy (for #if). Handles negation via leading "!".
     *
     * @param null|array<string, mixed>|object $model
     */
    public function isTruthy(array|object|null $model, string $expression): bool
    {
        $negated = str_starts_with($expression, '!');
        $path = trim(str_replace('!', '', $expression));
        $value = $this->resolve($model, $path);
        $truthy = (bool) $value;

        return $negated ? !$truthy : $truthy;
    }

    /**
     * @param array<array-key, mixed>|object $current
     */
    private function segmentExists(array|object $current, string $segment): bool
    {
        if (str_ends_with($segment, '()')) {
            $method = substr($segment, 0, -2);

            return is_object($current) && method_exists($current, $method);
        }
        $index = null;
        $property = $segment;
        if (preg_match('/^(\w+)\[(.*)\]$/s', $segment, $matches)) {
            $property = $matches[1];
            $index = $this->parseIndex($matches[2]);
        }
        $hasProperty = is_array($current)
            ? array_key_exists($property, $current)
            : property_exists($current, $property);
        if (!$hasProperty) {
            return false;
        }
        if (null === $index) {
            return true;
        }
        $value = $this->get($current, $property);
        if (is_array($value)) {
            return array_key_exists($index, $value);
        }
        if (is_object($value)) {
            return property_exists($value, (string) $index);
        }

        return false;
    }

    private function resolveSegment(mixed $current, string $segment): mixed
    {
        if (!is_array($current) && !is_object($current)) {
            return null;
        }
        if (str_ends_with($segment, '()')) {
            $method = substr($segment, 0, -2);
            if (is_object($current) && method_exists($current, $method)) {
                return $current->{$method}();
            }

            return null;
        }

        $index = null;
        $property = $segment;
        if (preg_match('/^(\w+)\[(.*)\]$/s', $segment, $matches)) {
            $property = $matches[1];
            $index = $this->parseIndex($matches[2]);
        }

        $value = $this->get($current, $property);
        if (null !== $index && null !== $value) {
            return $this->getIndex($value, $index);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed>|object $current
     */
    private function get(array|object $current, string $property): mixed
    {
        if (is_array($current)) {
            return array_key_exists($property, $current) ? $current[$property] : null;
        }
        if (property_exists($current, $property)) {
            return $current->{$property};
        }

        return null;
    }

    private function getIndex(mixed $value, int|string $index): mixed
    {
        if (is_array($value) && array_key_exists($index, $value)) {
            return $value[$index];
        }
        if (is_object($value) && property_exists($value, (string) $index)) {
            return $value->{(string) $index};
        }

        return null;
    }

    private function parseIndex(string $raw): int|string
    {
        $clean = trim(str_replace(['"', "'"], '', $raw));

        return is_numeric($clean) ? (int) $clean : $clean;
    }
}
