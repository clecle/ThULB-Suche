<?php
/**
 * Override of the VuFind Row Definition for user
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
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

namespace ThULB\Db\Row;
use VuFind\Db\Row\User as OriginalUser;

/**
 * Description of OAuthUser
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class OAuthUser extends OriginalUser
{    
    const DUMMY_PASSWORD = 'password123';

    /**
     * Dummy save of ILS login credentials.
     *
     * @param string $username Username to save
     * @param string $password Password to save
     *
     * @return mixed           The output of the save method.
     */
    public function saveCredentials($username, $password)
    {
        $this->firstname = substr($this->firstname, 0, 50);
        $this->lastname = substr($this->lastname, 0, 50);
        return parent::saveCredentials($username, self::DUMMY_PASSWORD);
    }
}
