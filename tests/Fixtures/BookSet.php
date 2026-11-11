<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Fixtures;

use Noctud\Collection\Doctrine\MutableDoctrineSet;
use Noctud\Collection\List\ImmutableList;

/**
 * A collection carrying domain methods.
 *
 * @extends MutableDoctrineSet<Book>
 */
final class BookSet extends MutableDoctrineSet
{
    /**
     * @return ImmutableList<string>
     */
    public function titles(): ImmutableList
    {
        return $this->map(static fn (Book $book): string => $book->title)->toList();
    }
}
