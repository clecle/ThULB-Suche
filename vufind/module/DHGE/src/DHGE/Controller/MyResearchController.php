<?php

namespace DHGE\Controller;

use Laminas\View\Model\ViewModel;
use ThULB\Controller\MyResearchController as OriginalController;

class MyResearchController extends OriginalController {

    /**
     * Provide a link to the password change site of the ILS.
     *
     * @return ViewModel
     */
    public function changePasswordLinkAction() : ViewModel {
        $view =  parent::changePasswordLinkAction();
        $view->setVariable('library', $this->getAuthManager()->getUserLibrary());
        return $view;
    }
}