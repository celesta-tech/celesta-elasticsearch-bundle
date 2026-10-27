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

use Generator;
use ONGR\ElasticsearchBundle\Mapping\Converter;
use ONGR\ElasticsearchBundle\Result\ObjectIterator;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Address;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Profile;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    public function testConvertArrayToDocumentRejectsAnUnknownClass(): void
    {
        $this->expectExceptionMessage('Cannot convert array to object of class `App\Document\Missing`.');

        (new Converter())->convertArrayToDocument('App\Document\Missing', []);
    }

    public function testConvertDocumentToArrayRejectsAnUnknownClass(): void
    {
        $this->expectExceptionMessage(sprintf('Cannot convert object of class `%s` to array.', Address::class));

        (new Converter())->convertDocumentToArray(new Address());
    }

    /**
     * @dataProvider embeddedObjectProvider
     */
    public function testConvertDocumentToArrayWithASingleEmbeddedObject(?Address $address, ?array $expected): void
    {
        $profile = new Profile();
        $profile->setAddress($address);

        self::assertSame(['address' => $expected], $this->converter()->convertDocumentToArray($profile));
    }

    public static function embeddedObjectProvider(): Generator
    {
        $address = new Address();
        $address->city = 'Lisbon';

        yield 'Set' => [
            'address' => $address,
            'expected' => ['city' => 'Lisbon'],
        ];

        yield 'Not set' => [
            'address' => null,
            'expected' => null,
        ];
    }

    public function testConvertArrayToDocumentWithEmbeddedObjects(): void
    {
        $profile = $this->converter(withTags: true)->convertArrayToDocument(Profile::class, [
            'address' => ['city' => 'Lisbon'],
            'tags' => [['city' => 'Porto'], ['city' => 'Faro']],
            'unmapped' => 'ignored',
        ]);

        self::assertInstanceOf(Profile::class, $profile);
        self::assertNotNull($profile->getAddress());
        self::assertSame('Lisbon', $profile->getAddress()->city);
        self::assertInstanceOf(ObjectIterator::class, $profile->tags);
        self::assertSame(['Porto', 'Faro'], $profile->tags->map(fn(Address $tag) => $tag->city)->toArray());
    }

    private function converter(bool $withTags = false): Converter
    {
        $city = [
            'city' => $this->field(['name' => 'city', 'public' => true]),
        ];
        $metadata = [
            'address' => $this->field([
                'name' => 'address',
                'getter' => 'getAddress',
                'setter' => 'setAddress',
                'embeded' => true,
                'singular' => true,
                'class' => Address::class,
                'sub_properties' => $city,
            ]),
        ];
        if ($withTags) {
            $metadata['tags'] = $this->field([
                'name' => 'tags',
                'public' => true,
                'embeded' => true,
                'singular' => false,
                'class' => Address::class,
                'sub_properties' => $city,
            ]);
        }

        $converter = new Converter();
        $converter->addClassMetadata(Profile::class, $metadata);

        return $converter;
    }

    private function field(array $overrides): array
    {
        return array_merge([
            'name' => null,
            'type' => 'keyword',
            'getter' => null,
            'setter' => null,
            'public' => false,
            'identifier' => false,
            'embeded' => false,
            'singular' => true,
            'class' => null,
            'sub_properties' => [],
        ], $overrides);
    }
}
