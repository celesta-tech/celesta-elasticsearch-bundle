<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping;

use ONGR\ElasticsearchBundle\Mapping\IndexSettings;
use PHPUnit\Framework\TestCase;

class IndexSettingsTest extends TestCase
{
    public function testGetters(): void
    {
        $settings = new IndexSettings(
            'App\Document\Player',
            'players',
            'players_alias',
            ['settings' => ['number_of_shards' => 1]],
            ['localhost:9200'],
            true
        );

        self::assertSame('App\Document\Player', $settings->getNamespace());
        self::assertSame('players', $settings->getIndexName());
        self::assertSame('players_alias', $settings->getAlias());
        self::assertSame(['settings' => ['number_of_shards' => 1]], $settings->getIndexMetadata());
        self::assertSame(['localhost:9200'], $settings->getHosts());
        self::assertTrue($settings->isDefaultIndex());
    }

    public function testDefaults(): void
    {
        $settings = new IndexSettings('App\Document\Player', 'players', 'players_alias');

        self::assertSame([], $settings->getIndexMetadata());
        self::assertSame([], $settings->getHosts());
        self::assertFalse($settings->isDefaultIndex());
    }

    public function testSetters(): void
    {
        $settings = new IndexSettings('App\Document\Player', 'players', 'players_alias');

        $settings->setNamespace('App\Document\Team')
            ->setIndexName('teams')
            ->setAlias('teams_alias')
            ->setIndexMetadata(['mappings' => []])
            ->setHosts(['es:9200'])
            ->setDefaultIndex(true);

        self::assertSame('App\Document\Team', $settings->getNamespace());
        self::assertSame('teams', $settings->getIndexName());
        self::assertSame('teams_alias', $settings->getAlias());
        self::assertSame(['mappings' => []], $settings->getIndexMetadata());
        self::assertSame(['es:9200'], $settings->getHosts());
        self::assertTrue($settings->isDefaultIndex());
    }
}
