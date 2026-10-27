<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Functional\Service;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\ServerErrorResponseException;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use ONGR\App\Document\DummyDocument;
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Event\PostCreateClientEvent;
use ONGR\ElasticsearchBundle\Exception\BulkWithErrorsException;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\IndexSettings;
use ONGR\ElasticsearchBundle\Result\DocumentIterator;
use ONGR\ElasticsearchBundle\Result\RawIterator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use ONGR\ElasticsearchDSL\Query\TermLevel\TermQuery;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Stopwatch\Stopwatch;

class IndexServiceTest extends AbstractElasticsearchTestCase
{
    #[\Override]
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                [
                    '_id' => 1,
                    'title' => 'foo'
                ],
                [
                    '_id' => 2,
                    'title' => 'foo',
                ],
                [
                    '_id' => 3,
                    'title' => 'bar',
                ],
            ]
        ];
    }

    public function indexNameDataProvider()
    {
        return [
          [ DummyDocument::class, DummyDocument::INDEX_NAME ],
          // this alias is overriden in configuration
          [ DummyDocumentInTheEntityDirectory::class, 'field-data-index' ],
        ];
    }

    /**
     * @dataProvider indexNameDataProvider
     */
    public function testIndexCrate($class, $indexName)
    {
        $index = $this->getIndex($class);

        $client = $index->getClient();
        $actualIndexExists = $client->indices()->exists(['index' => $indexName]);

        $this->assertTrue($actualIndexExists);
    }

    public function testDocumentInsertAndFindById()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $document */
        $document = $index->find(3);

        $this->assertEquals('bar', $document->title);
    }

    public function testFindOneBy()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $result */
        $result = $index->findOneBy(['title.raw' => 'bar']);
        $this->assertInstanceOf(DummyDocument::class, $result);
        $this->assertEquals(3, $result->id);
    }

    public function testFind()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DummyDocument $result */
        $result = $index->find(3);
        $this->assertInstanceOf(DummyDocument::class, $result);
        $this->assertEquals(3, $result->id);
        $this->assertEquals('bar', $result->title);
    }

    public function testFindBy()
    {
        $index = $this->getIndex(DummyDocument::class);

        /** @var DocumentIterator $result */
        $result = $index->findBy(['title.raw' => 'foo']);
        $this->assertInstanceOf(DocumentIterator::class, $result);
        $this->assertEquals(2, $result->count());

        $actualList = [];
        /** @var DummyDocument $item */
        foreach ($result as $item) {
            $actualList[] = (int) $item->id;
        }
        sort($actualList);

        $this->assertEquals([1,2], $actualList);
    }

    public function testIndexConfigOverride()
    {
        $index = $this->getIndex(DummyDocumentInTheEntityDirectory::class);
        $hosts = $index->getIndexSettings()->getHosts();
        $expectedHost = $_ENV['ELASTICSEARCH_HOST'] ?? $_SERVER['ELASTICSEARCH_HOST'] ?? 'localhost:9200';

        $this->assertEquals([$expectedHost], $hosts);
    }

    public function testAccessors(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $settings = new IndexSettings(DummyDocument::class, 'dummy', 'dummy');
        $stopwatch = new Stopwatch();

        self::assertSame($index, $index->setIndexSettings($settings));
        self::assertSame($settings, $index->getIndexSettings());
        self::assertSame($index, $index->setBulkCommitSize(50));
        self::assertSame(50, $index->getBulkCommitSize());
        self::assertSame($index, $index->setStopwatch($stopwatch));
        self::assertSame($stopwatch, $index->getStopwatch());
        self::assertInstanceOf(Converter::class, $index->getConverter());
        self::assertNotNull($index->getEventDispatcher());
    }

    public function testResetClientBuildsANewClient(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $client = $index->getClient();

        $index->resetClient();

        self::assertNotSame($client, $index->getClient());
    }

    public function testCommitFlushAndRefreshAfterResetClient(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->bulk('index', ['_id' => 7, 'title' => 'baz']);

        $index->resetClient();
        self::assertFalse($index->commit()['errors']);
        $index->resetClient();
        self::assertArrayHasKey('_shards', $index->flush());
        $index->resetClient();
        self::assertArrayHasKey('_shards', $index->refresh());
    }

    public function testFindOfAMissingDocumentThrows(): void
    {
        $this->expectException(Missing404Exception::class);
        $this->expectExceptionMessage('"found":false');

        $this->getIndex(DummyDocument::class)->find(999);
    }

    public function testFindOfAMissingDocumentWithIgnored404ReturnsNull(): void
    {
        self::assertNull($this->getIndex(DummyDocument::class)->find(999, ['client' => ['ignore' => 404]]));
    }

    public function testFindByIds(): void
    {
        $ids = [];
        foreach ($this->getIndex(DummyDocument::class)->findByIds([1, 3]) as $document) {
            $ids[] = $document->id;
        }
        sort($ids);

        self::assertSame(['1', '3'], $ids);
    }

    public function testFindByAppliesTheOrder(): void
    {
        $result = $this->getIndex(DummyDocument::class)->findBy([], ['title.raw' => 'asc'], 1);

        self::assertSame('3', $result->first()->id);
    }

    public function testScrollingSearch(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $search = $index->createSearch()->setSize(1)->setScroll('1m');

        $result = $index->findDocuments($search);
        $ids = [];
        foreach ($result as $document) {
            $ids[] = $document->id;
        }
        sort($ids);

        self::assertTrue($result->isScrollable());
        self::assertSame(['1', '2', '3'], $ids);
        self::assertArrayHasKey('hits', $index->scroll($result->getRaw()['_scroll_id'], '1m'));
    }

    public function testFindRaw(): void
    {
        $index = $this->getIndex(DummyDocument::class);

        $result = $index->findRaw($index->createSearch()->addQuery(new TermQuery('title.raw', 'bar')));

        self::assertInstanceOf(RawIterator::class, $result);
        self::assertSame(1, $result->count());
        self::assertSame(['title' => 'bar'], $result->first()['_source']);
    }

    public function testCounts(): void
    {
        $index = $this->getIndex(DummyDocument::class);

        self::assertSame(3, $index->getIndexDocumentCount());
        self::assertSame(2, $index->count($index->createSearch()->addQuery(new TermQuery('title.raw', 'foo'))));
    }

    public function testRemove(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->bulk('index', ['_id' => 4, '_routing' => 'tenant-1', 'title' => 'baz']);
        $index->commit();

        self::assertSame('deleted', $index->remove(1)['result']);
        self::assertSame('deleted', $index->remove(4, 'tenant-1')['result']);
        $index->refresh();
        self::assertSame(2, $index->getIndexDocumentCount());
    }

    public function testUpdate(): void
    {
        $index = $this->getIndex(DummyDocument::class);

        self::assertSame('updated', $index->update(3, ['title' => 'qux'])['result']);
        self::assertSame('qux', $index->find(3)->title);

        $index->update(3, [], ['source' => "ctx._source.title = 'quux'"]);
        self::assertSame('quux', $index->find(3)->title);
    }

    public function testCommitWithErrors(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->bulk('index', ['_id' => 5, 'number' => 'not-a-number']);

        try {
            $index->commit();
            self::fail('Expected a BulkWithErrorsException.');
        } catch (BulkWithErrorsException $exception) {
            self::assertTrue($exception->getResponse()['errors']);
            self::assertSame(400, $exception->getResponse()['items'][0]['index']['status']);
        }
    }

    /**
     * @dataProvider commitModeProvider
     */
    public function testCommitModes(string $commitMode): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $index->bulk('index', ['_id' => 6, 'title' => 'baz']);

        self::assertFalse($index->commit($commitMode)['errors']);
        $index->refresh();
        self::assertSame('baz', $index->find(6)->title);
    }

    public static function commitModeProvider(): \Generator
    {
        yield 'Flush' => ['commitMode' => 'flush'];
        yield 'Synced flush' => ['commitMode' => 'flush_synced'];
    }

    public function testFlushAndClearCache(): void
    {
        $index = $this->getIndex(DummyDocument::class);

        self::assertArrayHasKey('_shards', $index->flush());
        self::assertArrayHasKey('_shards', $index->clearElasticIndexCache());
    }

    public function testDropAndCreateIndexIgnoresDropFailures(): void
    {
        $requests = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            Events::POST_CLIENT_CREATE,
            function (PostCreateClientEvent $event) use (&$requests): void {
                $event->getClient()->setHandler(function (array $request) use (&$requests): CompletedFutureArray {
                    $requests[] = $request['http_method'] . ' ' . $request['uri'];

                    return $this->serverErrorResponse();
                });
            }
        );
        $index = new IndexService(
            DummyDocument::class,
            new Converter(),
            $dispatcher,
            new IndexSettings(DummyDocument::class, 'dummy', 'dummy', [], ['mock-handler:9200'])
        );

        try {
            $index->dropAndCreateIndex();
            self::fail('Expected a ServerErrorResponseException.');
        } catch (ServerErrorResponseException) {
            self::assertSame(['HEAD /dummy', 'PUT /dummy'], $requests);
        }
    }

    private function serverErrorResponse(): CompletedFutureArray
    {
        $body = fopen('php://memory', 'r+');
        fwrite($body, '{"error":"boom"}');
        rewind($body);

        return new CompletedFutureArray([
            'status' => 500,
            'reason' => 'Internal Server Error',
            'headers' => ['Content-Type' => ['application/json']],
            'body' => $body,
            'effective_url' => 'http://mock-handler:9200/dummy',
            'transfer_stats' => ['total_time' => 0],
        ]);
    }
}
