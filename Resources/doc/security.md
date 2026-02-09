---
title: Authentication & fine-grained access (API keys)
type: elasticsearch-bundle
order: 50
---

This bundle builds the Elasticsearch PHP client (`elasticsearch/elasticsearch`) and exposes an extension point
to customize the `ClientBuilder` via the `ongr.es.event.post_client_create` event.

## Fine-grained access control (FGAC) in Elasticsearch

Elasticsearch enforces "fine-grained access" primarily through:

- **API keys** with scoped privileges (recommended for apps)
- **Index privileges** (limit access to specific indices/patterns)
- Optional **Document Level Security (DLS)** and **Field Level Security (FLS)** (depending on your Elasticsearch features/licensing)

Relevant Elastic docs (7.17):

- Create API key API: https://www.elastic.co/guide/en/elasticsearch/reference/7.17/security-api-create-api-key.html
- Grant API key API: https://www.elastic.co/guide/en/elasticsearch/reference/7.17/security-api-grant-api-key.html
- Field & document level security: https://www.elastic.co/guide/en/elasticsearch/reference/7.17/field-and-document-access-control.html

## Provide an API key to the client

In your Symfony application, register an event subscriber that listens to `ongr.es.event.post_client_create`
and calls `setApiKey()` on the client builder.

```php
<?php

namespace App\EventSubscriber;

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
```

## Create a scoped API key (example)

When you create an API key, you can include `role_descriptors` to restrict privileges (e.g. to `tenant-123-*` indices).
Elasticsearch will then enforce these privileges for all requests made with that key.

Example request (simplified):

```json
{
  "name": "tenant-123",
  "role_descriptors": {
    "tenant_123_role": {
      "cluster": ["monitor"],
      "indices": [
        {
          "names": ["tenant-123-*"],
          "privileges": ["read", "write"]
        }
      ]
    }
  }
}
```

## Notes about DLS/FLS

Elastic notes that DLS/FLS is intended to operate with **read-only privileged accounts**.
Be careful when combining multiple roles, as it can widen access if any role is less restrictive.

