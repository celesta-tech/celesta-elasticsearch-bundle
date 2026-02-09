<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\EventSubscriber;

use ONGR\ElasticsearchBundle\Event\Events;
use ONGR\ElasticsearchBundle\Event\PostCreateClientEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ElasticsearchApiKeySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $apiKeyId,
        private string $apiKey
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [Events::POST_CLIENT_CREATE => 'onPostClientCreate'];
    }

    public function onPostClientCreate(PostCreateClientEvent $event): void
    {
        $event->getClient()->setApiKey($this->apiKeyId, $this->apiKey);
    }
}

