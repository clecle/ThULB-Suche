<?php

namespace ThULB\Recommend;

use VuFind\Recommend\RecommendLinks as OriginalRecommendLinks;

class RecommendLinks extends OriginalRecommendLinks
{
    protected ?string $lookfor = null;

    public function init($params, $request) : void {
        $this->lookfor = $request->get('lookfor');
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * RecommendLinks:[ini section]:[ini name]
     *     Display a list of recommended links, taken from [ini section] in
     *     [ini name], where the section is a mapping of label => URL or
     *     label => [sub ini section]. [ini name] defaults to searches.ini,
     *     and [ini section] defaults to RecommendLinks.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        $settings = explode(':', $settings);
        $mainSection = empty($settings[0]) ? 'RecommendLinks' : $settings[0];
        $iniName = $settings[1] ?? 'searches';
        $config = $this->configLoader->get($iniName);

        $mainSectionConfig = isset($config->$mainSection) ? $config->$mainSection->toArray() : [];
        foreach($mainSectionConfig as $desc => $item) {
            $this->links[$desc] = isset($config->$item) ? $config->$item->toArray() : $item;
        }
    }

    public function getLinks() {
        return array_map([$this, 'replaceToken'], $this->links);
    }

    protected function replaceToken($link) : array|string {
        if(is_array($link)) {
            return array_map([$this, 'replaceToken'], $link);
        }
        return str_replace('%%query_lookfor%%', $this->lookfor, $link);
    }
}
