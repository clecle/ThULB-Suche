<?php
/**
 * Override of the PAIA ILS Driver for VuFind to get patron information
 *
 * PHP version 5
 *
 * Copyright (C) Oliver Goldschmidt, Magda Roos, Till Kinstler, André Lahmann 2013,
 * 2014, 2015.
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @package  ILS_Drivers
 * @author   Oliver Goldschmidt <o.goldschmidt@tuhh.de>
 * @author   Magdalena Roos <roos@gbv.de>
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:ils_drivers Wiki
 */

namespace ThULB\ILS\Driver;

use Exception;
use VuFind\Exception\Forbidden as ForbiddenException;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\ILS\Driver\PAIA as OriginalPAIA,
    VuFind\Exception\ILS as ILSException,
    VuFind\Exception\Auth as AuthException;

/**
 * ThULB extension for the PAIA/DAIA driver
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class PAIA extends OriginalPAIA
    implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;
    
    const DAIA_DOCUMENT_ID_PREFIX = 'http://uri.gbv.de/document/opac-de-27:ppn:';
    const PAIA_INVALID_CREDENTIALS_MSG = '0:access_denied (invalid patron or password)';

    /**
     * Perform an HTTP request.
     *
     * Custom: Added Accept-Language parameter in header.
     *
     * @param string $id id for query in daia
     *
     * @return string
     * @throws ILSException
     */
    protected function doHTTPRequest($id) : string
    {
        $http_headers = [
            'Content-type: ' . $this->contentTypesRequest[$this->daiaResponseFormat],
            'Accept: ' . $this->contentTypesRequest[$this->daiaResponseFormat],
            'Accept-Language: ' . strtolower($this->getTranslatorLocale())
        ];

        $params = [
            'id' => $id,
            'format' => $this->daiaResponseFormat,
        ];

        try {
            $result = $this->httpService->get(
                $this->baseUrl,
                $params,
                $this->daiaTimeout,
                $http_headers
            );
        } catch (\Exception $e) {
            $msg = 'HTTP request exited with Exception ' . $e->getMessage() .
                ' for record: ' . $id;
            $this->throwAsIlsException($e, $msg);
        }

        if (!$result->isSuccess()) {
            throw new ILSException(
                'HTTP status ' . $result->getStatusCode() .
                ' received, retrieving availability information for record: ' . $id
            );
        }

        // check if result matches daiaResponseFormat
        if ($this->contentTypesResponse != null) {
            if ($this->contentTypesResponse[$this->daiaResponseFormat]) {
                $contentTypesResponse = array_map(
                    'trim',
                    explode(
                        ',',
                        $this->contentTypesResponse[$this->daiaResponseFormat]
                    )
                );
                [$responseMediaType] = array_pad(
                    explode(
                        ';',
                        $result->getHeaders()->get('Content-type')->getFieldValue(),
                        2
                    ),
                    2,
                    null
                ); // workaround to avoid notices if encoding is not set in header
                if (!in_array(trim($responseMediaType), $contentTypesResponse)) {
                    throw new ILSException(
                        'DAIA-ResponseFormat not supported. Received: ' .
                        $responseMediaType . ' - ' .
                        'Expected: ' .
                        $this->contentTypesResponse[$this->daiaResponseFormat]
                    );
                }
            }
        }

        return $result->getBody();
    }

    /**
     * Place Hold
     *
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details
     *
     * Make a request on a specific record
     *
     * @param array $holdDetails An array of item and patron data
     *
     * @return array An array of data on the request including
     *               whether it was successful and a system message (if available)
     *
     * @throws ForbiddenException
     */
    public function placeHold($holdDetails) : array
    {
        $details = parent::placeHold($holdDetails);
        
        if ($details['success'] === false) {
            $details['sysMessage'] = $this->translate($details['sysMessage']);
        }
        
        return $details;
    }

    /**
     * Get Patron Holds
     *
     * This is responsible for retrieving all holds by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return array Array of the patron's holds on success.
     *
     * @throws ForbiddenException
     */
    public function getMyHoldsAndSRR(array $patron) : array
    {
        // filters for getMyHolds are:
        // status = 1 - reserved (the document is not accessible for the patron yet,
        //              but it will be)
        //          2 - ordered (the document is ordered by the patron)
        //          5 - rejected
        $filter = ['status' => [1, 2, 5]];
        // get items-docs for given filters
        $items = $this->paiaGetItems($patron, $filter);

        return $this->mapPaiaItems($items, 'myHoldsMapping');
    }

    /**
     * Get Patron Loans
     *
     * This is responsible for retrieving all loans (i.e. all checked out items,
     * all storage retrieval requests and all holds with status "provided") by a
     * specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return array        Array of the patron's transactions on success
     *
     * @throws ForbiddenException
     */
    public function getMyProvidedItems(array $patron) : array
    {
        /* 
         * filters for getMyTransactions are:
         * status = 4 - provided (the document is ready to be used by the patron)
         */
        $filter = ['status' => [4]];
        // get items-docs for given filters
        $items = $this->paiaGetItems($patron, $filter);

        return $this->mapPaiaItems($items, 'myProvidedItemsMapping');
    }

    /**
     * Get Patron Profile
     *
     * This is responsible for retrieving the profile for a specific patron.
     *
     * @param array $patron The patron array
     *
     * @return array Array of the patron's profile data on success,
     */
    public function getMyProfile($patron) : array
    {
        $profile = parent::getMyProfile($patron);
        
        if (isset($profile['firstname']) && isset($profile['lastname'])) {
            $profile['name'] = $profile['firstname'] . ' ' . $profile['lastname'];
        }
        
        if (isset($patron['email'])) {
            $profile['email'] = $patron['email'];
        }
        
        if (isset($patron['address'])) {
            $profile['address1'] = $patron['address'];
        }
        
        if (isset($patron['type'])
            && is_array($patron['type'])
            && !empty($patron['type'])
            && preg_match('/de-27:user-type:\d{1}/', $patron['type'][0])
        ) {
            $profile['groupcode'] = preg_replace('/de-27:user-type:(\d{1})/', '$1', $patron['type'][0]);
        }
        
        if (isset($patron['status']) && is_numeric($patron['status'])) {
            $profile['statuscode'] = $patron['status'];
        }
        
        if (isset($patron['note'])) {
            $profile['note'] = $patron['note'];
        }
        
        $profile['user_id'] = $patron['id'];
        
        return $profile;
    }
    
    protected function myTransactionsMapping($items) : array
    {
        $result = parent::myTransactionsMapping($items);
        
        // add queue information
        foreach ($items as $i => $item) {
            if ($item['queue']) {
                $result[$i]['queue'] = $item['queue'];
            }
            
            if (isset($item['storage']) && substr($item['storage'], 0, 14) === 'Sonderlesesaal') {
                // storage (0..1) textual description of location of the document
                $result[$i]['location'] = $item['storage'];
            }
        }
        
        foreach ($result as $index => $doc) {
            if (isset($doc['callnumber'])) {
                $result[$index]['callnumber'] = $this->getItemCallnumber(['label' => $doc['callnumber']]);
                $result[$index]['departmentId'] = $this->getDepIdFromItem(['label' => $doc['callnumber']], $result[$index]['callnumber']);
            }
        }
        
        $sort = function ($a, $b) {
            $dateA = date_create_from_format('d.m.Y', $a['dueTime']);
            $dateB = date_create_from_format('d.m.Y', $b['dueTime']);

            if ($dateA == $dateB) {
                return 0;
            }
            return ($dateA < $dateB) ? -1 : 1;
        };
        
        usort($result, $sort);
        
        return $result;
    }
    
    protected function myProvidedItemsMapping($items) : array
    {
        $results = [];

        foreach ($items as $doc) {
            $result = [];
            // canrenew (0..1) whether a document can be renewed (bool)
            $result['renewable'] = (isset($doc['canrenew']))
                ? $doc['canrenew'] : false;

            // item (0..1) URI of a particular copy
            $result['item_id'] = (isset($doc['item']) ? $doc['item'] : '');

            $result['renew_details']
                = ($result['renewable']) ? $result['item_id'] : '';

            // edition (0..1)  URI of a the document (no particular copy)
            // hook for retrieving alternative ItemId in case PAIA does not
            // the needed id
            $result['id'] = (isset($doc['edition'])
                ? $this->getAlternativeItemId($doc['edition']) : '');

            // requested (0..1) URI that was originally requested

            // about (0..1) textual description of the document
            $result['title'] = (isset($doc['about']) ? $doc['about'] : null);

            // queue (0..1) number of waiting requests for the document or item
            $result['request'] = (isset($doc['queue']) ? $doc['queue'] : null);

            // renewals (0..1) number of times the document has been renewed
            $result['renew'] = (isset($doc['renewals']) ? $doc['renewals'] : null);

            // reminder (0..1) number of times the patron has been reminded
            $result['reminder'] = (
                isset($doc['reminder']) ? $doc['reminder'] : null
            );

            // custom PAIA field
            // starttime (0..1) date and time when the status began
            $result['startTime'] = (isset($doc['starttime'])
                ? $this->convertDatetime($doc['starttime']) : '');

            // endtime (0..1) date and time when the status will expire
            $result['dueTime'] = (isset($doc['endtime'])
                ? $this->convertDatetime($doc['endtime']) : '');

            if ($doc['status'] == '4') {
                $result['expire'] = (isset($doc['endtime'])
                    ? $this->convertDatetime($doc['endtime']) : '');
            } elseif ($doc['status'] == '3') {
                // duedate (0..1) date when the current status will expire (deprecated)
                $result['duedate'] = (isset($doc['duedate'])
                    ? $this->convertDate($doc['duedate']) : '');
            }
            
            if ($doc['status'] != 3) {
                // storage (0..1) textual description of location of the document
                $result['location'] = (isset($doc['storage'])) ? $doc['storage'] : null;
            }

            // cancancel (0..1) whether an ordered or provided document can be
            // canceled

            // error (0..1) error message, for instance if a request was rejected
            $result['message'] = (isset($doc['error']) ? $doc['error'] : '');

            // storageid (0..1) location URI

            // PAIA custom field
            // label (0..1) call number, shelf mark or similar item label
            $result['callnumber'] = $this->getItemCallnumber($doc);
            $result['departmentId'] = $this->getDepIdFromItem($doc, $result['callnumber']);

            // status: provided (the document is ready to be used by the patron)
            $result['available'] = $doc['status'] == 4 ? true : false;
            
            $result['queue'] = isset($doc['queue']) ? $doc['queue'] : 0;

            // Optional VuFind fields
            /*
            $result['barcode'] = null;
            $result['dueStatus'] = null;
            $result['renewLimit'] = "1";
            $result['volume'] = null;
            $result['publication_year'] = null;
            $result['isbn'] = null;
            $result['issn'] = null;
            $result['oclc'] = null;
            $result['upc'] = null;
            $result['institution_name'] = null;
            */

            $results[] = $result;
        }
        
        $sort = function ($a, $b) {
            $dateA = date_create_from_format('d.m.Y', $a['startTime']);
            $dateB = date_create_from_format('d.m.Y', $b['startTime']);

            if ($dateA == $dateB) {
                return 0;
            }
            return ($dateA < $dateB) ? -1 : 1;
        };
        
        usort($results, $sort);

        return $results;
    }

    /**
     * Get the callnumber of this item
     *
     * @param array $doc Array of PAIA item.
     *
     * @return String|null
     */
    protected function getCallNumber($doc) : ?string
    {
        return isset($doc['label']) ? $this->removeDepIdFromCallNumber($doc['label']) : null;
    }

    /**
     * PAIA support method to retrieve needed ItemId in case PAIA-response does not
     * contain it
     *
     * @param string $id itemId
     *
     * @return string $id
     */
    protected function getAlternativeItemId($id) : string
    {
        return str_replace(self::DAIA_DOCUMENT_ID_PREFIX, '', $id);
    }

    /**
     * Returns an array with status information for provided item.
     *
     * @param array $item Array with DAIA item data
     *
     * @return array
     */
    protected function getItemStatus($item) : array
    {
        $status = parent::getItemStatus($item);

        if(isset($item['available'])) {
            foreach ($item['available'] as $available) {
                if (isset($available['service']) && in_array($available['service'], ['remote', 'openaccess'])) {
                    $href = trim($available['href']);
                    // custom DAIA field
                    $status['remotehref'] = $href;
                    // custom DAIA field
                    $status['remotedomain'] = parse_url($href)['host'] ?? $href;
                    // custom DAIA field
                    $status['remotetitle'] = isset($available['title']) ? $available['title'] : '';

                    break;
                }
            }
        }

        if (!$status['availability'] 
            && !isset($status['duedate'])
            && $status['holdtype'] !== 'recall'
        ) {
            $status['use_unknown_message'] = true;
        }
        
        // items that are on recall should be shown as unavailable
        if (
            $status['holdtype'] === 'recall'
            && $status['status'] === 'available'
        ) {
            $status['status'] = 'unavailable';
        }
        
        return $status;
    }
    
    protected function getStatusString($item) : string
    {
        $status = 'unknown';
        if (isset($item['available']) && $item['available']) {
            $status = 'available';
        } elseif (isset($item['unavailable']) && $item['unavailable']) {
            $status = 'unavailable';
        }
        
        return $status;
    }

    /**
     * Returns the value for "callnumber" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemCallnumber($item) : string
    {
        $callnumber = isset($item['label']) && !empty($item['label']) ? $item['label'] : '';
        
        return $this->removeDepIdFromCallNumber($callnumber);
    }
    
    /**
     * Parse an array with DAIA status information.
     *
     * @param string $id        Record id for the DAIA array.
     * @param array  $daiaArray Array with raw DAIA status information.
     *
     * @return array            Array with VuFind compatible status information.
     */
    protected function parseDaiaArray($id, $daiaArray) : array
    {
        $result = [];
        
        $doc_id = null;
        $doc_href = null;
        if (isset($daiaArray['id'])) {
            $doc_id = $daiaArray['id'];
        }
        if (isset($daiaArray['href'])) {
            // url of the document (not needed for VuFind)
            $doc_href = $daiaArray['href'];
        }
        if (isset($daiaArray['message'])) {
            // log messages for debugging
            $this->logMessages($daiaArray['message'], 'document');
        }
        // if one or more items exist, iterate and build result-item
        if (isset($daiaArray['item']) && is_array($daiaArray['item'])) {
            $number = 0;
            foreach ($daiaArray['item'] as $item) {
                $result_item = [];
                $result_item['id'] = $id;
                // custom DAIA field
                $result_item['doc_id'] = $doc_id;
                $result_item['item_id'] = $item['id'];
                // custom DAIA field used in getHoldLink()
                $result_item['ilslink']
                    = (isset($item['href']) ? $item['href'] : $doc_href);
                // about Field
                $result_item['about']
                    = (isset($item['about']) ? $item['about'] : "");
                // chronology > about Field
                $result_item['chronology_about']
                    = (isset($item['chronology']['about']) ? $item['chronology']['about'] : "");
                // count items
                $number++;
                $result_item['number'] = $this->getItemNumber($item, $number);
                // set default value for barcode
                $result_item['barcode'] = $this->getItemBarcode($item);
                // set default value for reserve
                $result_item['reserve'] = $this->getItemReserveStatus($item);
                // get callnumber
                $result_item['callnumber'] = $this->getItemCallnumber($item);
                // get department id
                $result_item['departmentId'] = $this->getDepIdFromItem($item, $result_item['callnumber']);
                // get location
                $result_item['location'] = $this->getItemDepartment($item);
                // custom DAIA field
                $result_item['locationid'] = $this->getItemDepartmentId($item);
                // get location link
                $result_item['locationhref'] = $this->getItemDepartmentLink($item);
                // custom DAIA field
                $result_item['storage'] = $this->getItemStorage($item);
                // custom DAIA field
                $result_item['storageid'] = $this->getItemStorageId($item);
                // custom DAIA field
                $result_item['storagehref'] = $this->getItemStorageLink($item);
                // status and availability will be calculated in own function
                $result_item = $this->getItemStatus($item) + $result_item;

                if($result_item['location'] == 'Unknown' && !empty($result_item['remotehref'])) {
                    $result_item['location'] = 'Remote';
                }
                elseif($result_item['location'] == 'Unknown' && $result_item['departmentId'] ?? false) {
                    $result_item['location'] = $result_item['departmentId'];
                }
                // add result_item to the result array, if at least one relevant
                // information is present
                if ($result_item['callnumber'] !== ''
                    || $result_item['about']
                    || $result_item['storage'] !== 'Unknown'
                    || ($result_item['remotehref'] ?? false)
                ) {
                    $result[] = $result_item;
                }
            } // end iteration on item
        }

        return $result;
    }

    /**
     * Patron Login
     *
     * This is responsible for authenticating a patron against the catalog.
     *
     * @param string $username The patron's username
     * @param string $password The patron's login password
     *
     * @return array|null      Associative array of patron info on successful login,
     *                         null on unsuccessful login.
     *
     * @throws ILSException
     * @throws AuthException
     */
    public function patronLogin($username, $password) : ?array
    {
        if ($username == '' || $password == '') {
            throw new ILSException('Invalid Login, Please try again.');
        }

        $session = $this->getSession();

        // if we already have a session with access_token and patron id, try to get
        // patron info with session data
        if (isset($session->expires) && $session->expires > time()) {
            try {
                return $this->enrichUserDetails(
                    $this->paiaGetUserDetails($session->patron),
                    $password
                );
            } catch (ILSException $e) {
                $this->debug('Session expired, login again', ['info' => 'info']);
            }
        }
        try {
            if ($this->paiaLogin($username, $password)) {
                return $this->enrichUserDetails(
                    $this->paiaGetUserDetails($session->patron),
                    $password
                );
            }
        } catch (ILSException $e) {
            if ($e->getMessage() === self::PAIA_INVALID_CREDENTIALS_MSG 
                    && $password !== \ThULB\Db\Row\OAuthUser::DUMMY_PASSWORD
            ) {
                throw new AuthException('authentication_error_invalid');
            }
            
            throw new ILSException($e->getMessage());
        }

        return null;
    }
    
    public function getOfflineMode() : bool
    {
        return false;
    }

    /**
     * Returns the value of item.storage.content instead of 
     * item.department.content (e.g. to be used in VuFind getStatus/getHolding
     * array as location)
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemDepartment($item) : string
    {
        return isset($item['storage']) && isset($item['storage']['content'])
        && !empty($item['storage']['content'])
            ? $item['storage']['content']
            : parent::getItemDepartment($item);
    }

    /**
     * Returns the value of item.storage.id instead of item.department.id (e.g.
     * to be used in VuFind getStatus/getHolding array as location)
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemDepartmentId($item) : string
    {
        return isset($item['storage']) && isset($item['storage']['id'])
            ? $item['storage']['id'] : parent::getItemDepartmentId($item);
    }

    /**
     * Returns the value of item.storage.href instead of item.department.href
     * (e.g. to be used in VuFind getStatus/getHolding array for linking the
     * location)
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemDepartmentLink($item) : string
    {
        return $item['storage']['href'] ?? parent::getItemDepartmentLink($item);
    }
    
    /**
     * remove the storage label at the beginning of an item label
     * 
     * @param string $callNumber the call number of an item, which potentially
     *                           begins with a storage id, separated by a colon
     * @return string
     */
    protected function removeDepIdFromCallNumber(string $callNumber) : string
    {
        $sepPos = strpos($callNumber, ':');
        
        if ($sepPos && isset($this->config['DepartmentTitles'][substr($callNumber, 0, $sepPos)])) {
            return substr($callNumber, $sepPos + 1);
        }
        else if (false === $sepPos && isset($this->config['DepartmentTitles'][$callNumber])) {
            return '';
        }
        else if ($sepPos !== false && isset($this->config['DepartmentRegex'])) {
            foreach($this->config['DepartmentRegex'] as $regex) {
                if(preg_match($regex, $callNumber)) {
                    return substr($callNumber, $sepPos + 1);
                }
            }
        }
        
        return $callNumber;
    }

    /**
     * Get department id of an item by removing the shortened call number
     * (without department id) from the item call number.
     *
     * @param array $document
     * @param string $shortenedCallnumber
     *
     * @return string|null
     */
    protected function getDepIdFromItem(array $document, string $shortenedCallnumber) : ?string{
        $callnumber = $document['label'] ?? null;
        if(!empty($callnumber) && !empty($shortenedCallnumber)) {
            return str_replace(':' . $shortenedCallnumber, '', $callnumber);
        }
        elseif (!empty($callnumber)) {
            return $callnumber;
        }

        return null;
    }

    /**
     * Helper function for PAIA to uniformly parse JSON. Extended and fixed
     * version.
     *
     * @param string $file JSON data
     *
     * @return array
     *
     * @throws ILSException
     */
    protected function paiaParseJsonAsArray($file) : array
    {
        $responseArray = json_decode($file, true);

        if($responseArray === null) {
            throw new ILSException('Could not decode ILS response. (' . $file . ')', 0);
        }
        elseif (isset($responseArray['error'])) {
            $message = $responseArray['error'];
            if (isset($responseArray['error_description'])) {
                $message .= ' (' . $responseArray['error_description'] . ')';
            }
            if (isset($responseArray['error_uri'])) {
                $message .= '; see ' . $responseArray['error_uri'] . ' for more information';
            }
            
            $code = (isset($responseArray['code'])) ? $responseArray['code'] : 0;
            
            throw new ILSException($message, $code);
        }

        return $responseArray;
    }

    /**
     * Check if hold or recall available
     *
     * This is responsible for determining if an item is requestable
     *
     * @param string $id     The Bib ID
     * @param array  $data   An Array of item data
     * @param array  $patron An array of patron data
     *
     * @return array|bool True if request is valid, false if not, array if patron is blocked
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkRequestIsValid($id, $data, $patron) : array|bool
    {
        if (isset($patron['status']) && $patron['status']  == 2) {
            return [
                    'valid'     => false,
                    'status'    => 'blocked_for_expired_account'
                ];
        } else if (isset($patron['status']) && $patron['status']  == 0
            && isset($patron['expires']) && $patron['expires'] >= date('Y-m-d')
            && in_array('write_items', $this->getScope())
        ) {
            return true;
        }
        return false;
    }

    /**
     * PAIA authentication function
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return bool
     *
     * @throws ILSException
     */
    protected function paiaLogin($username, $password) : bool
    {
        // perform full PAIA auth and get patron info
        $post_data = [
            "username"   => $username,
            "password"   => $password,
            "grant_type" => "password",
            "scope"      => "read_patron read_fees read_items write_items change_password"
        ];
        $responseJson = $this->paiaLoginRequest('auth/login', $post_data);

        try {
            $responseArray = $this->paiaParseJsonAsArray($responseJson);
        } catch (ILSException $e) {
            if ($e->getMessage() === 'access_denied') {
                return false;
            }
            throw new ILSException(
                $e->getCode() . ':' . $e->getMessage()
            );
        }

        if (!isset($responseArray['access_token'])) {
            throw new ILSException(
                'Unknown error! Access denied.'
            );
        } elseif (!isset($responseArray['patron'])) {
            throw new ILSException(
                'Login credentials accepted, but got no patron ID?!?'
            );
        } else {
            // at least access_token and patron got returned which is sufficient for
            // us, now save all to session
            $session = $this->getSession();

            $session->patron
                = isset($responseArray['patron'])
                ? $responseArray['patron'] : null;
            $session->access_token
                = isset($responseArray['access_token'])
                ? $responseArray['access_token'] : null;
            $session->scope
                = isset($responseArray['scope'])
                ? explode(' ', $responseArray['scope']) : null;
            $session->expires
                = isset($responseArray['expires_in'])
                ? (time() + ($responseArray['expires_in'])) : null;

            return true;
        }
    }
    
    /**
     * Post something to a foreign host
     *
     * @param string      $file         POST target URL
     * @param array       $data_to_send POST data
     * @param string|null $access_token PAIA access token for current session
     *
     * @return string                   POST response
     *
     * @throws ILSException
     */
    protected function paiaLoginRequest(string $file, array $data_to_send, string $access_token = null) : string
    {
        $postData = http_build_query($data_to_send);

        $http_headers = [];
        
        if (isset($access_token)) {
            $http_headers['Authorization'] = 'Bearer ' . $access_token;
        }

        try {
            $result = $this->httpService->post(
                $this->paiaURL . $file,
                $postData,
                'application/x-www-form-urlencoded; charset=UTF-8',
                $this->paiaTimeout,
                $http_headers
            );
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
        }
        if (!$result->isSuccess()) {
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received'
            );
        }
        return ($result->getBody());
    }

    /**
     * Post something to a foreign host
     *
     * @param string $file         POST target URL
     * @param array  $data_to_send POST data
     * @param string $access_token PAIA access token for current session
     *
     * @return string              POST response
     *
     * @throws ILSException
     */
    protected function paiaPostRequest($file, $data_to_send, $access_token = null) : string
    {
        // json-encoding
        $postData = json_encode($data_to_send);

        $http_headers = [];
        if (isset($access_token)) {
            $http_headers['Authorization'] = 'Bearer ' . $access_token;
        }

        try {
            $result = $this->httpService->post(
                $this->paiaURL . $file,
                $postData,
                'application/json; charset=UTF-8',
                $this->paiaTimeout,
                $http_headers
            );
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // log error for debugging
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received'
            );
        }
        // return any result as error-handling is done elsewhere
        return ($result->getBody());
    }
    
    /**
     * Post something at given URL and return it as json_decoded array
     *
     * @param string $file POST target URL
     * @param array  $data POST data
     *
     * @return array
     *
     * @throws ILSException
     */
    protected function paiaPostAsArray($file, $data) : array
    {
        $responseJson = $this->paiaPostRequest(
            $file,
            $data,
            $this->getSession()->access_token
        );

        try {
            $responseArray = $this->paiaParseJsonAsArray($responseJson);
        } catch (ILSException $e) {
            $this->debug($e->getCode() . ':' . $e->getMessage());
            /* TODO: do not return empty array, this causes eventually confusion */
            return [];
        }

        return $responseArray;
    }

    /**
     * Public Function which changes the password in the library system
     * (not supported prior to VuFind 2.4)
     *
     * @param array $details Array with patron information, newPassword and
     *                       oldPassword.
     *
     * @return array An array with patron information.
     *
     * @throws ForbiddenException
     */
    public function changePassword($details) : array {
        $details = parent::changePassword($details);
        $details['status'] = $this->translate($details['status']);

        return $details;
    }
}