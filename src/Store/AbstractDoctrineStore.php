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
use function array_key_first;
use function array_key_last;
use function array_keys;
use function array_reverse;
use function shuffle;

/**
 * What the stores reading and writing a Doctrine collection have in common.
 *
 * @internal
 * @template TKey of array-key
 * @template T
 */
abstract class AbstractDoctrineStore
{
    /**
     * The closure returns the current collection on each call: the ORM replaces
     * the one a custom collection holds on the first flush and on refresh.
     *
     * @param Closure():Collection<TKey,T> $collection
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

    // --- Ordering ---

    public function reverse(): void
    {
        $this->rewrite(array_reverse($this->collection()->toArray(), true));
    }

    public function shuffle(): void
    {
        $elements = $this->collection()->toArray();
        $keys = array_keys($elements);
        shuffle($keys);

        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $elements[$key];
        }

        $this->rewrite($shuffled);
    }

    // --- Internal ---

    /**
     * @return Collection<TKey,T>
     */
    protected function collection(): Collection
    {
        return ($this->collection)();
    }

    /**
     * Replaces the elements with the same ones in another order.
     *
     * An association has no persisted order, so a managed collection is reordered
     * behind its change tracking: it must not become dirty.
     *
     * @param array<TKey,T> $elements
     */
    protected function rewrite(array $elements): void
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
