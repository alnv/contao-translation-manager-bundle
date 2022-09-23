<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

use Contao\System;

class Translation extends CacheResolver {

    protected $strKey = 'name';
    protected $strValue = 'translation';
    protected $strTable = 'tl_translation';
    protected static $objInstance = null;

    protected function setModelOptions() {

        return ['column' => ['language=? AND (invisible IS NULL OR invisible="")'] , 'value' => [$this->strLanguage]];
    }

    public static function getInstance($strLanguage='') {

        if (null === self::$objInstance) {

            self::$objInstance = new self($strLanguage);
        }

        return self::$objInstance;
    }

    public function translate($strKey, $strFallbackLabel='', $arrData = []) {

        $objParser = System::getContainer()->get('contao.insert_tag.parser');

        $strTranslation = $this->get($strKey, $strFallbackLabel);
        $strTranslation = $objParser->replaceInline((string) $strTranslation);

        return System::getContainer()->get('contao.string.simple_token_parser')->parse($strTranslation, $arrData, true);
    }
}