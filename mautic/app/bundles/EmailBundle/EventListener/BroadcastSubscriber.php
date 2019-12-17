<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class BroadcastSubscriber.
 */
class BroadcastSubscriber implements EventSubscriberInterface
{
    /**
     * @var EmailModel
     */
    private $model;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * BroadcastSubscriber constructor.
     *
     * @param EmailModel $emailModel
     */
    public function __construct(EmailModel $emailModel, EntityManager $em, TranslatorInterface $translator)
    {
        $this->model      = $emailModel;
        $this->em         = $em;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::CHANNEL_BROADCAST => ['onBroadcast', 0],
        ];
    }

    /**
     * @param ChannelBroadcastEvent $event
     */
    public function onBroadcast(ChannelBroadcastEvent $event)
    {
        if (!$event->checkContext('email')) {
            return;
        }

        // Get list of published broadcasts or broadcast if there is only a single ID
        $id     = $event->getId();
        $emails = $this->model->getRepository()->getPublishedBroadcasts($id);

        $output = $event->getOutput();

        /** @var Email $email */
        while (($email = $emails->next()) !== false) {
            list($sentCount, $failedCount, $ignore) = $this->model->sendEmailToLists($email[0], null, 100, true, $output);

            $event->setResults($this->translator->trans('mautic.email.email').': '.$email[0]->getName(), $sentCount, $failedCount);
            $this->em->detach($email[0]);
        }
    }
}
