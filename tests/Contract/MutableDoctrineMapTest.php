<?php

/**
 * This file is part of the Noctud Collection.
 * Copyright (c) Noctud.dev
 */

declare(strict_types=1);

namespace Noctud\Collection\Doctrine\Tests\Contract;

use Closure;
use Noctud\Collection\Doctrine\MutableDoctrineMap;
use Noctud\Collection\Exception\InvalidKeyTypeException;
use Noctud\Collection\Map\MapEntry;
use Noctud\Collection\Map\MutableMap;
use Noctud\Collection\Tests\Map\Case\AbstractMapTestCase;
use Noctud\Collection\Tests\Map\MapChangedWithNull;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use function strlen;

/**
 * Runs the map tests of noctud/collection against a map backed by a Doctrine collection.
 *
 * The ordering tests turned off by WithoutKeyPreservation only fail on the key '1' of their
 * data: they are written again here with keys a PHP array keeps.
 */
final class MutableDoctrineMapTest extends AbstractMapTestCase
{
    use MapChangedWithNull;
    use WithoutCopyOnWrite;
    use WithoutKeyPreservation;

    #[Test]
    public function keys_are_those_of_a_php_array(): void
    {
        /** @var MutableMap<int|string,string> $map */
        $map = $this->mapOfPairs([['1', 'a'], ['01', 'b']]);

        $this->assertSame([1, '01'], $map->keys->toArray());
        $this->assertSame('a', $map[1]);
        $this->assertSame('a', $map['1']);
    }

    #[Test]
    public function a_key_of_another_type_is_never_found(): void
    {
        $map = $this->mapOf(['a' => 1]);

        $this->assertFalse($map->containsKey(1.5));
        $this->assertFalse($map->containsKey(new stdClass()));
        $this->assertNull($map->getOrNull(true)); // @phpstan-ignore argument.type
        $this->assertSame(['a' => 1], $map->remove(false)->toArray());
    }

    #[Test]
    public function a_key_of_another_type_cannot_be_written(): void
    {
        $map = $this->mapOf([]);

        $this->expectException(InvalidKeyTypeException::class);
        $map->put(1.5, 'a');
    }

    #[Test]
    public function sorted_by_key_and_by_value(): void
    {
        $map = $this->mapOf(['b' => 2, 'a' => 3, 'c' => 1]);

        $this->assertSame(['a' => 3, 'b' => 2, 'c' => 1], $map->sortedByKey()->toArray());
        $this->assertSame(['c' => 1, 'b' => 2, 'a' => 3], $map->sortedByKeyDesc()->toArray());
        $this->assertSame(['c' => 1, 'b' => 2, 'a' => 3], $map->sortedByValue()->toArray());
        $this->assertSame(['a' => 3, 'b' => 2, 'c' => 1], $map->sortedByValueDesc()->toArray());
        $this->assertSame(['b' => 2, 'a' => 3, 'c' => 1], $map->toArray());

        $this->assertSame(['a' => 3, 'b' => 2, 'c' => 1], $map->sortByKey()->toArray());
        $this->assertSame(['c' => 1, 'b' => 2, 'a' => 3], $map->sortByValue()->toArray());
    }

    #[Test]
    public function sorted_by_a_selector_and_with_a_comparator(): void
    {
        $map = $this->mapOf(['ax' => 2, 'b' => 10, 'ccc' => 3]);
        $selector = static fn (int $value, string $key): int => strlen($key) + $value;

        $this->assertSame(['ax' => 2, 'ccc' => 3, 'b' => 10], $map->sortedBy($selector)->toArray());
        $this->assertSame(['b' => 10, 'ccc' => 3, 'ax' => 2], $map->sortedByDesc($selector)->toArray());
        $this->assertSame(['b' => 10, 'ax' => 2, 'ccc' => 3], $map->sortedWithKey(static fn (string $a, string $b): int => strlen($a) <=> strlen($b))->toArray());
        $this->assertSame(['ax' => 2, 'ccc' => 3, 'b' => 10], $map->sortedWithValue(static fn (int $a, int $b): int => $a <=> $b)->toArray());
        $this->assertSame(['b' => 10, 'ccc' => 3, 'ax' => 2], $map->sortedWith(static fn (MapEntry $a, MapEntry $b): int => $b->value <=> $a->value)->toArray());
        $this->assertSame(['ax' => 2, 'b' => 10, 'ccc' => 3], $map->toArray());

        $this->assertSame(['ax' => 2, 'ccc' => 3, 'b' => 10], $map->sortBy($selector)->toArray());
    }

    #[Test]
    public function reversed_and_filtered_by_key(): void
    {
        $map = $this->mapOf(['b' => 2, 'a' => 3, 5 => 1]);

        $this->assertSame([5 => 1, 'a' => 3, 'b' => 2], $map->reversed()->toArray());
        $this->assertSame([5 => 1], $map->filterKeys(static fn (int|string $key): bool => $key === 5)->toArray());
        $this->assertSame(['b' => 2, 'a' => 3, 5 => 1], $map->toArray());

        $this->assertSame([5 => 1, 'a' => 3, 'b' => 2], $map->reverse()->toArray());
    }

    protected function sampleKey(): string
    {
        return 'test_key';
    }

    /**
     * @template K of int|string
     * @template V
     * @param iterable<K,V>|Closure():iterable<K,V> $data
     * @return MutableMap<K,V>
     */
    public function mapOf(iterable|Closure $data): MutableMap
    {
        return new MutableDoctrineMap($data instanceof Closure ? $data() : $data);
    }

    /**
     * @template K of int|string
     * @template V
     * @param iterable<array{0:K,1:V}>|Closure():iterable<array{0:K,1:V}> $data
     * @return MutableMap<K,V>
     */
    public function mapOfPairs(iterable|Closure $data): MutableMap
    {
        /** @var MutableDoctrineMap<K,V> $map */
        $map = new MutableDoctrineMap();

        foreach ($data instanceof Closure ? $data() : $data as [$key, $value]) {
            $map->put($key, $value);
        }

        return $map;
    }

    /**
     * @template K of int|string
     * @template V
     * @param iterable<K,V>|Closure():iterable<K,V> $data
     * @return MutableMap<K,V>
     */
    public function enumerableOf(iterable|Closure $data): MutableMap
    {
        return $this->mapOf($data);
    }
}
