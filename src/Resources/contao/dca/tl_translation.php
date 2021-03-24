<?php

$GLOBALS['TL_DCA']['tl_translation'] = [
    'config' => [
        'dataContainer' => 'Table',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'name,language' => 'index'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 2,
            'flag' => 4,
            'fields' => [ 'language' ],
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => [ 'name', 'translation' ],
            'showColumns' => true
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.gif'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.gif'
            ]
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => 'language,name,translation,invisible',
    ],
    'subpalettes' => [],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true ]
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'language' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
                'mandatory' => true,
                'blankOptionLabel' => '-',
                'includeBlankOption' => true
            ],
            'length' => 32,
            'flag' => 32,
            'options' => \System::getLanguages(),
            'filter' => true,
            'sql' => ['type' => 'string', 'length' => 5, 'default' => '']
        ],
        'name' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 64,
                'mandatory' => true,
                'tl_class' => 'w50'
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],
        'translation' => [
            'inputType' => 'textarea',
            'eval' => [
                'tl_class' => 'clr',
                'allowHtml' => true
            ],
            'search' => true,
            'sql' => ['type' => 'text', 'notnull' => false]
        ],
        'invisible' => [
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'clr',
                'multiple' => false
            ],
            'filter' => true,
            'sql' => ['type'=>'string', 'fixed'=>true, 'length' => '1', 'notnull'=>false]
        ]
    ]
];