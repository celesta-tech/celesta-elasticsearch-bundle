<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Annotation;

use ONGR\ElasticsearchBundle\Annotation\Routing;
use PHPUnit\Framework\TestCase;

class RoutingTest extends TestCase
{
    public function testName(): void
    {
        self::assertSame('_routing', (new Routing())->getName());
    }
}
