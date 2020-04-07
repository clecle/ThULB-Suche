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

    /**
     * Action for placing a journal request.
     *
     * @return ViewModel
     */
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
                $this->addFlashMessage(true, 'storage_retrieval_request_journal_succeeded');
            }
            else {
                $this->addFlashMessage(false, 'storage_retrieval_request_journal_failed');
            }
        }

        return new ViewModel([
            'formData' => $formData,
            'recordId' => $this->loadRecord()->getUniqueID(),
            'inventory' => $this->getInventoryForRequest()
        ]);
    }

    /**
     * Get data array of values from the request or default values.
     *
     * @return array
     */
    protected function getFormData() {
        $params = $this->params();
        $user = $this->getUser();
        $inventory = $this->getInventoryForRequest();
        $defaultCallnumber = count($inventory) == 1 ? array_shift($inventory)['callnumber'] : '';

        return array (
            'name'       => $params->fromPost('name', $user['firstname'] . ' ' . $user['lastname']),
            'username'   => $params->fromPost('username', $user['cat_id']),
            'title'      => $params->fromPost('title', $this->loadRecord()->getTitle()),
            'callnumber' => $params->fromPost('callnumber', $defaultCallnumber),
            'year'       => $params->fromPost('year', ''),
            'volume'     => $params->fromPost('volume', ''),
            'issue'      => $params->fromPost('issue', ''),
            'pages'      => $params->fromPost('pages', ''),
            'comment'    => $params->fromPost('comment', '')
        );
    }

    /**
     * Get the items available in journal request form.
     * Performs a DAIA-Request for the current record and returns a filtered list.
     *
     * Return format:
     *     array (
     *         array (
     *             'departmentId' => ...,
     *             'callnumber' => ...,
     *             'location' => ...,
     *             'chronology' => ...
     *         ),
     *         ...
     *     )
     *
     * @return array Array of available items.
     */
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

    /**
     * Create the pdf for the request and save it.
     *
     * @param array $formData Data to create pdf with.
     * @param string $fileName Name for the pdf to vbe saved as.
     *
     * @return bool Success of the pdf creation.
     */
    protected function createPDF($formData, $fileName) {
        try {
            $savePath = $this->mainConfig->JournalRequest->request_save_path;

            $pdf = new JournalRequest($this->getViewRenderer()->plugin('translate'));

            $pdf->setCallNumber($formData['callnumber']);
            $pdf->setComment($formData['comment']);
            $pdf->setVolume($formData['volume']);
            $pdf->setIssue($formData['issue']);
            $pdf->setPages($formData['pages']);
            $pdf->setName($formData['name']);
            $pdf->setUserName($formData['username']);
            $pdf->setWorkTitle($formData['title']);
            $pdf->setYear($formData['year']);

            $pdf->create();
            $pdf->Output('F', $savePath . $fileName);
//            $pdf->Output();
        }
        catch (ErrorException $e) {
            $this->addFlashMessage(false, 'storage_retrieval_request_journal_failed');
            $this->addFlashMessage(false, $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Send the request email.
     *
     * @param string $fileName Name of the file to be attached to the email
     * @param string $recipient Recipient of the email.
     *
     * @return bool Success of sending the email.
     */
    protected function sendRequestEmail($fileName, $recipient) {
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
//                $recipient,
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

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
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

    /**
     * Validate form data.
     *
     * @param $formData
     *
     * @return bool
     */
    protected function validateFormData($formData) {
        $error = false;
        if(empty($formData['callnumber'])) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_field_empty', ['%%field%%' => 'storage_retrieval_request_select_location']
            );
            $error = true;
        }
        if(empty($formData['year']) && empty($formData['comment'])) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_fields_empty', ['%%field1%%' => 'storage_retrieval_request_year', '%%field2%%' => 'Note']
            );
            $error = true;
        }
        return !$error;
    }

    /**
     * Adds a flash message.
     *
     * @param bool $success Type of flash message. TRUE for success message, FALSE for error message.
     * @param string $messageKey Key of the message to translate.
     * @param array $messageFields Additional fields to translate and insert into the message.
     */
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
