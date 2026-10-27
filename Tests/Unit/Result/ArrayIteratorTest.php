<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Result;

use ONGR\ElasticsearchBundle\Result\ArrayIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use PHPUnit\Framework\TestCase;

class ArrayIteratorTest extends TestCase
{
    public function testArrayAccess(): void
    {
        $iterator = $this->iterator([
            ['_id' => 'foo', '_source' => ['title' => 'Foo']],
        ]);

        self::assertTrue(isset($iterator[0]));
        self::assertFalse(isset($iterator[1]));
        self::assertSame(['title' => 'Foo', '_id' => 'foo'], $iterator[0]);
        self::assertNull($iterator[1]);

        $iterator[1] = ['_id' => 'bar', '_source' => ['title' => 'Bar']];
        self::assertSame(['title' => 'Bar', '_id' => 'bar'], $iterator[1]);

        unset($iterator[0]);
        self::assertFalse(isset($iterator[0]));
    }

    public function testDocumentFromStoredFields(): void
    {
        $iterator = $this->iterator([
            ['_id' => 'foo', 'fields' => ['title' => ['Foo'], 'tags' => ['a', 'b']]],
        ]);

        self::assertSame(['title' => 'Foo', 'tags' => 'a', '_id' => 'foo'], $iterator[0]);
    }

    public function testGetAggregation(): void
    {
        $iterator = new ArrayIterator(
            ['aggregations' => ['brands' => ['buckets' => []]]],
            $this->createMock(IndexService::class)
        );

        self::assertSame(['buckets' => []], $iterator->getAggregation('brands'));
        self::assertNull($iterator->getAggregation('categories'));
    }

    private function iterator(array $hits): ArrayIterator
    {
        return new ArrayIterator(
            ['hits' => ['total' => ['value' => count($hits)], 'hits' => $hits]],
            $this->createMock(IndexService::class)
        );
    }
}
