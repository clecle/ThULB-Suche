<?php

namespace DHGE\View\Helper\Record;

use ThULB\View\Helper\Record\OnlineContent as OriginalOnlineContentHelper;
use VuFind\RecordDriver\DefaultRecord;

class OnlineContent extends OriginalOnlineContentHelper {
    public function getFulltextLink(DefaultRecord $driver, array $doiLinks = []): array
    {
        if($fulltextLink = parent::getFulltextLink($driver, $doiLinks)) {
            return $fulltextLink;
        }
        elseif($fulltextLink = $this->getDOIFulltextLink($driver)) {
            return $fulltextLink;
        }

        return [];
    }
}