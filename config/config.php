<?php

return [
    'civ' => ['Mme/M.' => 'Mme/M.', 'Mme' => 'Mme', 'M.' => 'M.', 'Dr' => 'Dr', 'Pr' => 'Pr'],
    'basic_state' => ['Brouillon' =>'Brouillon','Désactivé' => 'Désactivé','Actif' =>'Actif'],
    'btns' => [
        'duplicate' => [
            'label' => 'Duplicate',
            'ajaxCaller' => 'onLoadDuplicateForm',
            'ajaxInlineCaller' => 'onLoadDuplicateContentForm',
            'icon' => 'oc-icon-files-o',
        ],
         'lot_fnc' => [
            'label' => 'Fonctions par lot',
            'class' => 'btn-secondary',
            'ajaxInlineCaller' => 'onExecuteLotFnc',
            'icon' => 'oc-icon-calculator',
        ],
    ],
    'ImageOptions' => [
        'width' => [
            'label' => "Largeur",
            'type' => "text",
            'span' => 'left',
        ],
        'height' => [
            'label' => "hauteur",
            'type' => "text",
            'span' => 'right',
        ],
    ],
    'image' => [
        'baseCrop' => [
            'exact' =>"Exacte",
            'portrait' => "Portrait",
            'landscape' => "Paysage",
            'auto' => "automatique",
            'fit' => 'Tenir',
            'crop' => "Couper",
        ]
    ],
    'scopesType' => [
        'model_value' => [
            'label' => "Restriction depuis une valeur d'un champ",
            'config' => 'scope_value',
        ],

        'model_values' => [
            'label' => "Restriction sur plusieurs valeurs d'un champ",
            'config' => 'scope_values',
        ],
        'model_relation' => [
            'label' => "Restriction en fonction d'une relation",
            'config' => 'scope_relation',
        ],
        'model_bool' => [
            'label' => "Restriction Vrai/Faux d'un champ",
            'config' => 'scope_bool',
        ],
        'user' => [
            'label' => "Restriction lié à l'utilisateur",
            'config' => 'scope_user',
        ],
        'user_role' => [
            'label' => "Restriction lié aux groupes d'utilisateurs",
            'config' => 'scope_user_role',
        ],
    ],
    'transformers' => [
        'word' => "$" . "{%s}",
        'twig' => "{{ %s }}",
        'types' => [
            'date' => [
                'word' => '${%s*date}',
                'twig' => "{{%s | localeDate('date')}}",
            ],
            'date-tiny' => [
                'word' => '${%s*date-tiny}',
                'twig' => "{{%s | localeDate('date-tiny')}}",
            ],
            'date-short' => [
                'word' => '${%s*date-short}',
                'twig' => "{{%s|localeDate('date-short')}}",
            ],
            'date-medium' => [
                'word' => '${%s*date-medium}',
                'twig' => "{{%s | localeDate('date-medium')}}",
            ],
            'date-full' => [
                'word' => '${%s*date-full}',
                'twig' => "{{%s | localeDate('date-full')}}",
            ],
            'date-time-full' => [
                'word' => '${%s*date-time-full}',
                'twig' => "{{%s | localeDate('date-time-full')}}",
            ],
            'date-time' => [
                'word' => '${%s*date-time}',
                'twig' => "{{%s|localeDate('date-time')}}",
            ],
            'float' => [
                'word' => '${%s*float}',
                'twig' => "{{%s|number_format(2,',',' ')}}",
            ],
            'int' => [
                'word' => '${%s*number}',
                'twig' => "{{%s|number_format(0,',',' ')}}",
            ],
            'euro' => [
                'word' => '${%s*euro}',
                'twig' => "{{%s|number_format(2,',',' ')}} €",
            ],
            'euro-int' => [
                'word' => '${%s*number}',
                'twig' => "{{%s|number_format(0,',',' ')}} €",
            ],
            'switch' => [
                'word' => '${%s*switch}',
                'twig' => "{{%s ? 'Oui' : 'Non'}}",
            ],
            'image' => [
                'word' => '${%s*IMG}',
                'twig' => "{{%s.path}}",
            ],
            'modelImage' => [
                'word' => '${IMG.%s}',
                'twig' => "{{IMG.%s.path}}",
            ],
            'htm' => [
                'word' => '${%s*HTM}',
                'twig' => "{{%s|raw}}",
            ],
            'md' => [
                'word' => '${%s*MD}',
                'twig' => "{{%s|md_safe}}",
            ],
            'txt' => [
                'word' => '${%s*TXT}',
                'twig' => "{{%s|striptags('<br><p>') }}",
            ],
            'nl2br' => [
                'word' => '${%s}',
                'twig' => "{{%s|nl2br}}",
            ],
        ],

    ],
];
