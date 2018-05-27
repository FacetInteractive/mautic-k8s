<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Event;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\UserBundle\Entity\User;

/**
 * Class StatusChangeEvent.
 */
class StatusChangeEvent extends Event
{
    /**
     * @var \Mautic\UserBundle\Entity\User
     */
    private $user;

    /**
     * StatusChangeEvent constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        parent::__construct();
    }

    /**
     * @return \Mautic\UserBundle\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
