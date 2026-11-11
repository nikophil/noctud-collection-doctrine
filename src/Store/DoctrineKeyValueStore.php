<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Store;

use Noctud\Collection\Exception\InvalidKeyTypeException;
use Noctud\Collection\Exception\NoSuchElementException;
use Noctud\Collection\Map\KeyCollisionStrategy;
use Noctud\Collection\Map\MapEntry;
use Noctud\Collection\Map\SimpleMapEntry;
use Noctud\Collection\Operation\SortOperation;
use Noctud\Collection\Store\KeyValueStore;
use Traversable;
use function array_diff_key;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_rand;
use function array_replace;
use function array_slice;
use function get_debug_type;
use function is_int;
use function is_string;
use function sprintf;
use function usort;

/**
 * Key-value store reading and writing a Doctrine collection, whose keys are those of a PHP array.
 *
 * A key of another type is never found, and cannot be written.
 *
 * @internal
 * @template K of int|string
 * @template V
 * @extends AbstractDoctrineStore<K,V>
 * @implements KeyValueStore<K,V>
 */
final class DoctrineKeyValueStore extends AbstractDoctrineStore implements KeyValueStore
{
    // --- Element Access ---

    public function get(string|int|bool|float|object $key, bool $throw = false): mixed
    {
        if (is_int($key) || is_string($key)) {
            $collection = $this->collection();
            $value = $collection->get($key);

            // a null value is told apart from a missing key
            if ($value !== null || $collection->containsKey($key)) {
                return $value;
            }
        }

        return $throw ? throw new NoSuchElementException('Key not found in map') : null;
    }

    public function first(bool $throw = false): ?MapEntry
    {
        $elements = $this->collection()->toArray();

        return $this->entry($elements, array_key_first($elements), $throw);
    }

    public function last(bool $throw = false): ?MapEntry
    {
        $elements = $this->collection()->toArray();

        return $this->entry($elements, array_key_last($elements), $throw);
    }

    public function random(bool $throw = false): ?MapEntry
    {
        $elements = $this->collection()->toArray();

        return $this->entry($elements, $elements === [] ? null : array_rand($elements), $throw);
    }

    // --- Querying ---

    public function containsKey(string|int|bool|float|object $key): bool
    {
        return (is_int($key) || is_string($key)) && $this->collection()->containsKey($key);
    }

    // --- Mutation ---

    public function put(string|int|bool|float|object $key, mixed $value): void
    {
        self::assertArrayKey($key);

        $this->collection()->set($key, $value);
    }

    public function putFirst(string|int|bool|float|object $key, mixed $value): void
    {
        self::assertArrayKey($key);

        $collection = $this->collection();
        $collection->set($key, $value);

        $this->rewrite([$key => $value] + $collection->toArray());
    }

    /**
     * The interface lets an immutable map widen its types: a mutable one holds K and V.
     */
    public function putAllFromAssoc(iterable $source): void
    {
        foreach ($source as $key => $value) {
            $this->put($key, $value); // @phpstan-ignore argument.type, argument.type
        }
    }

    /**
     * The interface lets an immutable map widen its types: a mutable one holds K and V.
     */
    public function putAllFromPairs(iterable $source): void
    {
        foreach ($source as [$key, $value]) {
            $this->put($key, $value); // @phpstan-ignore argument.type, argument.type
        }
    }

    public function remove(string|int|bool|float|object $key): void
    {
        if (is_int($key) || is_string($key)) {
            $this->collection()->remove($key);
        }
    }

    public function removeIf(callable $predicate): void
    {
        $collection = $this->collection();

        foreach ($collection->toArray() as $key => $value) {
            if ($predicate($value, $key)) {
                $collection->remove($key);
            }
        }
    }

    public function removeIfKey(callable $predicate): void
    {
        $this->removeIf(static fn (mixed $value, int|string $key): bool => $predicate($key));
    }

    public function removeIfValue(callable $predicate): void
    {
        $this->removeIf(static fn (mixed $value): bool => $predicate($value));
    }

    // --- Ordering ---

    public function sortByPairs(callable $comparator): void
    {
        $pairs = $this->toPairs();
        usort($pairs, $comparator);

        $elements = [];
        foreach ($pairs as [$key, $value]) {
            $elements[$key] = $value;
        }

        $this->rewrite($elements);
    }

    public function sortBy(callable $selector, bool $descending = false): void
    {
        $elements = $this->collection()->toArray();

        $selected = [];
        foreach ($elements as $key => $value) {
            $selected[$key] = $selector($value, $key);
        }

        $this->rewrite(array_replace(SortOperation::order($selected, $descending), $elements));
    }

    public function slice(int $offset, ?int $length = null): void
    {
        $collection = $this->collection();
        $elements = $collection->toArray();

        foreach (array_keys(array_diff_key($elements, array_slice($elements, $offset, $length, true))) as $key) {
            $collection->remove($key);
        }
    }

    // --- Conversion ---

    public function toArray(KeyCollisionStrategy $onCollision = KeyCollisionStrategy::Throw): array
    {
        return $this->collection()->toArray();
    }

    public function toPairs(): array
    {
        $pairs = [];
        foreach ($this->collection()->toArray() as $key => $value) {
            $pairs[] = [$key, $value];
        }

        return $pairs;
    }

    public function keys(): array
    {
        return $this->collection()->getKeys();
    }

    public function values(): array
    {
        return $this->collection()->getValues();
    }

    public function getIterator(): Traversable
    {
        yield from $this->collection()->toArray();
    }

    // --- Internal ---

    /**
     * @param array<K,V> $elements
     * @param K|null $key
     * @return MapEntry<K,V>|null
     */
    private function entry(array $elements, int|string|null $key, bool $throw): ?MapEntry
    {
        if ($key === null) {
            return $throw ? throw new NoSuchElementException() : null;
        }

        return new SimpleMapEntry($key, $elements[$key]);
    }

    /**
     * Guards the writes: the static type of a key does not stop a caller from passing another one.
     *
     * @phpstan-assert int|string $key
     */
    private static function assertArrayKey(mixed $key): void
    {
        if (!is_int($key) && !is_string($key)) {
            throw new InvalidKeyTypeException(sprintf('A map backed by a Doctrine collection requires int or string keys, got %s', get_debug_type($key)));
        }
    }
}
