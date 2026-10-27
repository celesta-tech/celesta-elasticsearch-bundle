<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Tests\Unit\Mapping\Fixture\Parser;

use ONGR\ElasticsearchBundle\Annotation as ES;

/**
 * @ES\Index()
 */
class AccessorDocument extends ParentDocument
{
    /**
     * @ES\Version()
     */
    private $version;

    /**
     * @ES\Property(type="keyword")
     */
    private $status;

    /**
     * @ES\Property(type="boolean")
     */
    private $enabled;

    /**
     * @ES\Property(type="keyword")
     */
    private $first_name;

    /**
     * @ES\Property(type="boolean")
     */
    private $vip_member;

    public function status()
    {
        return $this->status;
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled($enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function setFirstName($firstName): void
    {
        $this->first_name = $firstName;
    }

    public function isVipMember()
    {
        return $this->vip_member;
    }

    public function setVipMember($vipMember): void
    {
        $this->vip_member = $vipMember;
    }
}
