<?php

namespace ThULB\View\Helper\Root;

use VuFind\View\Helper\Root\SearchTabs as OriginalSearchTabs;

class SearchTabs extends OriginalSearchTabs
{
    public function getTabCount() : int {
        return count($this->helper->getTabConfig());
    }
}
