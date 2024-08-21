<?php

namespace ThULB\View\Helper\Root;

use Laminas\View\Helper\AbstractHelper;
use ThULB\Content\GND\lobid;

class NormLink extends AbstractHelper {
    protected array $wikipediaGndList = array();

    protected lobid $lobid;

    public function __construct(lobid $lobid) {
        $this->lobid = $lobid;
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

            $links = array_merge($links, $this->lobid->getSameAs($gnd));
        }

        return $links;
    }
}