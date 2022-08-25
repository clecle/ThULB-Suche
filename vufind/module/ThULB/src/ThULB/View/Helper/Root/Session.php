<?php

namespace ThULB\View\Helper\Root;

use Laminas\Session\SessionManager;
use Laminas\View\Helper\AbstractHelper;

class Session extends AbstractHelper
{
    protected $sessionManager;

    public function __construct(SessionManager $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    /**
     * Checks if a message with the given identifier should be displayed.
     *
     * @param $identifier
     *
     * @return bool
     */
    public function isMessageDisplayed($identifier) : bool {
        $value = 0;
        $identifier = $identifier . '_expires';

        if($this->sessionManager->sessionExists()) {
            $value = $this->sessionManager->getStorage()->offsetGet($identifier);
        }

        return $value < time();
    }
}