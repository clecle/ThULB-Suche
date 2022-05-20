<?php

namespace ThULB\View\Helper\Root;

use VuFind\View\Helper\Root\Citation as OriginalCitation;

class Citation extends OriginalCitation
{
    public function __invoke($driver)
    {
        parent::__invoke($driver);

        // Remove authors with roles 'dgg' and 'dgs'

        if(!$authorRoles = (array) $driver->tryMethod('getPrimaryAuthorsRoles')) {
            $authorRoles = (array) $driver->tryMethod('getCorporateAuthorsRoles');
        }
        $authorRoles = array_merge($authorRoles, (array) $driver->tryMethod('getSecondaryAuthorsRoles'));

        for($i = count($authorRoles) - 1; $i >= 0; $i--) {
            if(in_array($authorRoles[$i], ['dgs', 'dgg'])) {
                unset($this->details['authors'][$i]);
            }
        }

        return $this;
    }
}