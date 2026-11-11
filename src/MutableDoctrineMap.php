<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Collection\DelegateCollection;
use Noctud\Collection\Doctrine\Store\DoctrineKeyValueStore;
use Noctud\Collection\Map\MutableMap;
use Noctud\Collection\Map\MutableMapLogic;
use Noctud\Collection\Map\View\MapEntrySet;
use Noctud\Collection\Map\View\MapKeySet;
use Noctud\Collection\Map\View\MapValueCollection;

/**
 * Mutable map holding the elements of a Doctrine to-many association indexed by a field.
 *
 * Every write reaches the collection managed by the ORM, so lazy loading,
 * change tracking, cascades and orphan removal work as with a plain collection.
 *
 * The keys are those of a PHP array, as in a Doctrine collection: a numeric
 * string such as '1' is the integer 1.
 *
 * @template K of int|string
 * @template V
 * @implements MutableMap<K,V>
 * @implements DelegateCollection<K,V>
 */
class MutableDoctrineMap implements MutableMap, DelegateCollection
{
    /** @use MutableMapLogic<K,V> */
    use MutableMapLogic;

    /**
     * The ORM puts the managed collection here.
     *
     * @var Collection<K,V>
     */
    private Collection $collection;

    /**
     * @param iterable<K,V> $entries
     */
    public function __construct(iterable $entries = [])
    {
        $this->collection = new ArrayCollection();
        $this->bindStore();
        $this->store->putAllFromAssoc($entries);
    }

    /**
     * The store and the views of a copy read the collection of that copy.
     */
    public function __clone()
    {
        $this->collection = clone $this->collection;
        $this->bindStore();
        $this->keys = new MapKeySet($this->store);
        $this->values = new MapValueCollection($this->store);
        $this->entries = new MapEntrySet($this->store); // @phpstan-ignore assign.propertyType (MapEntry<K,V> is invariant, as in MapLogic)
    }

    /**
     * Only the collection is serialized: the store reads it through a closure.
     *
     * @return array{collection: Collection<K,V>}
     */
    public function __serialize(): array
    {
        return ['collection' => $this->collection];
    }

    /**
     * @param array{collection: Collection<K,V>} $data
     */
    public function __unserialize(array $data): void
    {
        $this->collection = $data['collection'];
        $this->bindStore();
    }

    private function bindStore(): void
    {
        $this->store = new DoctrineKeyValueStore(fn (): Collection => $this->collection);
    }
}
