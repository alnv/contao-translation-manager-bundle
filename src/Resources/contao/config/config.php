<?php

use Contao\ArrayUtil;
use Alnv\ContaoTranslationManagerBundle\Models\TranslationModel;
use Alnv\ContaoTranslationManagerBundle\Library\Inserttag;

ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['catalog-manager-bundle'], 3, [
    'translation-manager' => [
        'name' => 'translation-manager-bundle',
        'tables' => [
            'tl_translation'
        ]
    ]
]);

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [Inserttag::class, 'replace'];
$GLOBALS['TL_MODELS']['tl_translation'] = TranslationModel::class;