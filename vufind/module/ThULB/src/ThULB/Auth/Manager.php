<?php

namespace ThULB\Auth;

use VuFind\Auth\Manager as OriginalManager;

class Manager extends OriginalManager
{
    /**
     * Check if this is the first time a user has logged in.
     * Should be called after the login attempt.
     *
     * @return boolean
     */
    public function isFirstLogin() {
        return is_callable([$this->getAuth(), 'isFirstLogin']) ? $this->getAuth()->isFirstLogin() : false;
    }
}