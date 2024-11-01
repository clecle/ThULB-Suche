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
use File_MARC_Exception;
use VuFind\RecordDriver\Response\PublicationDetails;
use VuFind\RecordDriver\SolrMarc;
use Laminas\Config\Config;
use VuFindSearch\Command\RetrieveBatchCommand;

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
    const GND_LINK_ID_PREFIX = 'DE-588';
    const DNB_LINK_ID_PREFIX = 'DE-101';
    const LIBRARY_ILN = '31';

    const SEPARATOR = '|\/|';

    /**
     * Contains all separators that are often part of MARC field entries and
     * should be eleminated, when custom formatting is applied
     *
     * @var array
     */
    protected static array $defaultSeparators = [' = ',' =', '= ', ' : ', ' :', ': '];
    
    /**
     * Contains all placeholders that are often used to fill missing MARC
     * subfields and should be removed in the displayed string
     * 
     * @var array
     */
    protected static array $defaultPlaceholders = ['[...]'];

    /**
     * Short title of the record.
     *
     * @var string 
     */
    protected ?string $shortTitle = null;
    
    /**
     * Title of the record.
     *
     * @var string
     */
    protected ?string $title = null;
    
    /**
     * The title of the record with highlighting markers
     * 
     * @var string
     */
    protected ?string $highlightedTitle = null;

    /**
     * Marc format configuration
     *
     * @var Config
     */
    protected Config $marcFormatConfig;

    /**
     * DAIA departments configuration
     *
     * @var Config
     */
    protected Config $departmentConfig;

    /**
     * ThULB configuration
     *
     * @var Config
     */
    protected Config $thulbConfig;

    protected array $holdingData = [];

    public function __construct($mainConfig = null, $recordConfig = null, $searchSettings = null,
                                $marcFormatConfig = null, $departmentConfig = null, $thulbConfig = null)
    {
        $this->marcFormatConfig = $marcFormatConfig;
        $this->departmentConfig = $departmentConfig;
        $this->thulbConfig = $thulbConfig;

        parent::__construct($mainConfig, $recordConfig, $searchSettings);
    }

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus() : bool
    {
        $noStatus = true;
        $noStatusMedia = ['Article', 'eBook', 'eJournal', 'electronic Article', 'electronic Resource'];
        
        foreach ($this->getFormats() as $format) {
            if (!in_array($format, $noStatusMedia)) {
                $noStatus = false;
                break;
            }
        }

        $leader = $this->getMarcReader()->getLeader();
        return ($leader[7] !== 's' && $leader[7] !== 'a' && $leader[19] !== 'a' && !$noStatus);
    }

    /**
     * Retrieve raw data from object (primarily for use in staff view and
     * autocomplete; avoid using whenever possible).
     *
     * @return mixed
     */
    public function getRawData() : mixed
    {
        ksort($this->fields, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($this->fields as $key => $field) {
            if(is_array($field)) {
                sort($field, SORT_NATURAL | SORT_FLAG_CASE);

                $this->fields[$key] = $field;
            }
        }

        return $this->fields;
    }

    /**
     * Get the short (pre-subtitle) title of the record.
     *
     * @return string
     */
    public function getShortTitle() : string
    {
        if (is_null($this->shortTitle)) {
            $shortTitle = $this->getFormattedMarcData('245a : 245b ( / 245c)') ?:
                              $this->getFormattedMarcData('490v: 490a');

            if ($shortTitle === '') {
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
    public function getHighlightedTitle() : string
    {
        if (is_null($this->highlightedTitle)) {
            if (!$this->highlight && !is_array($this->highlight)) {
                return '';
            }
            
            $this->highlightedTitle = '';
            foreach ($this->highlightDetails as $highlightElement => $highlightDetail) {
                if (str_contains($highlightElement, 'title')) {
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
    public function getTitle() : string
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
    public function getSubtitle() : string
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
    public function getContainerTitle() : string
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
    public function getContainerReference() : string
    {
        $containerRef = $this->getFieldArray('773', ['g'], false);
        return ($containerRef) ? $containerRef[0] : '';
    }

    /**
     * Get the container link of the item from 773.
     *
     * @return array|bool
     *
     * @throws File_MARC_Exception
     * @throws Exception
     */
    public function getContainerLink()
    {
        return $this->getLinkFromField($this->getMarcReader()->getField('773'));
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

        $params['contenttype'] = !empty($this->fields['format']) ? ((array) $this->fields['format'])[0] : '';

        $collection_details = $this->fields['collection_details'];

        // is Main-Config Content > IIIF set?
        if ($this->mainConfig->Content->IIIF ?? false) {
            // get IIIF array: "collection-Name" = "IIIF-API-url"
            $collections = $this->mainConfig->Content->IIIF->toArray();
            $IIIF_collections = array_keys($collections);
            // add only, if SOLR-Field "collection_details" contains the same Value as given in
            // Main-Config - should be one, usually!
            $params['collection_details'] = array_intersect($collection_details, $IIIF_collections);
        }
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
     */
    public function getBasicClassification() : array {
        $conditions = array(
            $this->createFieldCondition('indicator', 1, '==', 'b'),
            $this->createFieldCondition('indicator', 2, '==', 'k'),
            $this->createFieldCondition('subfield', 'a', '!=', false),
        );

        $fields = array();
        foreach($this->getFieldsConditional('936', $conditions) as $dataField) {
            $descriptions = $this->getSubfields($dataField, 'j');
            $fields[] = array(
                'bklnumber' => $this->getSubfield($dataField, 'a'),
                'bklname' => count($descriptions) ? $descriptions : null
            );
        }

        return $fields;
    }

    /**
     * Get classification numbers of the record in the "Thüringen-Bibliographie".
     *
     * @return array
     */
    public function getThuBiblioClassification() : array
    {
        $classNumbers = $this->getConditionalFieldArray('983', ['a'], true, ' ', ['2' => static::LIBRARY_ILN]);
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
     */
    public function getZDBID() : array {
        $ids = $this->getFieldArray('035', ['a']);
        $zdbIds = [];

        foreach ($ids as $id) {
            if (str_starts_with($id, '(DE-599)ZDB')) {
                $zdbIds[] = substr($id, 11);
            }
        }

        return $zdbIds;
    }

    /**
     *  Erscheinungsverlauf from 362 $a
     *
     * @TODO repeatable?
     *
     * @return string|null
     */
    public function getNumbering() : ?string {
        return $this->getFirstFieldValue('362', ['a']);
    }

    /**
     * Erscheinungsverlauf from 515 $a
     *
     * not repeatable
     *
     * @return string|null
     */
    public function getNumberingPeculiarities() : ?string {
        return $this->getFirstFieldValue('515', ['a']);
    }

    /**
     * Anmerkungen from 546 $a
     *
     * not repeatable
     *
     * @return string|null
     */
    public function getLanguageNotes() : ?string {
        return $this->getFirstFieldValue('546', ['a']);
    }

    /**
     * Fingerprint information from Marc Field 026
     *
     * not repeatable
     *
     * @return array
     */
    public function getFingerprint() : array
    {
        return $this->getFieldArray('026', ['e', '5'], false);
    }

    /**
     * Get bibliographic citations from Marc field 510
     *
     * @return string
     */
    public function getBibliographicCitation() : string
    {
        return implode(' ; ', $this->getFieldArray('510', ['a'], false));
    }

    /**
     * Get an array of physical descriptions of the item.
     *
     * @return array
     */
    public function getPhysicalDescriptions() : array
    {
        $fields = $this->getMarcReader()->getFields('300');

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
     * @return array
     */
    public function getCartographicScale() : array
    {
        return $this->getFieldArray('255', ['a'], true, ' ; ');
    }

    /**
     * Get the projection of a map.
     *
     * @return string|null
     */
    public function getCartographicProjection() : ?string
    {
        return $this->getFirstFieldValue('255', ['b']);
    }

    /**
     * Get the coordinates of a map.
     *
     * @return string|null
     */
    public function getCartographicCoordinates() : ?string
    {
        return $this->getFirstFieldValue('255', ['c']);
    }

    /**
     * Get the equinox of a map.
     *
     * @return string|null
     */
    public function getCartographicEquinox() : ?string
    {
        return $this->getFirstFieldValue('255', ['e']);
    }

    /**
     * Get the dissertation notes of the item from 502.
     *
     * @return string|null
     */
    public function getDissertationNote() : ?string
    {
        $dissNote = $this->getFieldArray('502', ['a', 'b', 'c', 'd', 'g', 'o'], true, ', ');
        return ($dissNote) ? ltrim($dissNote[0], '@') : '';
    }

    /**
     * Get the part info of the item from 245.
     *
     * @return string
     */
    public function getPartInfo() : string
    {
        $nSubfields = $this->getFieldArray('245', ['n'], false);
        $pSubfields = $this->getFieldArray('245', ['p'], false);

        $numOfEntries = max([count($nSubfields), count($pSubfields)]);

        $partInfo = '';
        for ($i = 0; $i < $numOfEntries; $i++) {
            $n = (isset($nSubfields[$i]) && !in_array($nSubfields[$i], static::$defaultPlaceholders)) ? $nSubfields[$i] : '';
            $p = (isset($pSubfields[$i]) && !in_array($pSubfields[$i], static::$defaultPlaceholders)) ? $pSubfields[$i] : '';
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
    public function getPrimaryAuthors($excludeRoles = []) : array
    {
        $relevantFields = [
            '100' => ['a', 'b', 'c']
        ];
        $formattingRules = [
            '100' => '100a (100b)(, 100c)'
        ];
        $conditions = [];
        if($excludeRoles) {
            $conditions[] = $this->createFieldCondition('subfield', '4', 'nin', $excludeRoles);
        }

        return $this->getFormattedData($relevantFields, $formattingRules, $conditions);
    }

    /**
     * Get the title and dates of the main authors of the record.
     *
     * @return array
     */
    public function getPrimaryAuthorsDetails() : array
    {
        $information = $this->getFormattedMarcData('( 100g)');
        return $information ? [$information] : [];
    }

    /**
     * Get the roles of the main authors of the record.
     *
     * @return array
     */
    public function getPrimaryAuthorsRoles() : array
    {
        $role = $this->getFirstFieldValue('100', ['4']);
        return $role ? [$role] : [];
    }

    /**
     * Get the gnd of the main authors of the record.
     *
     * @return array
     */
    public function getPrimaryAuthorsGnds() : array
    {
        $ids = $this->getFieldArray('100', ['0'], false);
        foreach($ids as $id) {
            if(str_starts_with($id, '(' . static::GND_LINK_ID_PREFIX . ')')) {
                return [substr($id, strlen(static::GND_LINK_ID_PREFIX) + 2)];
            }
        }
        return [];
    }

    /**
     * Get an array of all secondary authors (complementing getPrimaryAuthors()).
     *
     * @return array
     */
    public function getSecondaryAuthors($excludeRoles = []) : array
    {
        $relevantFields = [
            '700' => ['a', 'b', 'c']
        ];
        $formattingRules = [
            '700' => '700a (700b)(, 700c)'
        ];
        $conditions = [];
        if($excludeRoles) {
            $conditions[] = $this->createFieldCondition('subfield', '4', 'nin', $excludeRoles);
        }

        return $this->getFormattedData($relevantFields, $formattingRules, $conditions);
    }

    /**
     * Get an array of all secondary authors titles and dates (complementing getPrimaryAuthors()).
     *
     * @return array
     */
    public function getSecondaryAuthorsDetails() : array
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
     */
    public function getSecondaryAuthorsRoles() : array
    {
        $roles = [];
        $fields = $this->getMarcReader()->getFields('700');
        foreach ($fields as $field) {
            if ($role = $this->getSubfield($field, '4')) {
                $roles[] = $role;
                continue;
            }
            $roles[] = '';
        }

        return $roles;
    }

    /**
     * Get the gnd of the secondary authors of the record.
     *
     * @return array
     */
    public function getSecondaryAuthorsGnds() : array
    {
        $gnds = [];
        $reader = $this->getMarcReader();
        foreach($reader->getFields('700') as $field) {
            foreach ($reader->getSubfields($field, '0') as $id) {
                if (str_starts_with($id, '(' . static::GND_LINK_ID_PREFIX . ')')) {
                    $gnds[] = substr($id, strlen(static::GND_LINK_ID_PREFIX) + 2);
                    continue 2;
                }
            }

            // no gnd found for this field
            $gnds[] = null;
        }
        return $gnds;
    }

    /**
     * Get an array of conferences or congresses, i.e. the names of meetings,
     * with wich the publication was created
     *
     * @return array
     */
    public function getMeetingNames() : array
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
     */
    public function getCorporateAuthors($excludeRoles = []) : array
    {
        $relevantFields = array(
            '110' => ['a', 'b'],
            '710' => ['a', 'b']
        );
        $formattingRules = array(
            '110' => '110a (/ 110b)',
            '710' => '710a (/ 710b)'
        );
        $conditions = [];
        if($excludeRoles) {
            $conditions[] = $this->createFieldCondition('subfield', '4', 'nin', $excludeRoles);
        }

        return $this->getFormattedData($relevantFields, $formattingRules, $conditions);
    }

    /**
     * Get the roles of corporate authors (if any) for the record.
     *
     * @return array
     */
    public function getCorporateAuthorsRoles() : array
    {
        $roles = [];

        $fields = array_merge(
            $this->getMarcReader()->getFields('110'),
            $this->getMarcReader()->getFields('710')
        );
        foreach ($fields as $field) {
            $roles[] = $this->getSubfield($field, '4');
        }

        return $roles;
    }

    public function getCorporateAuthorsDetails() {
        $details = array();

        $reader = $this->getMarcReader();
        foreach(['110', '710'] as $fieldTag) {
            foreach($reader->getFields($fieldTag) as $field) {
                $details[] = $reader->getSubfield($field, 'g');
            }
        }

        return $details;
    }

    /**
     * Get the gnd of the corporate authors of the record.
     *
     * @return array
     */
    public function getCorporateAuthorsGnds() : array
    {
        $gnds = [];
        $reader = $this->getMarcReader();
        foreach(['110', '710'] as $fieldTag) {
            foreach($reader->getFields($fieldTag) as $field) {
                foreach ($reader->getSubfields($field, '0') as $id) {
                    if (str_starts_with($id, '(' . static::GND_LINK_ID_PREFIX . ')')) {
                        $gnds[] = substr($id, strlen(static::GND_LINK_ID_PREFIX) + 2);
                        continue 2;
                    }
                }

                // no gnd found for this field
                $gnds[] = null;
            }
        }
        return $gnds;
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
     * @return array|null
     *
     * @throws File_MARC_Exception
     * @throws Exception
     */
    public function getLineageRecordLinks() : ?array
    {
        // Load configurations:
        $fieldsNames = ['780', '785'];
        $useVisibilityIndicator
            = isset($this->mainConfig->Record->marc_links_use_visibility_indicator)
            ? $this->mainConfig->Record->marc_links_use_visibility_indicator : true;

        $retVal = [];
        foreach ($fieldsNames as $value) {
            $value = trim($value);
            $fields = $this->getMarcReader()->getFields($value);
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    // Check to see if we should display at all
                    if ($useVisibilityIndicator) {
                        if ($field['i1'] == '1') {
                            continue;
                        }
                    }

                    // Get data for field
                    $tmp = $this->getFieldData($field);
                    if (is_array($tmp)) {
                        if ($subfieldA = $this->getSubfield($field, 'a')) {
                            $tmp['value'] .= ' ' . trim($subfieldA);
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
     */
    protected function checkListForAvailability($recordLinkList) : array {
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
            $availablePPNs = $this->checkAvailabilityOfPPNs($linkedPPNs);

            // Set links to NULL if not available
            foreach($recordLinkList as $index => $recordLink) {
                if (!is_array($recordLink['link']) || !in_array($recordLink['link']['value'], $availablePPNs)) {
                    $recordLinkList[$index]['link'] = null;
                }
            }
        }

        return $recordLinkList;
    }

    /**
     * Checks the availability of a list of PPNs.
     *
     * @param array $ppnList PPNs to check.
     *
     * @return array List of available PPNs.
     *
     */
    protected function checkAvailabilityOfPPNs (array $ppnList) : array {

        if(empty($ppnList)) {
            return $ppnList;
        }

        $result = $this->searchService->invoke(
            new RetrieveBatchCommand('Solr', $ppnList)
        );

        $availablePPNs = array();
        /* @var $record SolrVZGRecord */
        foreach($result->getResult()->getRecords() as $record) {
            try {
                $availablePPNs[] = $record->getUniqueID();
            }
            catch (Exception $ignored){}
        }

        return $availablePPNs;
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs() : array
    {
        $conditions = array($this->createFieldCondition('subfield', 'z', '==', false));

        $data = [];
        foreach($this->getFieldsConditional('020', $conditions) as $field) {
            $fieldData = array(
                '0209' => $this->getSubfield($field, '9') ?: $this->getSubfield($field, 'a'),
                '020c' => $this->getSubfield($field, 'c') ?: null
            );

            $data[] = $this->getFormattedMarcData('0209 : 020c', true, true, $fieldData);
        }

        return $data;
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISMNs() : array
    {
        $relevantFields = array('024' => ['9', 'c']);
        $formattingRules = array('024' => '0249 024c');
        $conditions = array ($this->createFieldCondition('indicator', 1, '==', '2'));
        return $this->getFormattedData($relevantFields, $formattingRules, $conditions);
    }

    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISSNs()
    {
        return $this->getMarcReader()->getFieldsSubfields('022', ['a'], null);
    }

    /**
     * Get an array of all invalid ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getInvalidISBNs() : array
    {
        return $this->getMarcReader()->getFieldsSubfields('020', ['z'], null);
    }

    /**
     * Get an array with the uniform title
     *
     * @return array
     */
    public function getTitleOfWork() : array
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
        $conditions = array($this->createFieldCondition('subfield', 't', 'nin', [false, '']));
        return array_merge($uniformTitle, $this->getFormattedData($relevantFields, $formattingRules, $conditions));
    }

    /**
     * Get an array with printing places
     *
     * @return array
     */
    public function getPrintingPlaces() : array
    {
        $printingPlaces = [];
        $fields = $this->getMarcReader()->getFields('751');
        foreach ($fields as $currentField) {
            $ind1 = $currentField['i1'];
            $ind2 = $currentField['i2'];
            if (($ind1 && trim($ind1)) || ($ind2 && trim($ind2))) {
                continue;
            }
            $subfields = $this->getSubfieldArray($currentField, ['a']);
            if ($subfields) {
                $printingPlaces[] = $subfields[0];
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
    public function getDeduplicatedAuthors($dataFields = ['detail', 'role', 'gnd']) : array {
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
     * @param array $field     The field to check.
     * @param array $condition The condition to check. What to check is determined by
     *                         the key of 'condition_type', e.g. 'subfield', or 'indicator'.
     *
     * @return bool
     */
    protected function conditionMet(array $field, array $condition) : bool {

        // Check if all conditions are met
        $valueToMatch = false;
        if(array_key_exists('subfield', $condition)) {
            if($data = $this->getSubfield($field, $condition['subfield'])) {
                $valueToMatch = $data;
            }
        }
        if(array_key_exists('indicator', $condition)
            && trim($field['i' . $condition['indicator']]) !== '') {
            $valueToMatch = $field['i' . $condition['indicator']];
        }

        switch ($condition['operator']) {
            case 'eq':
            case '==':
                return $valueToMatch == $condition['value'];
            case 'neq':
            case '!=':
                return $valueToMatch !== $condition['value'];
            case 'in':
                return is_array($condition['value']) && in_array($valueToMatch, $condition['value']);
            case 'nin':
                return is_array($condition['value']) && !in_array($valueToMatch, $condition['value']);
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
     */
    public function getFormattedData(array $relevantFields, array $formattingRules, array $conditions = []) : array {
        $returnData = array();
        foreach ($relevantFields as $fieldNumber => $subfields) {
            $fields = $this->getMarcReader()->getFields($fieldNumber);
            foreach ($fields as $field) {

                // Check if all conditions are met
                foreach($conditions as $condition) {
                    if(!$this->conditionMet($field, $condition)) {
                        continue 2;
                    }
                }

                $fieldData = [];
                foreach($subfields as $subfield) {
                    $fieldData[$fieldNumber . $subfield] =
                        implode(', ', $this->getSubfields($field, $subfield)) ?: null;
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
     * @param string $format              Describes the desired formatted output; MARC
     *                                    fields and their subfields are coded with a 3
     *                                    digit number that is immediately followed by the
     *                                    character of the subfield (e.g. "260a");
     *                                    to make hints for the separator priority in case
     *                                    of missing MARC fields, simple parentheses are
     *                                    used; examples:
     *                                    - "264a : 264b, 264c. 250a": no information for
     *                                      separator priority - they are all treated as
     *                                      postfix; if e.g 264b is missing, the output is
     *                                      "264a : 264c. 250a"
     *                                    - "((264a : 264b), 264c). 250a": the evaluation
     *                                      order is provided; if e.g. 264b is missing,
     *                                      the output is "264a, 264c. 250a"
     * @param boolean $removeSeparators   MARC subfields may contain separators at
     *                                    the beginning or at the end; Set to true,
     *                                    when they should be removed from the
     *                                    strings (default)
     * @param boolean $ignorePlaceholders Missing MARC subfields may contain
     *                                    placeholder strings; Set to true, to
     *                                    remove them
     * @param array $data Data of the field to get data from.
     *
     * @return string
     */
    protected function getFormattedMarcData(string $format, bool $removeSeparators = true,
                                            bool $ignorePlaceholders = true, array $data = []) : string
    {
        // keep all escaped parentheses by converting them to their html equivalent
        $format = str_replace('\(', '&#40;', $format);
        $format = str_replace('\)', '&#41;', $format);

        // get all MARC data that is required (only first field values)
        $marcData = [];
        $marcFieldStrings = [];
        preg_match_all('/[\d]{3}[\da-z]/', $format, $marcFieldStrings, PREG_OFFSET_CAPTURE);
        foreach ($marcFieldStrings[0] as $marcFieldInfo) {
            $fieldNumber = substr($marcFieldInfo[0], 0, 3);
            $subfieldChar = substr($marcFieldInfo[0], 3);
            if ($data) {
                $value = $data[$fieldNumber . $subfieldChar] ?? null;
            } else {
                $value = $this->getFirstFieldValue($fieldNumber, [$subfieldChar]);
            }
            $value = ($ignorePlaceholders && in_array($value, static::$defaultPlaceholders)) ? null : $value;
            if (!is_null($value) && $value != '') {
                $marcData[$fieldNumber . $subfieldChar] = $value;
                $replacement = 'T';
                // check for separators in the marc field and marc the separator
                // in the format string as removable
                if ($removeSeparators) {
                    foreach (static::$defaultSeparators as $separator) {
                        if (str_starts_with($value, $separator)) {
                            $replacement = 'ST';
                        } else if ((str_ends_with($value, $separator))) {
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
     * @param array $field Field to examine
     *
     * @return array|bool  Array on success, boolean false if no
     *                     valid link could be found in the data.
     *
     * @throws File_MARC_Exception
     * @throws Exception
     */
    protected function getFieldData($field)
    {
        $leader = $this->getMarcReader()->getLeader();
        // Make sure that there is a t field to be displayed:
        if (!$title = $this->getSubfield($field, 't')) {
            if (strtolower($leader[7]) === 'm'
                && strtolower($leader[19]) === 'c'
            ) {
                $title = $this->getFirstFieldValue('245', ['a']);
            } else {
                $title = false;
            }
        }

        $link = $this->getLinkFromField($field, $title);

        // Make sure we have something to display:
        return ($link === false) ? false : [
            'title' => $this->getRecordLinkNote($field),
            'value' => $title ? $title : 'Link',
            'link'  => $link,
            'pages' => $this->getMarcReader()->getSubfield($field, 'g')
        ];
    }

    /**
     * Extract link information from a given MARC field
     *
     * @param array       $field
     * @param string|bool $title Optional title to search for in a fallback search
     *
     * @return bool|array
     *
     * @throws Exception
     */
    protected function getLinkFromField(array $field, $title = false)
    {
        $linkTypeSetting = isset($this->mainConfig->Record->marc_links_link_types)
            ? $this->mainConfig->Record->marc_links_link_types
            : 'id,isbn,issn,dnb,zdb,title';
        $linkTypes = explode(',', $linkTypeSetting);
        $linkFields = $this->getSubfields($field, 'w');

        // Run through the link types specified in the config.
        // For each type, check field for reference
        // If reference is found, exit loop and go straight to end
        // If no reference found, check the next link type instead
        foreach ($linkTypes as $linkType) {
            switch (trim($linkType)){
            case 'id':
                foreach ($linkFields as $current) {
                    $bibLink = trim($this->getIdFromLinkingField($current, static::PPN_LINK_ID_PREFIX, true), '*');
                    if ($bibLink) {
                        $link = ['type' => 'bib', 'value' => $bibLink];
                    }
                }
                break;
            case 'isbn':
                if ($isbn = $this->getSubfield($field, 'z')) {
                    $link = [
                        'type' => 'isbn', 'value' => trim($isbn),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'issn':
                if ($issn = $this->getSubfield($field, 'x')) {
                    $link = [
                        'type' => 'issn', 'value' => trim($issn),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'dnb':
                foreach ($linkFields as $current) {
                    $bibLink = $this->getIdFromLinkingField($current, static::DNB_LINK_ID_PREFIX, true);
                    if ($bibLink) {
                        $link = ['type' => 'dnb', 'value' => $bibLink];
                    }
                }
                break;
            case 'zdb':
                foreach ($linkFields as $current) {
                    $bibLink = $this->getIdFromLinkingField($current, static::ZDB_LINK_ID_PREFIX, true);
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

        return $link ?? false;
    }

    /**
     * Support method for getFieldData() -- factor the relationship indicator
     * into the field number where relevant to generate a note to associate
     * with a record link.
     *
     * @param array $field Field to examine
     *
     * @return string
     */
    protected function getRecordLinkNote($field) : string
    {
        // If set, use relationship information from subfield i and n
        if ($subfieldI = $this->getSubfield($field, 'i')) {
            $data = trim($subfieldI);
            if (!empty($data)) {
                if ($subfieldN = $this->getSubfield($field, 'n')) {
                    $data .= ' ' . trim($subfieldN);
                }
                return $data;
            }
        }

        // Normalize blank relationship indicator to 0:
        $relationshipIndicator = $field['i2'];
        if ($relationshipIndicator == ' ') {
            $relationshipIndicator = '0';
        }

        // Assign notes based on the relationship type
        $value = $field['tag'];
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
    public function getGeneralNotes() : array
    {
        $relevantFields = array(
            '246' => ['a', 'f', 'g', 'i'],
            '247' => ['a', 'b', 'f', 'g']
        );
        $formattingRules = array(
            '246' => '246i: (246a, (246f, 246g))',
            '247' => '247f: (247a, (247b, 247g))'
        );
        $conditions = array($this->createFieldCondition('indicator', '1', '==', '1'));

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
     */
    public function getSeries($includeUnavailable = true) : array
    {
        $primaryFields = []; // not used
        return $this->getSeriesFromMARC($primaryFields, $includeUnavailable);
    }

    /**
     * Support method for getSeries() -- given a field specification, look for
     * series information in the MARC record.
     *
     * @param array $fieldInfo Associative array of field => subfield information
     *                         (used to find series name)
     *
     * @return array
     */
    protected function getSeriesFromMARC($fieldInfo, $includeUnavailable = true) : array {
        $matches = [];

        // Did we find any matching fields?
        $series = $this->getFieldsConditional('490', [$this->createFieldCondition('subfield', 'a', '!=', false)]);
        foreach ($series as $currentField) {
            $currentArray = ['name' => $this->getSubfield($currentField, 'a')];
            if ($number = $this->getSubfield($currentField, 'v')) {
                $currentArray['number'] = $number;

                // Do we have IDs to link the field to
                $secondaryFields = $this->getFieldsConditional(['800', '810', '830'], [
                    $this->createFieldCondition('subfield', 'v', '==', $number),
                    $this->createFieldCondition('subfield', 'w', '!=', false)
                ]);
                if(count($secondaryFields) > 0) {
                    $rawId = $this->getSubfield($secondaryFields[0], 'w');
                    if (str_starts_with($rawId, '(' . static::PPN_LINK_ID_PREFIX . ')')) {
                        $currentArray['id'] = substr($rawId, 8);
                    }
                }
            }

            // Save the current match:
            $matches[] = $currentArray;
        }

        // Did we find any matching fields?
        $series = $this->getFieldsConditional('773', [$this->createFieldCondition('subfield', 'w', '!=', false)]);
        foreach ($series as $currentField) {
            if (!($name = $this->getSubfield($currentField, 't'))) {
                $name = $this->getMarcReader()->getFieldsSubfields('245', ['a'])[0] ?? false;
            }
            $currentArray = ['name' => $name ?: $this->translate('Main entry')];

            if ($number = $this->getSubfield($currentField, 'g')) {
                $currentArray['number'] = $number;
            }

            // Do we have IDs to link the field to
            $rawId = $this->getSubfield($currentField, 'w');
            if (str_starts_with($rawId, '(' . static::PPN_LINK_ID_PREFIX . ')')) {
                $currentArray['id'] = substr($rawId, 8);
            }

            // Save the current match:
            $matches[] = $currentArray;
        }

        $ppnList = array();
        foreach($matches as $match) {
            if(!empty($match['id'])) {
                $ppnList[] = $match['id'];
            }
        }
        $ppnList = $this->checkAvailabilityOfPPNs($ppnList);
        foreach ($matches as $key => $match) {
            if(empty($match['id']) || !in_array($match['id'], $ppnList)) {
                if($includeUnavailable) {
                    $matches[$key]['id'] = null;
                }
                else {
                    unset($matches[$key]);
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
    public function getURLs() : array
    {
        $retVal = [];
        $conditions = array(
            $this->createFieldCondition('indicator', 1, '==', 4),
            $this->createFieldCondition('indicator', 2, '==', 2),
            $this->createFieldCondition('subfield', 'u', '!=', false),
            $this->createFieldCondition('subfield', 3, 'nin', ['Volltext', 'Cover', 'Unbekannt'])
        );

        $urls = $this->getFieldsConditional('856', $conditions);
        foreach ($urls as $url) {
            if(!($description = $this->getSubfield($url, '3'))) {
                $description = $this->getSubfield($url, 'y');
            }

            if ($description) {
                $lowerDescription = strtolower($description);

                if(!isset($retVal[$lowerDescription])) {
                    $retVal[$lowerDescription] = [
                        'url' => $this->getSubfield($url, 'u'),
                        'desc' => $description
                    ];
                }
            }
        }

        return $retVal;
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
    public function getFullTextURL() : array
    {
        $retVal = [];
        $basicConditions = array(
            $this->createFieldCondition('indicator', 1, '==', 4),
            $this->createFieldCondition('indicator', 2, '==', 0),
            $this->createFieldCondition('subfield', 'u', '!=', false),
        );
        $fulltextCondition = array(
            $this->createFieldCondition('subfield', '3', '==', 'Volltext'),
            $this->createFieldCondition('subfield', 'z', 'in', ['Kostenfrei', 'kostenfrei'])
        );
        $freeCondition = array(
            $this->createFieldCondition('subfield', '3', '==', false),
            $this->createFieldCondition('subfield', 'z', 'in', ['Kostenfrei', 'kostenfrei'])
        );

        $urls = $this->getFieldsConditional('856', array_merge($basicConditions, $fulltextCondition));
        if(!$urls) {
            $urls = $this->getFieldsConditional('856', array_merge($basicConditions, $freeCondition));
        }
        if(!$urls && in_array('NL', $this->fields['collection'] ?? [])) {
            $urls = $this->getFieldsConditional('856', $basicConditions);
        }

        foreach($urls as $url) {
            $retVal[] = array(
                'link' => $link = $this->getSubfield($url, 'u'),
                'desc' => $this->translate('Full text online'),
                'remotetitle' => parse_url($link)['host'] ?? $link,
                'about' => 'externer Link, ' . $this->getSubfield($url, 'z')
            );
        }

        return $retVal;
    }

    /**
     * Get all fields that meet the specified conditions.
     * For conditions @see conditionMet
     *
     * @param array|string $spec tag name
     * @param array  $conditions
     *
     * @return array
     */
    protected function getFieldsConditional($spec, array $conditions = []) : array {
        if(is_string($spec)) {
            $spec = [$spec];
        }

        $retFields = [];
        foreach($spec as $fieldName) {
            foreach ($this->getMarcReader()->getFields($fieldName) as $field) {
                // Check if all conditions are met
                foreach ($conditions as $condition) {
                    if (!$this->conditionMet($field, $condition)) {
                        continue 2;
                    }
                }

                $retFields[] = $field;
            }
        }

        return $retFields;
    }

    /**
     * Creates a condition to be used with {@see getFieldsConditional} and {@see getFormattedData}.
     *
     * @param string $type     The type of the field to be checked, e.g. 'subfield' or 'indicator'.
     * @param string $field    Which field or indicator is to be checked.
     * @param string $operator Type of comparision. {@see conditionMet}
     * @param mixed  $value    The value(s) to check with.
     *
     * @return array
     */
    protected function createFieldCondition(string $type, string $field, string $operator, $value) : array {
        return array (
            $type => $field,
            'operator' => $operator,
            'value' => $value
        );
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
     *                           and the expected subfield content as value, if the value
     *                           is an array it is considered as an 'OR' operation
     *
     * @return array
     *
     * @see \VuFind\RecordDriver\SolrMarc::getFieldArray() for the original function
     */
    protected function getConditionalFieldArray(string $field, array $subfields = ['a'], bool $concat = true,
                                                string $separator = ' ', array $conditions = []
    ) : array {
        // Initialize return array
        $matches = [];

        // Try to look up the specified field, return empty array if it doesn't
        // exist.
        $fields = $this->getMarcReader()->getFields($field);

        // Extract all the requested subfields, if applicable.
        foreach ($fields as $currentField) {
            foreach ($conditions as $conditionSubfield => $conditionValue) {
                if(!is_array($conditionValue)) {
                    $conditionValue = [$conditionValue];
                }

                $check = $this->getSubfieldArray($currentField, [$conditionSubfield]);
                if(!array_intersect($conditionValue, $check)) {
                    continue 2;
                }
            }
            $next = $this->getSubfieldArray($currentField, $subfields, $concat, $separator);
            $matches = array_merge($matches, $next);
        }

        return $matches;
    }

    /**
     * Check if the record is a news paper.
     *
     * @return bool
     */
    public function isNewsPaper() : bool
    {
        $leader = $this->getMarcReader()->getLeader();
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
     */
    public function isFormat(string $format = '', bool $pcre = false) : bool {
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
     * Checks, if there are copies in archives of the ThULB.
     *
     * @return bool
     */
    public function isInArchive() : bool {
        $depMails = $this->thulbConfig->JournalRequest->ArchiveEmail ?? [];
        $archiveCodes = array_keys($depMails ? $depMails->toArray() : []);
        $holdingsLocations = $this->getHoldingsLocations();

        foreach($holdingsLocations as $location) {
            if(in_array($location['departmentId'], $archiveCodes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get locations of holdings. Locations are gathered from DAIA.
     * Return format:
     *   array (
     *     array (
     *       'departmentId' => id,
     *       'desc' => description
     *     ),
     *     ...
     *   )
     *
     * @return array
     */
    protected function getHoldingsLocations() : array {
        $locations = array();
        $holdings = $this->getHoldings();

        foreach ($holdings['holdings'] as $holding) {
            foreach ($holding['items'] as $item) {
                $locations[] = array(
                    'departmentId' => $item['departmentId'],
                    'desc' => $item['location']
                );
            }
        }

        return $locations;
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
    public function getOnlineHoldings() : array
    {
        $retVal = [];

        /* extract all LINKS form MARC 981 */
        $links = $this->getConditionalFieldArray(
            '981', ['1', 'y', 'r', 'w'], true, static::SEPARATOR, ['2' => static::LIBRARY_ILN]
        );

        if (!empty($links)){
            /* what kind of LINKS do we have?
             * is there more Information in MARC 980 / 982?
             */
            foreach ($links as $link) {
                $more = '';
                $linkElements = explode(static::SEPARATOR, $link);
                $id = $linkElements[0] ?? '';
                $txt = $linkElements[1] ?? '';
                $url = $linkElements[2] ?? '';

                /* do we have a picture? f.e. ELS-gif */
                if (substr($txt, -3) == 'gif') {
                    $retVal[$id] = $txt;
                    continue;
                }

                /* seems that the real LINK is in 981y if 981r or w is empty... */
                if (empty($txt)) {
                    $txt = $url;
                }
                /* ... and vice versa */
                if (empty($url)) {
                    $url = $txt;
                    $txt = 'fulltext';
                }

                /* Now, we are ready to extract extra-information
                * @details for each link is common catalogisation till RDA-introduction
                */
                $details = $this->getConditionalFieldArray(
                    '980', ['g', 'k'], false, '', ['2' => static::LIBRARY_ILN, '1' => $id]
                );

                if (empty($details)) {
                    /* new catalogisation rules with RDA: One Link and single Details for each part */
                    $details = $this->getConditionalFieldArray(
                    '980', ['g', 'k'], false, '', ['2' => static::LIBRARY_ILN]);
                }
                if (!empty($details)) {
                    foreach ($details as $detail) {
                        $more .= $detail . "<br>";
                    }
                }

                $corporates = $this->getConditionalFieldArray(
                    '982', ['a'], false, '', ['2' => static::LIBRARY_ILN, '1' => $id]
                );

                if (!empty($corporates)) {
                    foreach ($corporates as $corporate) {
                        $more .= $corporate . "<br>";
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
                $retVal[$id] = $txt_sanitized . static::SEPARATOR .
                $txt . static::SEPARATOR .
                $url . static::SEPARATOR .
                $more . static::SEPARATOR .
                $tmp;
            }
        }
        else {
            $retVal = "";
        }

        return $retVal;
    }

    /**
     * Return an array of all Holding-Comments
     * Field 980g, k
     *
     * @param string $epn_str
     *
     * @return array
     */
    public function getHoldingComments(string $epn_str) : array {
        list($txt, $epn) = explode(":epn:", $epn_str);
        /* extract all Comments form MARC 980 */
        $comments_g = $this->getConditionalFieldArray(
            '980', ['g', 'k'], false, '', ['2' => static::LIBRARY_ILN, 'b' => $epn]
        );
        $comments_k = $this->getConditionalFieldArray(
            '980', ['k'], false, '', ['2' => static::LIBRARY_ILN, 'b' => $epn]
        );

        return array($comments_g[0], $comments_k[0]);
    }

    /**
     * Get the Hierarchy Type (false if none)
     *
     * @return string|bool
     */
    public function getHierarchyType()
    {
        $hierarchyType = $this->fields['hierarchytype'] ?? false;
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
     *
     * @return string
     */
    protected function transferHighlighting(string $plainString, string $highlightedString) : string
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
                        $parts[$i] = $replace($part, $searches, $highlights);
                    }

                    return implode($highlightString, $parts);
                }

                return $subject;
            };

            $modifiedString = trim($replace($plainString, array_keys($replacements), array_values($replacements)));
        }

        return $modifiedString;
    }

    /**
     * Get the group highlighting of the item.
     *
     * @param array $highlightStrings
     *
     * @return array
     */
    protected function groupHighlighting(array $highlightStrings) : array
    {
        return preg_replace('/\{\{\{\{END_HILITE\}\}\}\}\s?\{\{\{\{START_HILITE\}\}\}\}/', ' ', $highlightStrings);
    }

    /**
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublicationInfo() and getPlacesOfPublication().
     *
     * @return array
     */
    public function getPublicationDetails() : array
    {
        $retVal = array();
        foreach($this->getMarcReader()->getFields('264') as $field) {
            $retVal[] = new PublicationDetails(
                implode(' ; ', $this->getSubfields($field, 'a')),
                $this->getSubfield($field, 'b'),
                $this->getSubfield($field, 'c')
            );
        }

        return $retVal;
    }

    /**
     * Generates a single line with basic publication information including the
     * first location of the publication, the publisher, the year and the
     * edition.
     *
     * @return String|null
     */
    public function getReducedPublicationInfo() : ?string
    {
        return $this->getFormattedMarcData('250a - (((264a : 264b), 264c)');
    }

    /**
     * Get production of the item from 264.
     *
     * @return array
     */
    public function getProduction() : array {
        $productions = array();
        foreach($this->getMarcReader()->getFields('264') as $currentField) {
            if($currentField['i2'] == 3) {
                $a = $this->getSubfields($currentField, 'a');

                $b = $this->getSubfield($currentField, 'b');
                $b = $b ? ' : ' . $b : '';

                $c = $this->getSubfield($currentField, 'c');
                $c = $c ? ', ' . $c : '';

                $productions[] = implode('; ', $a) . $b . $c;
            }
        }

        return $productions;
    }

    /**
     * Get reproduction of the item from 533.
     *
     * @return array
     */
    public function getReproduction() : array {
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
     */
    public function getTOC() : array
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
     */
    public function getOtherTitles() : string {
        $fields = $this->getMarcReader()->getFields('249');

        if(count($fields) < 1) {
            return '';
        }
        $field = $fields[0];

        $data = '';
        foreach ($field['subfields'] as $subField) {
            if($subField['code'] === 'a') {
                $separator = !empty($data) ? ' ; ' : '';
            }
            else {
                $separator = $subField['code'] === 'b' ? ' : ' : ' / ';
            }

            $data .= $separator . $subField['data'];
        }

        return $data;
    }

    /**
     * Returns a formatted string with the content types.
     *
     * @return string
     */
    public function getTypeOfContent() : string {
        $relevantFields = array('655' => ['a', 'x', 'y', 'z']);
        $formattingRules = array('655' => '655a \(655x, 655y, 655z\)');
        return implode('; ', $this->getFormattedData($relevantFields, $formattingRules));
    }

    /**
     * Returns a multidimensional array with all subjects.
     *
     * @param bool $extended
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false) : array {
        $subjects = array_unique(
            array_merge(
                $this->getSubjectsFromField650(),
                $this->getSubjectsFromField689()
            ), SORT_REGULAR
        );

        usort($subjects, function($o1, $o2) {
            if(count($o1) < count($o2)) {
                return 1;
            }
            elseif(count($o1) > count($o2)) {
                return -1;
            }
            else {
                return strcasecmp($o1[0], $o2[0]);
            }
        });

        return $subjects;
    }

    /**
     * Reads subjects with hierarchies from MRC 650 fields
     *
     * @return array
     */
    private function getSubjectsFromField650() : array {
        $fields = $this->getMarcReader()->getFields('650');
        if (!$fields) {
            return [];
        }

        $subjects = array();
        foreach ($fields as $field) {
            if ($subfield = $this->getSubfield($field, '8')) {
                $level = preg_split('/\./', $subfield);
                if ($subfield = $this->getSubfield($field, 'a')) {
                    $subjects[$level[0]][$level[1]] = $subfield;
                }
            } else {
                if ($subfield = $this->getSubfield($field, 'a')) {
                    $subjects[][0] = $subfield;
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
     */
    private function getSubjectsFromField689() : array {
        $conditions = array(
            $this->createFieldCondition('indicator', '1', '!=', false),
            $this->createFieldCondition('indicator', '2', '!=', false),
            $this->createFieldCondition('subfield',  'a', '!=', false),
        );

        $fields = $this->getFieldsConditional('689', $conditions);

        $subjects = array();
        foreach ($fields as $field) {
            $subjects[$field['i1']][$field['i2']] = $this->getSubfield($field, 'a');
        }

        return $subjects;
    }

    /**
     * Get all record links related to the current record. Each link is returned as
     * array.
     * Also checks if the linked resources are available through the system.
     *
     * @return array|null
     *
     * @throws Exception
     */
    public function getAllRecordLinks() : ?array {
        $recordLinks = parent::getAllRecordLinks();

        if(!is_array($recordLinks)) {
            return null;
        }

        // Display ISBN or ISSN (if existing) as text
        foreach($recordLinks as $index => $recordLink) {
            if(in_array($recordLink['link']['type'], ['isbn', 'issn'])) {
                $recordLinks[$index]['value'] = strtoupper($recordLink['link']['type'])
                    . " " . $recordLink['link']['value'];
                $recordLinks[$index]['link'] = null;
            }
            elseif ($recordLink['link']['type'] != 'bib') {
                $recordLinks[$index]['link'] = null;

            }
        }

        return $this->checkListForAvailability($recordLinks);
    }

    /**
     * Checks if the record is part of the "Thüringen-Bibliographie"
     *
     * @return bool
     */
    public function isThuBibliography() : bool {
        if(isset($this->fields['class_local_iln']) && is_array($this->fields['class_local_iln'])) {
            foreach ($this->fields['class_local_iln'] as $classLocal) {
                if (preg_match('/^31:.*<Thüringen>$/', $classLocal)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getHoldings() : array {
        if(!$this->holdingData) {
            $this->holdingData = $this->getRealTimeHoldings();

            foreach ($this->holdingData['holdings'] as $key => $holding) {
                $this->holdingData['holdings'][$key]['isHandsetLocation'] =
                    $holding['items'][0]['isHandset'] ?? false;
            }

            uasort($this->holdingData['holdings'], function($holding1, $holding2) {
                return $holding1['isHandsetLocation'] <=> $holding2['isHandsetLocation'];
            });
        }

        return $this->holdingData;
    }

    /**
     * Return first ISMN found for this record, or false if none is found
     *
     * @return string|false
     */
    public function getCleanISMN() {
        $conditions = array(
            $this->createFieldCondition('indicator', '1', '==', '2'),
            $this->createFieldCondition('subfield', 'a', '!=', false)
        );

        // Fix for cases where 024 $a is not set
        $fields024 = $this->getFieldsConditional('024', $conditions);

        return (count($fields024) > 0) ? $this->getSubfield($fields024[0], 'a') : false;
    }

    /**
     * Return first ISMN found for this record, or false if none is found
     *
     * @return array
     */
    public function getLegalInformation() : array {
        // Fix for cases where 024 $a is not set
        $fields540 = $this->getMarcReader()->getFields('540');
        $data = array();
        foreach ($fields540 as $field) {
            $description = $link = null;
            if($subfield = $this->getSubfield($field,'f')) {
                $description = $subfield;
            }
            elseif($subfield = $this->getSubfield($field, 'a')) {
                $description = $subfield;
            }

            if($subfield = $this->getSubfield($field, 'u')) {
                $link = $subfield;
            }
            if($description || $link) {
                $data[] = array(
                    'desc' => $description ?: $link,
                    'link' => $link ?: false
                );
            }
        }
        return $data;
    }

    /**
     * Get access restriction notes for the record.
     *
     * @return array
     */
    public function getAccessRestrictions() : array {
        $retValue = [];
        $fields = $this->getFieldsConditional('506', [$this->createFieldCondition('subfield', 'a', '!=', false)]);
        foreach ($fields as $field) {
            $retValue[] = array(
                'desc' => $this->getSubfield($field, 'a'),
                'link' => $this->getSubfield($field, 'u') ?: false
            );
        }

        if(empty($retValue) && $this->isOpenAccess()) {
            $retValue[] = array(
                'desc' => 'Open Access',
                'link' => false
            );
        }

        return $retValue;
    }

    /**
     * Get the URN for the record
     *
     * @return string|false
     */
    public function getURN () {
        $conditions = array(
            $this->createFieldCondition('subfield', 'a', '!=', false),
            $this->createFieldCondition('subfield', '2', '==', 'urn')
        );

        $fields = $this->getFieldsConditional('024', $conditions);
        foreach ($fields as $field) {
            return $this->getSubfield($field, 'a');
        }

        return false;
    }

    /**
     * Return the first valid DOI found in the record (false if none).
     *
     * @return string|false
     */
    public function getCleanDOI() {
        $conditions = array(
            $this->createFieldCondition('indicator', '1', '==', '7'),
            $this->createFieldCondition('subfield', '2', '==', 'doi'),
            $this->createFieldCondition('subfield', 'a', '!=', false),
        );

        if($fields = $this->getFieldsConditional('024', $conditions)) {
            return $this->getSubfield($fields[0], 'a');
        }

        return false;
    }

    /**
     * Get the RVK notations of the record.
     *
     * @return array
     */
    public function getRvkNotation() : array {
        $fields = $this->getFieldsConditional('936', [
            $this->createFieldCondition('indicator', '1', '==', 'r'),
            $this->createFieldCondition('indicator', '2', '==', 'v'),
            $this->createFieldCondition('subfield', 'a', '!=', false)
        ]);

        $data = [];
        foreach($fields as $field) {
            $data[] = $this->getMarcReader()->getSubfield($field, 'a');
        }

        return $data;
    }

    /**
     * Get the DDC notations of the record.
     *
     * @return array
     */
    public function getDdcNotationDNB() : array {
        $fields = $this->getFieldsConditional('082', [
            $this->createFieldCondition('indicator', '1', '==', '0'),
            $this->createFieldCondition('indicator', '2', '==', '4'),
            $this->createFieldCondition('subfield', '2', 'in', ['ddc', false])
        ]);

        $data = [];
        foreach($fields as $field) {
            $data = array_merge(
                $data, $this->getMarcReader()->getSubfields($field, 'a')
            );
        }

        $data = array_unique($data);
        sort($data);

        return $data;
    }

    /**
     * Get the local classification of the record.
     *
     * @return array
     */
    public function getLocalClassification() : array {
        $fields = $this->getFieldsConditional('983', [
            $this->createFieldCondition('subfield', '2', '==', static::LIBRARY_ILN),
            $this->createFieldCondition('subfield', '8', '==', '00'),
            $this->createFieldCondition('subfield', 'a', '!=', false)
        ]);

        $data = [];
        foreach($fields as $field) {
            $data[] = $this->getMarcReader()->getSubfield($field, 'a');
        }

        return $data;
    }

    /**
     * Get the local subject terms of the record.
     *
     * @return array
     */
    public function getLocalSubjects() : array {
        $fields = $this->getFieldsConditional('982', [
            $this->createFieldCondition('subfield', '2', '==', static::LIBRARY_ILN),
            $this->createFieldCondition('subfield', '1', '==', '00'),
            $this->createFieldCondition('subfield', 'a', '!=', false)
        ]);

        $data = [];
        foreach($fields as $field) {
            $data[] = $this->getMarcReader()->getSubfield($field, 'a');
        }

        return $data;
    }

    /**
     * Return a source for the record.
     *
     * @return array
     */
    public function getSource() : array {
        if($recordSources = $this->thulbConfig->RecordSources) {
            foreach ($recordSources->toArray() as $source) {
                foreach (explode(',', $source['conditions']) as $condition) {
                    list($field, $value) = explode(':', $condition);
                    if(!isset($this->fields[$field])) {
                        continue 2;
                    }

                    $fieldData = is_array($this->fields[$field]) ? $this->fields[$field] : [$this->fields[$field]];
                    if (!in_array($value, $fieldData)) {
                        continue 2;
                    }
                }

                return [[
                    'name' => $source['name'],
                    'url' => $source['url'] ?? null,
                ]];
            }
        }

        return [];
    }

    public function getSummary() : array {
        return [$this->getFirstFieldValue('520')];
    }

    public function isOpenAccess() : bool {
        return ($this->fields['isOA_bool'] ?? 'false') == 'true';
    }

    /**
     * Get all related holdings available for ordering or placing a hold
     *
     * @return array
     */
    public function getHoldingsToOrderOrReserve() : array {
        $excludedIds = $this->thulbConfig->OrderReserve?->exclude?->toArray() ?? [];

        $holdingsOrder = $holdingsReserve = [];
        $holdings = $this->getHoldings();
        $hasOpenStock = false;
        foreach ($holdings['holdings'] ?? [] as $holdingLocation => $holding) {
            foreach ($holding['items'] as $item) {
                if (($item['isHandset'] ?? false)
                    || $item['availability']->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_UNCERTAIN)
                    || $item['availability']->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_UNKNOWN)
                    || $item['availability']->is(\ThULB\ILS\Logic\AvailabilityStatus::STATUS_ORDERED)
                ) {
                    // ignore handsets or items without status 'available' or 'unavailable'
                    continue;
                }

                if (in_array($item['departmentId'], $excludedIds)) {
                    continue;
                }

                $isOpenStack =  $item['availability']->isAvailable()
                    && !isset($item['link'])
                    && !isset($item['storageRetrievalRequestLink']);
                $isNewspaperRetrieval = isset($item['storageRetrievalRequestLink'])
                    && $this->isNewsPaper();
                if ($isOpenStack || $isNewspaperRetrieval) {
                    // item is available in open stack
                    $hasOpenStock = true;
                    continue;
                }

                if ($item['availability']->isAvailable()) {
                    // available in stack, no need to look for other items
                    $holdingsOrder[$holdingLocation][] = $item;
                }
                elseif ($item['link'] ?? false) {
                    // look for the item with the least placed requests or earliest due date
                    if (!isset($tmpLinkData['duedate'])
                        || $tmpLinkData['requests_placed'] > $item['requests_placed']
                        || ($tmpLinkData['requests_placed'] == $item['requests_placed']
                            && strtotime($tmpLinkData['duedate']) > strtotime($item['duedate']))
                    ) {
                        $holdingsReserve[$holdingLocation][] = $item;
                    }
                }
            }
        }

        return $hasOpenStock ? $holdingsOrder : array_merge_recursive($holdingsOrder, $holdingsReserve);
    }

    /**
     * Get all provenances.
     *
     * @return array
     */
    public function getProvenance() : array {
        $marcReader = $this->getMarcReader();
        $data = [];
        foreach($marcReader->getFields('361') as $field) {
            if($marcReader->getSubfield($field, '5') != 'DE-27') {
                continue;
            }

            $link = null;
            foreach ($marcReader->getSubfields($field, '0') as $subfield) {
                if(str_starts_with($subfield, '(' . static::GND_LINK_ID_PREFIX . ')')) {
                    $link = substr($subfield, 8);
                    break;
                }
            }

            $callnumber = $marcReader->getSubfield($field, 's');
            $type = $marcReader->getSubfield($field, 'o');

            $data[$callnumber][$type][] = array (
                'link' => $link,
                'name' => $marcReader->getSubfield($field, 'a'),
                'evidence' => $marcReader->getSubfields($field, 'f'),
                'type' => $type,
                'callnumber' => $callnumber,
                'attribute' => $marcReader->getSubfield($field, 'u'),
                'note' => $marcReader->getSubfield($field, 'z'),
                'date' => $marcReader->getSubfield($field, 'k'),
            );
        }

        return $data;
    }

    /**
     * Get index field data.
     *
     * @param string $field
     *
     * @return array|string|null
     */
    public function getIndexField(string $field) : array|string|null {
        return $this->fields[$field] ?? null;
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
//                $format = substr($this->getMarcReader()->getLeader(), 6, 1);
//                if(isset($this->marcFormatConfig->Leader6_Format[$format])) {
//                    $formats[$index] = $this->marcFormatConfig->Leader6_Format[$format];
//                }
//            }
//        }
//
//        return $formats;
//    }
}