<?php
/**
 * BrowZine DOI linker
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace ThULB\DoiLinker;

use VuFind\DoiLinker\BrowZine as OriginalBrowZine;

/**
 * BrowZine DOI linker
 *
 * @category VuFind
 * @package  DOI
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
class BrowZine extends OriginalBrowZine
{
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
    public function getLinks(array $doiArray)
    {
        $baseIconUrl = 'https://assets.thirdiron.com/images/integrations/';
        $response = [];
        foreach ($doiArray as $doi) {
            $data = $this->connector->lookupDoi($doi)['data'] ?? null;
            if ($this->arrayKeyAvailable('browzineWebLink', $data)) {
                $response[$doi][] = [
                    'link' => $data['browzineWebLink'],
                    'label' => $this->translate('BrowZine'),
                    'icon' => $baseIconUrl . 'browzine-open-book-icon.svg',
                    'source' => 'browzine',
                    'data' => $data,
                ];
            }
            if ($this->arrayKeyAvailable('fullTextFile', $data)) {
                $response[$doi][] = [
                    'link' => $data['fullTextFile'],
                    'label' => $this->translate('LibKey'),
                    'icon' => $baseIconUrl . 'browzine-pdf-download-icon.svg',
                    'source' => 'browzine',
                    'data' => $data,
                ];
            }
        }
        return $response;
    }
}
