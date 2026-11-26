<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoTranslationManagerBundle\Models\TranslationModel;
use Contao\Database;
use Contao\Model;
use Contao\StringUtil;
use Contao\System;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class CacheResolver
{
    public const CACHE_POOL = 'alnv.translation_manager.cache';

    public const PARAM_PLACEHOLDERS_ENABLED = 'alnv_translation_manager.placeholders.enabled';

    public const PARAM_PLACEHOLDERS_EXCLUDE = 'alnv_translation_manager.placeholders.exclude';

    protected string $strKey;

    protected string $strTable;

    protected string $strValue;

    protected string $strLanguage;

    protected static array $arrValues = [];

    protected static array $arrKnown = [];

    protected static ?array $arrPlaceholderConfig = null;

    protected static ?object $objScopeMatcher = null;

    public function __construct($strLanguage = '')
    {
        if (!$strLanguage) {
            $request = System::getContainer()->get('request_stack')->getCurrentRequest();
            $strLanguage = ($GLOBALS['TL_LANGUAGE'] ?? '') ?: ($request ? $request->getLocale() : 'de');
        }

        $this->strLanguage = (string)$strLanguage;
    }

    public static function invalidate(): void
    {
        static::$arrValues = [];
        static::$arrKnown = [];

        static::getCachePool()?->clear();
    }

    abstract protected function setModelOptions();

    protected function getEntities()
    {
        $strModel = Model::getClassFromTable($this->strTable);
        if ($strModel) {
            $objModel = new $strModel();
            return $objModel->findAll($this->setModelOptions());
        }

        $container = System::getContainer();
        if (in_array('AlnvContaoCatalogManagerBundle', array_keys($container->getParameter('kernel.bundles')), true)) {
            $objModel = new ModelWizard($this->strTable);
            $objModel = $objModel->getModel();

            return $objModel->findAll($this->setModelOptions());
        }

        return null;
    }

    public function get($strKey, $strFallback = '', bool $blnRegister = true)
    {
        $strKey = $this->getKeyname($strKey);
        if (!$strKey) {
            return $strFallback;
        }

        $arrValues = $this->getValues();

        if (\array_key_exists($strKey, $arrValues)) {
            return $arrValues[$strKey];
        }

        if (!$strFallback || !$blnRegister || !$this->isFrontendRequest() || !$this->shouldRegister($strKey)) {
            return $strFallback;
        }

        if (isset($this->getKnownKeys()[$strKey])) {
            return $strFallback;
        }

        if (!$this->keyExists($strKey)) {
            try {
                $this->createPlaceholder($strKey, (string)$strFallback);
            } catch (\Exception) {
                return $strFallback;
            }
        }

        $this->rememberKnownKey($strKey);

        return $strFallback;
    }

    protected function getValues(): array
    {
        if (isset(static::$arrValues[$this->strTable][$this->strLanguage])) {
            return static::$arrValues[$this->strTable][$this->strLanguage];
        }

        return static::$arrValues[$this->strTable][$this->strLanguage] = $this->remember(
            $this->getCacheKey('values', $this->strLanguage),
            function (): array {
                $arrValues = [];
                $objEntities = $this->getEntities();

                if ($objEntities) {
                    while ($objEntities->next()) {
                        if ($this->strLanguage !== $objEntities->language) {
                            continue;
                        }

                        $strKey = $this->getKeyname($objEntities->{$this->strKey});

                        // Bei doppelten Schlüsseln gewinnt der erste Datensatz
                        if (!$strKey || \array_key_exists($strKey, $arrValues)) {
                            continue;
                        }

                        $arrValues[$strKey] = StringUtil::decodeEntities($objEntities->{$this->strValue});
                    }
                }

                return $arrValues;
            }
        );
    }

    protected function getKnownKeys(): array
    {
        if (isset(static::$arrKnown[$this->strTable])) {
            return static::$arrKnown[$this->strTable];
        }

        return static::$arrKnown[$this->strTable] = $this->remember(
            $this->getCacheKey('known'),
            fn(): array => $this->loadKnownKeys()
        );
    }

    protected function loadKnownKeys(): array
    {
        $arrKnown = [];
        $objRows = Database::getInstance()
            ->prepare('SELECT DISTINCT ' . Database::quoteIdentifier($this->strKey) . ' FROM ' . $this->strTable)
            ->execute();

        while ($objRows->next()) {
            if ($strKey = $this->getKeyname($objRows->{$this->strKey})) {
                $arrKnown[$strKey] = true;
            }
        }

        return $arrKnown;
    }

    protected function keyExists(string $strKey): bool
    {
        return (bool)Database::getInstance()
            ->prepare('SELECT id FROM ' . $this->strTable . ' WHERE ' . Database::quoteIdentifier($this->strKey) . '=?')
            ->limit(1)
            ->execute($strKey)
            ->numRows;
    }

    protected function createPlaceholder(string $strKey, string $strFallback): void
    {
        $objTranslation = new TranslationModel();
        $objTranslation->tstamp = time();
        $objTranslation->invisible = '1';
        $objTranslation->name = substr($strKey, 0, 255);
        $objTranslation->translation = $strFallback;
        $objTranslation->save();
    }

    protected function rememberKnownKey(string $strKey): void
    {
        static::$arrKnown[$this->strTable][$strKey] = true;

        $objPool = static::getCachePool();
        if ($objPool === null) {
            return;
        }

        $objItem = $objPool->getItem($this->getCacheKey('known'));
        if (!$objItem->isHit()) {
            return;
        }

        $arrKnown = (array)$objItem->get();
        $arrKnown[$strKey] = true;
        $objPool->save($objItem->set($arrKnown));
    }

    protected function shouldRegister(string $strKey): bool
    {
        $arrConfig = static::getPlaceholderConfig();

        if (!$arrConfig['enabled']) {
            return false;
        }

        foreach ($arrConfig['exclude'] as $strPattern) {
            if (\preg_match($strPattern, $strKey)) {
                return false;
            }
        }

        return true;
    }

    protected function isFrontendRequest(): bool
    {
        $container = System::getContainer();
        if (self::$objScopeMatcher === null) {
            self::$objScopeMatcher = $container->get('contao.routing.scope_matcher');
        }

        return self::$objScopeMatcher->isFrontendRequest($container->get('request_stack')->getCurrentRequest() ?? Request::create(''));
    }

    protected function getKeyname($strName): string
    {
        return str_replace(["{", "}", "(", ")", "/", "\\", "@", ':', ' '], '', (string)$strName);
    }

    protected static function getPlaceholderConfig(): array
    {
        if (static::$arrPlaceholderConfig !== null) {
            return static::$arrPlaceholderConfig;
        }

        $container = System::getContainer();

        return static::$arrPlaceholderConfig = [
            'enabled' => !$container->hasParameter(self::PARAM_PLACEHOLDERS_ENABLED) || $container->getParameter(self::PARAM_PLACEHOLDERS_ENABLED),
            'exclude' => $container->hasParameter(self::PARAM_PLACEHOLDERS_EXCLUDE) ? array_values((array)$container->getParameter(self::PARAM_PLACEHOLDERS_EXCLUDE)) : [],
        ];
    }

    private function remember(string $strCacheKey, callable $callback): mixed
    {
        $objPool = static::getCachePool();

        if ($objPool === null) {
            return $callback();
        }

        $objItem = $objPool->getItem($strCacheKey);

        if ($objItem->isHit()) {
            return $objItem->get();
        }

        $varValue = $callback();
        $objPool->save($objItem->set($varValue));

        return $varValue;
    }

    private function getCacheKey(string $strType, string $strLanguage = ''): string
    {
        return (string)\preg_replace('/[^A-Za-z0-9_.-]/', '_', $strType . '.' . $this->strTable . ($strLanguage !== '' ? '.' . $strLanguage : ''));
    }

    private static function getCachePool(): ?CacheItemPoolInterface
    {
        $container = System::getContainer();

        return $container->has(self::CACHE_POOL) ? $container->get(self::CACHE_POOL) : null;
    }
}
