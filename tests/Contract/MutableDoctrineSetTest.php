<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Contract;

use Closure;
use Noctud\Collection\Doctrine\MutableDoctrineSet;
use Noctud\Collection\Set\MutableSet;
use Noctud\Collection\Tests\Collection\CollectionMutateWrite;
use Noctud\Collection\Tests\Collection\Set\Case\AbstractSetTestCase;
use Noctud\Collection\Tests\Collection\Set\SetChangedWithNull;
use Noctud\Collection\Tests\Collection\Set\SetMutateWrite;

/**
 * Runs the set tests of noctud/collection against a set backed by a Doctrine collection.
 */
final class MutableDoctrineSetTest extends AbstractSetTestCase
{
    use CollectionMutateWrite;
    use SetChangedWithNull;
    use SetMutateWrite;
    use WithoutCopyOnWrite;

    /**
     * @template E
     * @param iterable<E>|Closure():iterable<E> $data
     * @return MutableSet<E>
     */
    public function collectionOf(iterable|Closure $data): MutableSet
    {
        return new MutableDoctrineSet($data instanceof Closure ? $data() : $data);
    }

    /**
     * @template E
     * @param iterable<E>|Closure():iterable<E> $data
     * @return MutableSet<E>
     */
    public function enumerableOf(iterable|Closure $data): MutableSet
    {
        return $this->collectionOf($data);
    }
}
