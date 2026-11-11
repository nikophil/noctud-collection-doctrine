<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Noctud\Collection\Doctrine\MutableDoctrineList;
use Noctud\Collection\Doctrine\MutableDoctrineMap;
use Noctud\Collection\List\MutableList;

#[ORM\Entity]
class Library
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    /**
     * Typed with the interface: the mapping names the class.
     *
     * @var MutableList<Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, cascade: ['persist'], fetch: 'EXTRA_LAZY', collectionClass: MutableDoctrineList::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    public MutableList $tags;

    /**
     * Typed with a domain collection: the ORM infers the class from the property type.
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'library', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['title' => 'ASC'])]
    public BookSet $books;

    /**
     * Indexed by a field of the target entity.
     *
     * @var MutableDoctrineMap<string,Shelf>
     */
    #[ORM\OneToMany(targetEntity: Shelf::class, mappedBy: 'library', cascade: ['persist'], fetch: 'EXTRA_LAZY', orphanRemoval: true, indexBy: 'code')]
    #[ORM\OrderBy(['code' => 'ASC'])]
    public MutableDoctrineMap $shelves;

    public function __construct()
    {
        $this->tags = new MutableDoctrineList();
        $this->books = new BookSet();
        $this->shelves = new MutableDoctrineMap();
    }
}
