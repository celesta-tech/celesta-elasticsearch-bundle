<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ONGR\ElasticsearchBundle\Exception;

class BulkWithErrorsException extends \Exception
{
    /**
     * {@inheritdoc}
     * @param mixed[] $response
     */
    public function __construct($message = '', $code = 0, ?\Exception $previous = null, protected $response = [])
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }
}
