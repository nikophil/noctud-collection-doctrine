<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Store;

use Closure;
use Doctrine\Common\Collections\Collection;
use Noctud\Collection\Exception\IndexOutOfBoundsException;
use Noctud\Collection\Exception\NoSuchElementException;
use Noctud\Collection\Store\AbstractElementStore;
use Noctud\Collection\Store\ReadWriteIndexedStore;
use Traversable;
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_rand;
use function array_search;
use function array_values;
use function end;
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
 * @extends AbstractDoctrineStore<array-key,E>
 * @implements ReadWriteIndexedStore<E>
 */
final class DoctrineElementStore extends AbstractDoctrineStore implements ReadWriteIndexedStore
{
    /**
     * @param Closure():Collection<array-key,E> $collection
     * @param bool $unique Whether adding an element the collection already holds is a no-op, as a set requires
     */
    public function __construct(
        Closure $collection,
        private readonly bool $unique = false,
    ) {
        parent::__construct($collection);
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

    // --- Mutation ---

    public function add(mixed $element): void
    {
        $collection = $this->collection();

        if (!$this->unique || !$collection->contains($element)) {
            $collection->add($element);
        }
    }

    public function addFirst(mixed $element): void
    {
        $collection = $this->collection();
        $key = $this->unique ? $collection->indexOf($element) : false;

        if ($key === false) {
            $collection->add($element);
            $key = array_key_last($collection->toArray());
        }

        $this->rewrite([$key => $element] + $collection->toArray());
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

    private function keyAt(int $index): int|string
    {
        return $this->collection()->getKeys()[$index] ?? throw new IndexOutOfBoundsException('Index out of bounds: ' . $index);
    }
}
