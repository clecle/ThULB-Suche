<?php

namespace ThULB\Controller;

use IOException;
use ThULB\PDF\JournalRequest;
use VuFind\Controller\RecordController as OriginalRecordController;
use VuFind\Exception\Mail as MailException;
use VuFind\Log\LoggerAwareTrait;
use VuFind\Mailer\Mailer;
use Whoops\Exception\ErrorException;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface;
use Laminas\Mime\Message;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Model\ViewModel;

class RequestController extends OriginalRecordController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ChangePasswordTrait;

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

        $this->mainConfig = $config;
        $this->departmentsConfig = $sm->get('VuFind\Config')->get('DepartmentsDAIA');
        $this->setLogger($sm->get('VuFind\Logger'));

        $this->accessPermission = "access.JournalRequest";
    }

    /**
     * Action for placing a journal request.
     *
     * @return ViewModel
     *
     * @throws IOException
     */
    public function journalAction () {

        // Force login if necessary:
        if (!$this->getUser()) {
            return $this->forceLogin();
        }

        // Block invalid requests:
        $validRequest = $this->getILS()->checkStorageRetrievalRequestIsValid(
            $this->loadRecord()->getUniqueID(), [], $this->catalogLogin()
        );
        if ((is_array($validRequest) && !$validRequest['valid']) || !$validRequest) {
            $this->flashMessenger()->addErrorMessage(
                is_array($validRequest)
                    ? $validRequest['status']
                    : 'storage_retrieval_request_error_blocked'
            );
            return;
        }

        $savePath = $this->mainConfig->JournalRequest->request_save_path;
        if ((!file_exists($savePath) && !mkdir($savePath)) || !is_readable($savePath) || !is_writable($savePath)) {
            throw new IOException('File not writable: "' . $savePath . '"');
        }

        $formData = $this->getFormData();

        if ($this->getRequest()->isPost() && $this->validateFormData($formData)) {
            $fileName = $formData['username'] . '__' . date('Y_m_d__H_i_s') . '.pdf';
            $callNumber = $this->getInventoryForRequest()[$formData['item']]['callnumber'];
            $email = $this->getEmailForCallnumber($callNumber);
            $borrowCounter = $this->getBorrowCounterForCallnumber($callNumber);
            $locationUrl = $this->getLocationUrlForCallnumber($callNumber);

            if ($this->createPDF($formData, $fileName) &&
                    $this->sendRequestEmail($fileName, $email)) {
                $this->addFlashMessage(true, 'storage_retrieval_request_journal_succeeded',
                    ['%%location%%' => $borrowCounter, '%%url%%' => $locationUrl]);
            }
            else {
                $this->addFlashMessage(false, 'storage_retrieval_request_journal_failed');
            }
        }

        return new ViewModel([
            'allowed' => true,
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
        $defaultItem = count($inventory) == 1 ? array_key_first($inventory) : '';

        return array (
            'firstname'  => $params->fromPost('firstname', $user['firstname']),
            'lastname'   => $params->fromPost('lastname', $user['lastname']),
            'username'   => $params->fromPost('username', $user['cat_id']),
            'title'      => $params->fromPost('title', $this->loadRecord()->getTitle()),
            'year'       => $params->fromPost('year', ''),
            'volume'     => $params->fromPost('volume', ''),
            'issue'      => $params->fromPost('issue', ''),
            'pages'      => $params->fromPost('pages', ''),
            'comment'    => $params->fromPost('comment', ''),
            'item'       => $params->fromPost('item', $defaultItem),
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
                foreach ($holding['items'] as $index => $item) {
                    if (!in_array($item['departmentId'], $archiveIds)) {
                        continue;
                    }

                    $key = sprintf("%s_%s_%02d", $location, $item['callnumber'], $index);
                    $this->inventory[$key] = array(
                        'departmentId' => $item['departmentId'],
                        'callnumber'   => $item['callnumber'],
                        'location'     => $location,
                        'chronology'   => !empty($item['chronology_about']) ? $item['chronology_about'] : $item['about']
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

            $pdf->setCallNumber($this->getInventoryForRequest()[$formData['item']]['callnumber']);
            $pdf->setComment($formData['comment']);
            $pdf->setVolume($formData['volume']);
            $pdf->setIssue($formData['issue']);
            $pdf->setPages($formData['pages']);
            $pdf->setFirstName($formData['firstname']);
            $pdf->setLastName($formData['lastname']);
            $pdf->setUserName($formData['username']);
            $pdf->setWorkTitle($formData['title']);
            $pdf->setYear($formData['year']);

            $pdf->create();
            $pdf->Output('F', $savePath . $fileName);
        }
        catch (ErrorException $e) {
            $this->addFlashMessage(false, 'storage_retrieval_request_journal_failed');

            if($this->logger != null && is_callable($this->logger, 'logException')) {
                $this->logger->logException($e, $this->getEvent()->getRequest()->getServer());
            }

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
                $recipient,
                $this->mainConfig->Mail->default_from,
                $this->translate('storage_retrieval_request_email_subject'),
                $mimeMessage
            );
        }
        catch (MailException $e) {
            if($this->logger != null && is_callable($this->logger, 'logException')) {
                $this->logger->logException($e, $this->getEvent()->getRequest()->getServer());
            }

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
        if(APPLICATION_ENV == 'development' || APPLICATION_ENV == 'testing') {
            return $this->departmentsConfig->JournalRequestTest->email;
        }

        foreach($this->getInventoryForRequest() as $archive) {
            if ($archive['callnumber'] == $callnumber) {
                if (isset($this->departmentsConfig->DepartmentEmails[$archive['departmentId']])) {
                    return $this->departmentsConfig->DepartmentEmails[$archive['departmentId']];
                }
            }
        }

        return null;
    }

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
    protected function getBorrowCounterForCallnumber($callnumber) {
        foreach($this->getInventoryForRequest() as $archive) {
            if ($archive['callnumber'] == $callnumber) {
                if (isset($this->departmentsConfig->DepartmentBorrowCounter[$archive['departmentId']])) {
                    return $this->departmentsConfig->DepartmentBorrowCounter[$archive['departmentId']];
                }
            }
        }

        return null;
    }

    /**
     * Gets the configured email for the given email.
     *
     * @param string $callnumber
     *
     * @return string|null
     */
    protected function getLocationUrlForCallnumber($callnumber) {
        foreach($this->getInventoryForRequest() as $archive) {
            if ($archive['callnumber'] == $callnumber) {
                if (isset($this->departmentsConfig->DepartmentLocationUrl[$archive['departmentId']])) {
                    return $this->departmentsConfig->DepartmentLocationUrl[$archive['departmentId']];
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
        if(!array_key_exists($formData['item'] ?? null, $this->getInventoryForRequest())) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_field_empty',
                ['%%field%%' => 'storage_retrieval_request_select_location']
            );
            $error = true;
        }
        if(empty($formData['year']) && empty($formData['comment'])) {
            $this->addFlashMessage(
                false, 'storage_retrieval_request_error_fields_empty',
                ['%%field1%%' => 'storage_retrieval_request_year', '%%field2%%' => 'Note']
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
        foreach ($messageFields as $field => $message) {
            $messageFields[$field] = $this->translate($message);
        }

        $this->flashMessenger()->addMessage(
            array (
                'html' => true,
                'msg' => $messageKey,
                'tokens' => $messageFields
            ),
            $success ? 'success' : 'error'
        );
    }
}
