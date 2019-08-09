<?php

namespace ThULB\AjaxHandler;

use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\Session\SessionManager;

class HideMessage extends AbstractBase
    implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    private $sessionManager;

    public function __construct(SessionManager $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Writes a timestamp to the session, when the message should be shown again
     *
     * @param Params $params
     */
    public function handleRequest(Params $params) {
        $identifier = $params->fromPost('message', $params->fromQuery('message'));

        if(isset($identifier) && !empty($identifier)) {
            $identifier = $identifier . '_expires';
            $expires = time() + 7 * 24 * 60 * 60;       // hide message for 7 days

            $this->sessionManager->getStorage()->offsetSet($identifier, $expires);
        }
    }
}