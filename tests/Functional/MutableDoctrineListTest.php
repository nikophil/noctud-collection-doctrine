<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Functional;

use Noctud\Collection\Doctrine\MutableDoctrineList;
use Noctud\Collection\Doctrine\Tests\Fixtures\Library;
use Noctud\Collection\Doctrine\Tests\Fixtures\Tag;
use Noctud\Collection\Exception\IndexOutOfBoundsException;
use PHPUnit\Framework\Attributes\Test;

final class MutableDoctrineListTest extends OrmTestCase
{
    #[Test]
    public function a_new_entity_keeps_its_list_when_flushed(): void
    {
        $library = new Library();
        $tags = $library->tags->add(new Tag('php'));

        $this->em->persist($library);
        $this->em->flush();

        $this->assertSame($tags, $library->tags);

        // the ORM replaced the collection inside the list on that first flush
        $library->tags->add(new Tag('doctrine'));

        $this->assertSame(['doctrine', 'php'], $this->tagNames($this->reload($library)));
    }

    #[Test]
    public function a_loaded_entity_gets_a_list(): void
    {
        $library = $this->reload($this->libraryWithTags('php', 'doctrine'));

        $this->assertInstanceOf(MutableDoctrineList::class, $library->tags);
        $this->assertCount(2, $library->tags);
        $this->assertSame(['doctrine', 'php'], $this->tagNames($library));
    }

    #[Test]
    public function additions_and_removals_are_persisted(): void
    {
        $library = $this->reload($this->libraryWithTags('doctrine', 'php', 'symfony'));

        $library->tags->removeAt(0);
        $library->tags->add(new Tag('noctud'));

        $this->assertSame(['noctud', 'php', 'symfony'], $this->tagNames($this->reload($library)));
    }

    #[Test]
    public function elements_are_addressed_by_position_after_a_removal(): void
    {
        $library = $this->reload($this->libraryWithTags('doctrine', 'php', 'symfony'));

        $library->tags->removeFirst();

        $this->assertSame('php', $library->tags[0]->name);
        $this->assertSame('symfony', $library->tags[1]->name);
        $this->assertSame(1, $library->tags->indexOf($library->tags->last()));

        $this->expectException(IndexOutOfBoundsException::class);
        $library->tags->get(2);
    }

    #[Test]
    public function counting_and_reading_one_element_do_not_load_an_extra_lazy_association(): void
    {
        $library = $this->reload($this->libraryWithTags('doctrine', 'php', 'symfony'));

        $this->assertCount(3, $library->tags);
        $this->assertSame('doctrine', $library->tags->first()->name);
        $this->assertSame('php', $library->tags[1]->name);
        $this->assertFalse($library->tags->contains(new Tag('php')));

        $this->assertFalse($this->managedCollection($library, 'tags')->isInitialized());
    }

    #[Test]
    public function sorting_reorders_the_list_without_making_the_collection_dirty(): void
    {
        $library = $this->reload($this->libraryWithTags('doctrine', 'php', 'symfony'));

        $library->tags->sortByDesc(static fn (Tag $tag): string => $tag->name);
        $this->assertSame(['symfony', 'php', 'doctrine'], $this->tagNames($library));

        $library->tags->reverse()->addFirst(new Tag('noctud'));
        $this->assertSame(['noctud', 'doctrine', 'php', 'symfony'], $this->tagNames($library));

        $managed = $this->managedCollection($library, 'tags');
        $this->assertCount(1, $managed->getInsertDiff());
        $this->assertSame([], $managed->getDeleteDiff());
    }

    #[Test]
    public function a_sorted_list_stays_clean(): void
    {
        $library = $this->reload($this->libraryWithTags('doctrine', 'php'));

        $library->tags->sortDesc()->shuffle();

        $this->assertFalse($this->managedCollection($library, 'tags')->isDirty());
    }

    #[Test]
    public function tracked_writes_are_persisted(): void
    {
        $library = $this->reload($this->libraryWithTags('php'));

        $this->assertTrue($library->tags->tracked()->add(new Tag('doctrine'))->changed);

        $this->assertSame(['doctrine', 'php'], $this->tagNames($this->reload($library)));
    }

    #[Test]
    public function transformations_leave_the_managed_collection_untouched(): void
    {
        $library = $this->reload($this->libraryWithTags('doctrine', 'php', 'symfony'));

        $reversed = $library->tags->reversed();
        $filtered = $library->tags->filter(static fn (Tag $tag): bool => $tag->name !== 'php');
        $dropped = $library->tags->dropFirst(2);

        $this->assertSame('symfony', $reversed->first()->name);
        $this->assertCount(2, $filtered);
        $this->assertCount(1, $dropped);
        $this->assertSame(['doctrine', 'php', 'symfony'], $this->tagNames($library));
        $this->assertFalse($this->managedCollection($library, 'tags')->isDirty());
    }

    #[Test]
    public function a_cloned_list_stops_writing_to_the_original(): void
    {
        $library = $this->reload($this->libraryWithTags('php'));

        $copy = clone $library->tags;
        $copy->add(new Tag('doctrine'));

        $this->assertCount(2, $copy);
        $this->assertCount(1, $library->tags);
    }

    private function libraryWithTags(string ...$names): Library
    {
        $library = new Library();

        foreach ($names as $name) {
            $library->tags->add(new Tag($name));
        }

        return $library;
    }

    /**
     * @return list<string>
     */
    private function tagNames(Library $library): array
    {
        return $library->tags->map(static fn (Tag $tag): string => $tag->name)->toArray();
    }
}
