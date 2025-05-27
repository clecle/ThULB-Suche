<?php

namespace ThULB\Mailer;

use Interop\Container\ContainerInterface;
use Laminas\Mime\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use VuFind\Exception\Mail as MailException;
use VuFind\Mailer\Mailer as OriginalMailer;
use VuFind\RecordDriver\AbstractBase;
use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\View\Renderer\PhpRenderer;

class Mailer extends OriginalMailer {

    private ?string $defaultReplyTo = null;
    private ?ContainerInterface $serviceLocator = null;

    protected function addTrailingSpaces (string $string): string {
        return preg_replace("/(?<!\t)((?<!\r)(?=\n)|(?=\r\n))/m", "   $1", $string);
    }

    /**
     * Send an email message.
     *
     * @param string|Address|AddressList $to      Recipient email address (or
     * delimited list)
     * @param string|Address             $from    Sender name and email address
     * @param string                     $subject Subject line for message
     * @param string|MimeMessage         $body    Message body
     * @param string                     $cc      CC recipient (null for none)
     * @param string|Address|AddressList $replyTo Reply-To address (or delimited
     * list, null for none)
     *
     * @throws MailException
     * @return void
     */
    public function send($to, $from, $subject, $body, $cc = null, $replyTo = null) {
        // add three trailing spaces to lines in text emails to prevent outlook from removing linebreaks. see
        // https://stackoverflow.com/questions/136052/how-do-i-format-a-string-in-an-email-so-outlook-will-print-the-line-breaks
        // https://stackoverflow.com/questions/247546/outlook-autocleaning-my-line-breaks-and-screwing-up-my-email-format

        if ($body instanceof MimeMessage) {
            $parts = $body->getParts();
            $body = new Message();

            foreach ($parts as $part) {
                if($part->getType() === Mime::TYPE_TEXT) {
                    $part->setContent(
                        $this->addTrailingSpaces($part->getContent())
                    );
                }
                $body->addPart($part);
            }
        }
        elseif (is_string($body)) {
            $body = $this->addTrailingSpaces($body);
        }

        parent::send($to, $from, $subject, $body, $cc, $replyTo);
    }

    /**
     * Send an email message representing a link.
     *
     * @param string                     $to      Recipient email address
     * @param string|Address             $from    Sender name and email address
     * @param string                     $msg     User notes to include in message
     * @param string                     $url     URL to share
     * @param PhpRenderer                $view    View object (used to render email templates)
     * @param string                     $subject Subject for email (optional)
     * @param string                     $cc      CC recipient (null for none)
     * @param string|Address|AddressList $replyTo Reply-To address (or delimited list, null for none)
     *
     * @return void
     *
     * @throws MailException
     */
    public function sendLink($to, $from, $msg, $url, $view, $subject = null,
                             $cc = null, $replyTo = null
    ) : void {
        if($this->serviceLocator) {
            $user = $this->serviceLocator->get(\VuFind\Auth\Manager::class)->getUserObject();
            $replyTo = $replyTo ?: $user->email;
        }
        $replyTo = $replyTo ?: $this->defaultReplyTo;
        parent::sendLink($to, $from, $msg, $url, $view, $subject, $cc, $replyTo);
    }

    /**
     * Send an email message representing a record.
     *
     * @param string                     $to      Recipient email address
     * @param string|Address             $from    Sender name and email address
     * @param string                     $msg     User notes to include in message
     * @param AbstractBase               $record  Record being emailed
     * @param PhpRenderer                $view    View object (used to render email templates)
     * @param string                     $subject Subject for email (optional)
     * @param string                     $cc      CC recipient (null for none)
     * @param string|Address|AddressList $replyTo Reply-To address (or delimited list, null for none)
     *
     * @return void
     *
     * @throws MailException
     */
    public function sendRecord($to, $from, $msg, $record, $view, $subject = null,
                               $cc = null, $replyTo = null
    ) : void {
        if($this->serviceLocator) {
            $user = $this->serviceLocator->get(\VuFind\Auth\Manager::class)->getUserObject();
            $replyTo = $replyTo ?: $user->email;
        }
        $replyTo = $replyTo ?: $this->defaultReplyTo;
        parent::sendRecord($to, $from, $msg, $record, $view, $subject, $cc, $replyTo);
    }

    /**
     * Sets an address a default value to use for the reply_to field.
     *
     * @param string $replyTo
     */
    public function setDefaultReplyTo(string $replyTo) : void {
        $this->defaultReplyTo = $replyTo;
    }

    public function setServiceLocator(ContainerInterface $serviceLocator) : void {
        $this->serviceLocator = $serviceLocator;
    }
}