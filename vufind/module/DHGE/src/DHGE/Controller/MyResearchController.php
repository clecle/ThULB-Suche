<?php

namespace DHGE\Controller;

use ThULB\Controller\MyResearchController as OriginalController;

class MyResearchController extends OriginalController {

    /**
     * Provide a link to the password change site of the ILS.
     *
     * @return mixed
     */
    public function changePasswordLinkAction() {
        $view =  parent::changePasswordLinkAction();
        $view->setVariable('library', $this->getAuthManager()->getUserLibrary());
        return $view;
    }
}