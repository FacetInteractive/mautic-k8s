<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\QueueBundle\Helper;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * Class RabbitMqProducer.
 */
class RabbitMqProducer extends Producer
{
    /**
     * @param string $queue
     */
    public function setQueue($queue)
    {
        if ($queue === $this->queueOptions['name']) {
            return;
        }

        $this->queueOptions['name']        = $queue;
        $this->queueOptions['auto_delete'] = false;
        $this->queueOptions['durable']     = true;
        $this->queueDeclared               = false;
        $this->setupFabric();
    }
}
