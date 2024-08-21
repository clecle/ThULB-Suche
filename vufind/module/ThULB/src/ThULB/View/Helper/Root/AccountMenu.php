<?php

namespace ThULB\View\Helper\Root
;
use \VuFind\View\Helper\Root\AccountMenu as OriginalAccountMenu;

class AccountMenu extends OriginalAccountMenu {
    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkProvidedItems(): bool {
        return $this->checkIlsCapability('getMyProvidedItems');
    }

    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkHoldsAndSSR(): bool {
        return $this->checkIlsCapability('getMyHoldsAndSRR');
    }

    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkProfile(): bool {
        return $this->checkIlsCapability('getMyProfile');
    }

    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkChangePassword(): bool {
        return $this->getAuthHelper()->getUserObject()
            && $this->getAuthHelper()->getManager()->supportsPasswordChange();
    }

    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkChangePasswordLink(): bool {
        return !$this->checkChangePassword();
    }

    /**
     * Check whether to show fines item
     *
     * @return bool
     */
    public function checkLetterOfAuthorization(): bool {
        return $this->getView()->plugin('config')->get('thulb')->LetterOfAuthorization->enabled ?? false;
    }
}