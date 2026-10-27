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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\Cache;
use ONGR\App\Document\CollectionNested;
use ONGR\App\Document\DummyDocument;
use ONGR\App\Document\TestDocument;
use ONGR\App\Entity\DummyDocumentInTheEntityDirectory;
use ONGR\ElasticsearchBundle\Mapping\DocumentParser;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Address;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\AccessorDocument;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\MappedTrait;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\NoGetterDocument;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\NoSetterDocument;
use ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\UntypedEmbeddedDocument;
use PHPUnit\Framework\TestCase;

class DocumentParserTest extends TestCase
{
    public function testDocumentParsing()
    {

        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));

        $indexMetadata = $parser->getIndexMetadata(new \ReflectionClass(DummyDocumentInTheEntityDirectory::class));

        $expected = [
            'mappings' => [
                'properties' => [
                    'keyword_field' => [
                        'type' => 'keyword',
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $indexMetadata);
    }

    public function testParsingWithMultiFieldsMapping()
    {
        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));

        $indexMetadata = $parser->getIndexMetadata(new \ReflectionClass(TestDocument::class));

        // Mapping definition for field "title" should be there
        $this->assertNotEmpty($indexMetadata['mappings']['properties']['title']);
        $title_field_def = $indexMetadata['mappings']['properties']['title'];

        // title should have `fields` sub-array
        $this->assertArrayHasKey('fields', $title_field_def);

        // `fields` should look like so:
        $expected = [
            'raw' => ['type' => 'keyword'],
            'increment' => ['type' => 'text', 'analyzer' => 'incrementalAnalyzer'],
            'sorting' => ['type' => 'keyword', 'normalizer' => 'lowercase_normalizer']
        ];

        $this->assertEquals($expected, $title_field_def['fields']);
    }

    public function testGetAnalysisConfig()
    {
        // Global analysis settings used for this test, usually set in the bundle configuration
        // sets custom analyzer, filter, and normalizer
        $config_analysis = [
            'analyzer' => [
                'incrementalAnalyzer' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => [
                        0 => 'lowercase',
                        1 => 'edge_ngram_filter',
                    ],
                ],
                'unusedAnalyzer' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard'
                ]
            ],
            'filter' => [
                'edge_ngram_filter' => [
                    'type' => 'edge_ngram',
                    'min_gram' => 1,
                    'max_gram' => 20,
                ],
            ],
            'normalizer' => [
                'lowercase_normalizer' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ],
                'unused_normalizer' => [
                    'type' => 'custom'
                ]
            ]
        ];

        $parser = new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class), $config_analysis);
        $analysis = $parser->getAnalysisConfig(new \ReflectionClass(TestDocument::class));

        $expected = [
            'analyzer' => [
                'incrementalAnalyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            0 => 'lowercase',
                            1 => 'edge_ngram_filter',
                        ],
                    ],
                ],
                // 'unusedAnalyzer' must not be there because it is not used
            'filter' => [
                'edge_ngram_filter' => [
                    'type' => 'edge_ngram',
                    'min_gram' => 1,
                    'max_gram' => 20,
                ],
            ],
            'normalizer' => [
                'lowercase_normalizer' => [
                    'type' => 'custom',
                    'filter' => ['lowercase']
                ]
                // 'unused_normalizer' must not be there
            ]
        ];

        $this->assertEquals($expected, $analysis);
    }

    public function testTraitsHaveNoMetadata(): void
    {
        $parser = $this->parser();
        $trait = new \ReflectionClass(MappedTrait::class);

        self::assertSame([], $parser->getIndexMetadata($trait));
        self::assertSame([], $parser->getPropertyMetadata($trait));
    }

    public function testPropertyMetadataGuessesAccessorsAndIncludesParentProperties(): void
    {
        $metadata = $this->parser()->getPropertyMetadata(new \ReflectionClass(AccessorDocument::class));

        self::assertSame(
            [
                'status' => ['status', 'setStatus'],
                'enabled' => ['isEnabled', 'setEnabled'],
                'first_name' => ['getfirstName', 'setfirstName'],
                'vip_member' => ['isvipMember', 'setvipMember'],
                'inherited' => [null, null],
            ],
            array_map(fn(array $field) => [$field['getter'], $field['setter']], $metadata)
        );
    }

    public function testIndexMetadataMapsOnlyPropertyAnnotations(): void
    {
        $metadata = $this->parser()->getIndexMetadata(new \ReflectionClass(AccessorDocument::class));

        self::assertSame(
            ['status', 'enabled', 'first_name', 'vip_member', 'inherited'],
            array_keys($metadata['mappings']['properties'])
        );
    }

    /**
     * @dataProvider missingAccessorProvider
     */
    public function testPropertyMetadataRequiresAccessorsForPrivateProperties(string $class, string $expectedMessage): void
    {
        $this->expectExceptionMessage($expectedMessage);

        $this->parser()->getPropertyMetadata(new \ReflectionClass($class));
    }

    public static function missingAccessorProvider(): \Generator
    {
        yield 'No getter' => [
            'class' => NoGetterDocument::class,
            'expectedMessage' => 'Could not determine a getter for `lastName` of class `ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\NoGetterDocument`',
        ];

        yield 'No setter' => [
            'class' => NoSetterDocument::class,
            'expectedMessage' => 'Could not determine a setter for `lastName` of class `ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser\NoSetterDocument`',
        ];
    }

    public function testEmbeddedClassNeedsAnObjectOrNestedType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(Address::class . ' must be used @ObjectType or @NestedType as embeddable object.');

        $this->parser()->getIndexMetadata(new \ReflectionClass(UntypedEmbeddedDocument::class));
    }

    public function testClassWithoutIndexHasNoIndexMetadata(): void
    {
        self::assertSame([], $this->parser()->getIndexMetadata(new \ReflectionClass(Address::class)));
    }

    public function testEmbeddedMapping(): void
    {
        $properties = $this->parser()->getIndexMetadata(new \ReflectionClass(DummyDocument::class))['mappings']['properties'];

        self::assertSame('nested', $properties['nested_collection']['type']);
        self::assertSame(['key', 'value'], array_keys($properties['nested_collection']['properties']));
        self::assertSame('object', $properties['object_collection']['type']);
    }

    public function testEmbeddedAndIdentifierPropertyMetadata(): void
    {
        $metadata = $this->parser()->getPropertyMetadata(new \ReflectionClass(DummyDocument::class));

        self::assertTrue($metadata['_id']['identifier']);
        self::assertSame('getPrivateField', $metadata['private']['getter']);
        self::assertTrue($metadata['nested_collection']['embeded']);
        self::assertFalse($metadata['nested_collection']['singular']);
        self::assertSame(CollectionNested::class, $metadata['nested_collection']['class']);
        self::assertSame(['key', 'value'], array_keys($metadata['nested_collection']['sub_properties']));
    }

    private function parser(): DocumentParser
    {
        return new DocumentParser(new AnnotationReader(), $this->createMock(Cache::class));
    }
}
