<?php

define( "CATALOG_TRANSLATION_BUNDLE_VERSION", "1.0" );

array_insert( $GLOBALS['BE_MOD']['system'], 3, [
    'translation-manager' => [
        'name' => 'translation-manager-bundle',
        'tables' => [
            'tl_translation'
        ]
    ]
]);

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoTranslationManagerBundle\Library\Inserttag', 'replace'];
$GLOBALS['TL_MODELS']['tl_translation'] = 'Alnv\ContaoTranslationManagerBundle\Models\TranslationModel';