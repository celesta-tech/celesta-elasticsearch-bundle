<?php

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

