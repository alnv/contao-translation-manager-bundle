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

    protected $strKey;
    protected $strTable;
    protected $strValue;
    protected $strLanguage;
    protected $objCache;

    public function __construct($strLanguage = '')
    {

        if (!$strLanguage) {
            $strLanguage = $GLOBALS['TL_LANGUAGE'] ?: System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
        }

        $this->objCache = new FilesystemAdapter('cm.translation.cache.' . $strLanguage, 60, TL_ROOT . '/var/cache');

        $this->strLanguage = $strLanguage;

        $this->setDataIntoCache();
    }

    protected function setDataIntoCache()
    {

        $objEntities = $this->getEntities();

        if ($objEntities) {
            while ($objEntities->next()) {
                if ($this->strLanguage != $objEntities->language) {
                    continue;
                }
                $strKey = $this->getKeyname($objEntities->{$this->strKey});
                $strValue = StringUtil::decodeEntities($objEntities->{$this->strValue});
                $objCacheEntity = $this->objCache->getItem($strKey);

                if ($strKey && !$objCacheEntity->isHit()) {
                    $objCacheEntity->set($strValue);
                    $this->objCache->save($objCacheEntity);
                }
            }
        }

        $objEmpty = Database::getInstance()->prepare('SELECT * FROM ' . $this->strTable . ' WHERE invisible=?')->execute(1);

        while ($objEmpty->next()) {

            $objCacheInvisible = $this->objCache->getItem('invisible_' . $this->getKeyname($objEmpty->{$this->strKey}));
            if (!$objCacheInvisible->isHit()) {
                $objCacheInvisible->set(true);
                $this->objCache->save($objCacheInvisible);
            }
        }
    }

    protected function getEntities()
    {

        $strModel = Model::getClassFromTable($this->strTable);
        if ($strModel) {
            $objModel = new $strModel();
            return $objModel->findAll($this->setModelOptions());
        }

        if (in_array('AlnvContaoCatalogManagerBundle', array_keys(System::getContainer()->getParameter('kernel.bundles')))) {
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
        $objCacheResult = $this->objCache->getItem($strKey);

        if ($objCacheResult->isHit()) {
            return $objCacheResult->get();
        }

        if (!System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')) || !$strFallback) {
            return $strFallback;
        }

        $objCacheInvisibleResult = $this->objCache->getItem('invisible_' . $strKey);
        if ($objCacheInvisibleResult->get()) {
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
            $objTranslation->name = $strKey;
            $objTranslation->translation = $strFallback;
            $objTranslation->save();
        } catch (\ErrorException $exception) {
        }

        return $strFallback;
    }

    protected function getKeyname($strName)
    {

        return str_replace(["{", "}", "(", ")", "/", "\\", "@", ':'], '', $strName);
    }
}