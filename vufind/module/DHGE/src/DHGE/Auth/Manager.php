<?php

namespace DHGE\Auth;

use Laminas\Config\Config;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SessionManager;
use ThULB\Auth\Manager as OriginalManager;
use VuFind\Auth\LoginTokenManager;
use VuFind\Auth\PluginManager;
use VuFind\Auth\UserSessionPersistenceInterface;
use VuFind\Cookie\CookieManager;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\Exception\Auth as AuthException;
use VuFind\Exception\AuthInProgress;
use VuFind\Exception\PasswordSecurity;
use VuFind\ILS\Connection;
use VuFind\Validator\CsrfInterface;

class Manager extends OriginalManager
{
    protected SessionContainer $paiaSession;

    public function __construct(
        Config $config,
        UserServiceInterface
        $userService,
        UserSessionPersistenceInterface $userSession,
        SessionManager $sessionManager,
        PluginManager $pluginManager,
        CookieManager $cookieManager,
        CsrfInterface $csrf,
        LoginTokenManager $loginTokenManager,
        Connection $ils
    ) {
        parent::__construct(
            $config,
            $userService,
            $userSession,
            $sessionManager,
            $pluginManager,
            $cookieManager,
            $csrf,
            $loginTokenManager,
            $ils
        );

        $this->paiaSession = new SessionContainer('PAIA', $this->sessionManager);
    }

    /**
     * Try to log in the user using current query parameters; return User object
     * on success, throws exception on failure.
     *
     * @param Request $request Request object containing account credentials.
     *
     * @throws AuthInProgress
     * @throws AuthException
     * @throws PasswordSecurity
     *
     * @return UserEntityInterface Object representing logged-in user.
     * */
    public function login($request)  {
        $library = $request->getPost()->get('library');
        $config = $this->getAuth()->getCatalog()->getConfig('LibraryURLs') ?? [];

        if($config[$library] ?? false) {
            $this->getAuth()->getCatalog()->setPaiaURL($config[$library]['PAIA']);
            $user = parent::login($request);
            $this->setUserLibrary($library);

            return $user;
        }

        unset($this->paiaSession->libraryPaia);

        throw new AuthException('Library not selected or not available');
    }

    public function getUserLibrary() {
        return $this->paiaSession->libraryPaia;
    }

    public function setUserLibrary($library) {
        $this->paiaSession->libraryPaia = $library;
    }
}