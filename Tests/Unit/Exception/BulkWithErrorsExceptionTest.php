<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Exception;

use ONGR\ElasticsearchBundle\Exception\BulkWithErrorsException;
use PHPUnit\Framework\TestCase;

class BulkWithErrorsExceptionTest extends TestCase
{
    public function testKeepsTheBulkResponse(): void
    {
        $previous = new \RuntimeException('previous');
        $response = ['errors' => true, 'items' => [['index' => ['status' => 400]]]];

        $exception = new BulkWithErrorsException('bulk failed', 7, $previous, $response);

        self::assertSame('bulk failed', $exception->getMessage());
        self::assertSame(7, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame($response, $exception->getResponse());
    }

    public function testResponseDefaultsToEmpty(): void
    {
        self::assertSame([], (new BulkWithErrorsException())->getResponse());
    }
}
