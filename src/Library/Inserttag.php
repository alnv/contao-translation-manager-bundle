<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

class Inserttag
{

    public function replace($strFragment)
    {

        $arrFragments = explode('::', $strFragment);

        if (is_array($arrFragments) && $arrFragments[0] == 'TRANSLATE' && isset($arrFragments[1])) {
            return Translation::getInstance()->translate($arrFragments[1], ($arrFragments[2] ?: ''));
        }

        return false;
    }
}