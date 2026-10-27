<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Service\Json;

use Generator;
use ONGR\ElasticsearchBundle\Service\IndexService;
use ONGR\ElasticsearchBundle\Service\Json\JsonReader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class JsonReaderTest extends TestCase
{
    protected function setUp(): void
    {
        vfsStream::setup('tmp');
    }

    public function testGetIndex(): void
    {
        $index = $this->createMock(IndexService::class);

        self::assertSame($index, (new JsonReader($index, vfsStream::url('tmp/missing.json')))->getIndex());
    }

    /**
     * @dataProvider invalidFileProvider
     */
    public function testCountOfAnInvalidFile(?string $content, string $expectedException, string $expectedMessage): void
    {
        if ($content !== null) {
            file_put_contents(vfsStream::url('tmp/export.json'), $content);
        }

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $this->reader()->count();
    }

    public static function invalidFileProvider(): Generator
    {
        yield 'Missing file' => [
            'content' => null,
            'expectedException' => \LogicException::class,
            'expectedMessage' => 'Can not open file.',
        ];

        yield 'No opening bracket' => [
            'content' => "{\"count\":1},\n",
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Given file does not match expected pattern.',
        ];

        yield 'No count in the metadata' => [
            'content' => "[\n{\"date\":\"2026-10-26\"},\n]",
            'expectedException' => \LogicException::class,
            'expectedMessage' => 'Given file does not contain count of documents.',
        ];
    }

    public function testMetadataIsReadOnce(): void
    {
        file_put_contents(vfsStream::url('tmp/export.json'), "[\n{\"count\":1},\n{\"_id\":\"1\",\"_source\":{}}\n]");
        $reader = $this->reader();

        self::assertSame(1, $reader->count());
        self::assertSame(1, $reader->count());
    }

    public function testFileWithoutClosingBracket(): void
    {
        // A real file: vfsStream reports end-of-file as soon as the last line is read, a real file only on the next read.
        $filename = tempnam(sys_get_temp_dir(), 'cls3893-reader-');
        file_put_contents($filename, "[\n{\"count\":1},\n{\"_id\":\"1\",\"_source\":{}}\n");
        $reader = new JsonReader($this->createMock(IndexService::class), $filename);

        try {
            $ids = [];
            foreach ($reader as $document) {
                $ids[] = $document['_id'];
            }

            self::assertSame(['1'], $ids);
            self::assertNull($reader->current());
        } finally {
            unset($reader);
            unlink($filename);
        }
    }

    private function reader(): JsonReader
    {
        return new JsonReader($this->createMock(IndexService::class), vfsStream::url('tmp/export.json'));
    }
}
