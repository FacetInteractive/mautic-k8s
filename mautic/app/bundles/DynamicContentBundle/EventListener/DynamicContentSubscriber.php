<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event as Events;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;

/**
 * Class DynamicContentSubscriber.
 */
class DynamicContentSubscriber extends CommonSubscriber
{
    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var PageTokenHelper
     */
    protected $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    protected $assetTokenHelper;

    /**
     * @var FormTokenHelper
     */
    protected $formTokenHelper;

    /**
     * @var FocusTokenHelper
     */
    protected $focusTokenHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * DynamicContentSubscriber constructor.
     *
     * @param TrackableModel   $trackableModel
     * @param PageTokenHelper  $pageTokenHelper
     * @param AssetTokenHelper $assetTokenHelper
     * @param FormTokenHelper  $formTokenHelper
     * @param AuditLogModel    $auditLogModel
     */
    public function __construct(
        TrackableModel $trackableModel,
        PageTokenHelper $pageTokenHelper,
        AssetTokenHelper $assetTokenHelper,
        FormTokenHelper $formTokenHelper,
        FocusTokenHelper $focusTokenHelper,
        AuditLogModel $auditLogModel
    ) {
        $this->trackableModel   = $trackableModel;
        $this->pageTokenHelper  = $pageTokenHelper;
        $this->assetTokenHelper = $assetTokenHelper;
        $this->formTokenHelper  = $formTokenHelper;
        $this->focusTokenHelper = $focusTokenHelper;
        $this->auditLogModel    = $auditLogModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            DynamicContentEvents::POST_SAVE         => ['onPostSave', 0],
            DynamicContentEvents::POST_DELETE       => ['onDelete', 0],
            DynamicContentEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param Events\DynamicContentEvent $event
     */
    public function onPostSave(Events\DynamicContentEvent $event)
    {
        $entity = $event->getDynamicContent();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'   => 'dynamicContent',
                'object'   => 'dynamicContent',
                'objectId' => $entity->getId(),
                'action'   => ($event->isNew()) ? 'create' : 'update',
                'details'  => $details,
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param Events\DynamicContentEvent $event
     */
    public function onDelete(Events\DynamicContentEvent $event)
    {
        $entity = $event->getDynamicContent();
        $log    = [
            'bundle'   => 'dynamicContent',
            'object'   => 'dynamicContent',
            'objectId' => $entity->deletedId,
            'action'   => 'delete',
            'details'  => ['name' => $entity->getName()],
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onTokenReplacement(MauticEvents\TokenReplacementEvent $event)
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($content) {
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough),
                $this->formTokenHelper->findFormTokens($content),
                $this->focusTokenHelper->findFocusTokens($content)
            );

            list($content, $trackables) = $this->trackableModel->parseContentForTrackables(
                $content,
                $tokens,
                'dynamicContent',
                $clickthrough['dynamic_content_id']
            );

            /**
             * @var string
             * @var Trackable $trackable
             */
            foreach ($trackables as $token => $trackable) {
                $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough);
            }

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);

            $event->setContent($content);
        }
    }
}
