<?php

namespace EAH\Controller;

use ThULB\Controller\RequestController as OriginalRecordController;
use Whoops\Exception\ErrorException;

class RequestController extends OriginalRecordController
{
    /**
     * Create the pdf for the request and save it.
     *
     * @param array $formData Data to create pdf with.
     * @param string $fileName Name for the pdf to vbe saved as.
     *
     * @return bool Success of the pdf creation.
     */
    protected function createPDF(array $formData, string $fileName) : bool {
        try {
            $savePath = $this->thulbConfig->JournalRequest->request_save_path ?? false;

            $pdf = $this->serviceLocator
                ->get(\ThULB\PDF\PluginManager::class)
                ->get(\EAH\PDF\JournalRequest::class);

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

            $pdf->setLocation($this->getInventoryForRequest()[$formData['item']]['location']);

            $pdf->create();
            $pdf->Output('F', $savePath . $fileName);
        }
        catch (ErrorException $e) {
            $this->logException($e);

            return false;
        }

        return true;
    }
}
