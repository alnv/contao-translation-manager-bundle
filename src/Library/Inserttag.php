<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

class Inserttag
{

    public function replace($strFragment)
    {

        $arrFragments = explode('::', (string)$strFragment);
        
        if (strtoupper($arrFragments[0]) === 'TRANSLATE' && isset($arrFragments[1])) {
            return Translation::getInstance()->translate($arrFragments[1], ($arrFragments[2] ?? ''));
        }

        return false;
    }
}