<?php

namespace ThULB\View\Helper\Root;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;
use ThULB\Content\GND\lobid;

class NormLink extends AbstractHelper {
    protected array $sources = [];

    public function __construct(protected Config $thulbConfig, protected lobid $lobid) {
        $this->sources = $this->thulbConfig->Normlink?->sources?->toArray() ?? ['DNB', 'dewiki', 'enwiki', 'DDB'];
    }

    public function getLinksForGND(string $gnd) : array {
        $links = [];
        if($gnd) {
            $links['gnd-explorer'] = [
                'name' => 'GND Explorer',
                'icon' => $this->view->imageLink('logo_gnd_explorer.svg'),
                'url' => 'https://explore.gnd.network/gnd/' .  $gnd
            ];

            $links['lobid'] = [
                'name' => 'lobid',
                'icon' => $this->view->imageLink('logo_lobid.png'),
                'url' => 'https://lobid.org/gnd/' . $gnd
            ];

            $links = array_merge($links, $this->lobid->getSameAs($gnd, $this->sources));
        }

        return $links;
    }

    public function isEnabled() : bool {
        return $this->thulbConfig->Normlink?->enabled ?? false;
    }
}