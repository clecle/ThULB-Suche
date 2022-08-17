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

use VuFind\RecordDriver\DefaultRecord;
use VuFind\RecordDriver\SolrDefault;
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
     * Return an array of associative fulltext URL arrays with one or more of the
     * following keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getFullTextURL() {
        return $this->hasFullText() ? parent::getURLs() : [];
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
        return $this->fields['IsOpenAccess'][0] ?? false;
    }

    /**
     * Get information, for libraries with this record
     *
     * Return structure:
     * array[]                  array Information for a specific library
     *         ['sequence']     string
     *         ['dbid']         string
     *         ['name']         string Name of the library
     *         ['url']          string URL to the homepage of the library
     *         ['sourceTypes']  array
     *
     * @return boolean|array
     */
    public function getDatabaseXML() {
        return (array_key_exists('Database_xml', $this->fields) && $this->fields['Database_xml'] && is_array($this->fields['Database_xml']))
            ? $this->fields['Database_xml']
            : false;
    }

    /**
     * Returns one of three things: a full URL to a thumbnail preview of the record
     * if an image is available in an external system; an array of parameters to
     * send to VuFind's internal cover generator if no fixed URL exists; or false
     * if no thumbnail can be generated.
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        $params = DefaultRecord::getThumbnail($size);

        // Support thumbnails embedded in the Summon record when no unique identifier
        // is found... (We don't use them in cases where we have an identifier, since
        // we want to allow these to be passed to configured external services).
        if (!isset($params['oclc']) && !isset($params['issn'])
            && !isset($params['isbn']) && !isset($params['upc'])
            && ($size === 'medium' || $size === 'large')
        ) {
            if (isset($this->fields['thumbnail_m'][0])) {
                return ['proxy' => $this->fields['thumbnail_m'][0]];
            }
        }

        $formats = $this->getFormats();
        if (!empty($formats)) {
            $params['contenttype'] = $formats[0];
        }
        return $params;
    }

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @param bool $extended Whether to return a keyed array with the following
     * keys:
     * - heading: the actual subject heading chunks
     * - type: heading type
     * - source: source vocabulary
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        $subjects = [];
        $lowerSubjects = [];
        foreach ($this->subjectFields as $field => $fieldType) {
            if (!isset($this->fields[$field])) {
                continue;
            }
            foreach ($this->fields[$field] as $topic) {
                $topic = trim($topic);
                $value = $extended ? "$fieldType|||$topic" : $topic;

                // do not show the same subject with a different case
                if(!in_array(strtolower($value), $lowerSubjects)) {
                    $subjects[] = $value;
                    $lowerSubjects[] = strtolower($value);
                }
            }
        }
        unset($lowerSubjects);
        sort($subjects, SORT_STRING | SORT_FLAG_CASE);

        $retVal = [];
        foreach($subjects as $value) {
            if($extended) {
                $splitValues = explode('|||', $value);
                $retVal[] = [
                    'heading' => [$splitValues[1]],
                    'type' => $splitValues[0],
                    'source' => ''
                ];
            }
            else {
                $retVal[] = [$value];
            }
        }

        return $retVal;
    }

    public function getHoldings() {

        $retVal = [];

        foreach($this->getURLs() as $url) {
            $retVal['holdings']['Remote']['location'] = 'Remote';
            $retVal['holdings']['Remote']['items'][] = [
                'barcode' => true,
                'callnumber' => '',
                'location' => 'Remote',
                'remotedesc' => $url['desc'],
                'remotehref' => $url['url'],
                'remotetitle' => '',
                'status' => 'available',
            ];
        }

        return $retVal;
    }

    /**
     * Get access restriction notes for the record.
     *
     * @return array
     */
    public function getAccessRestrictions() : array
    {
        $retValue = [];
        if($this->isOpenAccess()) {
            $retValue[] = array(
                'desc' => 'Open Access',
                'link' => false
            );
        }

        return $retValue;
    }
}
