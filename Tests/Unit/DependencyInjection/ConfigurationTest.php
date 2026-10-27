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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider configurationProvider
     */
    public function testProcessConfiguration(array $configs, array $expected): void
    {
        self::assertSame($expected, (new Processor())->processConfiguration(new Configuration(), $configs));
    }

    public static function configurationProvider(): Generator
    {
        $emptyAnalysis = [
            'tokenizer' => [],
            'filter' => [],
            'analyzer' => [],
            'normalizer' => [],
            'char_filter' => [],
        ];

        yield 'Defaults' => [
            'configs' => [],
            'expected' => [
                'logger' => true,
                'source_directories' => ['/src'],
                'indexes' => [],
                'analysis' => $emptyAnalysis,
            ],
        ];

        yield 'Every option set' => [
            'configs' => [[
                'cache' => false,
                'profiler' => true,
                'logger' => false,
                'source_directories' => ['/app/Document'],
                'indexes' => ['App\Document\Player' => ['alias' => 'players']],
                'analysis' => ['analyzer' => ['lowercase' => ['type' => 'custom', 'tokenizer' => 'standard']]],
            ]],
            'expected' => [
                'cache' => false,
                'profiler' => true,
                'logger' => false,
                'source_directories' => ['/app/Document'],
                'indexes' => ['App\Document\Player' => ['alias' => 'players']],
                'analysis' => [
                    'analyzer' => ['lowercase' => ['type' => 'custom', 'tokenizer' => 'standard']],
                    'tokenizer' => [],
                    'filter' => [],
                    'normalizer' => [],
                    'char_filter' => [],
                ],
            ],
        ];
    }
}
