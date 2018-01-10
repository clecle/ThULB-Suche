<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters fingerprint view helper functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class FingerprintMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Fingerprint';
    protected $metadataKey = 'Fingerprint';
    protected $recordDriverFunction = 'getFingerprint';
    protected $template = 'data-fingerprint.phtml';
}
