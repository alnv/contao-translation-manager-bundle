<?php

$GLOBALS['TL_DCA']['tl_translation'] = [

    'config' => [

        'dataContainer' => 'Table',

        'sql' => [

            'keys' => [

                'id' => [

                    'id' => 'primary',
                    'language' => 'index'
                ]
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

            'fields' => [ 'name' ]
        ],

        'operations' => [

            'edit' => [

                'label' => &$GLOBALS['TL_LANG']['tl_translation']['edit'],
                'href' => 'act=edit',
                'icon' => 'header.gif'
            ],

            'copy' => [

                'label' => &$GLOBALS['TL_LANG']['tl_translation']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif'
            ],

            'delete' => [

                'label' => &$GLOBALS['TL_LANG']['tl_translation']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],

            'show' => [

                'label' => &$GLOBALS['TL_LANG']['tl_translation']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif'
            ]
        ],

        'global_operations' => [

            'all' => [

                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ],

    'palettes' => [
        '__selector__' => [],
        'default' => 'language,name,translation',
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

            'label' =>  &$GLOBALS['TL_LANG']['tl_translation']['language'],
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
            'exclude' => true,
            'filter' => true,
            'sql' => ['type' => 'string', 'length' => 5, 'default' => '']
        ],

        'name' => [

            'label' =>  &$GLOBALS['TL_LANG']['tl_translation']['name'],
            'inputType' => 'text',
            'eval' => [

                'maxlength' => 64,
                'mandatory' => true,
                'tl_class' => 'w50'
            ],
            'search' => true,
            'exclude' => true,
            'sql' => ['type' => 'string', 'length' => 64, 'default' => '']
        ],

        'translation' => [

            'label' =>  &$GLOBALS['TL_LANG']['tl_translation']['translation'],
            'inputType' => 'textarea',
            'eval' => [

                'tl_class' => 'clr'
            ],
            'search' => true,
            'exclude' => true,
            'sql' => [ 'type' => 'text', 'notnull' => false ]
        ],
    ]
];