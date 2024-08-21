<?php
/**
 * View helper for holdings
 *
 * PHP version 5
 *
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @author   Clemes Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\View\Helper\Record;

use Laminas\View\Helper\AbstractHelper;

class HoldingHelper extends AbstractHelper
{
    /**
     * Creates a string including the link to place a recall.
     *
     * @param array $itemRow
     *
     * @return array
     */
    public function getRecallLink(array $item): array {
        if ($item['link'] ?? false) {
            $check = isset($item['check']) && $item['check'];
            $url = $this->view->recordLinker()->getRequestUrl($item['link'], false)
                . '&duedate=' . $item['duedate'] . '&requests_placed=' . $item['requests_placed'];

            return [
                'classes' => ($check ? 'checkRequest' : '') . 'placehold btn btn-primary btn-xs',
                'link' => $url,
                'desc' => $check ? 'Check Recall' : 'Recall This'
            ];
        }

        return [];
    }

    public function getRequestLinks(array $item, bool $isNewsPaper) : array {
        $check = $item['check'] ?? false;
        $checkStorageRetrievalRequest = $item['checkStorageRetrievalRequest'] ?? false;
        $checkILLRequest = $item['checkILLRequest'] ?? false;

        $links = array ();
        if ($item['link'] ?? false) {
            $links[] = array (
                'classes' => ($check ? 'checkRequest ' : '') . 'placehold btn btn-primary btn-xs',
                'link' => $this->view->recordLinker()->getRequestUrl($item['link']),
                'desc' => $check ? 'Check Hold' : 'Place a Hold'
            );
        }

        if (($item['storageRetrievalRequestLink'] ?? false) && !$isNewsPaper) {
            $links[] = array (
                'classes' => ($checkStorageRetrievalRequest ? 'checkStorageRetrievalRequest ' : '') . 'placeStorageRetrievalRequest btn btn-primary btn-xs',
                'link' => $this->view->recordLinker()->getRequestUrl($item['storageRetrievalRequestLink']),
                'desc' => $checkStorageRetrievalRequest ? 'storage_retrieval_request_check_text' : 'storage_retrieval_request_place_text'
            );
        }

        if ($item['ILLRequestLink'] ?? false) {
            $links[] = array (
                'classes' => ($checkILLRequest ? 'checkILLRequest ' : '') . 'placeILLRequest',
                'link' => $this->view->recordLinker()->getRequestUrl($item['ILLRequestLink']),
                'desc' => $checkILLRequest ? 'ill_request_check_text' : 'ill_request_place_text',
                'icon' => '<i class="fa fa-flag" aria-hidden="true"></i>'
            );
        }

        return $links;
    }

    public function getLocation(array $holding, bool $includeHTML = true): string {
        $locationText = $this->view->transEscWithPrefix('location_', $holding['location']);

        if ($includeHTML && isset($holding['locationhref']) && $holding['locationhref']) {
            $locationText = '<a href="' . $holding['locationhref'] . '" class="external-link" target="_blank">' . $locationText . '</a>';
        }

        return $locationText;
    }

    public function getLocationInfoMessage(array $holding): ?string {
        $msg = null;
        $thulbConfig = $this->getView()->config()->get('thulb');
        if (($holding['items'][0] ?? false) && ($configData = $thulbConfig->LocationInfoMsg ?? null)) {
            $msg = $configData->toArray()[$holding['items'][0]['departmentId'] ?? false] ?? null;
        }

        return $msg;
    }

    public function getCallNumber(array $item): string {
        return $item['callnumber'] ?: '';
    }

    public function getCallNumbers(array $holding): string {
        $callnumberString = '';

        $callNos = $this->view->tab->getUniqueCallNumbers($holding['items']);
        if (!empty($callNos)) {
            foreach ($callNos as $callNo) {
                if ($this->view->callnumberHandler) {
                    $callnumberString .= '<a href="' . $this->view->url('alphabrowse-home') . '?source=' . $this->view->escapeHtmlAttr($this->view->callnumberHandler) . '&amp;from=' . $this->view->escapeHtmlAttr($callNo) . '">' . $this->view->escapeHtml($callNo) . '</a>';
                }
                else {
                    $callnumberString .= $this->view->escapeHtml($callNo);
                }
                $callnumberString .= '<br />';
            }
        }
        else {
            $callnumberString = '&nbsp;';
        }

        return $callnumberString;
    }

    public function getHoldingComments(array $itemRow): array {
        $holding_comments = array();
        if (!empty($itemRow['about'])) {
            $holding_comments = explode("\n", $itemRow['about']);
        }
        return $holding_comments;
    }

    public function getHoldingChronology(array $itemRow): array {
        $holding_chron = array();
        if (!empty($itemRow['chronology_about'])) {
            $holding_chron[] = $itemRow['chronology_about'];
        }
        return $holding_chron;
    }

    public function getReadingRoomOnlyString(array $item) : string {
        return ($item['availability']->isAvailable() ?? false) && !in_array("loan", $item['services']) ? $this->view->transEsc('reading_room_only') : '';
    }

    public function getRequestsPlacedString(array $item) : string {
        if (isset($item['requests_placed']) && $item['requests_placed'] > 0) {
            return '<span>(' . $this->view->escapeHtml($item['requests_placed']) . 'x ' . $this->view->transEsc("ils_hold_item_requested") . ')</span>';
        }

        return '';
    }
}
