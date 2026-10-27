<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection;

use Generator;
use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use ONGR\ElasticsearchBundle\DependencyInjection\ONGRElasticsearchExtension;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ONGRElasticsearchExtensionTest extends TestCase
{
    /**
     * @dataProvider loadProvider
     */
    public function testLoad(array $configs, array $expectedParameters): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        (new ONGRElasticsearchExtension())->load($configs, $container);

        self::assertTrue($container->hasDefinition(DocumentParser::class));
        foreach ($expectedParameters as $name => $value) {
            self::assertSame($value, $container->getParameter($name), $name);
        }
    }

    public static function loadProvider(): Generator
    {
        yield 'Defaults follow kernel.debug' => [
            'configs' => [],
            'expectedParameters' => [
                Configuration::ONGR_CACHE_CONFIG => true,
                Configuration::ONGR_PROFILER_CONFIG => true,
                Configuration::ONGR_LOGGER_CONFIG => true,
                Configuration::ONGR_INDEXES_OVERRIDE => [],
                Configuration::ONGR_SOURCE_DIR => ['/src'],
            ],
        ];

        yield 'Explicit values' => [
            'configs' => [[
                'cache' => false,
                'profiler' => false,
                'logger' => false,
                'source_directories' => ['/app/Document'],
                'indexes' => ['App\Document\Player' => ['alias' => 'players']],
            ]],
            'expectedParameters' => [
                Configuration::ONGR_CACHE_CONFIG => false,
                Configuration::ONGR_PROFILER_CONFIG => false,
                Configuration::ONGR_LOGGER_CONFIG => false,
                Configuration::ONGR_INDEXES_OVERRIDE => ['App\Document\Player' => ['alias' => 'players']],
                Configuration::ONGR_SOURCE_DIR => ['/app/Document'],
            ],
        ];
    }
}
