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

use Exception;
use File_MARC_Data_Field;
use File_MARC_Exception;
use VuFind\RecordDriver\Response\PublicationDetails;
use VuFind\RecordDriver\SolrMarc;
use Laminas\Config\Config;

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

class SolrVZGRecord extends SolrMarc
{
    const PPN_LINK_ID_PREFIX = 'DE-627';
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
     * The title of the record with highlighting markers
     * 
     * @var string
     */
    protected $highlightedTitle;

    /**
     * Marc format configuration
     *
     * @var Config
     */
    protected $marcFormatConfig;

    public function __construct($mainConfig = null, $recordConfig = null, $searchSettings = null, $marcFormatConfig = null)
    {
        $this->marcFormatConfig = $marcFormatConfig;
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
    }

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     *
     * @throws File_MARC_Exception
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
        $allCopies = $this->getConditionalFieldArray('980', ['e'], true, '', ['2' => '31']);

        return ($leader[7] !== 's' && $leader[7] !== 'a' && $leader[19] !== 'a'
            && !$noStatus && count($allCopies) !== count($ordered));
        
    }

    /**
     * Get the short (pre-subtitle) title of the record.
     *
     * @return string
     */
    public function getShortTitle()
    {
        if (is_null($this->shortTitle)) {
            $shortTitle = $this->getFormattedMarcData('245a : 245b ( / 245c)') ?:
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

    /**
     * Get the title of the item from 245 or 490.
     *
     * @return string
     */
    public function getTitle()
    {
        if (is_null($this->title)) {
            $title = $this->getFormattedMarcData('245n: (245p. (245a : 245b))') ?: $this->getFormattedMarcData('490v: 490a');

            if ($title === '') {
                $title = isset($this->fields['title']) ?
                    (is_array($this->fields['title']) ?
                            $this->fields['title'][0] : $this->fields['title']) : '';
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
     *
     * @throws File_MARC_Exception
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
     *
     * @throws File_MARC_Exception
     */
    public function getContainerReference()
    {
        $containerRef = $this->getFieldArray('773', ['g'], false);
        return ($containerRef) ? $containerRef[0] : '';
    }

    /**
     * Get the container link of the item from 773.
     *
     * @return string
     *
     * @throws File_MARC_Exception
     * @throws Exception
     */
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
     * Get basic classification numbers of the record. If available descriptions are returned as
     * an array with the values of the $j subfields, else description is null.
     *
     * Format:
     * array(
     *     'bklnumber' => classification_number
     *     'bklname'  => array(
     *         description_string_1,
     *         description_string_2,
     *         description_string_3,
     *         ...
     *     )
     * )
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getBasicClassification() {
        $fields = array();
        foreach($this->getMarcRecord()->getFields('936') as $dataField) {
            if($dataField->getIndicator(1) == 'b' && $dataField->getIndicator(2) == 'k') {
                $descriptions = array();
                foreach($dataField->getSubfields('j') as $subField) {
                    $descriptions[] = $subField->getData();
                }
                if($subFieldA = $dataField->getSubfield('a')) {
                    $fields[] = array(
                        'bklnumber' => $subFieldA->getData(),
                        'bklname' => count($descriptions) ? $descriptions : null
                    );
                }
            }
        }
        return $fields;
    }

    /**
     * Get classification numbers of the record in the "Thüringen-Bibliographie".
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getThuBiblioClassification()
    {
        $classNumbers = $this->getConditionalFieldArray('983', ['a'], true, ' ', ['2' => '31']);
        $thuBib = array();

        foreach($classNumbers as $classNumber) {
            $isThuBib = $this->getConditionalFieldArray('983', ['b', '0'], true, ' ', ['a' => $classNumber]);
            if( $isThuBib && preg_match('/.*<Thüringen>$/', $isThuBib[0])) {
                array_push($thuBib, $classNumber);
            }
        }
        return $thuBib;
    }

    /**
     * extract ZDB Number from 035 $a
     *
     * searches for a string like "(DE-599)ZDBNNNNNN"
     * where DE-599 stands for ISIL - Staatsbibliothek Berlin
     * followed by ZDB Number
     *
     * @return array
     *
     * @throws File_MARC_Exception
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
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getFingerprint()
    {
        return $this->getFieldArray('026', ['e', '5'], false);
    }
    
    // Bibliographic citation from Marc field 510

    /**
     * Get bibliographic citations from Marc field 510
     *
     * @return string
     *
     * @throws File_MARC_Exception
     */
    public function getBibliographicCitation()
    {
        return implode(' ; ', $this->getFieldArray('510', ['a'], false));
    }

    /**
     * Get an array of physical descriptions of the item.
     *
     * @return array
     *
     * @throws File_MARC_Exception
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
     *
     * @throws File_MARC_Exception
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

    /**
     * Get the dissertation notes of the item from 502.
     *
     * @return string
     *
     * @throws File_MARC_Exception
     */
    public function getDissertationNote()
    {
        $dissNote = $this->getFieldArray('502', ['a', 'b', 'c', 'd', 'g', 'o'], true, ', ');
        return ($dissNote) ? ltrim($dissNote[0], '@') : null;
    }

    /**
     * Get the part info of the item from 245.
     *
     * @return string
     *
     * @throws File_MARC_Exception
     */
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
        $author = $this->getFormattedMarcData('100a (100b)(, 100c)');
        return $author ? [$author] : [];
    }
    
    /**
     * Get the title and dates of the main authors of the record.
     * 
     * @return array
     */
    public function getPrimaryAuthorsDetails()
    {
        $information = $this->getFormattedMarcData('( 100g)');
        return $information ? [$information] : [];
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
     *
     * @throws File_MARC_Exception
     */
    public function getSecondaryAuthors()
    {
        $relevantFields = [
            '700' => ['a', 'b', 'c']
        ];
        $formattingRules = [
            '700' => '700a (700b)(, 700c)'
        ];

        return $this->getFormattedData($relevantFields, $formattingRules);
    }

    /**
     * Get an array of all secondary authors titles and dates (complementing getPrimaryAuthors()).
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getSecondaryAuthorsDetails()
    {
        $relevantFields = array('700' => ['g']);
        $formattingRules = array('700' => '( 700g)');

        return $this->getFormattedData($relevantFields, $formattingRules);
    }

    /**
     * Get an array of all secondary authors roles (complementing
     * getPrimaryAuthorsRoles()).
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getSecondaryAuthorsRoles()
    {
        $roles = [];
        $fields = $this->getMarcRecord()->getFields('700');
        foreach ($fields as $field) {
            foreach ($field->getSubfields() as $subfield) {
                if ($subfield->getCode() === '4') {
                    $roles[] = $subfield->getData();
                    continue 2;
                }
            }
            $roles[] = '';
        }

        return $roles;
    }

    /**
     * Get an array of conferences or congresses, i.e. the names of meetings,
     * with wich the publication was created
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getMeetingNames()
    {
        $relevantFields = array(
            '111' => ['a', 'c', 'd', 'g', 'n'],
            '711' => ['a', 'c', 'd', 'g', 'n']
        );
        $formattingRules = array(
            '111' => '111a \((111g, )(111n, )(111d, )(111c)\)',
            '711' => '711a \((711g, )(711n, )(711d, )(711c)\)'
        );
        return $this->getFormattedData($relevantFields, $formattingRules);
    }

    /**
     * Get the corporate authors (if any) for the record
     *
     * @return array
     * @throws File_MARC_Exception
     */
    public function getCorporateAuthors()
    {
        $relevantFields = array(
            '110' => ['a', 'b', 'c', 'd', 'g'],
            '710' => ['a', 'b', 'c', 'd', 'g']
        );
        $formattingRules = array(
            '110' => '110a (/ 110b, (\((110c, 110d)\)))( 110g)',
            '710' => '710a (/ 710b, (\((710c, 710d)\)))( 710g)'
        );
        return $authors = $this->getFormattedData($relevantFields, $formattingRules);
    }

    /**
     * Get the roles of corporate authors (if any) for the record.
     *
     * @return array
     * @throws File_MARC_Exception
     */
    public function getCorporateAuthorsRoles()
    {
        $roles = [];

        $fields = $this->getMarcRecord()->getFields('110|710', true);
        foreach ($fields as $field) {
            if ($subfield = $field->getSubField('4')) {
                $roles[] = $subfield->getData();
            } else {
                $roles[] = '';
            }
        }

        return $roles;
    }

    /**
     * Get all record links related to the current record, that are preceding or
     * succeeding titles respectively of the current record. Each link is returned
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
     *
     * @throws File_MARC_Exception
     * @throws Exception
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
                        if ($subfieldA = $field->getSubfield('a')) {
                            $tmp['value'] .= ' ' . trim($subfieldA->getData());
                        }
                        $retVal[] = $tmp;
                    }
                }
            }
        }

        return empty($retVal) ? null : $this->checkListForAvailability($retVal);
    }

    /**
     * Checks if the given records are available in the library.
     * The link field of records with PPNs not available in the library will be set to NULL.
     * The given list needs the following format:
     * array(
     *     array(
     *         'title' => label_for_title
     *         'value' => link_name
     *         'link'  => link_URI
     *     ),
     *     ...
     * )
     *
     * @param $recordLinkList
     *
     * @return array The list with unavailable links set to NULL.
     *
     * @throws Exception
     */
    protected function checkListForAvailability($recordLinkList) {
        if(!is_array($recordLinkList)) {
            return $recordLinkList;
        }

        // Get all linked PPNs
        $linkedPPNs = array();
        for($i = 0; $i < count($recordLinkList); $i++) {
            if(isset($recordLinkList[$i]['link']['value']) && $recordLinkList[$i]['link']['type'] == 'bib') {
                $linkedPPNs[] = $recordLinkList[$i]['link']['value'];
            }
        }

        // Check if the PPNs are available in ThULB
        if(count($linkedPPNs) > 0) {
            $result = $this->searchService->retrieveBatch('Solr', $linkedPPNs);

            $availablePPNs = array();
            /* @var $record SolrVZGRecord */
            foreach($result->getRecords() as $record) {
                $availablePPNs[] = $record->getUniqueID();
            }

            // Set links to NULL if not available
            foreach($recordLinkList as $index => $recordLink) {
                if (!in_array($recordLink['link']['value'], $availablePPNs)) {
                    $recordLinkList[$index]['link'] = null;
                }
            }
        }

        return $recordLinkList;
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getISBNs()
    {
        $relevantFields = array('020' => ['9', 'c']);
        $formattingRules = array('020' => '0209 : 020c');
        $conditions = array (['subfield' => 'z', 'operator' => '==', 'value' => null]);
        return $this->getFormattedData($relevantFields, $formattingRules, $conditions);
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getISMNs()
    {
        $relevantFields = array('024' => ['9', 'c']);
        $formattingRules = array('024' => '0249 024c');
        $conditions = array (['indicator' => '1', 'operator' => '==', 'value' => '2']);
        return $this->getFormattedData($relevantFields, $formattingRules, $conditions);
    }

    /**
     * Get an array of all invalid ISBNs associated with the record (may be empty).
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getInvalidISBNs()
    {
        $fields = $this->getMarcRecord()->getFields('020');

        $invalidISBNs = array();
        foreach($fields as $field) {
            if($field->getSubfield('z') && $field->getSubfield('9')) {
                $invalidISBNs[] = $field->getSubfield('9')->getData();
            }
        }

        return $invalidISBNs;
    }

    /**
     * Get an array with the uniform title
     *
     * @return array
     * @throws File_MARC_Exception
     */
    public function getTitleOfWork()
    {
        $uniformTitle = $this->getFieldArray(
            '130',
            ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't'],
            true,
            ', '
        );

        $uniformTitle = array_merge($uniformTitle, $this->getFieldArray(
            '240',
            ['a', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't'],
            true,
            ', '
        ));

        $relevantFields = array('700' => ['a', 'b', 'c', 'd', 'f', 'l', 't']);
        $formattingRules = array('700' => '700a(, 700b)(, 700c)(, 700d)(, 700l)(, 700t)(, 700f)');
        $conditions = array(
            ['subfield' => 't', 'operator' => '!=', 'value' => null],
            ['subfield' => 't', 'operator' => '!=', 'value' => '']
        );
        return array_merge($uniformTitle, $this->getFormattedData($relevantFields, $formattingRules, $conditions));
    }

    /**
     * Get an array with printing places
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getPrintingPlaces()
    {
        $printingPlaces = [];
        $fields = $this->getMarcRecord()->getFields('751');
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
     * Deduplicate author information into associative array with main/corporate/
     * secondary keys.
     *
     * @param array $dataFields An array of extra data fields to retrieve (see
     * getAuthorDataFields)
     *
     * @return array
     */
    public function getDeduplicatedAuthors($dataFields = ['detail', 'role']) {
        return parent::getDeduplicatedAuthors($dataFields);
    }

    /**
     * Checks if a condition is met. Compares 2 values with a given operator.
     *
     * Format:
     *     $condition = array(
     *          condition_type => condition_field,
     *          'operator' => condition_operator,
     *          'value' => value_to_compare
     *      )
     *
     * @param File_MARC_Data_Field $field The field to check.
     * @param array $condition            The condition to check. What to check is determined by
     *                                    the key of 'condition_type', e.g. 'subfield', or 'indicator'.
     *
     * @return bool
     *
     * @throws File_MARC_Exception
     */
    protected function conditionMet($field, $condition) {

        // Check if all conditions are met
        $valueToMatch = null;
        if(array_key_exists('subfield', $condition)) {
            $conditionSubField = $field->getSubfield($condition['subfield']);
            $valueToMatch = $conditionSubField ? $conditionSubField->getData() : null;
        }
        if(array_key_exists('indicator', $condition)) {
            $valueToMatch = $field->getIndicator($condition['indicator']);
        }

        switch ($condition['operator']) {
            case '==':
                return $valueToMatch == $condition['value'];
            case '!=':
                return $valueToMatch != $condition['value'];
            default:
                return false;
        }
    }

    /**
     * Wrapper function for 'getFormattedMarcData' to simplify the usage.
     * The condition type is determined by the name of the array key, e.g. subfield or indicator.
     *
     * Formats:
     *     $relevantFields = array (
     *         field_name_1 => array (
     *             subfield_name_1, subfield_name_2, ...
     *         ),
     *         ...
     *     )
     *     $formattingRules = array (
     *         field_name_1 => format_rule,
     *         ...
     *     )
     *     $conditions = array (
     *         array (
     *             condition_type => condition_field,
     *             'operator' => condition_operator,
     *             'value' => value_to_compare
     *         ),
     *         ...
     *     )
     *
     * @param array $relevantFields  The marc fields and subfields used.
     * @param array $formattingRules The rules by which to format the data.
     * @param array $conditions      The conditions, which must be met to include a field.
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getFormattedData($relevantFields, $formattingRules, $conditions = []) {
        $returnData = array();
        foreach ($relevantFields as $fieldNumber => $subfields) {
            $fields = $this->getMarcRecord()->getFields($fieldNumber);
            foreach ($fields as $field) {

                // Check if all conditions are met
                foreach($conditions as $condition) {
                    if(!$this->conditionMet($field, $condition)) {
                        continue 2;
                    }
                }

                $fieldData = [];
                foreach ($field->getSubfields() as $subfield) {
                    if (in_array($subfield->getCode(), $subfields)) {
                        $fieldData[$fieldNumber . $subfield->getCode()] =
                            isset($fieldData[$fieldNumber . $subfield->getCode()]) ?
                                $fieldData[$fieldNumber . $subfield->getCode()] . ', ' . $subfield->getData() :
                                $subfield->getData();
                    }
                }

                if ($fieldData) {
                    $returnData[] = $this->getFormattedMarcData(
                        $formattingRules[$fieldNumber],
                        true,
                        true,
                        $fieldData
                    );
                }
            }
        }

        return $returnData;
    }

    /**
     * Get a formatted string from different MARC fields
     *
     * @param string $format Describes the desired formatted output; MARC
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
     * @param boolean $ignorePlaceholders Missing MARC subfields may contain
     *                                      placeholder strings; Set to true, to
     *                                      remove them
     * @param array $data Data of the field to get data from.
     *
     * @return string
     */
    protected function getFormattedMarcData($format, $removeSeparators = true, $ignorePlaceholders = true, $data = [])
    {   
        // keep all escaped parentheses by converting them to their html equivalent
        $format = str_replace('\(', '&#40;', $format);
        $format = str_replace('\)', '&#41;', $format);
        
        // get all MARC data that is required (only first field values)
        $marcData = [];
        $marcFieldStrings = [];
        preg_match_all('/[\d]{3}[\da-z]/', $format, $marcFieldStrings, PREG_OFFSET_CAPTURE);
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
        // parentheses:
        $format = preg_replace('/[^T\()&;]*F[^T\(\)&;]*/', '', $format);
        // Remove all content in parentheses, that doesn't represent existing
        // Marc fields together with surrounding content
        $format = preg_replace('/[^T\(\)&;]*\([^T]*\)[^T\(\)&;]*/', '', $format);
        // Remove separators for fields, where they are given with the field content
        $format = preg_replace('/([^T\(\)]+S)|(S[^T\(\)]+)/', ' ', $format);
        // Transform to a valid formatter string
        $format = str_replace('T', '%s', str_replace('(', '', str_replace(')', '', $format)));
        
        
        // keep all escaped parentheses by converting them to their html equivalent
        $format = str_replace('&#40;', '(', $format);
        $format = str_replace('&#41;', ')', $format);

        // Remove empty previously escaped parentheses if empty
        $format = preg_replace('/[^%s\(\)]*\([^%s]*\)[^%s\(\)]*/', '', $format);

        return trim(vsprintf($format, $marcData));
    }

    /**
     * Returns the array element for the 'getAllRecordLinks' method
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return array|bool                 Array on success, boolean false if no
     * valid link could be found in the data.
     *
     * @throws File_MARC_Exception
     * @throws Exception
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
     * @param string|bool $title Optional title to search for in a fallback search
     * @return bool|array
     *
     * @throws Exception
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
     *
     * @throws File_MARC_Exception
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
     *
     * @throws File_MARC_Exception
     */
    public function getGeneralNotes()
    {
        $relevantFields = array(
            '246' => ['a', 'f', 'g', 'i'],
            '247' => ['a', 'b', 'f', 'g']
        );
        $formattingRules = array(
            '246' => '246i: (246a, (246f, 246g))',
            '247' => '247f: (247a, (247b, 247g))'
        );
        $conditions = array(['indicator' => '1', 'operator' => '==', 'value' => '1']);

        $titleVariations = $this->getFormattedData($relevantFields, $formattingRules, $conditions);

        return array_merge(
                $titleVariations,
                $this->getFieldArray('500')
            );
    }

    /**
     * Get an array of all series names containing the record.  Array entries may
     * be either the name string, or an associative array with 'name' and 'number'
     * keys.
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getSeries()
    {
        $primaryFields = []; // not used
        $matches = $this->getSeriesFromMARC($primaryFields);
        
        return $matches;
    }

    /**
     * Support method for getSeries() -- given a field specification, look for
     * series information in the MARC record.
     *
     * @param array $fieldInfo Associative array of field => subfield information
     * (used to find series name)
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    protected function getSeriesFromMARC($fieldInfo)
    {
        $matches = [];

        // Did we find any matching fields?
        $series = $this->getMarcRecord()->getFields('490');
        if (is_array($series)) {
            foreach ($series as $currentField) {
                if (($name = $currentField->getSubfield('a')) === false ) {
                    continue;
                }
                $currentArray = ['name' => $name->getData()];

                if ($number = $currentField->getSubfield('v')) {
                    $currentArray['number'] = $number->getData();
                }

                // Do we have IDs to link the field to
                $secondaryFields = $this->getMarcRecord()->getFields('800|810|830', true);
                foreach ($secondaryFields as $secondaryField) {
                    $secondaryNumber = $secondaryField->getSubfield('v');
                    if ($number !== false && $secondaryNumber !== false &&
                        $secondaryNumber->getData() === $number->getData()) {

                        $subFieldW = $secondaryField->getSubfield('w');
                        $rawId = $subFieldW ? $subFieldW->getData() : '';
                        if (strpos($rawId, '(' . self::PPN_LINK_ID_PREFIX . ')') === 0) {
                            $currentArray['id'] = substr($rawId, 8);
                            break;
                        }
                    }
                }

                // Save the current match:
                $matches[] = $currentArray;
            }
        }

        // Did we find any matching fields?
        $series = $this->getMarcRecord()->getFields('773');
        if (is_array($series)) {
            /* @var $currentField File_MARC_Data_Field */
            foreach ($series as $currentField) {
                if ($currentField->getSubfield('w')) {
                    if (( $name = $currentField->getSubfield('t')) === false) {
                        $field = $this->getMarcRecord()->getField('245');
                        $name = $field ? $field->getSubfield('a') : '';
                    }
                    $currentArray = ['name' => $name ? $name->getData() : $this->translate('Main entry')];

                    if ($number = $currentField->getSubfield('g')) {
                        $currentArray['number'] = $number->getData();
                    }

                    // Do we have IDs to link the field to
                    $subFieldW = $currentField->getSubfield('w');
                    $rawId = $subFieldW ? $subFieldW->getData() : '';
                    if (strpos($rawId, '(' . self::PPN_LINK_ID_PREFIX . ')') === 0) {
                        $currentArray['id'] = substr($rawId, 8);
                    }

                    // Save the current match:
                    $matches[] = $currentArray;
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
     *
     * @throws File_MARC_Exception
     */
    public function getURLs()
    {
        $retVal = [];
        
        $urls = $this->getMarcRecord()->getFields('856');
        foreach ($urls as $url) {
            $address = $url->getSubfield('u');
            $description = $url->getSubfield('3');
            if ($address && $description) {
                $address = $address->getData();
                $description = $description->getData();
                $lowerDescription = strtolower($description);
                if(!isset($retVal[$lowerDescription]) && !in_array($lowerDescription, ['cover', 'volltext'])) {
                    $retVal[$lowerDescription] = [
                        'url' => $address,
                        'desc' => $description
                    ];
                }
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
     * @return array
     *
     * @throws File_MARC_Exception
     *
     * @see \VuFind\RecordDriver\SolrMarc::getFieldArray() for the original function
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
            $next = $this
                ->getSubfieldArray($currentField, $subfields, $concat, $separator);
            $matches = array_merge($matches, $next);
        }

        return $matches;
    }

    /**
     * Check if the record is a news paper.
     *
     * @return bool
     *
     * @throws File_MARC_Exception
     */
    public function isNewsPaper()
    {
        $leader = $this->getMarcRecord()->getLeader();
        if ( strtoupper($leader[7] ) == "S" ) {
            return true;
        }

        return false;
    }

    /**
     * Check if the record is of the given format.
     *
     * @param string $format Format to test for
     * @param bool   $pcre if true, then match as a regular expression
     *
     * @return bool
     *
     * @throws File_MARC_Exception
     */
    public function isFormat($format = null, $pcre = null) {
        $formats = $this->getFormats();
        if(is_array($formats) && count($formats) > 0) {
            if (($pcre && preg_match("/$format/", $formats[0]))
                || (!$pcre && $formats[0] === $format)
            ) {
                return true;
            }
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
     *
     * @throws File_MARC_Exception
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
     * @param string $epn_str
     * @return array
     *
     * @throws File_MARC_Exception
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
            $replace = function ($subject, $searches, $highlights) use (&$replace) {
                $searchString = array_pop($searches);
                if (!$searchString) {
                    return $subject;
                }
                $highlightString = array_pop($highlights);
                $parts = explode($searchString, $subject);
                if (is_array($parts) && $parts) {
                    foreach ($parts as $i => $part) {
                        $parts[$i] = trim($replace(' ' . $part . ' ', $searches, $highlights));
                    }
                
                    return implode($highlightString, $parts);
                }
                
                return $subject;
            };
            
            $modifiedString = trim($replace(' ' . $plainString . ' ', array_keys($replacements), array_values($replacements)));
        }
        
        return $modifiedString;
    }

    /**
     * Get the group highlighting of the item.
     *
     * @param string $highlightString
     *
     * @return array
     */
    protected function groupHighlighting($highlightString)
    {
        return preg_replace('/\{\{\{\{END_HILITE\}\}\}\}\s?\{\{\{\{START_HILITE\}\}\}\}/', ' ', $highlightString);
    }

    /**
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublicationInfo() and getPlacesOfPublication().
     *
     * @return array
     */
    public function getPublicationDetails()
    {
        $places = $this->getPlacesOfPublication();
        $names = $this->getPublicationInfo('b');
        $dates = $this->getHumanReadablePublicationDates();

        $i = 0;
        $retVal = [];
        while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
            // Build objects to represent each set of data; these will
            // transform seamlessly into strings in the view layer.
            $retVal[] = new PublicationDetails(
                isset($places[$i]) ? $places[$i] : '',
                isset($names[$i]) ? $names[$i] : '',
                isset($dates[$i]) ? $dates[$i] : ''
            );
            $i++;
        }

        return $retVal;
    }

    /**
     * Get production of the item from 264.
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getProduction() {
        $productions = array();
        foreach($this->getMarcRecord()->getFields('264') as $currentField) {
            if($currentField->getIndicator(2) == 0) {
                $a = array();
                $subfields = $currentField->getSubfields('a');
                foreach($subfields as $currentSubfield) {
                    $a[] = $currentSubfield->getData();
                }

                $b = $currentField->getSubfield('b');
                $b = $b ? ' : ' . $b->getData() : '';

                $productions[] = implode('; ', $a) . $b;
            }
        }

        return $productions;
    }

    /**
     * Get reproduction of the item from 533.
     *
     * @return array
     */
    public function getReproduction() {
        return $this->getFieldArray(
            '533',
            ['a', 'b', 'c', 'd', 'e', 'f', 'n'],
            true,
            ', '
        );
    }

    /**
     * Get an array of lines from the table of contents.
     *
     * @return array
     * @throws File_MARC_Exception
     */
    public function getTOC()
    {
        $relevantFields = array(
            '501' => ['a'],
            '505' => ['a', 't', 'r']
        );
        $formattingRules = array(
            '501' => '501a',
            '505' => '(505a) (505t (/ 505r)'
        );

        return $this->getFormattedData($relevantFields, $formattingRules);
    }

    /**
     * Returns a string with all other titles of the work.
     *
     * @return string
     *
     * @throws File_MARC_Exception
     */
    public function getOtherTitles() {
        $fields = $this->getMarcRecord()->getFields('249');

        if(!is_array($fields) || count($fields) < 1) {
            return '';
        }
        $field = $fields[0];

        $data = '';
        foreach ($field->getSubFields() as $subField) {
            if($subField->getCode() === 'a') {
                $separator = !empty($data) ? ' ; ' : '';
            }
            else {
                $separator = $subField->getCode() === 'b' ? ' : ' : ' / ';
            }

            $data .= $separator . $subField->getData();
        }

        return $data;
    }

    /**
     * Returns a formatted string with the content types.
     *
     * @return string
     *
     * @throws File_MARC_Exception
     */
    public function getTypeOfContent() {
        $relevantFields = array('655' => ['a', 'x', 'y', 'z']);
        $formattingRules = array('655' => '655a \(655x, 655y, 655z\)');
        return implode('; ', $this->getFormattedData($relevantFields, $formattingRules));
    }

    /**
     * Returns an multidimensional array with all subjects.
     *
     * @param bool $extended
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getAllSubjectHeadings($extended = false) {
        return array_unique(
            array_merge($this->getSubjectsFromField650(), $this->getSubjectsFromField689()),
            SORT_REGULAR);
    }

    /**
     * Reads subjects with hierarchies from MRC 650 fields
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    private function getSubjectsFromField650() {
        $fields = $this->getMarcRecord()->getFields('650');
        if (!$fields) {
            return [];
        }

        $subjects = array();
        foreach ($fields as $field) {
            if ($subfield = $field->getSubfield('8')) {
                $level = preg_split('/\./', $subfield->getData());
                if ($subfield = $field->getSubfield('a')) {
                    $subjects[$level[0]][$level[1]] = $subfield->getData();
                }
            } else {
                if ($subfield = $field->getSubfield('a')) {
                    $subjects[][0] = $subfield->getData();
                }
            }
        }

        $subjects = array_values($subjects);
        for($i = 0; $i < count ($subjects); $i++) {
            $subjects[$i] = array_values($subjects[$i]);
        }
        return $subjects;
    }

    /**
     * Reads subjects with hierarchies from MRC 689 fields
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    private function getSubjectsFromField689() {
        $fields = $this->getMarcRecord()->getFields('689');
        if (!$fields) {
            return [];
        }

        $subjects = array();
        foreach ($fields as $field) {
            $primary   = $field->getIndicator(1);
            $secondary = $field->getIndicator(2);
            if($primary !== false && $secondary !== false) {
                if($subfield = $field->getSubfield('a')) {
                    $subjects[$primary][$secondary] = $subfield->getData();
                }
            }
        }

        return $subjects;
    }

    /**
     * Returns ppn links for this record.
     *
     * @return array
     *
     * @throws File_MARC_Exception
     */
    public function getPPNLink() {
        $ppnLinks = array();

        /* @var $fields File_MARC_Data_Field[] */
        $fields = $this->getMarcRecord()->getFields('760|762|765|767|770|772|774|775|776|777|780|787', true);
        foreach ($fields as $field) {
            $links = $field->getSubfields('w');
            if(is_array($links) && count($links) > 0) {
                foreach ($links as $link) {
                    if(strpos($link->getData(), '(' . self::PPN_LINK_ID_PREFIX . ')') !== false) {
                        $ppnLinks[] = substr($link->getData(), 8);
                    }
                }
            }
        }
        return $ppnLinks;
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Also checks if the linked resources are available through the system.
     *
     * @return null|array
     *
     * @throws Exception
     */
    public function getAllRecordLinks() {
        $recordLinks = parent::getAllRecordLinks();

        if(!is_array($recordLinks)) {
            return $recordLinks;
        }

        // Display ISBN or ISSN (if existing) as text
        foreach($recordLinks as $index => $recordLink) {
            if(in_array($recordLink['link']['type'], ['isbn', 'issn'])) {
                $recordLinks[$index]['value'] = strtoupper($recordLink['link']['type'])
                    . " " . $recordLink['link']['value'];
                $recordLinks[$index]['link'] = null;
            }
        }

        $recordLinks = $this->checkListForAvailability($recordLinks);

        return $recordLinks;
    }

    /**
     * Checks if the record is part of the "Thüringen-Bibliographie"
     *
     * @return bool
     */
    public function isThuBibliography() {
        if(isset($this->fields['class_local_iln']) && is_array($this->fields['class_local_iln'])) {
            foreach ($this->fields['class_local_iln'] as $classLocal) {
                if (preg_match('/^31:.*<Thüringen>$/', $classLocal)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getHoldings()
    {
        return parent::getRealTimeHoldings();
    }

    /**
     * Return first ISMN found for this record, or false if no one fonund
     *
     * @return mixed
     */
    public function getCleanISMN()
    {
        // Fix for cases where 024 $a is not set
        $fields024 = $this->getMarcRecord()->getFields('024');
        $ismn = null;
        foreach ($fields024 as $field) {
            if ($field->getIndicator(1) == 2) {
                if($data = $field->getSubfield('a')) {
                    $ismn = $data->getData();
                    break;
                }
            }
        }
        return $ismn ?? false;
    }

//    Commented out for possible future use.
//    /**
//     * Get an array of all the formats associated with the record.
//     * Get the format from the leader if a format is 'unknown'.
//     *
//     * @return array
//     *
//     * @throws File_MARC_Exception
//     */
//    public function getFormats() {
//        $formats = parent::getFormats();
//        foreach($formats as $index => $format) {
//            if(strtolower($format) == 'unknown') {
//                $format = substr($this->getMarcRecord()->getLeader(), 6, 1);
//                if(isset($this->marcFormatConfig->Leader6_Format[$format])) {
//                    $formats[$index] = $this->marcFormatConfig->Leader6_Format[$format];
//                }
//            }
//        }
//
//        return $formats;
//    }
}
