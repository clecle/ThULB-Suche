<?php

namespace ThULBTest\View\Helper\Root;

/**
 * Test class for the record data formatters primary author view helper
 * functionality.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */

class AuthorMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = '1. Verfasser';
    protected $metadataKey = 'Main Authors';
    protected $recordDriverFunction = 'getDeduplicatedAuthors';
    protected $template = 'data-authors.phtml';
    protected $options = [
                    'context' => [
                        'type' => 'primary',
                        'schemaLabel' => 'author',
                        'requiredDataFields' => [
                            ['name' => 'role', 'prefix' => 'CreatorRoles::']
                        ]]
                ];
}
