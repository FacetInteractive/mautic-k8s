<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class QueueEvent.
 */
class QueueEvent extends CommonEvent
{
    /**
     * @var int|null
     */
    private $messages;

    /**
     * @var array
     */
    private $payload;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var string
     */
    private $queueName;

    /**
     * QueueEvent constructor.
     *
     * @param string   $protocol
     * @param string   $queueName
     * @param array    $payload
     * @param int|null $messages
     */
    public function __construct($protocol, $queueName, array $payload = [], $messages = null)
    {
        $this->messages  = $messages;
        $this->payload   = $payload;
        $this->protocol  = $protocol;
        $this->queueName = $queueName;
    }

    /**
     * @return int|null
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    public function getPayload($returnArray = false)
    {
        return ($returnArray) ? $this->payload : json_encode($this->payload);
    }

    /**
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @param string $protocol
     *
     * @return bool
     */
    public function checkContext($protocol)
    {
        return $protocol == $this->protocol;
    }
}
