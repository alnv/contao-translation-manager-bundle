<?php

namespace Alnv\ContaoTranslationManagerBundle\ContaoManager;

use Alnv\ContaoTranslationManagerBundle\AlnvContaoTranslationManagerBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{

    public function getBundles(ParserInterface $parser)
    {

        return [
            BundleConfig::create(AlnvContaoTranslationManagerBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['contao-translation-manager-bundle']),
        ];
    }
}