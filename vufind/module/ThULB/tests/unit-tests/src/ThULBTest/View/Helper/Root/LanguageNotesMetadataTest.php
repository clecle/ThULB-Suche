<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters languages notes view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */

class LanguageNotesMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Angaben über Sprache und Schrift';
    protected $metadataKey = 'LanguageNotes';
    protected $template = 'data-language_notes.phtml';
}
