<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ThULB\Controller;

use VuFind\Controller\AjaxController as OriginalAjaxController;

/**
 * Description of AjaxController
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class AjaxController extends OriginalAjaxController {
    
    public function getResultCountAjax()
    {
        $index = $this->params()->fromPost('index', $this->params()->fromQuery('index'));
        $lookFor = $this->params()->fromPost('lookfor', $this->params()->fromQuery('lookfor'));
        $type = $this->params()->fromPost('type', $this->params()->fromQuery('type'));
       
        $runner = $this->getServiceLocator()->get('VuFind\SearchRunner');
        $result = $runner->run(['limit' => '0', 'type' => $type, 'lookfor' => $lookFor], $index);
        
        $numberFormatter = $this->getViewRenderer()->plugin('localizedNumber');
        
        return $this->output(['count' => $numberFormatter($result->getResultTotal())], self::STATUS_OK);
    }
}
