<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Event;

use Elasticsearch\ClientBuilder;
use Symfony\Contracts\EventDispatcher\Event;

class PostCreateClientEvent extends Event
{
    public function __construct(private string $namespace, private ClientBuilder $client)
    {
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getClient(): ClientBuilder
    {
        return $this->client;
    }

    public function setClient(ClientBuilder $client): PostCreateClientEvent
    {
        $this->client = $client;
        return $this;
    }
}
