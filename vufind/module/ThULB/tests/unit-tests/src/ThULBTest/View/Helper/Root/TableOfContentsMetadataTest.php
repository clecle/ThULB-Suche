<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters short title view helper
 * functionality.
 */
class TableOfContentsMetadataTest extends AbstractRecordDataFormatterTest
{
    protected ?string $sheetName = 'Inhaltsangaben';
    protected ?string $metadataKey = 'Table of Contents';
    protected ?string $recordDriverFunction = 'getToc';
}