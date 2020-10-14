<?php

namespace ThULB\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;

class RemoveZWNJ extends AbstractHelper
{
    /**
     * Remove Zero-width non-joiner from a string.
     *
     * @param String $string String to format
     *
     * @return String
     */
    public function __invoke($string)
    {
        return str_replace("\xE2\x80\x8C", "", $string);
    }
}
