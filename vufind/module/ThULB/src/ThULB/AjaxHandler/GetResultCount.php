<?php

namespace ThULB\AjaxHandler;

use VuFind\AjaxHandler\AbstractBase;
use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\I18n\Translator\TranslatorAwareTrait;
use VuFind\Search\SearchRunner;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;

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
    public function handleRequest(Params $params) : array
    {
        $index = $params->fromPost('index', $params->fromQuery('index'));
        $lookFor = $params->fromPost('lookfor', $params->fromQuery('lookfor'));
        $type = $params->fromPost('type', $params->fromQuery('type'));

        $result = $this->runner->run(['limit' => '0', 'type' => $type, 'lookfor' => $lookFor], $index);

        return $this->formatResponse(['count' => $this->viewRenderer->localizedNumber(
            $result->getResultTotal() > 0 ? $result->getResultTotal() : 0
        )]);
    }
}