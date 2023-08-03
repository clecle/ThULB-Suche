<?php

namespace ThULB\AjaxHandler;

use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Session\Container as SessionContainer;
use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;

class HideMessage extends AbstractBase
    implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    private SessionContainer $sessionContainer;

    public function __construct(SessionContainer $sessionContainer) {
        $this->sessionContainer = $sessionContainer;
    }

    /**
     * Writes a timestamp to the session, when the message should be shown again
     *
     * @param Params $params
     *
     * @return array
     */
    public function handleRequest(Params $params) : array {
        $identifier = $params->fromPost('message', $params->fromQuery('message'));

        if(!empty($identifier)) {
            $identifier = $identifier . '_expires';
            //$expires = time() + 7 * 24 * 60 * 60;       // hide message for 7 days
            $expires = time() + 24 * 60 * 60;       // hide message for 1 day

            $this->sessionContainer->$identifier = $expires;
        }

        return $this->formatResponse(true);
    }
}