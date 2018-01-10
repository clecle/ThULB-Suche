<?php

namespace ThULB\RecordDriver;

use VuFind\RecordDriver\Summon as OriginalSummon;

/**
 * Overwrites and extends VuFinds standard Summon RecordDriver
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Summon extends OriginalSummon
{
    public function getURLs()
    {
        $hasNoFulltext = !isset($this->fields['hasFullText']) || !$this->fields['hasFullText'];
        if (isset($this->fields['link']) && $hasNoFulltext) {
            return [
                [
                    'url' => $this->fields['link'],
                    'desc' => $this->translate('get_citation')
                ]
            ];
        } else {
            return parent::getURLs();
        }
    }

    /**
     * Get a full, free-form reference to the context of the item that contains this
     * record (i.e. volume, year, issue, pages).
     *
     * @return string
     */
    public function getContainerReference()
    {
        $str = '';
        $vol = $this->getContainerVolume();
        if (!empty($vol)) {
            $str .= $this->translate('citation_volume_abbrev')
                . ' ' . $vol;
        }
        $no = $this->getContainerIssue();
        if (!empty($no)) {
            if (strlen($str) > 0) {
                $str .= ', ';
            }
            $str .= $this->translate('citation_issue_abbrev')
                . ' ' . $no;
        }
        $start = $this->getContainerStartPage();
        if (!empty($start)) {
            if (strlen($str) > 0) {
                $str .= ', ';
            }
            $end = $this->getContainerEndPage();
            if ($start == $end) {
                $str .= $this->translate('citation_singlepage_abbrev')
                    . ' ' . $start;
            } else {
                $str .= $this->translate('citation_multipage_abbrev')
                    . ' ' . $start . ' - ' . $end;
            }
        }
        return $str;
    }

    /**
     * Get an array of all corporate authors.
     *
     * @return array
     */
    public function getCorporateAuthors()
    {
        $authors = [];
        if (isset($this->fields['CorporateAuthor_xml'])) {
            for ($i = 0; $i < count($this->fields['CorporateAuthor_xml']); $i++) {
                if (isset($this->fields['CorporateAuthor_xml'][$i]['name'])) {
                    $authors[] = $this->fields['CorporateAuthor_xml'][$i]['name'];
                }
            }
        }
        return $authors;
    }
}
