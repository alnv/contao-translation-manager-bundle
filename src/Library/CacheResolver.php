<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

use Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard;
use Alnv\ContaoTranslationManagerBundle\Models\TranslationModel;
use Contao\Database;
use Contao\Model;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

abstract class CacheResolver
{
    protected string $strKey;

    protected string $strTable;

    protected string $strValue;

    protected string $strLanguage;
    protected FilesystemAdapter $objCache;

    protected static array $arrInitializedLanguages = [];

    protected static ?object $objScopeMatcher = null;

    public function __construct($strLanguage = '')
    {
        $container = System::getContainer();

        if (!$strLanguage) {
            $request = $container->get('request_stack')->getCurrentRequest();
            $strLanguage = $GLOBALS['TL_LANGUAGE'] ?: ($request ? $request->getLocale() : 'de');
        }

        $strRootDir = $container->getParameter('kernel.project_dir');
        $this->objCache = new FilesystemAdapter('cm.translation.cache.' . $strLanguage, 60, $strRootDir . '/var/cache');
        $this->strLanguage = $strLanguage;

        if (!isset(self::$arrInitializedLanguages[$strLanguage])) {
            $this->setDataIntoCache();
            self::$arrInitializedLanguages[$strLanguage] = true;
        }
    }

    protected function setDataIntoCache(): void
    {
        $objEntities = $this->getEntities();

        if ($objEntities) {
            while ($objEntities->next()) {
                if ($this->strLanguage !== $objEntities->language) {
                    continue;
                }

                $strKey = $this->getKeyname($objEntities->{$this->strKey});
                if (!$strKey) {
                    continue;
                }

                $objCacheEntity = $this->objCache->getItem($strKey);

                if (!$objCacheEntity->isHit()) {
                    $strValue = StringUtil::decodeEntities($objEntities->{$this->strValue});
                    $objCacheEntity->set($strValue);
                    $this->objCache->saveDeferred($objCacheEntity);
                }
            }
        }

        $objEmpty = Database::getInstance()->prepare('SELECT * FROM ' . $this->strTable . ' WHERE invisible=?')->execute(1);

        if ($objEmpty) {
            while ($objEmpty->next()) {
                $strKey = $this->getKeyname($objEmpty->{$this->strKey});
                if (!$strKey) {
                    continue;
                }

                $objCacheInvisible = $this->objCache->getItem('invisible_' . $strKey);
                if (!$objCacheInvisible->isHit()) {
                    $objCacheInvisible->set(true);
                    $this->objCache->saveDeferred($objCacheInvisible);
                }
            }
        }

        $this->objCache->commit();
    }

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

    abstract protected function setModelOptions();

    public function get($strKey, $strFallback = '')
    {
        $strKey = $this->getKeyname($strKey);
        if (!$strKey) {
            return $strFallback;
        }

        $objCacheResult = $this->objCache->getItem($strKey);

        if ($objCacheResult->isHit()) {
            return $objCacheResult->get();
        }

        $container = System::getContainer();
        if (self::$objScopeMatcher === null) {
            self::$objScopeMatcher = $container->get('contao.routing.scope_matcher');
        }

        $request = $container->get('request_stack')->getCurrentRequest() ?? Request::create('');

        if (!self::$objScopeMatcher->isFrontendRequest($request) || !$strFallback) {
            return $strFallback;
        }

        $objCacheInvisibleResult = $this->objCache->getItem('invisible_' . $strKey);
        if ($objCacheInvisibleResult->isHit() && $objCacheInvisibleResult->get()) {
            return $strFallback;
        }

        $objTranslation = TranslationModel::findOneBy('name', $strKey);

        if ($objTranslation) {
            return $strFallback;
        }

        try {
            $objTranslation = new TranslationModel();
            $objTranslation->tstamp = time();
            $objTranslation->invisible = '1';
            $objTranslation->name = substr($strKey, 0, 255);
            $objTranslation->translation = $strFallback;
            $objTranslation->save();
        } catch (\Exception $exception) {
        }

        return $strFallback;
    }

    protected function getKeyname($strName): string
    {
        return str_replace(["{", "}", "(", ")", "/", "\\", "@", ':', ' '], '', $name ?? $strName);
    }
}