<?php

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
