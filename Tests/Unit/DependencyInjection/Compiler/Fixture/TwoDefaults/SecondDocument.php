<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler\Fixture\TwoDefaults;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index(alias="second", default=true)
 */
class SecondDocument
{
    /**
     * @ES\Property(type="keyword")
     */
    public $title;
}
