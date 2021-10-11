<?php

namespace ThULB\Controller\Plugin;

use VuFind\Controller\Plugin\IlsRecords as OriginalIlsRecords;

class IlsRecords extends OriginalIlsRecords
{
    const ID_URI_PREFIX = 'http://uri.gbv.de/document/opac-de-27:ppn:';

    /**
     * Get record driver objects corresponding to an array of record arrays returned
     * by an ILS driver's methods such as getMyHolds / getMyTransactions.
     *
     * @param array $records Record information
     *
     * @return \VuFind\RecordDriver\AbstractBase[]
     */
    public function getDrivers(array $records): array
    {
        $records = array_map(function($current) {
            $current['id'] = str_replace(self::ID_URI_PREFIX, '', $current['id']);
            return $current;
        }, $records);

        return parent::getDrivers($records);
    }
}