<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Model;

use Doctrine\Common\Collections\Criteria;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Joomla\Http\Http;
use Joomla\Http\Response;
use Mautic\ApiBundle\Serializer\Exclusion\PublishDetailsExclusionStrategy;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\EventRepository;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\LogRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Event as Events;
use Mautic\WebhookBundle\Event\WebhookEvent;
use Mautic\WebhookBundle\WebhookEvents;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ReportModel.
 */
class WebhookModel extends FormModel
{
    /**
     *  2 possible types of the processing of the webhooks.
     */
    const COMMAND_PROCESS   = 'command_process';
    const IMMEDIATE_PROCESS = 'immediate_process';

    /**
     * Whet queue mode is turned on.
     *
     * @var string
     */
    protected $queueMode;

    /**
     * Deprecated property, should be 0 by default.
     *
     * @var int
     */
    protected $webhookStart;

    /**
     * How many entities to add into one queued webhook.
     *
     * @var int
     */
    protected $webhookLimit;

    /**
     * How many responses in 1 row can fail until the webhook disables itself.
     *
     * @var int
     */
    protected $disableLimit;

    /**
     * How many seconds will we wait for the response.
     *
     * @var int in seconds
     */
    protected $webhookTimeout;

    /**
     * The key is queue ID, the value is the WebhookQueue object.
     *
     * @var array
     */
    protected $webhookQueueIdList = [];

    /**
     * How many recent log records should be kept.
     *
     * @var int
     */
    protected $logMax;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * Queued events default order by dir
     * Possible values: ['ASC', 'DESC'].
     *
     * @var string
     */
    protected $eventsOrderByDir;

    /**
     * WebhookModel constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param Serializer           $serializer
     * @param NotificationModel    $notificationModel
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        Serializer $serializer,
        NotificationModel $notificationModel
    ) {
        $this->setConfigProps($coreParametersHelper);
        $this->serializer        = $serializer;
        $this->notificationModel = $notificationModel;
    }

    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $params = [])
    {
        if (!$entity instanceof Webhook) {
            throw new MethodNotAllowedHttpException(['Webhook']);
        }

        if (!empty($action)) {
            $params['action'] = $action;
        }

        $params['events'] = $this->getEvents();

        return $formFactory->create('webhook', $entity, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Webhook();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\WebhookBundle\Entity\WebhookRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Webhook');
    }

    /**
     * Gets array of custom events from bundles subscribed MauticWehbhookBundle::WEBHOOK_ON_BUILD.
     *
     * @return mixed
     */
    public function getEvents()
    {
        static $events;

        if (empty($events)) {
            //build them
            $events = [];
            $event  = new Events\WebhookBuilderEvent($this->translator);
            $this->dispatcher->dispatch(WebhookEvents::WEBHOOK_ON_BUILD, $event);
            $events = $event->getEvents();
        }

        return $events;
    }

    /**
     * Get a list of webhooks by matching events.
     *
     * @param string $type string of event type
     *
     * @return array
     */
    public function getEventWebooksByType($type)
    {
        $results = $this->getEventRepository()->getEntitiesByEventType($type);

        return $results;
    }

    /**
     * @param $type
     * @param $payload
     * @param $groups
     */
    public function queueWebhooksByType($type, $payload, array $groups = [])
    {
        return $this->queueWebhooks(
            $this->getEventWebooksByType($type),
            $payload,
            $groups
        );
    }

