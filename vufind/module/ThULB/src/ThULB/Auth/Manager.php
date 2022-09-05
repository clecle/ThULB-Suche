<?php

namespace ThULB\Auth;

use VuFind\Auth\Manager as OriginalManager;
use VuFind\Exception\Auth as AuthException;

class Manager extends OriginalManager
{
    /**
     * Validate the given password against the password policies defined in the config.
     *
     * @param string $password
     *
     * @return bool False if password does not match the policies or auth driver does not support validation.
     */
    public function validatePasswordAgainstPolicy(string $password) : bool {
        try {
            $this->tryOnAuth('validatePasswordAgainstPolicy', $password);
        }
        catch (AuthException $e) {
            return false;
        }

        return true;
    }

    /**
     * Try a method on the auth driver.
     *
     * @param string $method    Method to try
     * @param mixed  $arg       An optional argument passed to the method.
     *
     * @return mixed|false      Returns false if the method can't be called.
     */
    protected function tryOnAuth(string $method, $arg = null) {
        return is_callable([$this->getAuth(), $method]) ? $this->getAuth()->$method($arg) : false;
    }
}