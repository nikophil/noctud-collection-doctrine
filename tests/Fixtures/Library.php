<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Fixtures;

use Doctrine\ORM\Mapping as ORM;
use Noctud\Collection\Doctrine\MutableDoctrineList;
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

    public function __construct()
    {
        $this->tags = new MutableDoctrineList();
    }
}
