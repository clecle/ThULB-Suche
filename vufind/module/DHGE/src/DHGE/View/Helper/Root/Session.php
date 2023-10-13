<?php

namespace DHGE\View\Helper\Root;

use Laminas\Session\Container as SessionContainer;
use Laminas\View\Helper\AbstractHelper;

class Session extends AbstractHelper
{
    protected SessionContainer $accountSession;

    public function __construct(SessionContainer $accountSession) {
        $this->accountSession = $accountSession;
    }

    /**
     * Returns the library of the logged-in user from the session.
     *
     * @return string|null
     */
    public function getLibrary() : ?string {
        return $this->accountSession->library ?? null;
    }
}