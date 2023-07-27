<?php

namespace ThULB\View\Helper\Record;

use Laminas\View\Helper\AbstractHelper;
use ThULB\ILS\Driver\Sera;
use VuFind\Exception\ILS;
use VuFind\RecordDriver\DefaultRecord;

class SeraHelper extends AbstractHelper
{
    private $sera;

    public function __construct(Sera $sera) {
        $this->sera = $sera;
    }

    /**
     * Get status for epn from Sera ILS driver
     *
     * @param string $epn
     *
     * @return array|null
     *
     * @throws ILS
     */
    public function __invoke(string $epn) : ?array {
        return $this->sera->getStatus($epn);
    }
}
