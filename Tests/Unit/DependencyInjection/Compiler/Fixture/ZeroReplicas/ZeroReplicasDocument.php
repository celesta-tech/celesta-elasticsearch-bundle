<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler\Fixture\ZeroReplicas;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index(alias="zero_replicas", numberOfReplicas=0, numberOfShards=1)
 */
class ZeroReplicasDocument
{
    /**
     * @ES\Property(type="keyword")
     */
    public $title;
}
