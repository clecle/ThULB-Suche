<?php

namespace ThULB\ILS;

use Laminas\Config\Config;

trait LibraryTrait
{
    private int|array $iln = 0;

    abstract protected function getThulbConfig(): Config;

    protected function getLibraryILN(): int|array {
        if($this->iln == 0) {
            $config = $this->getThulbConfig();

            $iln = $config->Library->ILN ?? 0;
            $this->iln = !is_numeric($iln) ? $iln->toArray() : $iln;
        }

        return $this->iln;
    }
}