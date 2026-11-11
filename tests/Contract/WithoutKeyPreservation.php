<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Contract;

/**
 * Turns off the map tests of noctud/collection that need a key a Doctrine collection cannot hold:
 * its keys are those of a PHP array.
 *
 * The overrides have no #[Test] attribute, so PHPUnit does not run them.
 */
trait WithoutKeyPreservation
{
    public function toArray_with_mapper_using_key_hasher_output(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toPairs_with_object_keys(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toArray_with_integer_compatible_float_keys(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toArray_fails_because_of_object(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toArray_float_precision_loss_with_keep_first(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toArray_fails_because_of_float_precision_loss(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function jsonSerialize_with_object_key_throws(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toArray_with_null_key_throws(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function preserved_but_not_suitable_for_to_array(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function preserved_and_suitable_for_to_array(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function null_key_throws(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function resource_key_throws(): void
    {
        // values kept under an object, a float or null: a PHP array has no such key
    }

    public function toArray_fails_because_of_key_duplicity(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function toArray_keep_first_on_collision(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function not_preserved_because_of_invalid_input(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function filter_preserves_keys(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function sortedByKey(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function sortedByValue(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function sortedBy_sorts_by_key_and_value(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function sortedWith_sorts_with_comparator(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }

    public function reverse(): void
    {
        // the string '1' kept apart from the integer 1: a PHP array holds one of them
    }
}
