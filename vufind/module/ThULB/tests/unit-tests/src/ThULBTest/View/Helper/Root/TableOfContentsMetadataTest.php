<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters short title view helper
 * functionality.
 */
class TableOfContentsMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Inhaltsangaben';
    protected $metadataKey = 'Table of Contents';
    protected $recordDriverFunction = 'getToc';
}