<?php
/**
 * Override of the VuFind Model for Summon records
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */

namespace ThULB\RecordDriver;

use VuFind\RecordDriver\Summon as OriginalSummon;

/**
 * Overwrites and extends VuFinds standard Summon RecordDriver
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Summon extends OriginalSummon
{
    public function getURLs()
    {
        $hasNoFulltext = !isset($this->fields['hasFullText']) || !$this->fields['hasFullText'];
        if (isset($this->fields['link']) && $hasNoFulltext) {
            return [
                [
                    'url' => $this->fields['link'],
                    'desc' => $this->translate('get_citation')
                ]
            ];
        } else {
            return parent::getURLs();
        }
    }

    /**
     * Get a full, free-form reference to the context of the item that contains this
     * record (i.e. volume, year, issue, pages).
     *
     * @return string
     */
    public function getContainerReference()
    {
        $str = '';
        $vol = $this->getContainerVolume();
        if (!empty($vol)) {
            $str .= $this->translate('citation_volume_abbrev')
                . ' ' . $vol;
        }
        $no = $this->getContainerIssue();
        if (!empty($no)) {
            if (strlen($str) > 0) {
                $str .= ', ';
            }
            $str .= $this->translate('citation_issue_abbrev')
                . ' ' . $no;
        }
        $start = $this->getContainerStartPage();
        if (!empty($start)) {
            if (strlen($str) > 0) {
                $str .= ', ';
            }
            $end = $this->getContainerEndPage();
            if ($start == $end) {
                $str .= $this->translate('citation_singlepage_abbrev')
                    . ' ' . $start;
            } else {
                $str .= $this->translate('citation_multipage_abbrev')
                    . ' ' . $start . ' - ' . $end;
            }
        }
        return $str;
    }

    /**
     * Get an array of all corporate authors.
     *
     * @return array
     */
    public function getCorporateAuthors()
    {
        $authors = [];
        if (isset($this->fields['CorporateAuthor_xml'])) {
            for ($i = 0; $i < count($this->fields['CorporateAuthor_xml']); $i++) {
                if (isset($this->fields['CorporateAuthor_xml'][$i]['name'])) {
                    $authors[] = $this->fields['CorporateAuthor_xml'][$i]['name'];
                }
            }
        }
        return $authors;
    }
    
    /**
     * Get the number of citations of this record
     * 
     * @return boolean|integer
     */
    public function getCitatedReferencesCount()
    {
        return (array_key_exists('ISICitedReferencesCount', $this->fields) && $this->fields['ISICitedReferencesCount'])
                ? $this->fields['ISICitedReferencesCount'][0]
                : false;
    }
    
    /**
     * Get a link with information about where this record was cited
     * 
     * @return boolean|string
     */
    public function getCitatedReferencesLink()
    {
        return (array_key_exists('ISICitedReferencesURI', $this->fields) && $this->fields['ISICitedReferencesURI'])
                ? $this->fields['ISICitedReferencesURI'][0]
                : false;
    }
    
     /**
     * Get OpenAccess information
     * 
     * @return bool
     */
    public function isOpenAccess()
    {
        return (array_key_exists('IsOpenAccess', $this->fields) && $this->fields['IsOpenAccess'])
                ? $this->fields['IsOpenAccess'][0]
                : false;
    }
}
