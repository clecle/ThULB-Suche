<?php

namespace DHGE\View\Helper\Root;

use Laminas\Session\Container;
use ThULB\View\Helper\Root\Session as OriginalSessionHelper;

class Session extends OriginalSessionHelper
{
    /**
     * @var Container
     */
    protected $accountSession;

    /**
     * Returns the library of the logged-in user from the session.
     *
     * @return string|null
     */
    public function getLibrary() : ?string {
        return $this->getAccountSession()->library ?? null;
    }

    /**
     * Creates and returns the container for the account namespace in the session.
     *
     * @return Container
     */
    protected function getAccountSession() : Container {
        if(!$this->accountSession) {
            $this->accountSession = new Container('Account', $this->sessionManager);
        }
        return $this->accountSession;
    }
}