<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

abstract class CacheResolver {

    protected $strKey;
    protected $strTable;
    protected $strValue;
    protected $strLanguage;
    protected $objCache;

    public function __construct($strLanguage='') {

        $this->objCache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter('cm.translation.cache', 60, TL_ROOT . '/var/cache');

        if (!$strLanguage) {
            $strLanguage = $GLOBALS['TL_LANGUAGE'] ?: \System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
        }

        $this->strLanguage = $strLanguage;
        $this->setDataIntoCache();
    }

    protected function setDataIntoCache() {

        $objEntities = $this->getEntities();

        if ($objEntities) {
            while ($objEntities->next()) {
                $strKey = $objEntities->{$this->strKey};
                $strValue = \StringUtil::decodeEntities($objEntities->{$this->strValue});
                $objCacheEntity = $this->objCache->getItem($strKey);

                if ($strKey && !$objCacheEntity->isHit()) {
                    $objCacheEntity->set($strValue);
                    $this->objCache->save($objCacheEntity);
                }
            }
        }

        $objEmpty = \Database::getInstance()->prepare('SELECT * FROM ' . $this->strTable . ' WHERE invisible=?')->execute(1);

        while ($objEmpty->next()) {
            $objCacheInvisible = $this->objCache->getItem('invisible_' . $objEmpty->{$this->strKey});
            if (!$objCacheInvisible->isHit()) {
                $objCacheInvisible->set(true);
                $this->objCache->save($objCacheInvisible);
            }
        }
    }

    protected function getEntities() {

        $strModel = \Model::getClassFromTable($this->strTable);

        if ($strModel) {

            $objModel = new $strModel();
            return $objModel->findAll($this->setModelOptions());
        }

        if (in_array('AlnvContaoCatalogManagerBundle',  array_keys(\System::getContainer()->getParameter('kernel.bundles')))) {

            $objModel = new \Alnv\ContaoCatalogManagerBundle\Helper\ModelWizard($this->strTable);
            $objModel = $objModel->getModel();

            return $objModel->findAll($this->setModelOptions());
        }

        return null;
    }

    abstract protected function setModelOptions();

    public function get($strKey, $strFallback='') {

        $objCacheResult = $this->objCache->getItem($strKey);

        if ($objCacheResult->isHit()) {
            return $objCacheResult->get();
        }

        if (TL_MODE != 'FE' || !$strFallback) {
            return $strFallback;
        }

        $objCacheInvisibleResult = $this->objCache->getItem('invisible_' . $strKey);
        if ($objCacheInvisibleResult->get()) {
            return $strFallback;
        }

        $objTranslation = \Alnv\ContaoTranslationManagerBundle\Models\TranslationModel::findOneBy('name', $strKey);
        if ($objTranslation) {
            return $strFallback;
        }

        try {
            $objTranslation = new \Alnv\ContaoTranslationManagerBundle\Models\TranslationModel();
            $objTranslation->tstamp = time();
            $objTranslation->invisible = '1';
            $objTranslation->name = $strKey;
            $objTranslation->translation = $strFallback;
            $objTranslation->save();
        } catch (\ErrorException $exception) {}

        return $strFallback;

        /*
        if (!\Cache::has($strKey)) {

            if (TL_MODE != 'FE' || !$strFallback) {
                return $strFallback;
            }

            if (\Cache::get('invisible_' . $strKey)) {
                return $strFallback;
            }

            $objTranslation = \Alnv\ContaoTranslationManagerBundle\Models\TranslationModel::findOneBy('name', $strKey);

            if ($objTranslation) {
                return $strFallback;
            }

            try {
                $objTranslation = new \Alnv\ContaoTranslationManagerBundle\Models\TranslationModel();
                $objTranslation->tstamp = time();
                $objTranslation->invisible = '1';
                $objTranslation->name = $strKey;
                $objTranslation->translation = $strFallback;
                $objTranslation->save();
            } catch (\ErrorException $exception) {}

            return $strFallback;
        }

        return \Cache::get($strKey);
        */
    }

}