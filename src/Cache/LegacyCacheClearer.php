<?php

namespace Alnv\ContaoTranslationManagerBundle\Cache;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

class LegacyCacheClearer implements CacheClearerInterface
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function clear(string $cacheDir): void
    {
        $arrDirs = glob($this->projectDir . '/var/cache/cm.translation.cache.*', GLOB_ONLYDIR) ?: [];

        if ($arrDirs) {
            (new Filesystem())->remove($arrDirs);
        }
    }
}
