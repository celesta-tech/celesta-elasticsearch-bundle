# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

The repo has no Docker setup of its own. Functional tests need a reachable Elasticsearch 7.10 (CI starts one as a
service container) at `ELASTICSEARCH_HOST`; without it they error out.

```bash
# Elasticsearch + PHP 8.4 with PCOV, on one network
docker network create esb
docker run -d --rm --name esb-es --network esb -e discovery.type=single-node -e xpack.security.enabled=false \
  -e ES_JAVA_OPTS="-Xms512m -Xmx512m" docker.elastic.co/elasticsearch/elasticsearch:7.10.2
docker run -d --name esb-php --network esb -v "$PWD":/app -w /app -e ELASTICSEARCH_HOST=esb-es:9200 php:8.4-cli sleep infinity
# inside esb-php: install git/unzip, `pecl install pcov && docker-php-ext-enable pcov`, Composer, then
composer install --ignore-platform-reqs

# Run all tests (Unit + Functional)
vendor/bin/phpunit

# Run a single test file (PHPUnit 9 takes one path only)
vendor/bin/phpunit Tests/Unit/Mapping/ConverterTest.php

# Coverage (CI enforces 100% with pcov)
php -d pcov.enabled=1 vendor/bin/phpunit --coverage-text
```

CI (`.github/workflows/ci.yml`) runs the tests on PHP 8.4 with Elasticsearch 7.10.2 on an approved review and on every push to `master`. A merged pull request on `master` creates the next release once the tests pass (`.github/workflows/release.yml`, `v1.3` → `v1.4`). segment is the only consumer (`1.*` in its `composer.json`) and gets a release only when it updates its `composer.lock` (`v1.1` on 2026-10-30).

## Testing notes

- PHPUnit 9 (`phpunit.xml.dist`, deprecated schema warning is expected). Warnings and notices are converted to
  exceptions, so a PHP warning in library code fails the test that reaches it.
- Functional tests extend `Test/AbstractElasticsearchTestCase`: `getIndex()` creates the index, fills it from
  `getDataArray()` and `tearDown()` drops it. The test kernel lives in `Tests/app` and maps the documents in
  `Tests/app/src` (`config_test.yml`).
- The test kernel's container is cached in `var/cache/test`. Once it exists, `Configuration`,
  `ONGRElasticsearchExtension` and `MappingPass` no longer run during functional tests; their unit tests cover them
  directly. `./var` and `./Test` are excluded from coverage.
- vfsStream reports end-of-file as soon as the last line is read, a real file only on the next read. `JsonReader`
  tests that depend on `feof()` use a real temporary file.

## Architecture

Symfony bundle (`ONGR\ElasticsearchBundle\` namespace, PSR-4 from the repo root; `Tests/` is excluded from the
classmap). It is a fork of ONGR's bundle, published as `lancasters/elasticsearch-bundle`.

- **Mapping:** document classes carry `@ES\Index` / `@ES\Property` / `@ES\Embedded` annotations. `DocumentParser`
  reads them into index metadata (mappings, settings, analysis) and property metadata (getter/setter, embedded
  classes); `Converter` uses the property metadata to turn documents into arrays and back.
- **Wiring:** `MappingPass` scans `source_directories`, and for every `@ES\Index` class registers a public
  `IndexService` service whose id is the document class. `indexes` in the config overrides alias, hosts, default and
  settings per class. `MappingPass` throws when two classes carry `@ES\Index(default=true)`; a `default` set under
  `indexes` is not part of that check.
- **`IndexService`:** one per index: search (`findBy`, `findDocuments`, `findArray`, `findRaw`, scrolling), counts,
  `update`/`remove`, and bulk writes. `bulk()` / `persist()` only queue operations; nothing reaches Elasticsearch until
  `commit()`, which throws `BulkWithErrorsException` when any item fails. `find()` throws
  `Missing404Exception` for a missing ID (segment's tests rely on it), or returns null when the request ignores 404.
- **Results:** `DocumentIterator` (documents), `ArrayIterator` (arrays, `ArrayAccess`) and `RawIterator` (raw hits)
  share `AbstractResultsIterator`, which pages through scroll results and clears the scroll on destruct.
- **Commands:** index create/drop/export/import and cache clear (`Command/`), backed by `ExportService` /
  `ImportService` and the `Json` reader/writer.

## Branches and commits

- One sub-task per branch, named after the sub-task key: `CLS-1234`.
- A commit message reads `CLS-1234: <what changed>`, in the imperative; a hotfix without a ticket reads `hotfix: <what changed>`. The pull request title starts with the same key, and its description links the sub-task.
- `sdd-plan` and `sdd-implement` commit and push on the sub-task branch after a yes, as their steps say. Otherwise, stage the changes and leave the commit to the developer; commit or push only when asked.

## Specification and plan

The specification of a change lives in its parent Jira ticket (project CLS). The plan of a sub-task lives in this repository as `.sdd/_plans/<KEY>.md`, on the branch named after the sub-task, and is reviewed in that branch's pull request.
