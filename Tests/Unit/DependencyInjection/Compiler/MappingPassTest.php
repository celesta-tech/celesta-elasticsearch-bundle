<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\Cache;
use ONGR\ElasticsearchBundle\DependencyInjection\Compiler\MappingPass;
use ONGR\ElasticsearchBundle\DependencyInjection\Configuration;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler\Fixture\ZeroReplicas\ZeroReplicasDocument;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MappingPassTest extends TestCase
{
    private const string FIXTURE_DIR = '/Tests/Unit/DependencyInjection/Compiler/Fixture';

    public function testZeroSettingsAreKept(): void
    {
        $container = $this->container(self::FIXTURE_DIR . '/ZeroReplicas');

        (new MappingPass())->process($container);

        $indexSettings = $container->getDefinition(ZeroReplicasDocument::class)->getArgument(3);
        self::assertSame(
            ['number_of_replicas' => 0, 'number_of_shards' => 1],
            $indexSettings->getArgument(3)['settings']
        );
    }

    public function testOnlyOneIndexCanBeTheDefault(): void
    {
        $this->expectException(\RuntimeException::class);
        // The directory iterator's file order depends on the filesystem, so either index can come first.
        $this->expectExceptionMessageMatches(
            '/^Only one index can be set as default\. We found 2 indexes as default ones `(first|second)` and `(first|second)`$/'
        );

        (new MappingPass())->process($this->container(self::FIXTURE_DIR . '/TwoDefaults'));
    }

    public function testMissingSourceDirectoryIsSkipped(): void
    {
        $container = $this->container('/Tests/Unit/DependencyInjection/Compiler/Missing');

        (new MappingPass())->process($container);

        self::assertSame([], $container->getParameter(Configuration::ONGR_INDEXES));
        self::assertFalse($container->getParameter(Configuration::ONGR_DEFAULT_INDEX));
    }

    public function testIndexOverridesAndProfilerTracer(): void
    {
        $container = $this->container(
            self::FIXTURE_DIR . '/ZeroReplicas',
            [
                ZeroReplicasDocument::class => [
                    'alias' => 'custom',
                    'hosts' => ['es:9200'],
                    'default' => true,
                    'settings' => ['refresh_interval' => '1s'],
                ],
            ],
            true
        );
        $tracer = $container->setDefinition('ongr.esb.tracer', new Definition(\stdClass::class));

        (new MappingPass())->process($container);

        $indexService = $container->getDefinition(ZeroReplicasDocument::class);
        $indexSettings = $indexService->getArgument(3);
        self::assertSame(['custom' => ZeroReplicasDocument::class], $container->getParameter(Configuration::ONGR_INDEXES));
        self::assertSame('custom', $indexSettings->getArgument(1));
        self::assertSame(['es:9200'], $indexSettings->getArgument(4));
        self::assertTrue($indexSettings->getArgument(5));
        self::assertSame('1s', $indexSettings->getArgument(3)['settings']['refresh_interval']);
        self::assertSame($tracer, $indexService->getArgument(4));
    }

    private function container(string $sourceDir, array $indexesOverride = [], bool $profiler = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 4));
        $container->setParameter(Configuration::ONGR_SOURCE_DIR, [$sourceDir]);
        $container->setParameter(Configuration::ONGR_INDEXES_OVERRIDE, $indexesOverride);
        $container->setParameter(Configuration::ONGR_PROFILER_CONFIG, $profiler);
        $container->set(
            DocumentParser::class,
            new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class))
        );
        $container->setDefinition(Converter::class, new Definition(Converter::class));
        $container->setDefinition('event_dispatcher', new Definition(\stdClass::class));

        return $container;
    }
}
