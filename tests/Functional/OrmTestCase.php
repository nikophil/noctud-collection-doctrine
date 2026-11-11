<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Functional;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\SchemaTool;
use Noctud\Collection\Doctrine\NoctudDelegateCollectionFactory;
use PHPUnit\Framework\TestCase;

abstract class OrmTestCase extends TestCase
{
    protected EntityManager $em;

    protected function setUp(): void
    {
        $config = new Configuration();
        $config->setMetadataDriverImpl(new AttributeDriver([__DIR__ . '/../Fixtures']));
        $config->enableNativeLazyObjects(true);
        $config->setDelegateCollectionFactory(new NoctudDelegateCollectionFactory());

        $this->em = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config), $config);

        new SchemaTool($this->em)->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    /**
     * Persists the entity, then loads it again as another request would.
     *
     * @template T of object
     * @param T $entity
     * @return T
     */
    protected function reload(object $entity): object
    {
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $metadata = $this->em->getClassMetadata($entity::class);
        $reloaded = $this->em->find($entity::class, $metadata->getIdentifierValues($entity));
        self::assertNotNull($reloaded);

        return $reloaded;
    }

    /**
     * Returns the collection the ORM manages inside the Noctud collection of an association.
     *
     * @return PersistentCollection<array-key,object>
     */
    protected function managedCollection(object $entity, string $association): PersistentCollection
    {
        $collection = $this->em->getClassMetadata($entity::class)->getFieldValue($entity, $association);
        self::assertInstanceOf(PersistentCollection::class, $collection);

        return $collection;
    }
}
