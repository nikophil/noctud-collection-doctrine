# Noctud Collection Doctrine

Doctrine ORM bridge for [Noctud Collection](https://github.com/noctud/collection): expose to-many associations as Noctud collections.

> [!WARNING]
> This package relies on the custom collection classes proposed in [doctrine/orm#12777](https://github.com/doctrine/orm/pull/12777), which is not merged yet. Until it is released, `composer.json` installs Doctrine ORM from the branch of that pull request.

```shell
composer require noctud/collection-doctrine
```

Requires **PHP 8.4+**, `noctud/collection` 0.2 and Doctrine ORM 3.8.

## Setup

Doctrine creates the collections of loaded entities without calling their constructor, and a Noctud collection needs its own. Register the factory of this package on the ORM configuration:

```php
use Noctud\Collection\Doctrine\NoctudDelegateCollectionFactory;

$config->setDelegateCollectionFactory(new NoctudDelegateCollectionFactory());
```

Without it, the first access to the collection of a loaded entity fails with `Typed property ...::$store must not be accessed before initialization`.

The factory only creates the collections of this package and their subclasses. It hands any other class over to the default factory of the ORM, or to the one you pass to its constructor.

## Usage

Type the property of a to-many association with a collection of this package, and create it in the constructor of the entity:

```php
use Doctrine\ORM\Mapping as ORM;
use Noctud\Collection\Doctrine\MutableDoctrineList;

#[ORM\Entity]
class Library
{
    /** @var MutableDoctrineList<Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private MutableDoctrineList $tags;

    public function __construct()
    {
        $this->tags = new MutableDoctrineList();
    }

    public function tag(Tag $tag): void
    {
        $this->tags->add($tag);
    }

    /** @return list<string> */
    public function tagNames(): array
    {
        return $this->tags->sortedBy(fn (Tag $tag) => $tag->name)->map(fn (Tag $tag) => $tag->name)->toArray();
    }
}
```

The property holds the same object for the whole life of the entity. The ORM puts the collection it manages inside it, and every write goes to that collection: lazy loading, `EXTRA_LAZY`, change tracking, cascades and orphan removal work as with a plain Doctrine collection.

| Class                 | Noctud type                                         |
| --------------------- | --------------------------------------------------- |
| `MutableDoctrineList` | [`MutableList`](https://noctud.dev/collection/list) |
| `MutableDoctrineSet`  | [`MutableSet`](https://noctud.dev/collection/set)   |
| `MutableDoctrineMap`  | [`MutableMap`](https://noctud.dev/collection/map)   |

To type the property with the Noctud interface, name the class in the mapping:

```php
/** @var MutableList<Tag> */
#[ORM\ManyToMany(targetEntity: Tag::class, collectionClass: MutableDoctrineList::class)]
private MutableList $tags;
```

### Indexed associations

An association indexed by a field of its target entity is a map, keyed by the values of that field:

```php
/** @var MutableDoctrineMap<string, Shelf> */
#[ORM\OneToMany(targetEntity: Shelf::class, mappedBy: 'library', indexBy: 'code')]
private MutableDoctrineMap $shelves;
```

```php
$this->shelves['A1']; // the shelf whose code is A1, throws if there is none
$this->shelves->getOrNull('A1');
$this->shelves->put($shelf->code, $shelf);
$this->shelves->keys; // the codes, as a Set
```

### Domain collections

The classes are open to extension, so a collection can carry the methods of your domain. Its constructor must be callable without argument:

```php
/** @extends MutableDoctrineList<Tag> */
final class TagList extends MutableDoctrineList
{
    public function names(): ImmutableList
    {
        return $this->map(fn (Tag $tag) => $tag->name);
    }
}
```

## Good to know

- **The order is not persisted.** `sort()`, `sortBy()`, `reverse()`, `shuffle()`, `addFirst()` and `putFirst()` reorder the elements in memory, without any query. When the entity is loaded again, the order comes from the database, or from `#[ORM\OrderBy]`.
- **Transformations return detached collections.** As in Noctud Collection, `filter()`, `map()`, `sorted()` and the like return a new immutable collection: the ORM does not track it.
- **A set compares by identity**, as Doctrine collections do: `Hashable` is not used. To keep its elements unique, `add()` first asks the collection whether it holds the element, which loads a `LAZY` association.
- **The keys of a map are those of a PHP array**, as in a Doctrine collection: `'1'` is the integer `1`, and a key of another type cannot be written. When the entity is loaded again, the keys come from the `indexBy` field, so put an element under the value of that field.
- **Reading one element does not load an `EXTRA_LAZY` association.** `count()`, `contains()`, `first()` and `get()` each run their own query, as do `get()` and `containsKey()` on a map.
