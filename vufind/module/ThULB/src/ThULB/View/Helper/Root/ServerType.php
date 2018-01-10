<?php

namespace ThULB\View\Helper\Root;

use Zend\View\Helper\AbstractHelper;

/**
 * Helper class to provide the VUFIND_ENV superglobal content
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class ServerType extends AbstractHelper
{
    /**
     * Get the VuFind environment variable value.
     *
     * @return String
     */
    public function __invoke()
    {
        return $_SERVER['VUFIND_ENV'];
    }
}
