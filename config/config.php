<?php

return [
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

];
