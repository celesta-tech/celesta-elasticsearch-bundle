<?php
/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Mapping;

class IndexSettings
{
    public function __construct(private string $namespace, private string $indexName, private string $alias, private array $indexMetadata = [], private array $hosts = [], private bool $defaultIndex = false)
    {
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setNamespace($namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function setIndexName($indexName): self
    {
        $this->indexName = $indexName;
        return $this;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias($alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function getIndexMetadata()
    {
        return $this->indexMetadata;
    }

    public function setIndexMetadata($indexMetadata): self
    {
        $this->indexMetadata = $indexMetadata;
        return $this;
    }

    public function getHosts()
    {
        return $this->hosts;
    }

    public function setHosts($hosts): self
    {
        $this->hosts = $hosts;
        return $this;
    }

    public function isDefaultIndex(): bool
    {
        return $this->defaultIndex;
    }

    public function setDefaultIndex(bool $defaultIndex): self
    {
        $this->defaultIndex = $defaultIndex;
        return $this;
    }
}
