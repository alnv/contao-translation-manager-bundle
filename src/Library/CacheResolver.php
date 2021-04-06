<?php

namespace Alnv\ContaoTranslationManagerBundle\Library;

abstract class CacheResolver {

    protected $strKey;
    protected $strTable;
    protected $strValue;
    protected $strLanguage;

    public function __construct($strLanguage='') {

        if (!$strLanguage) {
            $strLanguage = $GLOBALS['TL_LANGUAGE'] ?: \System::getContainer()->get('request_stack')->getCurrentRequest()->getLocale();
        }

        $this->strLanguage = $strLanguage;
        $this->setDataIntoCache();
    }

    protected function setDataIntoCache() {

        $objEntities = $this->getEntities();

        if ($objEntities == null) {
            return null;
        }

        while ($objEntities->next()) {
            $strKey = $objEntities->{$this->strKey};
            $strValue = \StringUtil::decodeEntities($objEntities->{$this->strValue});
            if ($strKey && !\Cache::has($strKey)) {
                \Cache::set($strKey, $strValue);
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

    public function get($strKey, $strFallback) {

        if (!\Cache::has($strKey)) {
            if (TL_MODE != 'FE' || !$strFallback) {
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
    }

}