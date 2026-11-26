<?php

namespace Alnv\ContaoTranslationManagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * alnv_contao_translation_manager:
 *     placeholders:
 *         enabled: true            # false: im Frontend nie Platzhalter für unbekannte Schlüssel anlegen
 *         exclude:                 # Regex auf den (normalisierten) Schlüssel: dafür keine Platzhalter
 *             - '/\.\d+$/'         # z. B. Optionswerte mit numerischer ID am Ende
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('alnv_contao_translation_manager');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('placeholders')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Legt im Frontend für unbekannte Schlüssel mit Fallback einen deaktivierten Datensatz an.')
                            ->defaultTrue()
                        ->end()
                        ->arrayNode('exclude')
                            ->info('Reguläre Ausdrücke (PCRE mit Begrenzern); passende Schlüssel bekommen keinen Platzhalter.')
                            ->scalarPrototype()
                                ->validate()
                                    ->ifTrue(static fn ($v): bool => !\is_string($v) || @\preg_match($v, '') === false)
                                    ->thenInvalid('Ungültiger regulärer Ausdruck %s')
                                ->end()
                            ->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
