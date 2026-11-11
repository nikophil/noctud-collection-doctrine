<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine;

use Doctrine\ORM\Collection\DefaultDelegateCollectionFactory;
use Doctrine\ORM\Collection\DelegateCollection;
use Doctrine\ORM\Collection\DelegateCollectionFactory;
use function array_any;
use function is_a;

/**
 * Creates the Noctud collections of loaded entities with their constructor.
 *
 * The ORM creates custom collections without calling their constructor, which would
 * leave a Noctud collection without its store. Any other class goes to the default factory.
 */
final readonly class NoctudDelegateCollectionFactory implements DelegateCollectionFactory
{
    private const array ConstructedClasses = [
        MutableDoctrineList::class,
        MutableDoctrineSet::class,
    ];

    public function __construct(
        private DelegateCollectionFactory $default = new DefaultDelegateCollectionFactory(),
    ) {
    }

    public function create(string $collectionClass): DelegateCollection
    {
        if (array_any(self::ConstructedClasses, static fn (string $class): bool => is_a($collectionClass, $class, true))) {
            return new $collectionClass();
        }

        return $this->default->create($collectionClass);
    }
}
