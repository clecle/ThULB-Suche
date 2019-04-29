<?php
/**
 * Description
 *
 * PHP version 5
 *
 * Copyright (C) Verbundzentrale des GBV, Till Kinstler 2014.
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
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\RecordDriver;

use VuFind\RecordDriver\Response\PublicationDetails;

/**
 * Customized record driver for Records of the Solr index of Verbundzentrale
 * Göttingen (VZG)
 *
 * @category ThULB
 * @package  RecordDrivers
 * @author   Till Kinstler <kinstler@gbv.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */

class SolrVZGRecord extends \VuFind\RecordDriver\SolrMarc
{
    const PPN_LINK_ID_PREFIX = 'DE-601';
    const ZDB_LINK_ID_PREFIX = 'DE-600';
    const DNB_LINK_ID_PREFIX = 'DE-101';

    const SEPARATOR = '|\/|';

    /**
     * Contains all separators that are often part of MARC field entries and
     * should be eleminated, when custom formatting is applied
     *
     * @var array
     */
    protected static $defaultSeparators = [' = ',' =', '= ', ' : ', ' :', ': '];
    
    /**
     * Contains all placeholders that are often used to fill missing MARC
     * subfields and should be removed in the displayed string
     * 
     * @var array
     */
    protected static $defaultPlaceholders = ['[...]'];

    /**
     * Short title of the record.
     *
     * @var string 
     */
    protected $shortTitle;
    
    /**
     * Title of the record.
     *
     * @var string
     */
    protected $title;
    
    /**
     * The title of the record with highlighing markers
     * 
     * @var string
     */
    protected $highlightedTitle;

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        $noStatus = true;
        $noStatusMedia = ['Article', 'eBook', 'eJournal', 'electronic Article', 'electronic Resource'];
        
        foreach ($this->getFormats() as $format) {
            if (!in_array($format, $noStatusMedia)) {
                $noStatus = false;
                break;
            }
        }
        
        $leader = $this->getMarcRecord()->getLeader();
        $ordered = $this->getConditionalFieldArray('980', ['e'], true, '', ['2' => '31', 'e' => 'a']);
        
