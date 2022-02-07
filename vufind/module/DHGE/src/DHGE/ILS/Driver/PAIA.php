<?php

namespace DHGE\ILS\Driver;

use ThULB\ILS\Driver\PAIA as OriginalPAIA;
use VuFind\Exception\ILS as ILSException;

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
        $docs = array();

        // are multiple DAIA urls available?
        if(isset($this->config['LibraryURLs'])) {
            $originalBaseUrl = $this->baseUrl;
            $libraries = $this->config['LibraryURLs'];

            // Check all DAIA urls and prefix the location with the library
            foreach ($libraries as $libraryName => $library) {
                if(!$baseUrl = $library['DAIA'] ?? false) {
                    continue;
                }
                $this->baseUrl = $baseUrl;
                $newDocs = parent::getStatus($id);
                for ($i = 0; $i < count($newDocs); $i++) {
                    $newDocs[$i]['library'] = $libraryName;
                    if ($newDocs[$i]['location'] != 'Remote') {
                        $newDocs[$i]['location'] = $libraryName . ": " . $newDocs[$i]['location'];
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

    /**
     * Get Statuses
     *
     * This is responsible for retrieving the status information for a
     * collection of records.
     * As the DAIA Query API supports querying multiple ids simultaneously
     * (all ids divided by "|") getStatuses(ids) would call getStatus(id) only
     * once, id containing the list of ids to be retrieved. This would cause some
     * trouble as the list of ids does not necessarily correspond to the VuFind
     * Record-id. Therefore getStatuses(ids) has its own logic for multiQuery-support
     * and performs the HTTPRequest itself, retrieving one DAIA response for all ids
     * and uses helper functions to split this one response into documents
     * corresponding to the queried ids.
     *
     * @param array $ids The array of record ids to retrieve the status for
     *
     * @return array    An array of status information values on success.
     */
    public function getStatuses($ids)
    {
        $status = [];

        // check cache for given ids and skip these ids if availability data is found
        foreach ($ids as $key => $id) {
            if ($this->daiaCacheEnabled
                && $item = $this->getCachedData($this->generateURI($id))
            ) {
                if ($item != null) {
                    $status[] = $item;
                    unset($ids[$key]);
                }
            }
        }

        // only query DAIA service if we have some ids left
        if (count($ids) > 0) {
            try {
                foreach ($ids as $id) {
                    if($data = $this->getStatus($id)) {
                        $status[] = $data;
                    }
                }
            } catch (ILSException $e) {
                $this->debug($e->getMessage());
            }
        }
        return $status;
    }

    /**
     * Initialize the driver.
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
     */
    public function init() {
        parent::init();

        $accountSession = new \Laminas\Session\Container('Account', $this->sessionManager);
        $library = $accountSession->library;
        $paiaURL = $this->config['LibraryURLs'][$library]['PAIA'] ?? false;
        if($paiaURL) {
            $this->paiaURL = $paiaURL;
        }
    }

    public function setPaiaURL($paiURL) {
        $this->paiaURL = $paiURL;
    }
}