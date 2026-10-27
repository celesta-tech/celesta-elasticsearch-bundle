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

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationValue;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use PHPUnit\Framework\TestCase;

class DocumentIteratorTest extends TestCase
{
    /**
     * Test for getAggregation() in case requested aggregation is not set.
     */
    public function testGetAggregationOnNull()
    {
        $index = $this->getMockBuilder(IndexService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new DocumentIterator([], $index);

        $this->assertNull($iterator->getAggregation('foo'));
    }

    public function testResultConvert()
    {
        $rawData = [
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_index' => 'test',
                        '_id' => 'foo',
                        '_score' => 1,
                        '_source' => [
                            'title' => 'Foo',
                        ],
                    ],
                ],
            ],
        ];

        $converter = $this->getMockBuilder(Converter::class)
            ->setMethods(['convertArrayToDocument'])
            ->disableOriginalConstructor()
            ->getMock();

        $document = new DummyDocument();
        $document->title = 'Foo';
        $converter->expects($this->any())->method('convertArrayToDocument')->willReturn($document);

        $index = $this->getMockBuilder(IndexService::class)
            ->setMethods(['getNamespace'])
            ->disableOriginalConstructor()
            ->getMock();

        $index->expects($this->any())->method('getNamespace')->willReturn(DummyDocument::class);

        $iterator = new DocumentIterator($rawData, $index, $converter);
        $this->assertEquals($document, $iterator->first());
    }

    public function testGetAggregationsWrapsEachAggregation(): void
    {
        $iterator = new DocumentIterator(
            ['aggregations' => ['brands' => ['buckets' => []], 'avg_price' => ['value' => 10.5]]],
            $this->createMock(IndexService::class)
        );

        $aggregations = $iterator->getAggregations();

        self::assertSame(['brands', 'avg_price'], array_keys($aggregations));
        self::assertContainsOnlyInstancesOf(AggregationValue::class, $aggregations);
        self::assertSame(10.5, $aggregations['avg_price']->getValue('value'));
    }
}
