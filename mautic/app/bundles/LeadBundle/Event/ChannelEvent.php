<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

@trigger_error('\Mautic\LeadBundle\Event\ChannelEvent was deprecated in 2.4 and to be removed in 3.0 Use \Mautic\ChannelBundle\Event\ChannelEvent instead', E_USER_DEPRECATED);

/**
 * Class ChannelEvent.
 *
 * @deprecated 2.4 to be removed in 3.0; use \Mautic\ChannelBundle\Event\ChannelEvent instead
 */
class ChannelEvent extends \Mautic\ChannelBundle\Event\ChannelEvent
{
}
