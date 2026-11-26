<?php

namespace Alnv\ContaoTranslationManagerBundle\DependencyInjection;

use Alnv\ContaoTranslationManagerBundle\Library\CacheResolver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AlnvContaoTranslationManagerExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter(CacheResolver::PARAM_PLACEHOLDERS_ENABLED, $config['placeholders']['enabled']);
        $container->setParameter(CacheResolver::PARAM_PLACEHOLDERS_EXCLUDE, $config['placeholders']['exclude']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}