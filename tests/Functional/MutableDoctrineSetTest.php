<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Functional;

use Noctud\Collection\Doctrine\Tests\Fixtures\Book;
use Noctud\Collection\Doctrine\Tests\Fixtures\BookSet;
use Noctud\Collection\Doctrine\Tests\Fixtures\Library;
use PHPUnit\Framework\Attributes\Test;

final class MutableDoctrineSetTest extends OrmTestCase
{
    #[Test]
    public function a_loaded_entity_gets_its_domain_collection(): void
    {
        $library = $this->reload($this->libraryWithBooks('Emma', 'Dune'));

        $this->assertInstanceOf(BookSet::class, $library->books);
        $this->assertSame(['Dune', 'Emma'], $library->books->titles()->toArray());
    }

    #[Test]
    public function a_book_is_held_once(): void
    {
        $library = $this->reload($this->libraryWithBooks('Dune'));
        $dune = $library->books->first();

        $library->books->add($dune)->addFirst($dune);

        $this->assertCount(1, $library->books);
        $this->assertFalse($library->books->tracked()->add($dune)->changed);
        $this->assertFalse($this->managedCollection($library, 'books')->isDirty());
    }

    #[Test]
    public function a_new_book_is_persisted_through_the_set(): void
    {
        $library = $this->reload($this->libraryWithBooks('Dune'));

        new Book($library, 'Emma');

        $this->assertSame(['Dune', 'Emma'], $this->reload($library)->books->titles()->toArray());
    }

    #[Test]
    public function a_removed_book_is_deleted_as_an_orphan(): void
    {
        $library = $this->reload($this->libraryWithBooks('Dune', 'Emma', 'Ulysses'));
        $dune = $library->books->first();

        $library->books->removeElement($dune);
        $library->books->removeIf(static fn (Book $book): bool => $book->title === 'Ulysses');

        $this->assertSame(['Emma'], $this->reload($library)->books->titles()->toArray());
        $this->assertSame(1, $this->em->getRepository(Book::class)->count());
    }

    #[Test]
    public function sorting_reorders_the_set_without_making_the_collection_dirty(): void
    {
        $library = $this->reload($this->libraryWithBooks('Dune', 'Emma', 'Ulysses'));

        $library->books->sortByDesc(static fn (Book $book): string => $book->title);
        $this->assertSame(['Ulysses', 'Emma', 'Dune'], $library->books->titles()->toArray());

        $library->books->addFirst($library->books->last());
        $this->assertSame(['Dune', 'Ulysses', 'Emma'], $library->books->titles()->toArray());

        $this->assertFalse($this->managedCollection($library, 'books')->isDirty());
    }

    private function libraryWithBooks(string ...$titles): Library
    {
        $library = new Library();

        foreach ($titles as $title) {
            new Book($library, $title);
        }

        return $library;
    }
}
