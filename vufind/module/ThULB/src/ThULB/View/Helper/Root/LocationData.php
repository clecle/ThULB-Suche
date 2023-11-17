<?php

namespace ThULB\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;
use ThULB\Content\LocationData\ThULB;

class LocationData extends AbstractHelper
{
    protected ThULB $thulbData;

    public function __construct(ThULB $thulbData) {
        $this->thulbData = $thulbData;
    }

    /**
     *
     */
    public function __invoke($locationId = null) : array {
        $data = $this->thulbData->getLocationData($locationId);

        return $locationId === null || empty($data) ? $data : $data[0];
    }
}
