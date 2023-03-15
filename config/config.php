<?php

return [
    'env' => env('APP_ENV', 'dev'),
    'civ' => ['Mme/M.' => 'Mme/M.', 'Mme' => 'Mme', 'M.' => 'M.', 'Dr' => 'Dr', 'Pr' => 'Pr'],
    'basic_state' => ['Brouillon' => 'Brouillon', 'Désactivé' => 'Désactivé', 'Actif' => 'Actif'],
    'btns' => [
        'duplicate' => [
            'label' => 'waka.utils::lang.duplicateBehavior.title',
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
            'exact' => "Exacte",
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
        'tbs' => "[%s]",
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
            'reTwig' => [
                'word' => 'INTERDIT',
                'twig' => "{{%s|reTwig(row,ds)}}",
            ],
        ],
        'add' => [
            'twig' => [
                'Si la fonction a des données' => "{%% if fncs.%s.show %%}",
                'Titre de la fonction' => "{%% fncs.%s.title %%}",
                'Déclaration de la boucle' => "{%%for row in fncs.%s.datas %%}",
                'Exemple' => "<li>{{row.name}}</li>",
                'Fin déclaration de la boucle' => "{%%for row in fncs.%s.datas %%}",
                'Fin si fonction à des données' => "{%% endif %%}",
            ],
            'word' => [
                'Si la fonction a des données' => '${IS_FNC.%s}',
                'Titre de la fonction' => '${FNC_M.%s.title}',
                'Déclaration de la boucle' => '${FNC.%s}',
                'Exemple' => '${%s.name}',
                'Fin déclaration de la boucle' => '${/FNC.%s}',
                'Fin si fonction à des données' => '${/IS_FNC.%s}',
            ],
            'tbs' => [],
        ],

    ],
    'froala' => [
        'html_toolbar_buttons' => [
            'full' => 'undo,redo,bold,italic,underline,paragraphFormat,paragraphStyle,inlineStyle,strikeThrough,subscript,superscript,clearFormatting,fontFamily,fontSize,color,emoticons,-,selectAll,align,formatOL,formatUL,outdent,indent,quote,insertHR,insertLink,insertImage,insertVideo,insertAudio,insertFile,insertTable,selectAll,html,fullscreen',
            'minimal' => 'undo,redo,bold,italic,underline,strikeThrough,subscript,superscript,',
            'minimalist' => 'bold,italic',
            'default' => 'undo,redo,bold,italic,underline,subscript,superscript,paragraphFormat,clearFormatting,color,selectAll,align,formatOL,formatUL,outdent,indent,quote,insertHR,html,fullscreen',
            'insert' => 'undo,redo,bold,italic,underline,subscript,superscript,paragraphFormat,clearFormatting,color,selectAll,align,formatOL,formatUL,outdent,indent,quote,insertHR,insertLink,insertImage,insertTable,html,fullscreen',
        ],
        "html_style_image" => [],
        "html_style_link" => [
            "text-primary" => "Couleur primaire",
            "text-secondary" => "Couleur secondaire"
        ],
        "html_style_paragraph" => [
            "text-white bg-primary" => "Primaire",
            "text-white bg-secondary" => "Secondaire"
        ],
        "html_style_table" => [
            "oc-dashed-borders" => "Dashed Borders",
            "oc-alternate-rows" => "Alternate Rows"
        ],
        "html_style_table_cell" => [
            "oc-cell-highlighted" => "Highlighted",
            "oc-cell-thick-border" => "Thick Border"
        ],
        "html_paragraph_formats" => [
            "N" => "Normal",
            "H1" => "H1",
            "H2" => "H2",
            "H3" => "H3",
            "H4" => "H4",
            "PRE" => "Code"
        ],
        "html_allow_empty_tags" => "textarea, a, iframe, object, video, style, script, .fa, .fr-emoticon, .fr-inner, path, line, hr, i",
        "html_allow_tags" => "a, abbr, address, area, article, aside, audio, b, bdi, bdo, blockquote, br, button, canvas, caption, cite, code, col, colgroup, datalist, dd, del, details, dfn, dialog, div, dl, dt, em, embed, fieldset, figcaption, figure, footer, form, h1, h2, h3, h4, h5, h6, header, hgroup, hr, i, iframe, img, input, ins, kbd, keygen, label, legend, li, link, main, map, mark, menu, menuitem, meter, nav, noscript, object, ol, optgroup, option, output, p, param, pre, progress, queue, rp, rt, ruby, s, samp, script, style, section, select, small, source, span, strike, strong, sub, summary, sup, table, tbody, td, textarea, tfoot, th, thead, time, title, tr, track, u, ul, var, video, wbr",
        "html_allow_attributes" => "accept, accept-charset, accesskey, action, align, allowfullscreen, allowtransparency, alt, aria-.*, async, autocomplete, autofocus, autoplay, autosave, background, bgcolor, border, charset, cellpadding, cellspacing, checked, cite, class, color, cols, colspan, content, contenteditable, contextmenu, controls, coords, data, data-.*, datetime, default, defer, dir, dirname, disabled, download, draggable, dropzone, enctype, for, form, formaction, frameborder, headers, height, hidden, high, href, hreflang, http-equiv, icon, id, ismap, itemprop, keytype, kind, label, lang, language, list, loop, low, max, maxlength, media, method, min, mozallowfullscreen, multiple, muted, name, novalidate, open, optimum, pattern, ping, placeholder, playsinline, poster, preload, pubdate, radiogroup, readonly, rel, required, reversed, rows, rowspan, sandbox, scope, scoped, scrolling, seamless, selected, shape, size, sizes, span, src, srcdoc, srclang, srcset, start, step, summary, spellcheck, style, tabindex, target, title, type, translate, usemap, value, valign, webkitallowfullscreen, width, wrap",
        "html_no_wrap_tags" => "figure, script, style",
        "html_remove_tags" => "script, style, base",
        "html_line_breaker_tags" => "figure, table, hr, iframe, form, dl"
    ]
];
