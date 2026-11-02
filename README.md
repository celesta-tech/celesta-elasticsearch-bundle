# Elasticsearch Bundle

[![CI](https://github.com/celesta-tech/celesta-elasticsearch-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/celesta-tech/celesta-elasticsearch-bundle/actions/workflows/ci.yml)

Celesta's fork of [ONGR's Elasticsearch bundle][1] for Symfony, published as `lancasters/elasticsearch-bundle`. It uses the official [elasticsearch-php][2] client.

* Maps document classes to indexes with annotations (`@ES\Index`, `@ES\Property`, `@ES\Embedded`), in a Doctrine-like way.
* Builds queries with the [Elasticsearch DSL][3].
* Iterates over results as documents, arrays or raw hits.
* Console commands to create, drop, export and import indexes, and to clear the cache.
* A profiler panel in the Symfony debug bar with every executed query.

## Requirements

| Package | Version |
| --- | --- |
| PHP | ^8.1 |
| Symfony | 4.4 to 7 |
| Elasticsearch | 7 (`elasticsearch/elasticsearch` ^7.17.2) |

## Documentation

The documentation is in [`Resources/doc/`](Resources/doc/index.md):

* [Index mapping](Resources/doc/mapping.md)
* [Configuration](Resources/doc/configuration.md)
* [Console commands](Resources/doc/commands.md)
* [CRUD actions](Resources/doc/crud.md)
* [Find functions](Resources/doc/find_functions.md)
* [Search](Resources/doc/search.md)
* [Parsing the results](Resources/doc/results_parsing.md)
* [Upgrading](Resources/doc/upgrade.md)
* [Overwriting parts of the bundle](Resources/doc/overwriting_bundle.md)

## Setup the bundle

### Step 1: Install the bundle

The package is on Celesta's private Packagist (`repo.packagist.com/lancastersolutions`).

```bash
composer require lancasters/elasticsearch-bundle "1.*"
```

Enable the bundle in `config/bundles.php`:

```php
<?php
// config/bundles.php

return [
    // ...
    ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
];
```

### (Optional) Step 2: Add configuration

```yaml
# config/packages/ongr_elasticsearch.yaml
ongr_elasticsearch:
    analysis:
        filter:
            edge_ngram_filter: # custom filter name, used in the analyzer below
                type: edge_ngram
                min_gram: 1
                max_gram: 20
        analyzer:
            eNgramAnalyzer: # analyzer name, used in the document field
                type: custom
                tokenizer: standard
                filter:
                    - lowercase
                    - edge_ngram_filter
    indexes:
        App\Document\Product:
            hosts: [elasticsearch:9200] # optional, the default is 127.0.0.1:9200
```

See [configuration](Resources/doc/configuration.md) for every option.

### Step 3: Define your documents

Each document class maps one index. Create the `Product` class for the `products` index:

```php
// src/Document/Product.php

namespace App\Document;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * alias and default are optional.
 * @ES\Index(alias="products", default=true)
 */
class Product
{
    /**
     * @ES\Id()
     */
    public $id;

    /**
     * @ES\Property(type="text", analyzer="eNgramAnalyzer")
     */
    public $title;

    /**
     * @ES\Property(type="float")
     */
    public $price;
}
```

See [mapping](Resources/doc/mapping.md) for more.

### Step 4: Create the index and its mapping

```bash
bin/console ongr:es:index:create
```

The `products` index now exists with the fields of the document. The other commands are in [commands](Resources/doc/commands.md).

## License

MIT, as the original bundle. See [LICENSE](LICENSE).

[1]: https://github.com/ongr-io/ElasticsearchBundle
[2]: https://github.com/elastic/elasticsearch-php
[3]: https://github.com/handcraftedinthealps/ElasticsearchDSL

## Development process

Specifications, plans and implementation follow Specification Driven Development through the engineering-playbook Claude Code plugin (`sdd-spec`, `sdd-split`, `sdd-plan`, `sdd-implement`). The repository's guidelines for it are in [CLAUDE.md](CLAUDE.md).
