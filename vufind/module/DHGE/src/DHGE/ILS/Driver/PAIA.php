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
        // are multiple DAIA urls available?
        if(isset($this->config['LibraryURLs'])) {
            $originalBaseUrl = $this->baseUrl;
            $libraries = $this->config['LibraryURLs'];

            // Check all DAIA urls and prefix the location with the library
            $docs = array();
            foreach ($libraries as $libraryName => $library) {
                if(!$baseUrl = $library['DAIA'] ?? false) {
                    continue;
                }
                $this->baseUrl = $baseUrl;
                $newDocs = parent::getStatus($id);
                for ($i = 0; $i < count($newDocs); $i++) {
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
        $library = $accountSession->offsetGet('library');
        $paiaURL = $this->config['LibraryURLs'][$library]['PAIA'] ?? false;
        if($paiaURL) {
            $this->paiaURL = $paiaURL;
        }
    }

    public function setPaiaURL($paiURL) {
        $this->paiaURL = $paiURL;
    }
}