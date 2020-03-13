<?php

namespace ThULB\Controller;

use ThULB\PDF\JournalRequest;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\View\Helper\Root\Translate;
use Whoops\Exception\ErrorException;
use Zend\View\Model\ViewModel;

class RequestController extends OriginalRecordController
{
    protected $inventory = array();

    public function journalAction () {

        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $formData = $this->getFormData();

        if($this->getRequest()->isPost() && $this->validateFormData($formData)) {
            $filename = $formData['username'] . '__' . date('Y_m_d__H_i_s') . '.pdf';

            try {
                $pdf = new JournalRequest($this->getTranslations());

                $pdf->setCallNumber($formData['callnumber']);
                $pdf->setComment($formData['comment']);
                $pdf->setVolume($formData['volume']);
                $pdf->setEmail($formData['email']);
                $pdf->setName($formData['name']);
                $pdf->setUserName($formData['username']);
                $pdf->setWorkTitle($formData['title']);
                $pdf->setYear($formData['year']);

                $pdf->create();
//                $pdf->Output('F', '/vagrant/pdfs/' . $filename);
//                $pdf->Output();

                $this->addFlashMessage(true, 'journal_request_succeeded');
            }
            catch (ErrorException $e) {
                $this->addFlashMessage(false, 'journal_request_failed');
                $this->addFlashMessage(false, $e->getMessage());
            }
        }

        return new ViewModel([
            'formData' => $formData,
            'recordId' => $this->loadRecord()->getUniqueID(),
            'inventory' => $this->getInventoryForRequest()
        ]);
    }

    protected function getFormData() {
        $params = $this->params();
        $user = $this->getUser();
        $inventory = $this->getInventoryForRequest();
        $defaultCallnumber = count($inventory) == 1 ? array_shift($inventory)['callnumber'] : '';

        return array (
            'email'      => $params->fromPost('email', $user['email']),
            'name'       => $params->fromPost('name', $user['firstname'] . ' ' . $user['lastname']),
            'username'   => $params->fromPost('username', $user['cat_id']),
            'title'      => $params->fromPost('title', $this->loadRecord()->getTitle()),
            'callnumber' => $params->fromPost('callnumber', $defaultCallnumber),
            'year'       => $params->fromPost('year', ''),
            'volume'     => $params->fromPost('volume', ''),
            'pages'      => $params->fromPost('pages', ''),
            'comment'    => $params->fromPost('comment', '')
        );
    }

    protected function getInventoryForRequest() {
        if(!$this->inventory) {
            $holdings = $this->loadRecord()->getRealTimeHoldings();
            foreach ($holdings['holdings'] as $location => $holding) {
                if (strpos($location, 'Magazin') === false) {
                    continue;
                }

                foreach ($holding['items'] as $item) {
                    $this->inventory[$location . $item['callnumber']] = array(
                        'callnumber' => $item['callnumber'],
                        'location' => $location,
                        'chronology' => !empty($item['chronology_about']) ? $item['chronology_about'] : $item['about']
                    );
                }
            }
            ksort($this->inventory);
        }

        return $this->inventory;
    }

    protected function validateFormData($formData) {
        $error = false;
        if(empty($formData['callnumber'])) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_field_empty', ['%%field%%' => 'storage_retrieval_request_select_location']
            );
            $error = true;
        }
        if(empty($formData['year'])) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_field_empty', ['%%field%%' => 'storage_retrieval_request_year']
            );
            $error = true;
        }
        return !$error;
    }

    protected function getTranslations() {
        /* @var $translator Translate */
        $translator = $this->getViewRenderer()->plugin('translate');
        $globalLocale = $translator->getTranslator()->getLocale();
        $translator->getTranslator()->setLocale('de');

        $translations = array();
        $translations['Call Number'] = $translator->translate("Call Number");
        $translations['Email'] = $translator->translate("Email");
        $translations['Name'] = $translator->translate("Name");
        $translations['Note'] = $translator->translate("Note");
        $translations['Title'] = $translator->translate("Title");
        $translations['Username'] = $translator->translate("Username");
        $translations['Volume'] = $translator->translate("storage_retrieval_request_volume");
        $translations['Year'] = $translator->translate("storage_retrieval_request_year");

        $translator->getTranslator()->setLocale($globalLocale);

        return $translations;
    }

    private function addFlashMessage($success, $messageKey, $messageFields = []) {
        $messageFunction = $success ? 'addSuccessMessage' : 'addErrorMessage';
        foreach ($messageFields as $field => $message) {
            $messageFields[$field] = $this->translate($message);
        }

        $this->flashMessenger()->$messageFunction (
            $this->translate($messageKey, $messageFields)
        );
    }
}
