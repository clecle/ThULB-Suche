<?php

namespace DHGE\ILS\Driver;

use ThULB\ILS\Driver\PAIA as OriginalPAIA;
use VuFind\Exception\ILS as ILSException;
use VuFind\Exception\ILS;

class PAIA extends OriginalPAIA {
    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id) {
        if(isset($this->config['LibraryURLs']['libDaiaUrls'])) {
            $originalBaseUrl = $this->baseUrl;
            $urls = $this->config['LibraryURLs']['libDaiaUrls'];

            $docs = array();
            foreach ($urls as $library => $baseUrl) {
                $this->baseUrl = $baseUrl;
                $newDocs = parent::getStatus($id);
                for ($i = 0; $i < count($newDocs); $i++) {
                    $newDocs[$i]['location'] = $library . ": " . $newDocs[$i]['location'];
                }
                $docs = array_merge($docs, $newDocs);
            }
            $this->baseUrl = $originalBaseUrl;
        }
        else {
            $docs = parent::getStatus($id);

        }

        return $docs;
    }

//    /**
//     * Get Statuses
//     *
//     * This is responsible for retrieving the status information for a
//     * collection of records.
//     * As the DAIA Query API supports querying multiple ids simultaneously
//     * (all ids divided by "|") getStatuses(ids) would call getStatus(id) only
//     * once, id containing the list of ids to be retrieved. This would cause some
//     * trouble as the list of ids does not necessarily correspond to the VuFind
//     * Record-id. Therefore getStatuses(ids) has its own logic for multiQuery-support
//     * and performs the HTTPRequest itself, retrieving one DAIA response for all ids
//     * and uses helper functions to split this one response into documents
//     * corresponding to the queried ids.
//     *
//     * @param array $ids The array of record ids to retrieve the status for
//     *
//     * @return array    An array of status information values on success.
//     */
//    public function getStatuses($ids) {
//        if(isset($this->config['LibraryURLs']['libDaiaUrls'])) {
//            $originalBaseUrl = $this->baseUrl;
//            $urls = $this->config['LibraryURLs']['libDaiaUrls'];
//
//            $statuses = array();
//            foreach ($urls as $library => $baseUrl) {
//                $this->baseUrl = $baseUrl;
//                $newDocs = parent::getStatuses($ids);
//                $statuses = array_merge($statuses, $newDocs);
//            }
//            $this->baseUrl = $originalBaseUrl;
//        }
//        else {
//            $statuses = parent::getStatuses($ids);
//        }
//        return $statuses;
//    }
}