    /**
     * @param       $webhookEvents
     * @param       $payload
     * @param array $serializationGroups
     */
    public function queueWebhooks($webhookEvents, $payload, array $serializationGroups = [])
    {
        if (!count($webhookEvents) || !is_array($webhookEvents)) {
            return;
        }

        /** @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($webhookEvents as $event) {
            $webhook = $event->getWebhook();
            $queue   = $this->queueWebhook($webhook, $event, $payload, $serializationGroups);

            if (self::COMMAND_PROCESS === $this->queueMode) {
                // Queue to the database to process later
                $this->getQueueRepository()->saveEntity($queue);
            } else {
                // Immediately process
                $this->processWebhook($webhook, $queue);
            }
        }
    }

    /**
     * Creates a WebhookQueue entity, sets the date and returns the created entity.
     *
     * @param Webhook $webhook
     * @param         $event
     * @param         $payload
     * @param array   $serializationGroups
     *
     * @return WebhookQueue
     */
    public function queueWebhook(Webhook $webhook, $event, $payload, array $serializationGroups = [])
    {
        $serializedPayload = $this->serializeData($payload, $serializationGroups);

        $queue = new WebhookQueue();
        $queue->setWebhook($webhook);
        $queue->setDateAdded(new \DateTime());
        $queue->setEvent($event);
        $queue->setPayload($serializedPayload);

        // fire events for when the queues are created
        if ($this->dispatcher->hasListeners(WebhookEvents::WEBHOOK_QUEUE_ON_ADD)) {
            $webhookQueueEvent = $event = new Events\WebhookQueueEvent($queue, $webhook, true);
            $this->dispatcher->dispatch(WebhookEvents::WEBHOOK_QUEUE_ON_ADD, $webhookQueueEvent);
        }

        return $queue;
    }

    /**
     * Execute a list of webhooks to their specified endpoints.
     *
     * @param array|\Doctrine\ORM\Tools\Pagination\Paginator $webhooks
     */
    public function processWebhooks($webhooks)
    {
        foreach ($webhooks as $webhook) {
            $this->processWebhook($webhook);
        }
    }

    /**
     * @param Webhook      $webhook
     * @param WebhookQueue $queue
     *
     * @return bool
     */
    public function processWebhook(Webhook $webhook, WebhookQueue $queue = null)
    {
        // instantiate new http class
        $http = new Http();

        // get the webhook payload
        $payload = $this->getWebhookPayload($webhook, $queue);

        // if there wasn't a payload we can stop here
        if (empty($payload)) {
            return false;
        }

        if (is_array($payload)) {
            $payload = json_encode($payload);
        }

        // Set up custom headers
        $headers = ['Content-Type' => 'application/json'];
        $start   = microtime(true);

        try {
            $response = $http->post($webhook->getWebhookUrl(), $payload, $headers, $this->webhookTimeout);

            // remove successfully processed queues from the Webhook object so they won't get stored again
            foreach ($this->webhookQueueIdList as $id => $queue) {
                $webhook->removeQueue($queue);
            }

            $this->addLog($webhook, $response->code, (microtime(true) - $start), $response->body);

            // throw an error exception if we don't get a 200 back
            if ($response->code >= 300 || $response->code < 200) {
                // The reciever of the webhook is telling us to stop bothering him with our requests by code 410
                if ($response->code == 410) {
                    $this->killWebhook($webhook, 'mautic.webhook.stopped.reason.410');
                }

                throw new \ErrorException($webhook->getWebhookUrl().' returned '.$response->code);
            }
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if ($this->isSick($webhook)) {
                $this->killWebhook($webhook);
                $message .= ' '.$this->translator->trans('mautic.webhook.killed', ['%limit%' => $this->disableLimit]);
            }

            // log any errors but allow the script to keep running
            $this->logger->addError($message);

            // log that the request failed to display it to the user
            $this->addLog($webhook, 'N/A', (microtime(true) - $start), $message);

            return false;
        }

        // Run this on command as well as immediate send because if switched from queue to immediate
        // it can have some rows in the queue which will be send in every webhook forever
        if (!empty($this->webhookQueueIdList)) {
            /** @var \Mautic\WebhookBundle\Entity\WebhookQueueRepository $webhookQueueRepo */
            $webhookQueueRepo = $this->getQueueRepository();

            // delete all the queued items we just processed
            $webhookQueueRepo->deleteQueuesById(array_keys($this->webhookQueueIdList));
            $queueCount = $webhookQueueRepo->getQueueCountByWebhookId($webhook->getId());

            // reset the array to blank so none of the IDs are repeated
            $this->webhookQueueIdList = [];

            // if there are still items in the queue after processing we re-process
            // WARNING: this is recursive
            if ($queueCount > 0) {
                $this->processWebhook($webhook);
            }
        }

        return true;
    }

    /**
     * Look into the history and check if all the responses we care about had failed.
     * But let it run for a while after the user modified it. Lets not aggravate the user.
     *
     * @param Webhook $webhook
     *
     * @return bool
     */
    public function isSick(Webhook $webhook)
    {
        // Do not mess with the user will! (at least not now)
        if ($webhook->wasModifiedRecently()) {
            return false;
        }

        $successRadio = $this->getLogRepository()->getSuccessVsErrorStatusCodeRatio($webhook->getId(), $this->disableLimit);

        // If there are no log rows yet, consider it healthy
        if ($successRadio === null) {
            return false;
        }

        return !$successRadio;
    }

    /**
     * Unpublish the webhook so it will stop emit the requests
     * and notify user about it.
     *
     * @param Webhook $webhook
     */
    public function killWebhook(Webhook $webhook, $reason = 'mautic.webhook.stopped.reason')
    {
        $webhook->setIsPublished(false);
        $this->saveEntity($webhook);

        $this->notificationModel->addNotification(
            $this->translator->trans(
                'mautic.webhook.stopped.details',
                [
                    '%reason%'  => $this->translator->trans($reason),
                    '%webhook%' => '<a href="'.$this->router->generate(
                        'mautic_webhook_action',
                        ['objectAction' => 'view', 'objectId' => $webhook->getId()]
                    ).'" data-toggle="ajax">'.$webhook->getName().'</a>',
                ]
            ),
            'error',
            false,
            $this->translator->trans('mautic.webhook.stopped'),
            null,
            null,
            $this->em->getReference('MauticUserBundle:User', $webhook->getCreatedBy())
        );
    }

    /**
     * Add a log for the webhook response HTTP status and save it.
     *
     * @param Webhook $webhook
     * @param int     $statusCode
     * @param float   $runtime    in seconds
     * @param string  $note
     */
    public function addLog(Webhook $webhook, $statusCode, $runtime, $note = null)
    {
        $log = new Log();

        if ($webhook->getId()) {
            $log->setWebhook($webhook);
            $this->getLogRepository()->removeOldLogs($webhook->getId(), $this->logMax);
        }

        $log->setNote($note);
        $log->setRuntime($runtime);
        $log->setStatusCode($statusCode);
        $log->setDateAdded(new \DateTime());
        $webhook->addLog($log);

        if ($webhook->getId()) {
            $this->saveEntity($webhook);
        }
    }

    /**
     * Get Qeueue Repository.
     *
     * @return WebhookQueueRepository
     */
    public function getQueueRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:WebhookQueue');
    }

