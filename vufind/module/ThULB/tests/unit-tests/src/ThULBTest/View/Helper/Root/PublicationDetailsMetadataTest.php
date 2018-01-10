<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters publication details view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class PublicationDetailsMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Publikationsangaben';
    protected $metadataKey = 'Publication Metadata';
    protected $recordDriverFunction = 'getPublicationDetails';
    protected $template = 'data-publicationDetails.phtml';
}
