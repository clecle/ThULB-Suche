<?php

namespace DHGE\ILS;

use ThULB\ILS\Logic\AvailabilityStatus;
use VuFind\ILS\Connection as OriginalConnection;

class Connection extends OriginalConnection {
    /**
     * Get a function that parses availability and status to an AvailabilityStatus object if necessary.
     *
     * @return callable
     */
    public function getStatusParser()
    {
        return function ($item) {
            if (!(($item['availability'] ?? null) instanceof AvailabilityStatus)) {
                $availability = $item['availability'] ?? false;
                if ($item['use_unknown_message'] ?? false) {
                    $availability = AvailabilityStatus::STATUS_UNKNOWN;
                }
                if ($item['callnumber'] == 'bestellt') {
                    $availability = AvailabilityStatus::STATUS_ORDERED;
                }
                $item['availability'] = new AvailabilityStatus(
                    $availability,
                    $item['status'] ?? '',
                    ['library' => $item['library']],
                );
                unset($item['status']);
                unset($item['use_unknown_message']);
            }
            return $item;
        };
    }
}