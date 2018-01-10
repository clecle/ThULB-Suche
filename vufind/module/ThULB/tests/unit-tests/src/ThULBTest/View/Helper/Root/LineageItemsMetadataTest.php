<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ThULBTest\View\Helper\Root;

/**
 * Description
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class LineageItemsMetadataTest extends AbstractRecordDataFormatterTest
{
    protected $sheetName = 'Vorheriger Späterer Titel';
    protected $metadataKey = 'Lineage Items';
    protected $recordDriverFunction = 'getLineageRecordLinks';
    protected $template = 'data-allRecordLinks.phtml';
}
