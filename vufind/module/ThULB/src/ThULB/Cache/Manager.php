<?php

namespace ThULB\Cache;

use \VuFind\Cache\Manager as OriginalManager;

class Manager extends OriginalManager {
    /**
     * Create a downloader-specific file cache.
     *
     * @param string $downloaderName Name of the downloader.
     * @param array $opts Cache options.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function addDownloaderCache($downloaderName, $opts = []) : string {
        $cacheName = 'downloader-' . $downloaderName;

        $cacheDir = $this->getCacheDir();
        if($opts['sub_dir'] ?? false) {
            $cacheDir .= $opts['sub_dir'] . '/';
            unset($opts['sub_dir']);
        }

        $this->createFileCache($cacheName, $cacheDir, $opts);
        return $cacheName;
    }
}