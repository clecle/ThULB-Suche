<?php
/**
 * Unpaywall DOI linker
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2019.
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
 * @category VuFind
 * @package  DOI
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:doi_linkers Wiki
 */
namespace ThULB\DoiLinker;

use VuFind\Log\LoggerAwareTrait;
use VuFind\DoiLinker\Unpaywall as OriginalUnpaywall;
use Laminas\Log\LoggerAwareInterface;

/**
 * Unpaywall DOI linker
 *
 * @category VuFind
 * @package  DOI
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:doi_linkers Wiki
 */
class Unpaywall extends OriginalUnpaywall implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const API_CALL_TIMEOUT = 5;

    /**
     * Given an array of DOIs, perform a lookup and return an associative array
     * of arrays, keyed by DOI. Each array contains one or more associative arrays
     * with required 'link' (URL to related resource) and 'label' (display text)
     * keys and an optional 'icon' (URL to icon graphic) key.
     *
     * @param array $doiArray DOIs to look up
     *
     * @return array
     */
    public function getLinks(array $doiArray) : array
    {
        $response = parent::getLinks($doiArray);
        if(!empty($response)) {
            foreach($response as $doi => $doiData) {
                foreach($doiData as $index => $data) {
                    $response[$doi][$index]['label'] = 'PDF';
                    $response[$doi][$index]['source'] = 'unpaywall';
                }
            }
        }
        return $response;
    }

    /**
     * Takes a DOI and do an API call to Unpaywall service
     *
     * @param string $doi DOI
     *
     * @return string|null
     */
    protected function callApi($doi) : ?string{
        try {
            $url = $this->apiUrl . "/" . urlencode($doi) . "?"
                . http_build_query(['email' => $this->email]);
            $client = $this->httpService->createClient($url);
            $client->setOptions(['timeout' => self::API_CALL_TIMEOUT]);
            $response = $client->send();
            if ($response->isSuccess()) {
                return $response->getBody();
            }
        }
        catch (\Exception $e) {
            $this->logWarning($e->getMessage());
        }

        return null;
    }
}
