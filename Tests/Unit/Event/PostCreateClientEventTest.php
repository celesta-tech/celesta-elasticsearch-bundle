<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Event;

use Elasticsearch\ClientBuilder;
use ONGR\ElasticsearchBundle\Event\PostCreateClientEvent;
use PHPUnit\Framework\TestCase;

class PostCreateClientEventTest extends TestCase
{
    public function testGetters(): void
    {
        $client = ClientBuilder::create();
        $event = new PostCreateClientEvent('App\Document\Player', $client);

        self::assertSame('App\Document\Player', $event->getNamespace());
        self::assertSame($client, $event->getClient());
    }

    public function testSetters(): void
    {
        $client = ClientBuilder::create();
        $event = new PostCreateClientEvent('App\Document\Player', ClientBuilder::create());

        $event->setNamespace('App\Document\Team');
        $event->setClient($client);

        self::assertSame('App\Document\Team', $event->getNamespace());
        self::assertSame($client, $event->getClient());
    }
}
