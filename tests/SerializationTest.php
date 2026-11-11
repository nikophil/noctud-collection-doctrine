<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests;

use Noctud\Collection\Doctrine\MutableDoctrineList;
use Noctud\Collection\Doctrine\MutableDoctrineMap;
use Noctud\Collection\Doctrine\MutableDoctrineSet;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function serialize;
use function unserialize;

/**
 * An entity is serialized with its collections, in a session for instance.
 */
final class SerializationTest extends TestCase
{
    #[Test]
    public function a_list_is_restored_with_a_store_of_its_own(): void
    {
        $list = new MutableDoctrineList(['a', 'b']);

        $restored = unserialize(serialize($list));

        $this->assertInstanceOf(MutableDoctrineList::class, $restored);
        $this->assertSame(['a', 'b', 'c'], $restored->add('c')->toArray());
        $this->assertSame(['a', 'b'], $list->toArray());
    }

    #[Test]
    public function a_set_is_restored_with_a_store_of_its_own(): void
    {
        $set = new MutableDoctrineSet(['a', 'b']);

        $restored = unserialize(serialize($set));

        $this->assertInstanceOf(MutableDoctrineSet::class, $restored);
        $this->assertSame(['a', 'b', 'c'], $restored->add('b')->add('c')->toArray());
        $this->assertSame(['a', 'b'], $set->toArray());
    }

    #[Test]
    public function a_map_is_restored_with_a_store_of_its_own(): void
    {
        $map = new MutableDoctrineMap(['a' => 1]);

        $restored = unserialize(serialize($map));

        $this->assertInstanceOf(MutableDoctrineMap::class, $restored);
        $this->assertSame(['a' => 1, 'b' => 2], $restored->put('b', 2)->toArray());
        $this->assertSame(['a', 'b'], $restored->keys->toArray());
        $this->assertSame(['a' => 1], $map->toArray());
    }
}
