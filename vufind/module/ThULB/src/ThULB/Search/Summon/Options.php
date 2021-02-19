<?php

namespace ThULB\Search\Summon;

use VuFind\Search\Summon\Options as OriginalOptions;

class Options extends OriginalOptions
{
    public function setResultLimit(int $limit) {
        $this->resultLimit = $limit;
    }
}