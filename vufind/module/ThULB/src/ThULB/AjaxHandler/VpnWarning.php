<?php

namespace ThULB\AjaxHandler;

use VuFind\AjaxHandler\AbstractBase;
use VuFind\Auth\Manager;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Role\PermissionDeniedManager;
use VuFind\Role\PermissionManager;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;

class VpnWarning extends AbstractBase
    implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    private $permissionManager;
    private $permissionDeniedManager;
    private $authManager;
    private $renderer;

    /**
     * Constructor
     *
     * @param PermissionManager       $pm   Permission Manager
     * @param PermissionDeniedManager $pdm  Permission Denied Manager
     * @param Manager                 $auth Auth manager
     * @param RendererInterface       $renderer View renderer
     */
    public function __construct(PermissionManager $pm, PermissionDeniedManager $pdm,
                                Manager $auth, RendererInterface $renderer
    ) {
        $this->permissionManager = $pm;
        $this->permissionDeniedManager = $pdm;
        $this->authManager = $auth;
        $this->renderer = $renderer;
    }

    /**
     * Writes a timestamp to the session, when the message should be shown again
     *
     * @param Params $params
     *
     * @return array
     */
    public function handleRequest(Params $params) {
        $hide = $this->permissionManager->isAuthorized('hide.VpnWarning');

        $html = '';
        if(!$hide) {
            $html = $this->renderer->render('Helpers/vpn-hint');
        }

        return $this->formatResponse([
            'html' => $html
        ]);
    }
}