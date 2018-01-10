<?php

namespace ThULB\View\Helper\Record;

use Zend\View\Helper\AbstractHelper;

class HoldingHelper extends AbstractHelper
{  
  public function getAvailability(&$itemRow)
  {
    // AJAX Check record?
    $check = isset($itemRow['check']) && $itemRow['check'];
    $checkStorageRetrievalRequest = isset($itemRow['checkStorageRetrievalRequest']) && $itemRow['checkStorageRetrievalRequest'];
    $checkILLRequest = isset($itemRow['checkILLRequest']) && $itemRow['checkILLRequest'];

    $availabilityString = '';

    if (isset($itemRow['barcode']) && $itemRow['barcode'] != "") {
      if ($itemRow['reserve'] == "Y") {
          $availabilityString .= '<link property=\"availability" href="http://schema.org/InStoreOnly" />';
          $availabilityString .= $this->view->transEsc("On Reserve - Ask at Circulation Desk") . '<br />';
      }
      if (isset($itemRow['use_unknown_message']) && $itemRow['use_unknown_message']) {
          $availabilityString .= '<span class="text-danger">' . $this->view->transEsc("status_unknown_message") . '</span>';
      } else {
        if ($itemRow['availability']) {
          /* Begin AVAILABLE Items (Holds) */
          $availabilityString .= '<span class="text-success">' . $this->view->transEsc("Available") . '<link property="availability" href="http://schema.org/InStock" /></span>';
          if (isset($itemRow['link']) && $itemRow['link']) {
              $availabilityString .= '<a class="' . ($check ? 'checkRequest ' : '') . 'placehold" data-lightbox href="' . $this->view->recordLink()->getRequestUrl($itemRow['link']) . '"><i class="fa fa-flag" aria-hidden="true"></i>&nbsp;' . $this->view->transEsc($check ? "Check Hold" : "Place a Hold") . '</a>';
          }
          if ( isset($itemRow['storageRetrievalRequestLink']) && $itemRow['storageRetrievalRequestLink'] && !($this->view->driver->isNewsPaper()) ) {
              $availabilityString .= '<a class="' . ($checkStorageRetrievalRequest ? 'checkStorageRetrievalRequest ' : '') . 'placeStorageRetrievalRequest" data-lightbox href="' . $this->view->recordLink()->getRequestUrl($itemRow['storageRetrievalRequestLink']) . '"> <i class="fa fa-flag" aria-hidden="true"></i>&nbsp;' . $this->view->transEsc($checkStorageRetrievalRequest ? "storage_retrieval_request_check_text" : "storage_retrieval_request_place_text") . '</a>';
          }
          if (isset($itemRow['ILLRequestLink']) && $itemRow['ILLRequestLink']) {
              $availabilityString .= '<a class="' . ($checkILLRequest ? 'checkILLRequest ' : '') . 'placeILLRequest" data-lightbox href="' . $this->view->recordLink()->getRequestUrl($itemRow['ILLRequestLink']) . '"><i class="fa fa-flag" aria-hidden="true"></i>&nbsp;' . $this->view->transEsc($checkILLRequest ? "ill_request_check_text" : "ill_request_place_text") . '</a>';
          }
          /* Nicht leihbar? Also Lesesaal! */
          if ( !in_array("loan", $itemRow['services']) ) {
            $availabilityString .= "<br>" . $this->view->transEsc('reading_room_only');
          }
        } else {
          /* Begin UNAVAILABLE Items (Recalls) */
          if ((isset($itemRow['returnDate']) && $itemRow['returnDate'])
            || (isset($itemRow['duedate']) && $itemRow['duedate'])) {
            /* is there a duedate? > "ausgeliehen" */
            $availabilityString .= '<span class="text-danger">' . $this->view->transEsc('ils_hold_item_' . $itemRow['status']) . '<link property="availability" href="http://schema.org/OutOfStock" /></span>';
          } else {
            /* no duedate? > "nicht verfügbar" */
            $availabilityString .= '<span class="text-danger">' . $this->view->transEsc('ils_hold_item_notavailable') . '<link property="availability" href="http://schema.org/OutOfStock" /></span>';
          }
          if (isset($itemRow['returnDate']) && $itemRow['returnDate']) {
              $availabilityString .= '&ndash; <span class="small">' . $this->view->escapeHtml($itemRow['returnDate']) . '</span>';
          }
          if (isset($itemRow['duedate']) && $itemRow['duedate']) {
              $availabilityString .= '&ndash; <span class="small">' . $this->view->transEsc("Due") . ': ' . $this->view->escapeHtml($itemRow['duedate']) . '</span>';
          }
          if (isset($itemRow['link']) && $itemRow['link']) {
              $availabilityString .= '<a class="' . ($check ? 'checkRequest' : '') . 'placehold" data-lightbox href="' . $this->view->recordLink()->getRequestUrl($itemRow['link']) . '"><i class="fa fa-flag" aria-hidden="true"></i>&nbsp;' . $this->view->transEsc($check ? "Check Recall" : "Recall This") . '</a>';
          }
          if (isset($itemRow['requests_placed']) && $itemRow['requests_placed'] > 0) {
              $availabilityString .= ' <span>(' . $this->view->escapeHtml($itemRow['requests_placed']) . 'x '. $this->view->transEsc("ils_hold_item_requested") . ')</span>';
          }
        }
      }
      /* Embed item structured data: library, barcode, call number */
      if ($itemRow['location']) {
          $availabilityString .= '<meta property="seller" content="' . $this->view->escapeHtmlAttr($itemRow['location']) . '" />';
      }
      if ($itemRow['barcode']) {
          $availabilityString .= '<meta property="serialNumber" content="' . $this->view->escapeHtmlAttr($itemRow['barcode']) . '" />';
      }
      if ($itemRow['callnumber']) {
          $availabilityString .= '<meta property="sku" content="' . $this->view->escapeHtmlAttr($itemRow['callnumber']) . '" />';
      }
      /* Declare that the item is to be borrowed, not for sale */
      $availabilityString .= '<link property="businessFunction" href="http://purl.org/goodrelations/v1#LeaseOut" />';
      $availabilityString .= '<link property="itemOffered" href="#record" />';
    }

    return $availabilityString;
  }

