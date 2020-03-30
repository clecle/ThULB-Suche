<?php

namespace ThULB\Controller;

use ThULB\PDF\JournalRequest;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\Exception\Mail as MailException;
use VuFind\Mailer\Mailer;
use Whoops\Exception\ErrorException;
use Zend\Config\Config;
use Zend\Mime\Message;
use Zend\Mime\Mime;
use Zend\Mime\Part;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;

class RequestController extends OriginalRecordController
{
    protected $departmentsConfig;
    protected $mainConfig;

    protected $inventory = array();

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm     Service manager
     * @param Config                  $config VuFind configuration
     */
    public function __construct(ServiceLocatorInterface $sm, Config $config)
    {
        // Call standard record controller initialization:
        parent::__construct($sm, $config);

        // Load default tab setting:
        $this->fallbackDefaultTab = isset($config->Site->defaultRecordTab)
            ? $config->Site->defaultRecordTab : 'Holdings';

        $this->mainConfig = $config;
        $this->departmentsConfig = $sm->get('VuFind\Config')->get('DepartmentsDAIA');
    }

    public function journalAction () {

        // Force login if necessary:
        if (!($user = $this->getUser())) {
            return $this->forceLogin();
        }

        $savePath = $this->mainConfig->JournalRequest->request_save_path;
        if (!file_exists($savePath) || !is_readable($savePath) || !is_writable($savePath)) {
            $this->addFlashMessage(false, 'File not writable: "' . $savePath . '"');
//            throw new IOException('File not writable: "' . $savePath . '"');
        }

        $formData = $this->getFormData();

        if ($this->getRequest()->isPost() && $this->validateFormData($formData)) {
            $fileName = $formData['username'] . '__' . date('Y_m_d__H_i_s') . '.pdf';
            $email = $this->getEmailForCallnumber($formData['callnumber']);

            if ($this->createPDF($formData, $fileName) &&
                    $this->sendRequestEmail($fileName, $email)) {
                $this->addFlashMessage(true, 'journal_request_succeeded');
            }
            else {
                $this->addFlashMessage(false, 'journal_request_failed');
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
            $archiveIds = array_keys($this->departmentsConfig->DepartmentEmails->toArray());
            $holdings = $this->loadRecord()->getRealTimeHoldings();
            foreach ($holdings['holdings'] as $location => $holding) {
                foreach ($holding['items'] as $item) {
                    if (!in_array($item['departmentId'], $archiveIds)) {
                        continue;
                    }

                    $this->inventory[$location . $item['callnumber']] = array(
                        'departmentId' => $item['departmentId'],
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

    protected function createPDF($formData, $filePath) {
        try {
            $savePath = $this->mainConfig->JournalRequest->request_save_path;

            $pdf = new JournalRequest($this->getViewRenderer()->plugin('translate'));

            $pdf->setCallNumber($formData['callnumber']);
            $pdf->setComment($formData['comment']);
            $pdf->setVolume($formData['volume']);
            $pdf->setEmail($formData['email']);
            $pdf->setName($formData['name']);
            $pdf->setUserName($formData['username']);
            $pdf->setWorkTitle($formData['title']);
            $pdf->setYear($formData['year']);

            $pdf->create();
            $pdf->Output('F', $savePath . $filePath);
//            $pdf->Output();
        }
        catch (ErrorException $e) {
            $this->addFlashMessage(false, 'journal_request_failed');
            $this->addFlashMessage(false, $e->getMessage());
            return false;
        }

        return true;
    }

    protected function sendRequestEmail($fileName, $email) {
        try {
            $savePath = $this->mainConfig->JournalRequest->request_save_path;

            // first create the parts
            $text = new Part();
            $text->type = Mime::TYPE_TEXT;
            $text->charset = 'utf-8';

            $fileContent = file_get_contents($savePath . $fileName, 'r');
            $attachment = new Part($fileContent);
            $attachment->type = 'application/pdf';
            $attachment->encoding = Mime::ENCODING_BASE64;
            $attachment->filename = $fileName;
            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

            // then add them to a MIME message
            $mimeMessage = new Message();
            $mimeMessage->setParts(array($text, $attachment));

            $mailer = $this->serviceLocator->get(Mailer::class);
            $mailer->send(
//                $email,
                'discovery_thulb@uni-jena.de',
                $this->mainConfig->Mail->default_from,
                'Neue Zeitschriftenanfrage',
                $mimeMessage
            );
        }
        catch (MailException $e) {
            return false;
        }

        return true;
    }

    protected function getEmailForCallnumber($callnumber) {
        foreach($this->getInventoryForRequest() as $archive) {
            if ($archive['callnumber'] == $callnumber) {
                if (isset($this->departmentsConfig->DepartmentEmails[$archive['departmentId']])) {
                    return $archive['departmentId'] . ' - ' . $this->departmentsConfig->DepartmentEmails[$archive['departmentId']];
                }
            }
        }

        return null;
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