    /**
     * @return EventRepository
     */
    public function getEventRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Event');
    }

    /**
     * @return LogRepository
     */
    public function getLogRepository()
    {
        return $this->em->getRepository('MauticWebhookBundle:Log');
    }

    /**
     * Get the payload from the webhook.
     *
     * @param Webhook      $webhook
     * @param WebhookQueue $queue
     *
     * @return array
     */
    public function getWebhookPayload(Webhook $webhook, WebhookQueue $queue = null)
    {
        if ($payload = $webhook->getPayload()) {
            return $payload;
        }

        $payload = [];

        if ($this->queueMode === self::COMMAND_PROCESS) {
            $queuesArray = $this->getWebhookQueues($webhook);
        } else {
            $queuesArray = [isset($queue) ? [$queue] : []];
        }

        /* @var WebhookQueue $queue */
        foreach ($queuesArray as $queues) {
            foreach ($queues as $queue) {
                /** @var \Mautic\WebhookBundle\Entity\Event $event */
                $event = $queue->getEvent();
                $type  = $event->getEventType();

                // create new array level for each unique event type
                if (!isset($payload[$type])) {
                    $payload[$type] = [];
                }

                $queuePayload              = json_decode($queue->getPayload(), true);
                $queuePayload['timestamp'] = $queue->getDateAdded()->format('c');

                // its important to decode the payload form the DB as we re-encode it with the
                $payload[$type][] = $queuePayload;

                // Add to the webhookQueueIdList only if ID exists.
                // That means if it was stored to DB and not sent via immediate send.
                if ($queue->getId()) {
                    $this->webhookQueueIdList[$queue->getId()] = $queue;

                    // Clear the WebhookQueue entity from memory
                    $this->em->detach($queue);
                }
            }
        }

        return $payload;
    }

    /**
     * Get the queues and order by date so we get events.
     *
     * @param Webhook $webhook
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getWebhookQueues(Webhook $webhook)
    {
        /** @var \Mautic\WebhookBundle\Entity\WebhookQueueRepository $queueRepo */
        $queueRepo = $this->getQueueRepository();

        return $queueRepo->getEntities(
            [
                'iterator_mode' => true,
                'start'         => $this->webhookStart,
                'limit'         => $this->webhookLimit,
                'orderBy'       => $queueRepo->getTableAlias().'.dateAdded',
                'orderByDir'    => $this->getEventsOrderbyDir($webhook),
                'filter'        => [
                    'force' => [
                        [
                            'column' => 'IDENTITY('.$queueRepo->getTableAlias().'.webhook)',
                            'expr'   => 'eq',
                            'value'  => $webhook->getId(),
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Returns either Webhook's orderbyDir or the value from configuration as default.
     *
     * @param Webhook|null $webhook
     *
     * @return string
     */
    public function getEventsOrderbyDir(Webhook $webhook = null)
    {
        // Try to get the value from Webhook
        if ($webhook && $orderByDir = $webhook->getEventsOrderbyDir()) {
            return $orderByDir;
        }

        // Use the global config value if it's not set in the Webhook
        return $this->eventsOrderByDir;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, SymfonyEvent $event = null)
    {
        if (!$entity instanceof Webhook) {
            throw new MethodNotAllowedHttpException(['Webhook'], 'Entity must be of class Webhook()');
        }

        switch ($action) {
            case 'pre_save':
                $name = WebhookEvents::WEBHOOK_PRE_SAVE;
                break;
            case 'post_save':
                $name = WebhookEvents::WEBHOOK_POST_SAVE;
                break;
            case 'pre_delete':
                $name = WebhookEvents::WEBHOOK_PRE_DELETE;
                break;
            case 'post_delete':
                $name = WebhookEvents::WEBHOOK_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new WebhookEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }
            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * @param       $payload
     * @param array $groups
     * @param array $customExclusionStrategies
     *
     * @return mixed|string
     */
    public function serializeData($payload, $groups = [], array $customExclusionStrategies = [])
    {
        $context = SerializationContext::create();
        if (!empty($groups)) {
            $context->setGroups($groups);
        }

        //Only include FormEntity properties for the top level entity and not the associated entities
        $context->addExclusionStrategy(
            new PublishDetailsExclusionStrategy()
        );

        foreach ($customExclusionStrategies as $exclusionStrategy) {
            $context->addExclusionStrategy($exclusionStrategy);
        }

        //include null values
        $context->setSerializeNull(true);

        // serialize the data and send it as a payload
        return $this->serializer->serialize($payload, 'json', $context);
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'webhook:webhooks';
    }

    /**
     * Sets all class properties from CoreParametersHelper.
     *
     * @param CoreParametersHelper $coreParametersHelper
     */
    private function setConfigProps(CoreParametersHelper $coreParametersHelper)
    {
        $this->webhookStart     = (int) $coreParametersHelper->getParameter('webhook_start', 0);
        $this->webhookLimit     = (int) $coreParametersHelper->getParameter('webhook_limit', 10);
        $this->disableLimit     = (int) $coreParametersHelper->getParameter('webhook_disable_limit', 100);
        $this->webhookTimeout   = (int) $coreParametersHelper->getParameter('webhook_timeout', 15);
        $this->logMax           = (int) $coreParametersHelper->getParameter('webhook_log_max', 1000);
        $this->queueMode        = $coreParametersHelper->getParameter('queue_mode');
        $this->eventsOrderByDir = $coreParametersHelper->getParameter('events_orderby_dir', Criteria::ASC);
    }
}
