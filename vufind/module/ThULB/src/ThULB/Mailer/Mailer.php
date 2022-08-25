<?php

namespace ThULB\Mailer;

use VuFind\Exception\Mail as MailException;
use VuFind\Mailer\Mailer as OriginalMailer;
use VuFind\RecordDriver\AbstractBase;
use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\View\Renderer\PhpRenderer;

class Mailer extends OriginalMailer {

    private $defaultReplyTo = null;
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
    ) {
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
    ) {
        $replyTo = $replyTo ?: $this->defaultReplyTo;
        parent::sendRecord($to, $from, $msg, $record, $view, $subject, $cc, $replyTo);
    }

    /**
     * Sets an address a default value to use for the reply_to field.
     *
     * @param string $replyTo
     */
    public function setDefaultReplyTo(string $replyTo) {
        $this->defaultReplyTo = $replyTo;
    }
}