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
use ONGR\ElasticsearchBundle\Service\ImportService;
use ONGR\ElasticsearchBundle\Test\AbstractElasticsearchTestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ImportServiceTest extends AbstractElasticsearchTestCase
{
    public function testRelativeFilenameIsResolvedFromTheWorkingDirectory(): void
    {
        $index = $this->getIndex(DummyDocument::class);
        $directory = sys_get_temp_dir() . '/' . uniqid('cls3893-import-');
        mkdir($directory);
        copy(__DIR__ . '/../../app/data_seed/command_import_9.json', $directory . '/import.json');
        $workingDirectory = getcwd();

        try {
            chdir($directory);
            (new ImportService())->importIndex($index, 'import.json', new BufferedOutput(), ['bulk-size' => 5]);
        } finally {
            chdir($workingDirectory);
            @unlink($directory . '/import.json');
            @rmdir($directory);
        }

        self::assertSame(9, $index->getIndexDocumentCount());
    }
}
