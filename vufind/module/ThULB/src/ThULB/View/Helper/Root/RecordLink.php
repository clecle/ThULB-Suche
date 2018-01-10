<?php

namespace ThULB\View\Helper\Root;
use VuFind\View\Helper\Root\RecordLink as OriginalRecordLink;

/**
 * Description
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class RecordLink extends OriginalRecordLink
{
    /**
     * Given an array representing a related record (which may be a bib ID or OCLC
     * number), this helper renders a URL linking to that record.
     *
     * @param array $link   Link information from record model
     * @param bool  $escape Should we escape the rendered URL?
     *
     * @return string       URL derived from link information
     */
    public function related($link, $escape = true)
    {
        $urlHelper = $this->getView()->plugin('url');
        switch ($link['type']) {
        case 'bib':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=id&jumpto=1';
            break;
        case 'isbn':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=isbn';
            break;
        case 'issn':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=issn';
            break;
        case 'zdb':
        case 'dnb':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=ctrlnum';
            break;
        case 'title':
            $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=title';
            break;
        default:
            throw new \Exception('Unexpected link type: ' . $link['type']);
        }

        $escapeHelper = $this->getView()->plugin('escapeHtml');
        return $escape ? $escapeHelper($url) : $url;
    }
}
