<?php

namespace ThULB\AjaxHandler;

use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Search\SearchRunner;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;

class GetResultCount extends AbstractBase
    implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected $runner;
    protected $viewRenderer;

    public function __construct(SearchRunner $runner, RendererInterface $viewRenderer)
    {
        $this->runner = $runner;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        $index = $params->fromPost('index', $params->fromQuery('index'));
        $lookFor = $params->fromPost('lookfor', $params->fromQuery('lookfor'));
        $type = $params->fromPost('type', $params->fromQuery('type'));

        $result = $this->runner->run(['limit' => '0', 'type' => $type, 'lookfor' => $lookFor], $index);

        return $this->formatResponse(['count' => $this->viewRenderer->localizedNumber($result->getResultTotal())]);
    }
}