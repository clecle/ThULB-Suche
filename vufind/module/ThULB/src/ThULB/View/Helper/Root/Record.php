<?php
/**
 * Record driver view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki 
 */

namespace ThULB\View\Helper\Root;
use Exception;
use Laminas\Config\Config;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\ResolverInterface;
use ThULB\RecordTab\NonArticleCollectionList;
use VuFind\RecordDriver\SolrDefault;
use VuFind\RecordTab\TabInterface;
use VuFind\Tags\TagsService;
use VuFind\View\Helper\Root\Record as OriginalRecord;
use Laminas\View\Exception\RuntimeException;

/**
 * Description of Record
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @author Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 */
class Record extends OriginalRecord
{
    protected NonArticleCollectionList $nonArticleCollection;

    /**
     * Constructor
     *
     * @param Config|null                   $config VuFind configuration
     * @param NonArticleCollectionList|null $nonArticleCollection
     */
    public function __construct(protected TagsService $tagsService, $config = null,
                                NonArticleCollectionList $nonArticleCollection = null)
    {
        parent::__construct($tagsService, $config);
        $this->nonArticleCollection = $nonArticleCollection;
    }

    /**
     * Get HTML to render a title. Maximum length limitation is not applied
     * anymore - it happens in javascript code.
     *
     * @param int $maxLength Maximum length of non-highlighted title.
     *
     * @return string
     */
    public function getTitleHtml($maxLength = 180) : string
    {
        $highlightedTitle = $this->driver->tryMethod('getHighlightedTitle');
        $title = trim($this->driver->tryMethod('getTitle'));
        
        
        if (!empty($highlightedTitle)) {
            $highlight = $this->getView()->plugin('highlight');
            return $highlight($highlightedTitle);
        }
        
        if (!empty($title)) {
            $escapeHtml = $this->getView()->plugin('escapeHtml');
            return $escapeHtml($title);
        }
        
        $transEsc = $this->getView()->plugin('transEsc');
        return $transEsc('Title not available');
    }

    /**
     * Render a list of record formats.
     *
     * @return string
     */
    public function getCitationReferences() : string
    {
        return $this->renderTemplate('citation-references.phtml');
    }
    
    /**
     * Is this Record OpenAccess?
     *
     * @return string
     */
    public function getOpenAccess() : string
    {
        return $this->renderTemplate('isopenaccess.phtml');
    }

    /**
     * Is this Record part of the thuringia bibliography?
     *
     * @return string
     */
    public function getThuringiaBibliography() : string
    {
        return $this->renderTemplate('isThuBibliography.phtml');
    }

    /**
     * Recursively locate and render a template that matches the provided class
     * name (or one of its parent classes); throw an exception if no match is
     * found.
     *
     * @param string            $template     Template path (with %s as class name placeholder)
     * @param string            $className    Name of class to apply to template.
     * @param ResolverInterface $resolver     Resolver to use
     * @param string            $topClassName Top-level parent class of $className (or null if
     *                                        $className is already the top level; used for recursion only).
     *
     * @return string
     * @throws RuntimeException
     */
    protected function resolveClassTemplate($template, $className, ResolverInterface $resolver,
                                            $topClassName = null
    ) : string {
        // If the template resolves, return it:
        $templateWithClass = $this->getTemplateWithClass($template, $className);
        if ($resolver->resolve($templateWithClass)) {
            return $templateWithClass;
        }

        // If the template doesn't resolve, let's see if we can inherit a
        // template from a parent class:
        $parentClass = get_parent_class($className);

        /*
         * Skip VuFind\RecordDriver\SolrDefault to use same base template for solr and summon
         */
        if($parentClass === SolrDefault::class) {
            $parentClass = get_parent_class($parentClass);
        }

        if (empty($parentClass)) {
            return '';
        }

        // Recurse until we find a template or run out of parents...
        return $this->resolveClassTemplate(
            $template,
            $parentClass,
            $resolver,
            $topClassName ?? $className
        );
    }

    /**
     * Get the detail information of the given author.
     *
     * @param string $author
     *
     * @return string
     */
    public function getAuthorDetails(string $author) : string {
        foreach ($this->driver->getDeduplicatedAuthors() as $type):
            if(isset($type[$author])):
                return isset($type[$author]['detail']) ? $type[$author]['detail'][0] : '';
            endif;
        endforeach;

        return '';
    }

    /**
     * Render the contents of the specified record tab.
     *
     * @param TabInterface $tab Tab to display
     *
     * @return string
     */
    public function getTab(TabInterface $tab) : string
    {
        $context = ['driver' => $this->driver, 'tab' => $tab];
        $classParts = explode('\\', get_class($tab));
        $template = 'RecordTab/' . strtolower(array_pop($classParts)) . '.phtml';
        $oldContext = $this->contextHelper->apply($context);
        $html = $this->view->render($template);
        $this->contextHelper->restore($oldContext);
        return trim($html);
    }

    /**
     * Checks if the record has related non-articles and the respective tab will be shown.
     *
     * @param PhpRenderer $renderer
     *
     * @return bool
     *
     * @throws Exception
     */
    public function hasNonArticleTab(PhpRenderer $renderer) : bool {
        // journal request feature activated and order link shown?
        if($renderer->permission()->allowDisplay('access.JournalRequest') &&
                $this->driver->isFormat('Journal') && $this->driver->isInArchive()) {
            return true;
        }

        // has non-articles?
        if($this->nonArticleCollection == null) {
            return false;
        }
        return $this->nonArticleCollection->getResults()->getResultTotal() > 0;
    }

    /**
     * Get all the links associated with this record. Returns an array of
     * associative arrays each containing 'desc' and 'url' keys.
     *
     * @param bool $openUrlActive Is there an active OpenURL on the page?
     *
     * @return array
     *
     * @throws Exception
     */
    public function getLinkDetails($openUrlActive = false): array {
        $linkDetails = parent::getLinkDetails($openUrlActive);
        foreach ($linkDetails as &$link) {
            $link['url'] = preg_replace('/^(http:)\/\/(d-nb\.info|swbplus\.bsz-bw\.de|www\.gbv\.de)/', 'https://$2', $link['url']);
        }

        return $linkDetails;
    }
}
