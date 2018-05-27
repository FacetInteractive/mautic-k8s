<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Webhook.
 */
class Webhook extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $webhookUrl;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $events;

    /**
     * @var ArrayCollection
     */
    private $queues;

    /**
     * @var ArrayCollection
     */
    private $logs;

    /**
     * @var array
     */
    private $removedEvents = [];

    /**
     * @var
     */
    private $payload;

    /**
     * Holds a simplified array of events, just an array of event types.
     * It's used for API serializaiton.
     *
     * @var array
     */
    private $triggers = [];

    /**
     * ASC or DESC order for fetching order of the events when queue mode is on.
     * Null means use the global default.
     *
     * @var string
     */
    private $eventsOrderbyDir;

    /*
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->queues = new ArrayCollection();
        $this->logs   = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhooks')
            ->setCustomRepositoryClass(WebhookRepository::class);

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->createOneToMany('events', 'Event')
            ->orphanRemoval()
            ->setIndexBy('event_type')
            ->mappedBy('webhook')
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->build();

        $builder->createOneToMany('queues', 'WebhookQueue')
            ->mappedBy('webhook')
            ->fetchExtraLazy()
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->build();

        $builder->createOneToMany('logs', 'Log')->setOrderBy(['dateAdded' => Criteria::DESC])
            ->fetchExtraLazy()
            ->mappedBy('webhook')
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->build();

        $builder->createField('webhookUrl', Type::STRING)
            ->columnName('webhook_url')
            ->length(255)
            ->build();

        $builder->addNullableField('eventsOrderbyDir', Type::STRING, 'events_orderby_dir');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('hook')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'description',
                    'webhookUrl',
                    'eventsOrderbyDir',
                    'category',
                    'triggers',
                ]
            )
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'webhookUrl',
            new Assert\Url(
                [
                    'message' => 'mautic.core.valid_url_required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'webhookUrl',
            new Assert\NotBlank(
                [
                    'message' => 'mautic.core.valid_url_required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'eventsOrderbyDir',
            new Assert\Choice(
                [
                    null,
                    Criteria::ASC,
                    Criteria::DESC,
                ]
            )
        );
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Webhook
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Webhook
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set webhookUrl.
     *
     * @param string $webhookUrl
     *
     * @return Webhook
     */
    public function setWebhookUrl($webhookUrl)
    {
        $this->isChanged('webhookUrl', $webhookUrl);
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    /**
     * Get webhookUrl.
     *
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * Set category.
     *
     * @param Category $category
     *
     * @return Webhook
     */
    public function setCategory(Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param $events
     *
     * @return $this
     */
    public function setEvents($events)
    {
        $this->isChanged('events', $events);

        $this->events = $events;
        /** @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($events as $event) {
            $event->setWebhook($this);
        }

        return $this;
    }

    /**
     * This builds a simple array with subscribed events.
     *
     * @return array
     */
    public function buildTriggers()
    {
        foreach ($this->events as $event) {
            $this->triggers[] = $event->getEventType();
        }
    }

    /**
     * Takes the array of triggers and builds events from them if they don't exist already.
     *
     * @param array $triggers
     */
    public function setTriggers(array $triggers)
    {
        foreach ($triggers as $key) {
            $this->addTrigger($key);
        }
    }

    /**
     * Takes a trigger (event type) and builds the Event object form it if it doesn't exist already.
     *
     * @param string $key
     *
     * @return bool
     */
    public function addTrigger($key)
    {
        if ($this->eventExists($key)) {
            return false;
        }

        $event = new Event();
        $event->setEventType($key);
        $event->setWebhook($this);
        $this->addEvent($event);

        return true;
    }

    /**
     * Check if an event exists comared to its type.
     *
     * @param string $key
     *
     * @return bool
     */
    public function eventExists($key)
    {
        foreach ($this->events as $event) {
            if ($event->getEventType() === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Mautic\WebhookBundle\Entity\Event $event
     *
     * @return $this
     */
    public function addEvent(Event $event)
    {
        $this->isChanged('events', $event);

        $this->events[] = $event;

        return $this;
    }

    /**
     * @param \Mautic\WebhookBundle\Entity\Event $event
     *
     * @return $this
     */
    public function removeEvent(Event $event)
    {
        $this->isChanged('events', $event);
        $this->removedEvents[] = $event;
        $this->events->removeElement($event);

        return $this;
    }

    /**
     * @param string $eventsOrderbyDir
     */
    public function setEventsOrderbyDir($eventsOrderbyDir)
    {
        $this->isChanged('eventsOrderbyDir', $eventsOrderbyDir);
        $this->eventsOrderbyDir = $eventsOrderbyDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventsOrderbyDir()
    {
        return $this->eventsOrderbyDir;
    }

    /**
     * @return ArrayCollection
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * @param $queues
     *
     * @return $this
     */
    public function addQueues($queues)
    {
        $this->queues = $queues;

        /** @var \Mautic\WebhookBundle\Entity\WebhookQueue $queue */
        foreach ($queues as $queue) {
            $queue->setWebhook($this);
        }

        return $this;
    }

    /**
     * @param WebhookQueue $queue
     *
     * @return $this
     */
    public function addQueue(WebhookQueue $queue)
    {
        $this->queues[] = $queue;

        return $this;
    }

    /**
     * @param WebhookQueue $queue
     *
     * @return $this
     */
    public function removeQueue(WebhookQueue $queue)
    {
        $this->queues->removeElement($queue);

        return $this;
    }

    /**
     * Get log entities.
     *
     * @return ArrayCollection
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param $logs
     *
     * @return $this
     */
    public function addLogs($logs)
    {
        $this->logs = $logs;

        /** @var \Mautic\WebhookBundle\Entity\Log $log */
        foreach ($logs as $log) {
            $log->setWebhook($this);
        }

        return $this;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function addLog(Log $log)
    {
        $this->logs[] = $log;

        return $this;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function removeLog(Log $log)
    {
        $this->logs->removeElement($log);

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     *
     * @return Webhook
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    public function wasModifiedRecently()
    {
        $dateModified = $this->getDateModified();

        if ($dateModified === null) {
            return false;
        }

        $aWhileBack = (new \DateTime())->modify('-2 days');

        if ($dateModified < $aWhileBack) {
            return false;
        }

        return true;
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } elseif ($prop == 'events') {
            $this->changes[$prop] = [];
        } elseif ($current != $val) {
            $this->changes[$prop] = [$current, $val];
        } else {
            parent::isChanged($prop, $val);
        }
    }
}
