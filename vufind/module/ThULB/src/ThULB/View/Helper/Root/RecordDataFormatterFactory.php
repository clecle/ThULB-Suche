<?php
/**
 * Factory for record driver data formatting view helper
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
 * @package  View_Helpers
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:record_data_formatter
 */
namespace ThULB\View\Helper\Root;

use Interop\Container\ContainerInterface;
use VuFind\View\Helper\Root\RecordDataFormatter;
use VuFind\View\Helper\Root\RecordDataFormatter\SpecBuilder;
use VuFind\View\Helper\Root\RecordDataFormatterFactory as OriginalFactory;

/**
 * Factory for record driver data formatting view helper
 *
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class RecordDataFormatterFactory extends OriginalFactory
{
    /**
     * Create the helper.
     *
     * @param ContainerInterface $container
     * @param string             $requestedName
     * @param array|null         $options
     *
     * @return RecordDataFormatter
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    ) {
        $helper = parent::__invoke($container, $requestedName, $options);
        $helper->setDefaults('full', $this->getDefaultFullSpecs());
        
        return $helper;
    }
    
    /**
     * Get default specifications for displaying data in full metadata.
     *
     * @return array
     */
    public function getDefaultFullSpecs()
    {
        $spec = new SpecBuilder();
        $spec->setLine('Other Titles', 'getOtherTitles');
        $spec->setLine('PartInfo', 'getPartInfo');
        $spec->setTemplateLine(
            'Main Authors', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'labelFunction' => function ($data) {
                    return count($data['primary']) > 1
                        ? 'Main Authors' : 'Main Author';
                },
                'context' => ['type' => 'primary',
                    'schemaLabel' => 'author',
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ],
            ]
        );
        $spec->setTemplateLine(
            'Corporate Authors', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'labelFunction' => function ($data) {
                    return count($data['corporate']) > 1
                        ? 'Corporate Authors' : 'Corporate Author';
                },
                'context' => [
                    'type' => 'corporate',
                    'schemaLabel' => 'creator',
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ],
            ]
        );
        $spec->setTemplateLine(
            'Other Authors', 'getDeduplicatedAuthors', 'data-authors.phtml',
            [
                'useCache' => true,
                'context' => [
                    'type' => 'secondary',
                    'schemaLabel' => 'contributor',
                    'requiredDataFields' => [
                        ['name' => 'role', 'prefix' => 'CreatorRoles::']
                    ]
                ],
            ]
        );
        $spec->setLine('Conference', 'getMeetingNames');
        $spec->setLine(
            'Format', 'getFormats', 'RecordHelper',
            ['helperMethod' => 'getFormatList']
        );
        $spec->setTemplateLine('Languages', 'getLanguages', 'data-languages.phtml');
        $spec->setTemplateLine('LanguageNotes', true, 'data-language_notes.phtml');
        $spec->setTemplateLine(
            'Published in', 'getContainerTitle', 'data-containerTitle.phtml'
        );
        $spec->setTemplateLine(
            'Publication Metadata', 'getPublicationDetails', 'data-publicationDetails.phtml'
        );
        $spec->setLine('Production', 'getProduction');
        $spec->setLine('Printing places', 'getPrintingPlaces');
        $spec->setLine('Dissertation', 'getDissertationNote');
        $spec->setLine('Map Scale', 'getCartographicScale');
        $spec->setLine('Map Projection', 'getCartographicProjection');
        $spec->setLine('Map Coordinates', 'getCartographicCoordinates');
        $spec->setLine('Map Equinox', 'getCartographicEquinox');
        $spec->setLine(
            'Edition', 'getEdition', null,
            ['prefix' => '<span property="bookEdition">', 'suffix' => '</span>']
        );
        $spec->setTemplateLine('Series', 'getSeries', 'data-series.phtml');
        $spec->setTemplateLine('Numbering', true, 'data-numbering.phtml');
        $spec->setTemplateLine('NumPecs', true, 'data-numbering_peculiarities.phtml');
        $spec->setTemplateLine(
            'Subjects', 'getAllSubjectHeadings', 'data-allSubjectHeadings.phtml'
        );
        $spec->setLine('Type of content','getTypeOfContent');
        $spec->setTemplateLine(
            'child_records', 'getChildRecordCount', 'data-childRecords.phtml',
            ['allowZero' => false]
        );
        $spec->setTemplateLine(
            'Related Items', 'getAllRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine(
            'Lineage Items', 'getLineageRecordLinks', 'data-allRecordLinks.phtml'
        );
        $spec->setTemplateLine('Tags', true, 'data-tags.phtml');
        $spec->setLine('Item Description', 'getGeneralNotes');
        $spec->setLine('Title of work', 'getTitleOfWork');
        $spec->setLine('Physical Description', 'getPhysicalDescriptions');
        $spec->setLine('Reproduction', 'getReproduction');
        $spec->setTemplateLine('Fingerprint', 'getFingerprint', 'data-fingerprint.phtml');
        $spec->setLine('Bibliographic Citations', 'getBibliographicCitation');
        $spec->setLine('Publication Frequency', 'getPublicationFrequency');
        $spec->setLine('Playing Time', 'getPlayingTimes');
        $spec->setLine('Audience', 'getTargetAudienceNotes');
        $spec->setLine('Awards', 'getAwards');
        $spec->setLine('Production Credits', 'getProductionCredits');
        $spec->setLine('Bibliography', 'getBibliographyNotes');
        $spec->setLine('ISBN', 'getISBNs');
        $spec->setLine('Invalid ISBN', 'getInvalidISBNs');
        $spec->setLine('ISSN', 'getISSNs');
        /* ZDB Id */
        $spec->setTemplateLine('ZDB', true, 'data-zdb.phtml');
        $spec->setLine('DOI', 'getCleanDOI');
        $spec->setLine('Access', 'getAccessRestrictions');
        $spec->setLine('Finding Aid', 'getFindingAids');
        $spec->setLine('Publication_Place', 'getHierarchicalPlaceNames');
        $spec->setTemplateLine('Author Notes', true, 'data-authorNotes.phtml');
        $spec->setTemplateLine('Basic Classification', true, 'data-basicClassification.phtml');
        $spec->setTemplateLine('Th_Biblio', true, 'data-thuBiblioClassification.phtml');
        $spec->setTemplateLine('Source', 'getDatabaseXML', 'data-source.phtml',
            [
                'useCache' => true,
                'labelFunction' => function ($data) {
                    return count($data) > 1 ? 'Sources' : 'Source';
                }
            ]
        );
        return $spec->getArray();
    }
}
