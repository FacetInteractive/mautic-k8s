<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\Personalization;

class SendGridMailPersonalization
{
    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addPersonalizedDataToMail(Mail $mail, \Swift_Mime_Message $message)
    {
        if (!$message instanceof MauticMessage) { //Used for "Send test email" in settings
            foreach ($message->getTo() as $recipientEmail => $recipientName) {
                $personalization = new Personalization();
                $to              = new Email($recipientName, $recipientEmail);
                $personalization->addTo($to);
                $mail->addPersonalization($personalization);
            }

            return;
        }

        $metadata = $message->getMetadata();
        $ccEmail  = $message->getCc();
        if ($ccEmail) {
            $cc = new Email(current($ccEmail), key($ccEmail));
        }
        foreach ($message->getTo() as $recipientEmail => $recipientName) {
            if (empty($metadata[$recipientEmail])) {
                //Recipient is not in metadata = we do not have tokens for this email.
                continue;
            }
            $personalization = new Personalization();
            $to              = new Email($recipientName, $recipientEmail);
            $personalization->addTo($to);

            if (isset($cc)) {
                $clone = clone $cc;
                $personalization->addCc($clone);
            }

            foreach ($metadata[$recipientEmail]['tokens'] as $token => $value) {
                $personalization->addSubstitution($token, (string) $value);
            }

            $mail->addPersonalization($personalization);
            unset($metadata[$recipientEmail]);
        }
    }
}
