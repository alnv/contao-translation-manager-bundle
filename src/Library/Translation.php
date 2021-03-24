<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

class Translation extends CacheResolver {

    protected $strKey = 'name';
    protected $strValue = 'translation';
    protected $strTable = 'tl_translation';
    protected static $objInstance = null;

    protected function setModelOptions() {

        return ['column' => ['language=?', 'invisible!=?'] , 'value' => [$this->strLanguage, '1']];
    }

    public static function getInstance($strLanguage = '') {

        if (null === self::$objInstance) {

            self::$objInstance = new self($strLanguage);
        }

        return self::$objInstance;
    }

    public function translate($strKey, $strFallbackLabel='', $arrData = []) {

        $strTranslation = $this->get($strKey, $strFallbackLabel);
        $strTranslation = \Controller::replaceInsertTags($strTranslation);
        return \StringUtil::parseSimpleTokens($strTranslation, $arrData);
    }
}