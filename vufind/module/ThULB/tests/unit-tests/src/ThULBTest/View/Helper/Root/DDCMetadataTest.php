<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters short title view helper
 * functionality.
 */
class DDCMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'DDC-Sachgruppe der DNB';
    protected ?string $metadataKey = 'DDC Notation DNB';
    protected ?string $recordDriverFunction = 'getDdcNotationDNB';
    protected ?string $template = "data-ddcNotation.phtml";
}