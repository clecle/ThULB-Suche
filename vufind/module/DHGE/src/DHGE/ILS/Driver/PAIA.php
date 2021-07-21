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
        // are multiple DAIA urls available?
        if(isset($this->config['LibraryURLs']['libDaiaUrls'])) {
            $originalBaseUrl = $this->baseUrl;
            $urls = $this->config['LibraryURLs']['libDaiaUrls'];

            // Check all DAIA urls and prefix the location with the library
            $docs = array();
            foreach ($urls as $library => $baseUrl) {
                $this->baseUrl = $baseUrl;
                $newDocs = parent::getStatus($id);
                for ($i = 0; $i < count($newDocs); $i++) {
                    if ($newDocs[$i]['location'] != 'Remote') {
                        $newDocs[$i]['location'] = $library . ": " . $newDocs[$i]['location'];
                    }
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
}