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
use Noctud\Collection\Doctrine\Store\DoctrineElementStore;
use Noctud\Collection\List\MutableList;
use Noctud\Collection\List\MutableListLogic;

/**
 * Mutable list holding the elements of a Doctrine to-many association.
 *
 * Every write reaches the collection managed by the ORM, so lazy loading,
 * change tracking, cascades and orphan removal work as with a plain collection.
 *
 * @template E
 * @implements MutableList<E>
 * @implements DelegateCollection<int,E>
 */
class MutableDoctrineList implements MutableList, DelegateCollection
{
    /** @use MutableListLogic<E> */
    use MutableListLogic;

    /**
     * The ORM puts the managed collection here.
     *
     * @var Collection<array-key,E>
     */
    private Collection $collection;

    /**
     * @param iterable<E> $elements
     */
    public function __construct(iterable $elements = [])
    {
        $this->collection = new ArrayCollection();
        $this->store = new DoctrineElementStore(fn (): Collection => $this->collection);
        $this->store->addAll($elements);
    }

    /**
     * The store of a copy reads the collection of that copy.
     */
    public function __clone()
    {
        $this->collection = clone $this->collection;
        $this->store = new DoctrineElementStore(fn (): Collection => $this->collection);
    }
}
