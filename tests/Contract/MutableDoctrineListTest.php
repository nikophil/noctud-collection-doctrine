<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Contract;

use Closure;
use Noctud\Collection\Doctrine\MutableDoctrineList;
use Noctud\Collection\List\MutableList;
use Noctud\Collection\Tests\Collection\List\Case\AbstractListTestCase;
use Noctud\Collection\Tests\Collection\List\ListChangedWithNull;

/**
 * Runs the list tests of noctud/collection against a list backed by a Doctrine collection.
 */
final class MutableDoctrineListTest extends AbstractListTestCase
{
    use ListChangedWithNull;
    use WithoutCopyOnWrite;

    /**
     * @template E
     * @param iterable<E>|Closure():iterable<E> $data
     * @return MutableList<E>
     */
    public function collectionOf(iterable|Closure $data): MutableList
    {
        return new MutableDoctrineList($data instanceof Closure ? $data() : $data);
    }

    /**
     * @template E
     * @param iterable<E>|Closure():iterable<E> $data
     * @return MutableList<E>
     */
    public function enumerableOf(iterable|Closure $data): MutableList
    {
        return $this->collectionOf($data);
    }
}