        return ($leader[7] !== 's' && $leader[7] !== 'a' && $leader[19] !== 'a' && !$noStatus && empty($ordered));
        
    }

    /**
     * Get the short (pre-subtitle) title of the record.
     *
     * @return string
     */
    public function getShortTitle()
    {
        if (is_null($this->shortTitle)) {
            $shortTitle = $this->getFormattedMarcData('245a : 245b') ?:
                              $this->getFormattedMarcData('490v: 490a');

            if ($shortTitle === '')
            {
                $shortTitle = isset($this->fields['title_short']) ?
                    is_array($this->fields['title_short']) ?
                    $this->fields['title_short'][0] : $this->fields['title_short'] : '';
            }

            $this->shortTitle = $shortTitle;
        }
        
        return $this->shortTitle;
    }

    /**
     * Get a highlighted title string, if available.
     *
     * @return string
     */
    public function getHighlightedTitle()
    {
        if (is_null($this->highlightedTitle)) {
            if (!$this->highlight && !is_array($this->highlight)) {
                return '';
            }
            
            $this->highlightedTitle = '';
            foreach ($this->highlightDetails as $highlightElement => $highlightDetail) {
                if (strpos($highlightElement, 'title') !== false) {
                    $this->highlightedTitle .= implode('', $this->groupHighlighting($highlightDetail));
                }
            }

            // Apply highlighting to our customized title
            if ($this->highlightedTitle) {
                $this->highlightedTitle = $this->transferHighlighting(
                        $this->getTitle(),
                        $this->highlightedTitle
                    );
            }
        }
        
        return $this->highlightedTitle;
    }

    public function getTitle()
    {
        if (is_null($this->title)) {
            $title = $this->getFormattedMarcData('245n: (245p. (245a : 245b))') ?: $this->getFormattedMarcData('490v: 490a');

            if ($title === '') {
                isset($this->fields['title']) ?
                            is_array($this->fields['title']) ?
                            $this->fields['title'][0] : $this->fields['title'] : '';
            }

            $this->title = $title;
        }
        
        return $this->title;
    }
    
    /**
     * Get the subtitle of the record.
     *
     * @return string
     */
    public function getSubtitle()
    {
        return isset($this->fields['title_sub']) ?
            is_array($this->fields['title_sub']) ?
            $this->fields['title_sub'][0] : $this->fields['title_sub'] : '';
    }
    
    /**
     * Get the title of the item that contains this record (i.e. MARC 773s of a
     * journal).
     *
     * @return string
     */
    public function getContainerTitle()
    {
        $containerTitle = $this->getFieldArray('773', ['t'], false);
        return ($containerTitle) ? $containerTitle[0] : '';
    }

    /**
     * Get a full, free-form reference to the context of the item that contains this
     * record (i.e. volume, year, issue, pages).
     *
     * @return string
     */
    public function getContainerReference()
    {
        $containerRef = $this->getFieldArray('773', ['g'], false); 
       return ($containerRef) ? $containerRef[0] : '';
    }
    
    public function getContainerLink()
    {
        $containerField = $this->getMarcRecord()->getField('773');
        return $this->getLinkFromField($containerField);
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
        $params = parent::getThumbnail($size);
        
        $params['contenttype'] = $this->fields['format'] ? $this->fields['format'][0] : '';
        
        return $params;
    }

    /**
     * Get basic classification numbers of the record.
     *
     * @return array
     */
    public function getBasicClassification()
    {
        $fields = $this->getConditionalFieldArray('084', ['a'], true, ' ', ['2' => 'bkl']);
        
        return $fields;
    }

    /**
     * Get classification numbers of the record in the "Thüringen-Bibliographie".
     *
     * @return array
     */
    public function getThuBiblioClassification()
    {
        $fields = $this->getConditionalFieldArray('983', ['a'], true, ' ', ['2' => '31']);
        
        return $fields;
    }
    
    /**
     * extract ZDB Number from 035 $a
     * 
     * searches for a string like "(DE-599)ZDBNNNNNN"
     * where DE-599 stands for ISIL - Staatsbibliothek Berlin
     * followed by ZDB Number
     * 
     * @return array
     */
    public function getZDBID() {
        $id_nums = $this->getFieldArray('035', ['a']);
        $zdb_nums[] = "";

        foreach ($id_nums as $id_num) {
            if (strpos($id_num, '(DE-599)ZDB') !== false) {
                array_push($zdb_nums, substr($id_num, 11));
            }
        }
        
        return $zdb_nums;
    }
    
    /**
     *  Erscheinungsverlauf from 362 $a
     * 
     * @TODO repeatable?
     * 
     * @return string
     */
    public function getNumbering() {
        return $this->getFirstFieldValue('362', ['a']);
    }
    
    /**
     * Erscheinungsverlauf from 515 $a
     * 
     * not repeatable
     * 
     * @return string
     */
    public function getNumberingPeculiarities() {
        return $this->getFirstFieldValue('515', ['a']);
    }
    
    /**
     * Anmerkungen from 546 $a
     * 
     * not repeatable
     * 
     * @return string
     */
    public function getLanguageNotes() {
        return $this->getFirstFieldValue('546', ['a']);
    }

    /**
     * Fingerprint information from Marc Field 026
     * 
     * not repeatable
     * 
     * @return string
     */
    public function getFingerprint()
    {
        return $this->getFieldArray('026', ['e', '5'], false);
    }
    
    // Bibliographic citation from Marc field 510
    public function getBibliographicCitation()
    {
        return implode(' ; ', $this->getFieldArray('510', ['a'], false));
    }
    
    /**
     * Get an array of physical descriptions of the item.
     *
     * @return array
     */
    public function getPhysicalDescriptions()
    {
        $fields = $this->getMarcRecord()->getFields('300');
        
        $physicalDescriptions = [];
        foreach ($fields as $singleField) {
            $pdPt1 = $this->getSubfieldArray($singleField, ['a', 'b'], true, ' : ');
            $pdPt2 = $this->getSubfieldArray($singleField, ['c', 'd', 'e'], true, ' ; ');
            
            if (!empty($pdPt1) && !empty($pdPt2)) {
                $physicalDescriptions[] = $pdPt1[0] . ' ; ' . $pdPt2[0];
            } else if (!empty($pdPt1)) {
                $physicalDescriptions[] = $pdPt1[0];
            } else if (!empty($pdPt2)) {
                $physicalDescriptions[] = $pdPt2[0];
            }
        }
        
        return $physicalDescriptions;
    }
    
    /**
     * Get the scale of a map.
     * 
     * @return string
     */
    public function getCartographicScale()
    {
        return $this->getFieldArray('255', ['a'], true, ' ; ');
    }
    
    /**
     * Get the projection of a map.
     * 
     * @return string
     */
    public function getCartographicProjection()
    {
        return $this->getFirstFieldValue('255', ['b']);
    }
    
    /**
     * Get the coordinates of a map.
     * 
     * @return string
     */
    public function getCartographicCoordinates()
    {
        return $this->getFirstFieldValue('255', ['c']);
    }
    
    /**
     * Get the equinox of a map.
     * 
     * @return string
     */
    public function getCartographicEquinox()
    {
        return $this->getFirstFieldValue('255', ['e']);
    }
    
    /**
     * Generates a single line with basic publication information including the
     * first location of the publication, the publisher, the year and the 
     * edition.
     * 
     * @return String
     */
    public function getReducedPublicationInfo()
    {
        return $this->getFormattedMarcData('250a - (((264a : 264b), 264c)');
    }
    
    public function getDissertationNote()
    {
        $dissNote = $this->getFieldArray('502', ['a', 'b', 'c', 'd', 'g', 'o'], true, ', ');
        return ($dissNote) ? ltrim($dissNote[0], '@') : null;
    }
    
    public function getPartInfo()
    {
        $nSubfields = $this->getFieldArray('245', ['n'], false);
        $pSubfields = $this->getFieldArray('245', ['p'], false);
        
        $numOfEntries = max([count($nSubfields), count($pSubfields)]);
        
        $partInfo = '';
        for ($i = 0; $i < $numOfEntries; $i++) {
            $n = (isset($nSubfields[$i]) && !in_array($nSubfields[$i], self::$defaultPlaceholders)) ? $nSubfields[$i] : '';
            $p = (isset($pSubfields[$i]) && !in_array($pSubfields[$i], self::$defaultPlaceholders)) ? $pSubfields[$i] : '';
            $separator = ($n && $p) ? ': ' : '';
            $partInfo .= (($i > 0 && ($n || $p)) ? ' ; ' : '') . 
                             $n . $separator . $p;
        }
        
        return $partInfo;
    }

    /**
     * Get the main authors of the record.
     *
     * @return array
     */
    public function getPrimaryAuthors()
    {
        $author = $this->getFormattedMarcData('100a (100b (\((100c, 100d)\)))( 100g)');
        return $author ? [$author] : [];
    }
    
    /**
     * Get the roles of the main authors of the record.
     * 
     * @return array
     */
    public function getPrimaryAuthorsRoles()
    {
        $role = $this->getFirstFieldValue('100', ['4']);
        return $role ? [$role] : [];
    }

    /**
     * Get an array of all secondary authors (complementing getPrimaryAuthors()).
     *
     * @return array
     */
    public function getSecondaryAuthors()
    {
        $secondaryAuthors = [];
        $authorData = [];
        $relevantFields = [
                '700' => ['a', 'b', 'c', 'd', 'g'],
                '710' => ['a', 'b', 'c', 'd', 'g', 'n']
            ];
        $formattingRules = [
                '700' => '700a (700b (\((700c, 700d)\)))( 700g)',
                '710' => '710a, (710b, (\((710n, (710d, 710c))\)))( 710g)'
            ];
        
        foreach ($relevantFields as $fieldnumber => $subfields) {
            $fields = $this->getMarcRecord()->getFields($fieldnumber);
            foreach ($fields as $field) {
                $fieldData = [];
                foreach ($field->getSubfields() as $subfield) {
                    if (in_array($subfield->getCode(), $subfields)) {
                        $fieldData[$fieldnumber . $subfield->getCode()] = 
                                isset($fieldData[$fieldnumber . $subfield->getCode()]) ? 
                                    $fieldData[$fieldnumber . $subfield->getCode()] . 
                                        ', ' . $subfield->getData() :
                                    $subfield->getData();
                    }
                }
                
                if ($fieldData) {
                    $secondaryAuthors[] = $this->getFormattedMarcData(
                            $formattingRules[$fieldnumber],
                            true, 
                            true, 
                            $fieldData
                        );
                }
            }
        }
        
        return $secondaryAuthors;
    }

    /**
     * Get an array of all secondary authors roles (complementing
     * getPrimaryAuthorsRoles()).
     *
     * @return array
     */
    public function getSecondaryAuthorsRoles()
    {
        $roles = [];
        foreach (['700', '710'] as $fieldnumber) {
            $fields = $this->getMarcRecord()->getFields($fieldnumber);
            foreach ($fields as $field) {
                $fieldData = [];
                foreach ($field->getSubfields() as $subfield) {
                    if ($subfield->getCode() === '4') {
                        $roles[] = $subfield->getData();
                        continue 2;
                    }
                }
                $roles[] = '';
            }
        }
        
        
        return $roles;
    }
    
    /**
     * Get an array of conferences or congresses, i.e. the names of meetings,
     * with wich the publication was created 
     * 
     * @return array
     */
    public function getMeetingNames()
    {
        $meetingNames = [];
        
        $fields = $this->getMarcRecord()->getFields('711');
        foreach ($fields as $field) {
            $fieldData = [];
            foreach ($field->getSubfields() as $subfield) {
                if (in_array($subfield->getCode(), ['a', 'c', 'd', 'n'])) {
                    $fieldData['711' . $subfield->getCode()] = 
                            isset($fieldData['711' . $subfield->getCode()]) ? 
                                ', ' . $subfield->getData() : $subfield->getData();
                }
            }

            $meetingNames[] = $this->getFormattedMarcData(
                    '(711a (\((711n, (711d, 711c))\))',
                    true, 
                    true, 
                    $fieldData
                );
        }
        
        return $meetingNames;
    }
    
    /**
     * Get the corporate authors (if any) for the record
     * 
     * @return array
     */
    public function getCorporateAuthors()
    {
        $author = $this->getFormattedMarcData('110a, (110b, (\((110c, 110d)\)))( 110g)');
        return $author ? [$author] : [];
    }
    
    /**
     * Get the roles of corporate authors (if any) for the record. 
     * 
     * @return array
     */
    public function getCorporateAuthorsRoles()
    {
        $role = $this->getFirstFieldValue('110', ['4']);
        return $role ? [$role] : [];
    }
    
    

    /**
     * Get all record links related to the current record, that are precedings or
     * succeding titles respectively of the current record. Each link is returned
     * as array.
     * Format:
     * array(
     *        array(
     *               'title' => label_for_title
     *               'value' => link_name
     *               'link'  => link_URI
     *        ),
     *        ...
     * )
     *
     * @return null|array
     */
    public function getLineageRecordLinks()
    {
        // Load configurations:
        $fieldsNames = ['780', '785'];
        $useVisibilityIndicator
            = isset($this->mainConfig->Record->marc_links_use_visibility_indicator)
            ? $this->mainConfig->Record->marc_links_use_visibility_indicator : true;

        $retVal = [];
        foreach ($fieldsNames as $value) {
            $value = trim($value);
            $fields = $this->getMarcRecord()->getFields($value);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    // Check to see if we should display at all
                    if ($useVisibilityIndicator) {
                        $visibilityIndicator = $field->getIndicator('1');
                        if ($visibilityIndicator == '1') {
                            continue;
                        }
                    }

                    // Get data for field
                    $tmp = $this->getFieldData($field);
                    if (is_array($tmp)) {
                        $retVal[] = $tmp;
                    }
                }
            }
        }
        return empty($retVal) ? null : $retVal;
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs()
    {
        $isbns = [];
        $fields = $this->getMarcRecord()->getFields('020');
        
        foreach ($fields as $field) {
            $fieldData = [];
            foreach ($field->getSubfields() as $subfield) {
                if (in_array($subfield->getCode(), ['9', 'c'])) {
                    $fieldData['020' . $subfield->getCode()] = 
                            isset($fieldData['020' . $subfield->getCode()]) ? 
                                ', ' . $subfield->getData() : $subfield->getData();
                }
            }

            $isbns[] = $this->getFormattedMarcData(
                    '0209 : 020c',
                    true, 
                    true, 
                    $fieldData
                );
        }
        
        return $isbns;
    }
    
    

    /**
     * Get an array of all invalid ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getInvalidISBNs()
    {
        return $this->getFieldArray('020', ['z'], false);
    }
    
    /**
     * Get an array with the uniform title
     * 
     * @return array
     */
    public function getTitleOfWork()
    {
        $uniformTitle = $this->getFieldArray(
                '130',
                ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't'], 
                true,
                ', '
            );
        
        return ($uniformTitle) ?: $this->getFieldArray(
                '240',
                ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't'], 
                true,
                ', '
            );
    }
    
    /**
     * Get an array with printing places
     * 
     * @return array
     */
    public function getPrintingPlaces()
    {
        $printingPlaces = [];
        $fields = $this->getMarcRecord()->getFields('260');
        if (is_array($fields)) {
            foreach ($fields as $currentField) {
                $ind1 = $currentField->getIndicator(1);
                $ind2 = $currentField->getIndicator(2);
                if (($ind1 && trim($ind1)) || ($ind2 && trim($ind2))) {
                    continue;
                }
                $subfields = $this->getSubfieldArray($currentField, ['a']);
                if ($subfields) {
                    $printingPlaces[] = $subfields[0];
                }
            }
        }
        
        return $printingPlaces;
    }
    
    /**
     * Get a formatted string from different MARC fields 
     * 
     * @param string $format    Describes the desired formatted output; MARC 
     *                          fields and their subfields are coded with a 3
     *                          digit number that is immediately followed by the
     *                          character of the subfield (e.g. "260a");
     *                          to make hints for the separator priority in case
     *                          of missing MARC fields, simple parentheses are
     *                          used; examples:
     *                          - "264a : 264b, 264c. 250a": no information for
     *                            separator priority - they are all treated as
     *                            postfix; if e.g 264b is missing, the output is 
     *                            "264a : 264c. 250a"
     *                          - "((264a : 264b), 264c). 250a": the evaluation 
     *                            order is provided; if e.g. 264b is missing,
     *                            the output is "264a, 264c. 250a"
     * @param boolean $removeSeparators MARC subfields may contain separators at
     *                                  the beginning or at the end; Set to true,
     *                                  when they should be removed from the
     *                                  strings (default)
     * @param boolean $ignorePlaceholders   Missing MARC subfields may contain
     *                                      placeholder strings; Set to true, to
     *                                      remove them
     */
    protected function getFormattedMarcData($format, $removeSeparators = true, $ignorePlaceholders = true, $data = [])
    {   
        // keep all escaped parantheses by converting them to their html equivalent
        $format = str_replace('\(', '&#40;', $format);
        $format = str_replace('\)', '&#41;', $format);
        
        // get all MARC data that is required (only first field values)
        $marcData = [];
        $marcFieldStrings = [];
        preg_match_all('/[\d]{3}[\da-z]{1}/', $format, $marcFieldStrings, PREG_OFFSET_CAPTURE);
        foreach ($marcFieldStrings[0] as $i => $marcFieldInfo) {
            $fieldNumber = substr($marcFieldInfo[0], 0, 3);
            $subfieldChar = substr($marcFieldInfo[0], 3);
            if ($data && isset($data[$fieldNumber . $subfieldChar])) {
                $value = $data[$fieldNumber . $subfieldChar];
            } else {
                $value = empty ($data) ? $this->getFirstFieldValue($fieldNumber, [$subfieldChar]) : null;
            }
            $value = ($ignorePlaceholders && !is_null($value) && in_array($value, self::$defaultPlaceholders)) ? null : $value;
            if (!is_null($value)) {
                $marcData[$fieldNumber . $subfieldChar] = $value;
                $replacement = 'T';
                // check for separators in the marc field and marc the separator
                // in the format string as removable
                if ($removeSeparators) {
                    foreach (self::$defaultSeparators as $separator) {
                        if (substr($value, 0, strlen($separator)) === $separator) {
                            $replacement = 'ST';
                        } else if ((substr($value, -strlen($separator)) === $separator)) {
                            $replacement = 'TS';
                        }
                    }
                }
                $format = str_replace($fieldNumber . $subfieldChar, $replacement, $format);
            } else {
                $format = str_replace($fieldNumber . $subfieldChar, 'F', $format);
            }
        }
        
        // Eliminate all missing fields and surrounding content inside the
        // parantheses:
        $format = preg_replace('/[^T\(\)]*F[^T\(\)]*/', '', $format);
        // Remove all content in parantheses, that doesn't represent existing
        // Marc fields together with surrounding content
        $format = preg_replace('/[^T\(\)]*\([^T]*\)[^T\(\)]*/', '', $format);
        // Remove separators for fields, where they are given with the field
        // content
        $format = preg_replace('/([^T\(\)]+S)|(S[^T\(\)]+)/', ' ', $format);
        // Transform to a valid formatter string
        $format = str_replace('T', '%s', str_replace('(', '', str_replace(')', '', $format)));
        
        
        // keep all escaped parantheses by converting them to their html equivalent
        $format = str_replace('&#40;', '(', $format);
        $format = str_replace('&#41;', ')', $format);
        
        return trim(vsprintf($format, $marcData));
    }

    /**
     * Returns the array element for the 'getAllRecordLinks' method
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return array|bool                 Array on success, boolean false if no
     * valid link could be found in the data.
     */
    protected function getFieldData($field)
    {
        $leader = $this->getMarcRecord()->getLeader();
        // Make sure that there is a t field to be displayed:
        if ($title = $field->getSubfield('t')) {
            $title = $title->getData();
        } else if (strtolower($leader[7]) === 'm'
            && strtolower($leader[19]) === 'c'
        ) {
            $title = $this->getFirstFieldValue('245', ['a']);
        } else {
            $title = false;
        }
        
        $link = $this->getLinkFromField($field, $title);
        
        $pages = $field->getSubfield('g');
        // Make sure we have something to display:
        return ($link === false) ? false : [
            'title' => $this->getRecordLinkNote($field),
            'value' => $title ? $title : 'Link',
            'link'  => $link,
            'pages' => $pages ? $pages->getData() : ''
        ];
    }
    
    /**
     * Extract link information from a given MARC field
     * 
     * @param File_MARC_Data_Field $field
     * @param string $title Optional title to search for in a fallback search
     * @return bool|array
     */
    protected function getLinkFromField($field, $title = false)
    {
        $linkTypeSetting = isset($this->mainConfig->Record->marc_links_link_types)
            ? $this->mainConfig->Record->marc_links_link_types
            : 'id,isbn,issn,dnb,zdb,title';
        $linkTypes = explode(',', $linkTypeSetting);
        $linkFields = $field->getSubfields('w');

        // Run through the link types specified in the config.
        // For each type, check field for reference
        // If reference found, exit loop and go straight to end
        // If no reference found, check the next link type instead
        foreach ($linkTypes as $linkType) {
            switch (trim($linkType)){
            case 'id':
                foreach ($linkFields as $current) {
                    $bibLink = trim($this->getIdFromLinkingField($current, self::PPN_LINK_ID_PREFIX), '*');
                    if ($bibLink) {
                        $link = ['type' => 'bib', 'value' => $bibLink];
                    }
                }
                break;
            case 'isbn':
                if ($isbn = $field->getSubfield('z')) {
                    $link = [
                        'type' => 'isbn', 'value' => trim($isbn->getData()),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'issn':
                if ($issn = $field->getSubfield('x')) {
                    $link = [
                        'type' => 'issn', 'value' => trim($issn->getData()),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'dnb':
                foreach ($linkFields as $current) {
                    $bibLink = $this->getIdFromLinkingField($current, self::DNB_LINK_ID_PREFIX);
                    if ($bibLink) {
                        $link = ['type' => 'dnb', 'value' => $bibLink];
                    }
                }
                break;
            case 'zdb':
                foreach ($linkFields as $current) {
                    $bibLink = $this->getIdFromLinkingField($current, self::ZDB_LINK_ID_PREFIX);
                    if ($bibLink) {
                        $link = ['type' => 'zdb', 'value' => $bibLink];
                    }
                }
                break;
            case 'title':
                if ($title) {
                    $link = ['type' => 'title', 'value' => $title];
                }
                break;
            }
            // Exit loop if we have a link
            if (isset($link)) {
                break;
            }
        }
        
        return isset($link) ? $link : false;
    }

    /**
     * Support method for getFieldData() -- factor the relationship indicator
     * into the field number where relevant to generate a note to associate
     * with a record link.
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return string
     */
    protected function getRecordLinkNote($field)
    {
        // If set, use relationship information from subfield i and n
        if ($subfieldI = $field->getSubfield('i')) {
            $data = trim($subfieldI->getData());
            if (!empty($data)) {
                if ($subfieldN = $field->getSubfield('n')) {
                    $data .= ' ' . trim($subfieldN->getData());
                }
                return $data;
            }
        }

        // Normalize blank relationship indicator to 0:
        $relationshipIndicator = $field->getIndicator('2');
        if ($relationshipIndicator == ' ') {
            $relationshipIndicator = '0';
        }

        // Assign notes based on the relationship type
        $value = $field->getTag();
        switch ($value) {
        case '780':
            if (in_array($relationshipIndicator, range('0', '7'))) {
                $value .= '_' . $relationshipIndicator;
            }
            break;
        case '785':
            if (in_array($relationshipIndicator, range('0', '8'))) {
                $value .= '_' . $relationshipIndicator;
            }
            break;
        }

        return 'note_' . $value;
    }

    /**
     * Get general notes on the record.
     *
     * @return array
     */
    public function getGeneralNotes()
    {
        
        $titleVariations = [];
        
        $fields = $this->getMarcRecord()->getFields('246');
        foreach ($fields as $field) {
            $visibilityIndicator = $field->getIndicator('1');
            if ($visibilityIndicator != '1') {
                continue;
            }
            $fieldData = [];
            foreach ($field->getSubfields() as $subfield) {
                if (in_array($subfield->getCode(), ['a', 'f', 'g', 'i'])) {
                    $fieldData['246' . $subfield->getCode()] = 
                            isset($fieldData['246' . $subfield->getCode()]) ? 
                                ', ' . $subfield->getData() : $subfield->getData();
                }
            }

            $titleVariations[] = $this->getFormattedMarcData(
                    '246i: (246a, (246f, 246g))',
                    true, 
                    true, 
                    $fieldData
                );
        }
        
        return array_merge(
                $titleVariations,
                $this->getFieldArray('500'),
                $this->getFieldArray('501')
            );
    }

    /**
     * Get an array of all series names containing the record.  Array entries may
     * be either the name string, or an associative array with 'name' and 'number'
     * keys.
     *
     * @return array
     */
    public function getSeries()
    {
        $matches = [];

        // First check the 440, 800 and 830 fields for series information:
        $primaryFields = [
            '440' => ['a', 'p'],
            '800' => ['a', 'b', 'c', 'd', 'f', 'p', 'q', 't'],
            '810' => ['a', 'p'],
            '830' => ['a', 'p']];
        $matches = $this->getSeriesFromMARC($primaryFields);
        
        // Now check 490 and add it only if it has only a name and no numbering:
        foreach ($this->getSeriesFromMARC(['490' => ['a']]) as $match) {
            if (array_keys($match) === ['name']) {
                $matches[] = $match;
            }
        }
        
        if (!empty($matches)) {
            return $matches;
        }

        // Still no results found?  Resort to the Solr-based method just in case!
        return parent::getSeries();
    }

    /**
     * Support method for getSeries() -- given a field specification, look for
     * series information in the MARC record.
     *
     * @param array $fieldInfo Associative array of field => subfield information
     * (used to find series name)
     *
     * @return array
     */
    protected function getSeriesFromMARC($fieldInfo)
    {
        $matches = [];

        // Loop through the field specification....
        foreach ($fieldInfo as $field => $subfields) {
            // Did we find any matching fields?
            $series = $this->getMarcRecord()->getFields($field);
            if (is_array($series)) {
                foreach ($series as $currentField) {
                    // Can we find a name using the specified subfield list?
                    $name = $this->getSubfieldArray($currentField, $subfields);
                    if (!isset($name[0])) {
                        $volume = $this->getSubfieldArray($currentField, ['v']);
                        if ($volume) {
                            $name = $this->getConditionalFieldArray('490', ['a'], true, ' ', ['v' => $volume[0]]);
                        }
                    }
                    
                    if (isset($name[0])) {
                        $currentArray = ['name' => $name[0]];

                        // Can we find a number in subfield v?  (Note that number is
                        // always in subfield v regardless of whether we are dealing
                        // with 440, 490, 800 or 830 -- hence the hard-coded array
                        // rather than another parameter in $fieldInfo).
                        $number
                            = $this->getSubfieldArray($currentField, ['v']);
                        if (isset($number[0])) {
                            $currentArray['number'] = $number[0];
                        }
                        
                        $id = $this->getSubfieldArray($currentField, ['w'], false);
                        foreach ($id as $rawId) {
                            if (strpos($rawId, '(DE-601)') === 0) {
                                $currentArray['id'] = substr($rawId, 8);
                                break;
                            }
                        }

                        // Save the current match:
                        $matches[] = $currentArray;
                    }
                }
            }
        }

        return $matches;
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
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
    public function getURLs()
    {
        $retVal = [];
        
        $urls = $this->getMarcRecord()->getFields('856');
        foreach ($urls as $url) {
            $address = $url->getSubfield('u');
            $description = $url->getSubfield('y');
            if ($address && $description) {
                $retVal[] = [
                        'url'   => $address->getData(),
                        'desc'  => $description->getData()
                    ];
            }  
        }

        return $retVal;
    }
    
    /**
     * Return an array of all values extracted from the specified field/subfield
     * combination.  If multiple subfields are specified and $concat is true, they
     * will be concatenated together in the order listed -- each entry in the array
     * will correspond with a single MARC field.  If $concat is false, the return
     * array will contain separate entries for separate subfields. If an conditions
     * array is provided with subfield-value pairs, only those entries are selected,
     * that have a subfiled with that value.
     *
     * @param string $field      The MARC field number to read
     * @param array  $subfields  The MARC subfield codes to read
     * @param bool   $concat     Should we concatenate subfields?
     * @param string $separator  Separator string (used only when $concat === true)
     * @param array  $conditions contains key value pairs with a subfield as key
     *                           and the expected subfield content as value
     * 
     * @see VuFind\RecordDriver\SolrMarc::getFieldArray() for the original function
     *
     * @return array
     */
    protected function getConditionalFieldArray($field, $subfields = null, $concat = true,
        $separator = ' ', $conditions = []
    ) { 
        // Default to subfield a if nothing is specified.
        if (!is_array($subfields)) {
            $subfields = ['a'];
        }

        // Initialize return array
        $matches = [];

        // Try to look up the specified field, return empty array if it doesn't
        // exist.
        $fields = $this->getMarcRecord()->getFields($field);
        if (!is_array($fields)) {
            return $matches;
        }

        // Extract all the requested subfields, if applicable.
        foreach ($fields as $currentField) {
            foreach ($conditions as $conditionSubfield => $conditionValue) {
                $check = $this->getSubfieldArray($currentField, [$conditionSubfield]);
                if (!in_array($conditionValue, $check)) {
                    continue 2;
                }
            }
            $next = $next = $this
                ->getSubfieldArray($currentField, $subfields, $concat, $separator);
            $matches = array_merge($matches, $next);
        }

        return $matches;
    }
    
    /**
     * Criteria:
     *    Leader 07 = s
     *  OR
     *    Leader 19 = a
     *  OR
     *    Leader 007 00 = c
     *    AND
     *    Leader 007 01 = r
     * @return boolean
     * 
     * @deprecated
     * 
     */
    public function isOnlineOnlyRecord()
    {
      $leader = $this->getMarcRecord()->getLeader();
      if ( strtoupper($leader[7] ) == "S" ) {
        return true;
      }
      if ( strtoupper($leader[19]) == "A" ) {
        return true;
      }
      $val = $this->getMarcRecord()->getFields('007');
      if ( !empty($val) ) {
        $val2 = $val[0]->getData();
        if ( strtoupper(substr($val2, 0, 2)) == "CR" ) {
          return true;
        }
      }

      return false;
    }
    
    public function isNewsPaper()
    {
      $leader = $this->getMarcRecord()->getLeader();
      if ( strtoupper($leader[7] ) == "S" ) {
        return true;
      }

      return false;
    }

     /**
      * Return an array of all OnlineHoldings from MARCRecord
      * Field 981: for Links
      * Field 980: for description
      * Field 982: 
      * 
      * $txt = Text for displaying the link
      * $url = url to OnlineContent
      * $more = further description (PICA 4801)
      * $tmp = ELS-gif for Higliting ELS Links
      * 
      * @return array
      */
    public function getOnlineHoldings()
    {
      $retVal = [];
      
      /* extract all LINKS form MARC 981 */
      $links = $this->getConditionalFieldArray('981', ['1', 'y', 'r', 'w'], true, self::SEPARATOR, ['2' => '31']);

      if ( !empty($links) ){
        /* what kind of LINKS do we have?
         * is there more Information in MARC 980 / 982?
         */
        foreach ( $links as $link ) {
          $more = "";
          $linkElements = explode(self::SEPARATOR, $link);
          $id = (isset($linkElements[0]) ? $linkElements[0] : '');
          $txt = (isset($linkElements[1]) ? $linkElements[1] : '');
          $url = (isset($linkElements[2]) ? $linkElements[2] : '');
       
          /* do we have a picture? f.e. ELS-gif */
          if ( substr($txt, -3) == "gif" ) {
            $retVal[$id] = $txt;
            continue;
          }
          
          /* seems that the real LINK is in 981y if 981r or w is empty... */
          if ( empty($txt) ) {
            $txt = $url;
          }
          /* ... and vice versa */
          if ( empty($url) ) {
            $url = $txt;
            $txt = "fulltext";
          }

          /* Now, we are ready to extract extra-information
           * @details for each link is common catalogisation till RDA-introduction
           */
          $details = $this->getConditionalFieldArray('980', ['g', 'k'], false, '', ['2' => '31', '1' => $id]);
          
          if ( empty($details) ) {
            /* new catalogisation rules with RDA: One Link and single Details for each part */
            $details = $this->getConditionalFieldArray('980', ['g', 'k'], false, '', ['2' => '31']);
          }
          if ( !empty($details) ) {
            foreach ($details as $detail) {
              $more .= $detail."<br>";
            }
          } else {
            $more = "";
          }

          $corporates = $this->getConditionalFieldArray('982', ['a'], false, '', ['2' => '31', '1' => $id]);
          if ( !empty($corporates) ) {
            foreach ($corporates as $corporate) {
              $more .= $corporate."<br>";
            }
          }
          
          /* extract Info/Links with same ID
           * thats the case, if we have an ELS-gif,
           * so we assume, that the gif is set-up before.
           * f.e.
           * 981 |2 31  |1 00  |w http://kataloge.thulb.uni-jena.de/img_psi/2.0/logos/eLS.gif 
           * 981 |2 31  |1 00  |y Volltext  |w http://mybib.thulb.uni-jena.de/els/browser/open/557127483  
           */
          
          // we just need to show host as link-text
          $url_data = parse_url($url);
          $txt_sanitized = $url_data['host'];
          
          $tmp = (isset($retVal[$id])) ? $retVal[$id] : '';
          $retVal[$id] = $txt_sanitized . self::SEPARATOR .
              $txt . self::SEPARATOR .
              $url . self::SEPARATOR .
              $more . self::SEPARATOR .
              $tmp;
        }
      } else {
        $retVal = "";
      }
      return $retVal;
    }

    /**
      * Return an array of all Holding-Comments
      * Field 980g, k
      * 
      * @return array
      * 
      */
    public function getHoldingComments($epn_str)
    {
      $retVal = [];
      list($txt, $epn) = explode(":epn:", $epn_str);
      /* extract all Comments form MARC 980 */
      $comments_g = $this->getConditionalFieldArray('980', ['g', 'k'], false, '', ['2' => '31', 'b' => $epn] );
      $comments_k = $this->getConditionalFieldArray('980', ['k'], false, '', ['2' => '31', 'b' => $epn] );
      
      $comments = array($comments_g[0], $comments_k[0]);
      return $comments;
    }
    
    /**
     * Get the Hierarchy Type (false if none)
     *
     * @return string|bool
     */
    public function getHierarchyType()
    {
        $hierarchyType = isset($this->fields['hierarchytype'])
            ? $this->fields['hierarchytype'] : false;
        if (!$hierarchyType) {
            $hierarchyType = isset($this->mainConfig->Hierarchy->driver)
                ? $this->mainConfig->Hierarchy->driver : false;
        }
        return $hierarchyType;
        
        return false;
    }
    
    /**
     * Apply highlightings in one string to another.
     * 
     * @param string $plainString
     * @param string $highlightedString
     * @return string
     */
    protected function transferHighlighting($plainString, $highlightedString)
    {
        $num = preg_match_all(
                '/\{\{\{\{START_HILITE\}\}\}\}[^\{]+\{\{\{\{END_HILITE\}\}\}\}/',
                $highlightedString,
                $matches
            );
        $modifiedString = $plainString;
        if ($num) {
            $replacements = [];
            foreach (array_unique($matches[0]) as $match) {
                $content = str_replace('{{{{END_HILITE}}}}', '', substr($match, 20));
                $replacements[$content] = $match;
            }

            // sort array to have long keys at the end, because long search terms can
            // contain a shorter one and therefor should be replaced first
            $keySorter = function ($a, $b) {
                return strlen($a) - strlen($b);
            };
            uksort($replacements, $keySorter);

            // use a recursive function to make replacements
            $replace = function ($subject, $searches, $highlightings) use (&$replace) {
                $searchString = array_pop($searches);
                if (!$searchString) {
                    return $subject;
                }
                $highlightString = array_pop($highlightings);
                $parts = explode($searchString, $subject);
                if (is_array($parts) && $parts) {
                    foreach ($parts as $i => $part) {
                        $parts[$i] = trim($replace(' ' . $part . ' ', $searches, $highlightings));
                    }
                
                    return implode($highlightString, $parts);
                }
                
                return $subject;
            };
            
            $modifiedString = trim($replace(' ' . $plainString . ' ', array_keys($replacements), array_values($replacements)));
        }
        
        return $modifiedString;
    }
    
    protected function groupHighlighting($highlightString)
    {
        return preg_replace('/\{\{\{\{END_HILITE\}\}\}\}\s?\{\{\{\{START_HILITE\}\}\}\}/', ' ', $highlightString);
    }
}
