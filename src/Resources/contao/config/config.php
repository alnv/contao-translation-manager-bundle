<?php

use Contao\ArrayUtil;
use Alnv\ContaoTranslationManagerBundle\Models\TranslationModel;
use Alnv\ContaoTranslationManagerBundle\Library\Inserttag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;

ArrayUtil::arrayInsert($GLOBALS['BE_MOD']['catalog-manager-bundle'], 3, [
    'translation-manager' => [
        'name' => 'translation-manager-bundle',
        'tables' => [
            'tl_translation'
        ]
    ]
]);

if (!class_exists(AsInsertTag::class)) {
    $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [Inserttag::class, 'replace'];
}

$GLOBALS['TL_MODELS']['tl_translation'] = TranslationModel::class;