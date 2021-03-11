<?php
/**
 * ILS authentication module.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
namespace ThULB\Auth;

use VuFind\Auth\ILS as OriginalILS;
use VuFind\Exception\Auth as AuthException;

/**
 * ILS authentication module.
 *
 * @category VuFind
 * @package  Authentication
 * @author   Franck Borel <franck.borel@gbv.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:authentication_handlers Wiki
 */
class ILS extends OriginalILS
{
    /**
     * Indicator for first login
     *
     * @var boolean
     */
    protected $isFirstLogin = false;

    /**
     * Update the database using details from the ILS, then return the User object.
     *
     * @param array $info User details returned by ILS driver.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Processed User object.
     */
    protected function processILSUser($info)
    {
        // Figure out which field of the response to use as an identifier; fail
        // if the expected field is missing or empty:
        $usernameField = $this->getUsernameField();
        if (!isset($info[$usernameField]) || empty($info[$usernameField])) {
            throw new AuthException('authentication_error_technical');
        }

        // Check to see if we already have an account for this user:
        $userTable = $this->getUserTable();
        if (!empty($info['id'])) {
            $user = $userTable->getByCatalogId($info['id']);
            if (empty($user)) {
                // Set flag for first login into vufind
                $this->isFirstLogin = true;
                
                $user = $userTable->getByUsername($info[$usernameField]);
                $user->saveCatalogId($info['id']);
            }
        } else {
            $user = $userTable->getByUsername($info[$usernameField]);
        }

        // No need to store the ILS password in VuFind's main password field:
        $user->password = '';

        // Update user information based on ILS data:
        $fields = ['firstname', 'lastname', 'major', 'college'];
        foreach ($fields as $field) {
            $user->$field = $info[$field] ?? ' ';
        }
        $user->updateEmail($info['email'] ?? '');

        // Update the user in the database, then return it to the caller:
        $user->saveCredentials(
            $info['cat_username'] ?? ' ',
            $info['cat_password'] ?? ' '
        );

        return $user;
    }

    /**
     * Check if this is the first time a user has logged in.
     * Should be called after the login attempt.
     *
     * @return boolean
     */
    public function isFirstLogin() {
        return $this->isFirstLogin;
    }
}
