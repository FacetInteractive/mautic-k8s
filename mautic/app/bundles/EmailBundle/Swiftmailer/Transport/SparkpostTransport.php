<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @see         https://github.com/SlowProg/SparkPostSwiftMailer/blob/master/SwiftMailer/SparkPostTransport.php for additional source reference
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\DoNotContact;
use SparkPost\SparkPost;
use SparkPost\SparkPostException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SparkpostTransport
 * The referrence class for this was provided by.
 */
class SparkpostTransport extends AbstractTokenArrayTransport implements \Swift_Transport, InterfaceTokenTransport, InterfaceCallbackTransport
{
    /**
     * @var string|null
     */
    protected $apiKey;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * SparkpostTransport constructor.
     *
     * @param $apiKey
     */
    public function __construct($apiKey, TranslatorInterface $translator)
    {
        $this->setApiKey($apiKey);
        $this->translator = $translator;
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return null|string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        try {
            $sparky   = $this->createSparkPost();
            $response = $sparky->transmissions->get();
            $response->wait();
        } catch (SparkPostException $exception) {
            $response = json_decode($exception->getMessage(), true);

            if (is_array($response) && isset($response['errors'])) {
                $this->throwException($response['errors'][0]['message']);
            } else {
                $this->throwException($exception->getMessage());
            }
        }

        $this->started = true;
    }

    /**
     * @return SparkPost
     *
     * @throws \Swift_TransportException
     */
    protected function createSparkPost()
    {
        if (empty($this->apiKey)) {
            $this->throwException($this->translator->trans('mautic.email.api_key_required', [], 'validators'));
        }

        $httpAdapter = new GuzzleAdapter(new Client());
        $sparky      = new SparkPost($httpAdapter, ['key' => $this->apiKey]);

        return $sparky;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int Number of messages sent
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $sendCount = 0;
        if ($event = $this->getDispatcher()->createSendEvent($this, $message)) {
            $this->getDispatcher()->dispatchEvent($event, 'beforeSendPerformed');
            if ($event->bubbleCancelled()) {
                return 0;
            }
        }

        try {
            $sparkPost        = $this->createSparkPost();
            $sparkPostMessage = $this->getSparkPostMessage($message);
            $response         = $sparkPost->transmissions->post($sparkPostMessage);

            $response = $response->wait();
            if (200 == (int) $response->getStatusCode()) {
                $results   = $response->getBody();
                $sendCount = $results['results']['total_accepted_recipients'];
            }
        } catch (\Exception $e) {
            $this->throwException($e->getMessage());
        }

        if ($event) {
            if ($sendCount > 0) {
                $event->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
            } else {
                $event->setResult(\Swift_Events_SendEvent::RESULT_FAILED);
            }
            $this->getDispatcher()->dispatchEvent($event, 'sendPerformed');
        }

        return $sendCount;
    }

    /**
     * https://jsapi.apiary.io/apis/sparkpostapi/introduction/subaccounts-coming-to-an-api-near-you-in-april!.html.
     *
     * @param \Swift_Mime_Message $message
     *
     * @return array SparkPost Send Message
     *
     * @throws \Swift_SwiftException
     */
    public function getSparkPostMessage(\Swift_Mime_Message $message)
    {
        $tags      = [];
        $inlineCss = null;

        $this->message = $message;
        $metadata      = $this->getMetadata();
        $mauticTokens  = $mergeVars  = $mergeVarPlaceholders  = [];

        // Sparkpost uses {{ name }} for tokens so Mautic's need to be converted; although using their {{{ }}} syntax to prevent HTML escaping
        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);

