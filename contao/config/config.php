<?php

use Contao\ArrayUtil;

Contao\ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['catalog-manager-bundle'], 3, [
    'translation-manager' => [
        'name' => 'translation-manager-bundle',
        'tables' => [
            'tl_translation'
        ]
    ]
]);

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Alnv\ContaoTranslationManagerBundle\Library\Inserttag', 'replace'];
$GLOBALS['TL_MODELS']['tl_translation'] = 'Alnv\ContaoTranslationManagerBundle\Models\TranslationModel';