  public function getLocation(&$holding)
  {
    $locationText = $this->view->transEsc('location_' . $holding['location'], [], $holding['location']);

    if (isset($holding['locationhref']) && $holding['locationhref']) {
      $locationText = '<a href="' . $holding['locationhref'] . '" target="_blank">' . $locationText . '</a>';
    }

    return $locationText;
  }

  public function getCallNumber(&$item)
  { 
    return $item['callnumber'] ?: '';
  }

  public function getCallNumbers($holding)
  {
    $callnumberString = '';

    $callNos = $this->view->tab->getUniqueCallNumbers($holding['items']);
    if (!empty($callNos)) {
      foreach ($callNos as $callNo) {
        if ($this->view->callnumberHandler) {
          $callnumberString .= '<a href="' . $this->view->url('alphabrowse-home') . '?source=' . $this->view->escapeHtmlAttr($this->view->callnumberHandler) . '&amp;from=' . $this->view->escapeHtmlAttr($callNo) . '">' . $this->view->escapeHtml($callNo) . '</a>';
        } else {
          $callnumberString .= $this->view->escapeHtml($callNo);
        }
        $callnumberString .= '<br />';
      }
    } else {
        $callnumberString = '&nbsp;';
    }

    return $callnumberString;
  }


  public function getHoldingComments(&$itemRow)
  {
    $holding_comments = "";
    if (!empty($itemRow['about'])) {
      $holding_comments = explode("\n", $itemRow['about']);
    }
    if (!empty($itemRow['chronology_about'])) {
      $holding_comments[] = $itemRow['chronology_about'];
    }
    return $holding_comments;
  }
}
