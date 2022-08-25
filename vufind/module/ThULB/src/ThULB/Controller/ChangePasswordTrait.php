<?php
/**
 * PHP version 5
 *
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\Controller;

use Laminas\Mvc\MvcEvent;
use VuFind\Exception\PasswordSecurity;

/**
 * A trait to add the function to force users to change the password
 */
trait ChangePasswordTrait
{
    /**
     * Execute the request.
     * Force logged in users to change their passwords.
     *
     * @param MvcEvent $event
     *
     * @return mixed
     *
     * @throws PasswordSecurity
     */
    public function onDispatch(MvcEvent $event)
    {
        $routeName = 'myresearch-changepassword';

        // Force change password if the password does not match the policies
        if($this->getAuthManager()->isLoggedIn()) {
            $forcing = $this->getRequest()->getPost('forcingNewPassword');
            $isPwChangeAction = in_array($event->getRouteMatch()->getParam('action'), ['ChangePassword', 'NewPassword']);

            if (!$isPwChangeAction && $this->isPasswordChangeNeeded()) {
                $this->forceNewPassword();

                parent::onDispatch($event);
                return $this->redirect()->toRoute($routeName);
            }
        }

        return parent::onDispatch($event);
    }

    /**
     * Checks if the enforcing of the password policy is activated and if the current
     * password is valid.
     *
     * @return bool
     *
     * @throws PasswordSecurity
     */
    protected function isPasswordChangeNeeded() : bool {
        if(!($this->getConfig()->Authentication->enforce_valid_password ?? false)) {
            return false;
        }
        if(!$this->getAuthManager()->isLoggedIn()) {
            return false;
        }

        $pw = $this->getAuthManager()->getIdentity()->getCatPassword();
        return !$this->getAuthManager()->validatePasswordAgainstPolicy($pw);
    }

    /**
     * Redirect the user to the change password screen.
     *
     * @param array  $extras  Associative array of extra fields to store
     * @param bool   $forward True to forward, false to redirect
     *
     * @return mixed
     */
    public function forceNewPassword(array $extras = [], bool $forward = true)
    {
        // We don't want to return to the lightbox
        $serverUrl = $this->getServerUrl();

        // Store the current URL as a login followup action
        $this->followup()->store($extras, $serverUrl);

        // Set a flag indicating that we are forcing new password:
        $this->getRequest()->getPost()->set('forcingNewPassword', true);

        if ($forward) {
            return $this->forwardTo('MyResearch', 'ChangePassword');
        }
        return $this->redirect()->toRoute('myresearch-home');
    }
}
