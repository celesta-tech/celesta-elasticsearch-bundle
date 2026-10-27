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

use ONGR\App\Document\DummyDocument;
use ONGR\ElasticsearchBundle\Service\ExportService;
use ONGR\ElasticsearchBundle\Service\Json\JsonReader;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Output\BufferedOutput;

class ExportServiceTest extends AbstractElasticsearchTestCase
{
    #[\Override]
    protected function getDataArray(): array
    {
        return [
            DummyDocument::class => [
                ['_id' => 1, 'title' => 'foo'],
                ['_id' => 2, 'title' => 'bar'],
                ['_id' => 3, 'title' => 'baz'],
            ],
        ];
    }

    public function testSplitsTheExportIntoFiles(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        vfsStream::setup('tmp');

        (new ExportService())->exportIndex($index, vfsStream::url('tmp/dummy.json'), 10, new BufferedOutput(), 2);

        $first = new JsonReader($index, vfsStream::url('tmp/dummy.json'));
        $second = new JsonReader($index, vfsStream::url('tmp/dummy_1.json'));
        $ids = [];
        foreach ([$first, $second] as $reader) {
            foreach ($reader as $document) {
                $ids[] = $document['_id'];
            }
        }
        sort($ids);

        self::assertSame(2, $first->count());
        self::assertSame(1, $second->count());
        self::assertSame(['1', '2', '3'], $ids);
    }

    public function testRelativeFilenameIsResolvedFromTheWorkingDirectory(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $directory = sys_get_temp_dir() . '/' . uniqid('cls3893-export-');
        mkdir($directory);
        $workingDirectory = getcwd();

        try {
            chdir($directory);
            (new ExportService())->exportIndex($index, 'dummy.json', 10, new BufferedOutput());

            self::assertSame(3, (new JsonReader($index, $directory . '/dummy.json'))->count());
        } finally {
            chdir($workingDirectory);
            @unlink($directory . '/dummy.json');
            @rmdir($directory);
        }
    }
}
