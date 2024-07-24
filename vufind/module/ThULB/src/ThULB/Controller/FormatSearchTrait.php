<?php

namespace ThULB\Controller;

use Laminas\Mvc\MvcEvent;
use VuFind\Exception\PasswordSecurity;

trait FormatSearchTrait
{
    protected ?string $searchLengthLimit = null;
    protected ?string $searchTermLimit = null;

    protected function formatLookFor(string $originalSearchString) : string {
        $formattedSearchString = $this->removeSymbols($originalSearchString);
        if($formattedSearchString != $originalSearchString) {
            $this->flashMessenger()->addWarningMessage('removed symbols and emojis from search string');
        }

        $shortenedSearchString = $this->shortenLookFor($formattedSearchString);
        if($shortenedSearchString != $formattedSearchString) {
            $this->flashMessenger()->addWarningMessage(
                $this->translate('search_limited_note', [
                    '%%searchLengthLimit%%' => $this->getSearchLengthLimit(),
                    '%%searchTermLimit%%'   => $this->getSearchTermLimit()
                ])
            );
        }

        return $shortenedSearchString;
    }

    protected function shortenLookFor(string $searchString) : string {
        $searchTermLimit = $this->getSearchTermLimit();
        if($searchTermLimit > 0 && substr_count($searchString, ' ')) {
            $searchString = implode(' ', array_slice(explode(' ', $searchString), 0, $searchTermLimit));
        }

        $searchLengthLimit = $this->getSearchLengthLimit();
        if($searchLengthLimit > 0 && strlen($searchString) > $searchLengthLimit) {
            if (preg_match("/^.{1,$searchLengthLimit}\b/s", $searchString, $match)) {
                $searchString = $match[0];
            }
        }

        return $searchString;
    }

    /**
     * Remove emojis, icons and other symbols from the string
     *
     * @param string $string
     *
     * @return string
     */
    protected function removeSymbols(string $string) : string {
        // Match Enclosed Alphanumeric Supplement
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';

        // Match Supplemental Symbols and Pictographs
        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';

        // Match Variation Selectors
        $regex_variation_selectors = '/[\x{FE00}-\x{FE0F}]/u';

        // Match non-printable characters
        $regex_nonPrintable = '/[\x{0000}-\x{001F}\x{007F}-\x{009F}\x{200B}-\x{200F}]/u';

        $string =  preg_replace([
            $regex_nonPrintable,
            $regex_alphanumeric,
            $regex_symbols,
            $regex_emoticons,
            $regex_transport,
            $regex_supplemental,
            $regex_misc,
            $regex_dingbats,
            $regex_variation_selectors,

        ], '', $string);

        return trim(preg_replace('/\s+/', ' ', $string));
    }

    protected function getSearchLengthLimit() {
        if(!$this->searchLengthLimit) {
            $this->searchLengthLimit =
                $this->serviceLocator->get('VuFind\Config')->get('searches')->General->searchLengthLimit ?? -1;
        }

        return $this->searchLengthLimit;
    }

    protected function getSearchTermLimit() {
        if(!$this->searchTermLimit) {
            $this->searchTermLimit =
                $this->serviceLocator->get('VuFind\Config')->get('searches')->General->searchTermLimit ?? -1;
        }

        return $this->searchTermLimit;
    }
}
