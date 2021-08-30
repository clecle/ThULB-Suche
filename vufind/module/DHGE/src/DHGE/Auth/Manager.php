<?php

namespace DHGE\Auth;

use Laminas\Http\PhpEnvironment\Request;
use ThULB\Auth\Manager as OriginalManager;
use VuFind\Db\Row\User as UserRow;
use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\AuthInProgress;
use VuFind\Exception\PasswordSecurity;

class Manager extends OriginalManager
{
    /**
     * Try to log in the user using current query parameters; return User object
     * on success, throws exception on failure.
     *
     * @param Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @throws PasswordSecurity
     * @throws AuthInProgress
     * @return UserRow Object representing logged-in user.
     */
    public function login($request) {
        $library = $request->getPost()->get('library');
        $config = $this->getAuth()->getCatalog()->getConfig('LibraryURLs') ?? [];

        if($config[$library] ?? false) {
            $this->getAuth()->getCatalog()->setPaiaURL($config[$library]['PAIA']);
            $user = parent::login($request);
            $this->session->offsetSet('library', $library);

            return $user;
        }

        $this->session->offsetUnset('library');

        throw new AuthException('Library not selected or not available');
    }
}