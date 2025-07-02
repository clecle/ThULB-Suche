<?php

namespace ThULB\View\Helper\Root;

use VuFind\View\Helper\Root\CookieConsent as OriginalCookieConsent;

class CookieConsent extends OriginalCookieConsent {

    protected function getConsentDialogConfig(): array {
        $config = parent::getConsentDialogConfig();

        $config['guiOptions'] = [
            'consentModal' => [
                'layout' => 'bar inline',
                'position' => 'bottom center',
                'equalWeightButtons' => false,
                'transition' => 'slide',
            ],
            'preferencesModal' => [
                'layout' => 'box',
                'transition' => 'none',
            ],
        ];

        return $config;
    }
}