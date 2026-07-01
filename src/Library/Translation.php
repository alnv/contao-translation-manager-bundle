<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

use Contao\System;

class Translation extends CacheResolver
{
    protected string $strKey = 'name';
    protected string $strValue = 'translation';
    protected string $strTable = 'tl_translation';

    protected static array $arrInstances = [];

    protected static ?object $insertTagParser = null;

    protected static ?object $tokenParser = null;

    protected function setModelOptions(): array
    {
        return ['column' => ['language=? AND (invisible IS NULL OR invisible="")'], 'value' => [$this->strLanguage]];
    }

    public static function getInstance($strLanguage = ''): Translation
    {
        if (empty($strLanguage)) {
            $strLanguage = $GLOBALS['TL_LANGUAGE'] ?? 'de';
        }

        if (!isset(self::$arrInstances[$strLanguage])) {
            self::$arrInstances[$strLanguage] = new self($strLanguage);
        }

        return self::$arrInstances[$strLanguage];
    }

    public function translate($strKey, $strFallbackLabel = '', $arrData = []): string
    {
        $strTranslation = $this->get($strKey, $strFallbackLabel);

        if ($strTranslation === '' || $strTranslation === null) {
            return '';
        }

        $strTranslation = (string)$strTranslation;

        if (str_contains($strTranslation, '{{') && str_contains($strTranslation, '}}')) {
            if (self::$insertTagParser === null) {
                self::$insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
            }
            $strTranslation = self::$insertTagParser->replaceInline($strTranslation);
        }

        if (!empty($arrData) && str_contains($strTranslation, '##')) {
            if (self::$tokenParser === null) {
                self::$tokenParser = System::getContainer()->get('contao.string.simple_token_parser');
            }
            return self::$tokenParser->parse($strTranslation, $arrData);
        }

        return $strTranslation;
    }
}