<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests;

use ArrayIterator;
use Doctrine\ORM\Collection\DelegateCollection;
use Noctud\Collection\Doctrine\MutableDoctrineList;
use Noctud\Collection\Doctrine\NoctudDelegateCollectionFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Traversable;

final class NoctudDelegateCollectionFactoryTest extends TestCase
{
    #[Test]
    public function a_noctud_collection_is_created_with_its_constructor(): void
    {
        $list = new NoctudDelegateCollectionFactory()->create(MutableDoctrineList::class);

        $this->assertInstanceOf(MutableDoctrineList::class, $list);
        $this->assertCount(1, $list->add('a'));
    }

    #[Test]
    public function a_subclass_is_created_with_its_constructor(): void
    {
        $subclass = new class extends MutableDoctrineList {
        };

        $list = new NoctudDelegateCollectionFactory()->create($subclass::class);

        $this->assertInstanceOf($subclass::class, $list);
        $this->assertCount(0, $list);
    }

    #[Test]
    public function any_other_class_is_created_by_the_default_factory(): void
    {
        $other = new class implements DelegateCollection {
            public bool $constructed = false;

            public function __construct()
            {
                $this->constructed = true;
            }

            public function getIterator(): Traversable
            {
                return new ArrayIterator([]);
            }
        };

        $created = new NoctudDelegateCollectionFactory()->create($other::class);

        $this->assertInstanceOf($other::class, $created);
        $this->assertFalse($created->constructed);
    }
}
