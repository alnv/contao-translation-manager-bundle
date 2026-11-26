<?php

namespace Alnv\ContaoTranslationManagerBundle\EventListener;

use Alnv\ContaoTranslationManagerBundle\Library\CacheResolver;
use Alnv\ContaoTranslationManagerBundle\Models\TranslationModel;
use Contao\CoreBundle\Event\InvalidateCacheTagsEvent;

class TranslationCacheListener
{
    private bool $blnInvalidated = false;

    public function onInvalidateCacheTags(InvalidateCacheTagsEvent $event): void
    {
        $strPattern = '/^contao\.db\.' . preg_quote(TranslationModel::getTable(), '/') . '(\.\d+)?$/';

        foreach ($event->getTags() as $strTag) {
            if (preg_match($strPattern, $strTag)) {
                $this->invalidate();

                return;
            }
        }
    }
    
    public function onUndo(): void
    {
        $this->invalidate();
    }

    public function onKernelTerminate(): void
    {
        if ($this->blnInvalidated) {
            CacheResolver::invalidate();
            $this->blnInvalidated = false;
        }
    }

    private function invalidate(): void
    {
        CacheResolver::invalidate();
        $this->blnInvalidated = true;
    }
}
