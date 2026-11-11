<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Contract;

/**
 * Turns off the copy-on-write tests of noctud/collection: converting a collection backed by
 * a Doctrine collection copies its elements out, there is no PHP array to share.
 *
 * The overrides have no #[Test] attribute, so PHPUnit does not run them.
 */
trait WithoutCopyOnWrite
{
    public function toMutable_uses_copy_on_write(): void
    {
        // not a test anymore
    }

    public function toImmutable_uses_copy_on_write(): void
    {
        // not a test anymore
    }

    public function constructing_from_existing_uses_copy_on_write(): void
    {
        // not a test anymore
    }

    public function constructing_cross_type_uses_copy_on_write(): void
    {
        // not a test anymore
    }

    public function constructing_from_pairs_uses_copy_on_write(): void
    {
        // not a test anymore
    }
}
