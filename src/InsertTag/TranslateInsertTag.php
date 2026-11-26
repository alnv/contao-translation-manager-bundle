<?php

namespace Alnv\ContaoTranslationManagerBundle\InsertTag;

use Alnv\ContaoTranslationManagerBundle\Library\Translation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;

#[AsInsertTag('translate')]
class TranslateInsertTag
{
    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $strKey = (string)$insertTag->getParameters()->get(0);
        $strFallback = (string)($insertTag->getParameters()->get(1) ?? '');
        
        return new InsertTagResult(Translation::getInstance()->translate($strKey, $strFallback), OutputType::html);
    }
}
