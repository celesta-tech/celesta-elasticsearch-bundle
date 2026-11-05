<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Profiler\Handler;

use Monolog\Logger;
use ONGR\ElasticsearchBundle\Profiler\Handler\CollectionHandler;
use PHPUnit\Framework\TestCase;

class CollectionHandlerTest extends TestCase
{
    public function testCollectsAndClearsRecords(): void
    {
        $handler = new CollectionHandler();
        $logger = new Logger('ongr');
        $logger->pushHandler($handler);

        $logger->info('query', ['duration' => 0.5]);

        $records = $handler->getRecords();
        self::assertCount(1, $records);
        self::assertSame('query', $records[0]['message']);
        self::assertSame(['duration' => 0.5], $records[0]['context']);

        $handler->clearRecords();
        self::assertSame([], $handler->getRecords());
    }
}
