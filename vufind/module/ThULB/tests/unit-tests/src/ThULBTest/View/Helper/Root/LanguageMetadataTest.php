<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters languages view helper functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class LanguageMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Sprachangaben';
    protected $metadataKey = 'Languages';
    protected $recordDriverFunction = 'getLanguages';
    protected $template = 'data-languages.phtml';
}