            $mergeVars = $mergeVarPlaceholders = [];
            foreach ($mauticTokens as $token) {
                $mergeVars[$token]            = strtoupper(preg_replace('/[^a-z0-9]+/i', '', $token));
                $mergeVarPlaceholders[$token] = '{{{ '.$mergeVars[$token].' }}}';
            }
        }

        $message = $this->messageToArray($mauticTokens, $mergeVarPlaceholders, true);

        // Sparkpost requires a subject
        if (empty($message['subject'])) {
            throw new \Exception($this->translator->trans('mautic.email.subject.notblank', [], 'validators'));
        }

        if (isset($message['headers']['X-MC-InlineCSS'])) {
            $inlineCss = $message['headers']['X-MC-InlineCSS'];
        }
        if (isset($message['headers']['X-MC-Tags'])) {
            $tags = explode(',', $message['headers']['X-MC-Tags']);
        }

        $recipients = [];
        foreach ($message['recipients']['to'] as $to) {
            $recipient = [
                'address'           => $to,
                'substitution_data' => [],
                'metadata'          => [],
            ];

            if (isset($metadata[$to['email']])) {
                foreach ($metadata[$to['email']]['tokens'] as $token => $value) {
                    $recipient['substitution_data'][$mergeVars[$token]] = $value;
                }

                unset($metadata[$to['email']]['tokens']);
                $recipient['metadata'] = $metadata[$to['email']];
            }

            // Apparently Sparkpost doesn't like empty substitution_data or metadata
            if (empty($recipient['substitution_data'])) {
                unset($recipient['substitution_data']);
            }
            if (empty($recipient['metadata'])) {
                unset($recipient['metadata']);
            }

            $recipients[] = $recipient;
        }

        if (isset($message['replyTo'])) {
            $headers['Reply-To'] = (!empty($message['replyTo']['name']))
                ?
                sprintf('%s <%s>', $message['replyTo']['email'], $message['replyTo']['name'])
                :
                $message['replyTo']['email'];
        }

        $content = [
            'from' => (!empty($message['from']['name'])) ? $message['from']['name'].' <'.$message['from']['email'].'>'
                : $message['from']['email'],
            'subject' => $message['subject'],
        ];

        // Sparkpost will set parts regardless if they are empty or not
        if (!empty($message['html'])) {
            $content['html'] = $message['html'];
        }

        if (!empty($message['text'])) {
            $content['text'] = $message['text'];
        }

        $sparkPostMessage = [
            'content'    => $content,
            'recipients' => $recipients,
            'headers'    => $message['headers'],
            'inline_css' => $inlineCss,
            'tags'       => $tags,
        ];

        if (!empty($message['recipients']['cc'])) {
            $sparkPostMessage['cc'] = array_values($message['recipients']['cc']);
        }

        if (!empty($message['recipients']['bcc'])) {
            $sparkPostMessage['bcc'] = array_values($message['recipients']['bcc']);
        }

        if (!empty($message['attachments'])) {
            $sparkPostMessage['attachments'] = $message['attachments'];
        }

        return $sparkPostMessage;
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 5000;
    }

    /**
     * @param \Swift_Message $message
     * @param int            $toBeAdded
     * @param string         $type
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc()) + $toBeAdded;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'sparkpost';
    }

    /**
     * Handle response.
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return array array('bounces' => array('hashID' => 'reason', ...));
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $payload = $request->request->all();

        $rows = [
            DoNotContact::BOUNCED => [
                'hashIds' => [],
                'emails'  => [],
            ],
            DoNotContact::UNSUBSCRIBED => [
                'hashIds' => [],
                'emails'  => [],
            ],
        ];

        foreach ($payload as $msys) {
            $msys = $msys['msys'];
            if (isset($msys['message_event'])) {
                $event = $msys['message_event'];
            } elseif (isset($msys['unsubscribe_event'])) {
                $event = $msys['unsubscribe_event'];
            } else {
                continue;
            }

            // Process events sent from Mautic
            if (!isset($event['rcpt_meta']['hashId'])) {
                continue;
            }

            if (isset($event['rcpt_type']) && 'to' !== $event['rcpt_type']) {
                // Ignore cc/bcc

                continue;
            }

            $hashId = $event['rcpt_meta']['hashId'];

            switch ($event['type']) {
                case 'bounce':
                    // Only parse hard bounces - https://support.sparkpost.com/customer/portal/articles/1929896-bounce-classification-codes
                    if (in_array((int) $event['bounce_class'], [10, 30, 50, 51, 52, 53, 54, 90])) {
                        $rows[DoNotContact::BOUNCED]['hashIds'][$hashId] = $event['raw_reason'];
                    }
                    break;
                case 'spam_complaint':
                    $rows[DoNotContact::BOUNCED]['hashIds'][$hashId] = $event['fbtype'];
                    break;
                case 'out_of_band':
                case 'policy_rejection':
                    $rows[DoNotContact::BOUNCED]['hashIds'][$hashId] = $event['raw_reason'];
                    break;
                case 'list_unsubscribe':
                case 'link_unsubscribe':
                    $rows[DoNotContact::UNSUBSCRIBED]['hashIds'][$hashId] = 'unsubscribed';
                    break;
            }
        }

        return $rows;
    }
}
