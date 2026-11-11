<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Functional;

use Noctud\Collection\Doctrine\MutableDoctrineMap;
use Noctud\Collection\Doctrine\Tests\Fixtures\Library;
use Noctud\Collection\Doctrine\Tests\Fixtures\Shelf;
use PHPUnit\Framework\Attributes\Test;

final class MutableDoctrineMapTest extends OrmTestCase
{
    #[Test]
    public function a_loaded_entity_gets_a_map_keyed_by_the_index_field(): void
    {
        $library = $this->reload($this->libraryWithShelves('B2', 'A1'));

        $this->assertInstanceOf(MutableDoctrineMap::class, $library->shelves);
        $this->assertSame(['A1', 'B2'], $library->shelves->keys->toArray());
        $this->assertSame('B2', $library->shelves['B2']->code);
    }

    #[Test]
    public function reading_by_key_does_not_load_an_extra_lazy_association(): void
    {
        $library = $this->reload($this->libraryWithShelves('A1', 'B2'));

        $this->assertCount(2, $library->shelves);
        $this->assertTrue($library->shelves->containsKey('A1'));
        $this->assertSame('B2', $library->shelves->get('B2')->code);
        $this->assertNull($library->shelves->getOrNull('C3'));

        $this->assertFalse($this->managedCollection($library, 'shelves')->isInitialized());
    }

    #[Test]
    public function additions_and_removals_are_persisted(): void
    {
        $library = $this->reload($this->libraryWithShelves('A1', 'B2', 'C3'));

        new Shelf($library, 'D4');
        $library->shelves->remove('A1');
        $this->assertTrue($library->shelves->tracked()->removeIf(static fn (Shelf $shelf): bool => $shelf->code === 'C3')->changed);

        $this->assertSame(['B2', 'D4'], $this->reload($library)->shelves->keys->toArray());
        $this->assertSame(2, $this->em->getRepository(Shelf::class)->count());
    }

    #[Test]
    public function sorting_reorders_the_map_without_making_the_collection_dirty(): void
    {
        $library = $this->reload($this->libraryWithShelves('A1', 'B2', 'C3'));

        $library->shelves->sortByKeyDesc();
        $this->assertSame(['C3', 'B2', 'A1'], $library->shelves->keys->toArray());

        $library->shelves->putFirst('A1', $library->shelves['A1']);
        $this->assertSame(['A1', 'C3', 'B2'], $library->shelves->keys->toArray());
        $this->assertSame('B2', $library->shelves->values->last()->code);

        $this->assertSame(['C3', 'B2'], $library->shelves->dropFirst()->keys->toArray());
        $this->assertCount(3, $library->shelves);

        $managed = $this->managedCollection($library, 'shelves');
        $this->assertSame([], $managed->getInsertDiff());
        $this->assertSame([], $managed->getDeleteDiff());
    }

    #[Test]
    public function a_cloned_map_stops_writing_to_the_original(): void
    {
        $library = $this->reload($this->libraryWithShelves('A1'));
        $keys = $library->shelves->keys;

        $copy = clone $library->shelves;
        $copy->remove('A1');

        $this->assertSame([], $copy->keys->toArray());
        $this->assertSame(['A1'], $keys->toArray());
    }

    private function libraryWithShelves(string ...$codes): Library
    {
        $library = new Library();

        foreach ($codes as $code) {
            new Shelf($library, $code);
        }

        return $library;
    }
}
