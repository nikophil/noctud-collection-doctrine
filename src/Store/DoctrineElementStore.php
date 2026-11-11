<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Store;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Noctud\Collection\Exception\IndexOutOfBoundsException;
use Noctud\Collection\Exception\NoSuchElementException;
use Noctud\Collection\Store\AbstractElementStore;
use Noctud\Collection\Store\ReadWriteIndexedStore;
use Traversable;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_rand;
use function array_reverse;
use function array_search;
use function array_values;
use function end;
use function shuffle;
use function sort;
use function usort;

/**
 * Element store reading and writing a Doctrine collection.
 *
 * Elements are addressed by their position: the keys of a Doctrine collection
 * have gaps once an element is removed.
 *
 * @internal
 * @template E
 * @implements ReadWriteIndexedStore<E>
 */
final class DoctrineElementStore implements ReadWriteIndexedStore
{
    /**
     * The closure returns the current collection on each call: the ORM replaces
     * the one a custom collection holds on the first flush and on refresh.
     *
     * @param Closure():Collection<array-key,E> $collection
     */
    public function __construct(private Closure $collection)
    {
    }

    /**
     * The logic traits clone the store, then sort or slice the copy.
     */
    public function __clone()
    {
        $detached = new ArrayCollection($this->collection()->toArray());
        $this->collection = static fn (): Collection => $detached;
    }

    // --- Element Access ---

    public function first(bool $throw = false): mixed
    {
        // slice() does not initialize an EXTRA_LAZY collection
        $elements = $this->collection()->slice(0, 1);

        if ($elements === []) {
            return $throw ? throw new NoSuchElementException() : null;
        }

        return $elements[array_key_first($elements)];
    }

    public function last(bool $throw = false): mixed
    {
        $elements = $this->collection()->toArray();

        if ($elements === []) {
            return $throw ? throw new NoSuchElementException() : null;
        }

        return $elements[array_key_last($elements)];
    }

    public function random(bool $throw = false): mixed
    {
        $elements = $this->collection()->toArray();

        if ($elements === []) {
            return $throw ? throw new NoSuchElementException() : null;
        }

        return $elements[array_rand($elements)];
    }

    public function get(int $index, bool $throw = false): mixed
    {
        $elements = $index >= 0 ? $this->collection()->slice($index, 1) : [];

        if ($elements === []) {
            return $throw ? throw new IndexOutOfBoundsException('Index out of bounds: ' . $index) : null;
        }

        return $elements[array_key_first($elements)];
    }

    public function indexOf(mixed $value): int
    {
        $index = array_search($value, $this->toArray(), true);

        return $index === false ? -1 : $index;
    }

    public function lastIndexOf(mixed $value): int
    {
        $indexes = array_keys($this->toArray(), $value, true);

        return $indexes === [] ? -1 : end($indexes);
    }

    // --- Querying ---

    public function contains(mixed $element): bool
    {
        return $this->collection()->contains($element);
    }

    public function isEmpty(): bool
    {
        return $this->collection()->isEmpty();
    }

    public function count(): int
    {
        return $this->collection()->count();
    }

    // --- Mutation ---

    public function add(mixed $element): void
    {
        $this->collection()->add($element);
    }

    public function addFirst(mixed $element): void
    {
        $collection = $this->collection();
        $collection->add($element);

        $elements = $collection->toArray();
        $key = array_key_last($elements);

        $this->rewrite([$key => $elements[$key]] + $elements);
    }

    public function addAll(iterable $source): void
    {
        foreach ($source as $element) {
            $this->add($element);
        }
    }

    public function set(int $index, mixed $element): void
    {
        $this->collection()->set($this->keyAt($index), $element);
    }

    public function removeAt(int $index): void
    {
        $this->collection()->remove($this->keyAt($index));
    }

    public function removeFirstOccurrence(mixed $element): void
    {
        $this->collection()->removeElement($element);
    }

    public function removeEvery(mixed $element): void
    {
        $collection = $this->collection();

        foreach (array_keys($collection->toArray(), $element, true) as $key) {
            $collection->remove($key);
        }
    }

    public function removeFirst(): void
    {
        $collection = $this->collection();
        $key = array_key_first($collection->toArray());

        if ($key !== null) {
            $collection->remove($key);
        }
    }

    public function removeLast(): void
    {
        $collection = $this->collection();
        $key = array_key_last($collection->toArray());

        if ($key !== null) {
            $collection->remove($key);
        }
    }

    public function clear(): void
    {
        $this->collection()->clear();
    }

    public function removeIf(callable $predicate): void
    {
        $collection = $this->collection();

        foreach ($collection->toArray() as $key => $element) {
            if ($predicate($element)) {
                $collection->remove($key);
            }
        }
    }

    // --- Ordering ---

    public function sort(?callable $comparator = null): void
    {
        $elements = $this->toArray();

        if ($comparator !== null) {
            usort($elements, $comparator);
        } else {
            sort($elements);
        }

        $this->rewrite($elements);
    }

    public function sortBy(callable $selector, bool $descending = false): void
    {
        $this->rewrite(array_values(AbstractElementStore::orderedBy($this->toArray(), $selector, $descending)));
    }

    public function reverse(): void
    {
        $this->rewrite(array_reverse($this->toArray()));
    }

    public function shuffle(): void
    {
        $elements = $this->toArray();
        shuffle($elements);

        $this->rewrite($elements);
    }

    // --- Conversion ---

    public function toArray(): array
    {
        return $this->collection()->getValues();
    }

    public function getIterator(): Traversable
    {
        yield from $this->toArray();
    }

    // --- Internal ---

    /**
     * @return Collection<array-key,E>
     */
    private function collection(): Collection
    {
        return ($this->collection)();
    }

    private function keyAt(int $index): int|string
    {
        return $this->collection()->getKeys()[$index] ?? throw new IndexOutOfBoundsException('Index out of bounds: ' . $index);
    }

    /**
     * Replaces the elements with the same ones in another order.
     *
     * An association has no persisted order, so a managed collection is reordered
     * behind its change tracking: it must not become dirty.
     *
     * @param array<array-key,E> $elements
     */
    private function rewrite(array $elements): void
    {
        $collection = $this->collection();

        if ($collection instanceof PersistentCollection) {
            $collection->initialize();
            $collection = $collection->unwrap();
        }

        $collection->clear();

        foreach ($elements as $key => $element) {
            $collection->set($key, $element);
        }
    }
}
