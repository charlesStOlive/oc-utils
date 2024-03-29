<?php

return [
    'menu' => [
        'data_sources' => 'Sources des données',
        'data_sources_description' => 'Gérer les sources et leur relation pour publisher, mailing, etc.',
        'settings_category' => 'Configuration ',
        'settings_category_model' => 'Gestion des Modèles',
        'job_list' => "Liste des taches",
        'job_list_s' => "Taches",
        'job_list_description' => "Liste des taches de l'application",
    ],
    'popup' => [
        'traitement_lots' => 'Traitement par lots',
        'all' => "Tous",
        'filtered' => "Lignes Filtrées",
        'checked' => "Lignes Cochées",
        "change_field" => "Modifier le champ",
        'export_lot' => 'Produire un lot',
        'choose_who' => 'Pour qui voulez vous créer'
    ],
    'import' => [
        "importExcel" => "Importer depuis excel",
        "importExcel_comment" => "Import les élements selectionnez ci dessus. Veuillez enregistrer vos modifications avant import",
        "button_import" => "Importer",
        "import_indicator" => "Import en cours",
    ],
    'global' => [
        'details' => 'Détails',
        'slug' => 'Slug',
        'updated_at' => 'MAJ',
        'created_at' => 'Crée le',
        'placeholder' => '-- Choisissez --',
        'placeholder_model' => '-- Choisissez un modèle --',
        'placeholder_w' => "-- Changez d'état--",
        'placeholder_contact' => '--Choisissez un contact--',
        'placeholder_client' => '--Choisissez un client--',
        'sort_order' => 'Ordre',
        'return' => 'Retour',
        'delete_selected' => 'Supprimer la sélection',
        'cancel' => 'Abandonner',
        'save' => 'Sauver',
        'close' => 'Fermer',
        'show' => 'Voir',
        'lot' => 'Lot',
        'test' => 'Tester',
        'save_indicator' => "Sauvegarde en cours",
        'delete' => 'Supprimer',
        'delete_indicator' => "Effacement en cours",
        'delete_confirm' => "Voulez vous vraiment supprimer la selection ?",
        "creating_indicator" => "Création en cours",
        'save_close' => 'Sauver & fermer',
        'create' => 'Créer',
        'create_close' => 'Créer & fermer',
        'termined' => 'Terminer',
        'show_in_navigator' => "Afficher dans le navigateur",
        'update' => 'Mettre à jour',
        'validate' => 'Valider',
        'reorder' => 'Réordonner',
        'or' => 'ou',
        'action' => 'Action',
        'open' => 'Ouvrir',
        'icon' => 'Icône',
        'placeholder_icon' => '--Choisissez une icône--',
        'saving' => 'Sauvegarde',
        'edit' => 'Éditer',
        'code' => 'Code',
        'code_identification' => "Code d'identification",
        'test' => 'Tester',
        'unkown' => 'Inconnu',
        'confirm_delete' => 'Confirmez-vous la suppression. Attention ! Cette action est irréversible',
        'save_indicator' => 'Sauvegarde en cours',
        'state' => "Etat",
        'test' => 'test',
        'is_ex' => 'Exemple',
        "prompt_record" => "Cliquez sur l'icône pour choisir un enregistrement",
        "find_record" => "Trouver un enregistrement",
        "settings" => "Réglages",
        "btn_add_row" => "Ajouter une ligne",
        "btn_delete_row" => "Supprimer une ligne",
        "no_productor" => "Aucun modèle disponible",
        "no_productor_com" => "Soit aucun modèle n'est configuré pour ce levier soit des restrictions bloquent le modèles.",

    ],
    'datasource' => [
        'title' => 'Source de données',
        'placeholder' => 'Choisissez une source',
        'tab_path' => "Chemins des Classes",
        'tab_contact' => "Liaison contact",
        'tab_relation' => "Relations",
        'name' => 'Intitulé de la source',
        'title' => 'Choisissez une source',
        'placeholder' => '--Choisissez une source--',
        'author' => 'Auteur du plugin',
        'plugin' => 'Nom du plugin',
        'model' => 'Nom du modèle',
        'section_controller' => 'Gestion des données',
        'controller' => 'Nom du contrôleur',
        'specific_list' => "Adresse spécifique de liste",
        'specific_update' => "Adresse spécifique d'édition",
        'specific_create' => "Adresse spécifique de création",
        'section_relation' => 'Gestion des relations',
        'relations' => 'Liste des relations à utiliser',
        'relations_prompt' => 'ajouter une relation',
        'relation_name' => 'Nom de la relation',
        'attributes' => 'Liste des attributs à utiliser',
        'attributes_pompt' => 'Ajouter une relation',
        'attribute_name' => "Nom de l'attribut",
        'test_id' => "Model d'exemple",
        'test_id_prompt' => "--Choisissez un modèle d'exemple--",
        'sector_access' => "Accès relation secteur",
        'param' => "Nom du Paramètre",
        'param_com' => "Paramètre qui fera transiter la clé",
        'key' => "key",
        'key_com' => "Modifier la clé si id n'existe pas. NE FONCTIONNE PAS ENCORE",
        'relation_collection_name' => "Nom de la relation collection",
        'section_contact' => "Gestion des relations pour accéder aux contacts email",
        'contacts' => "Configuration yaml des contacts",
        'model_scopes' => 'Class Scope du modèle',
        'has_image' => 'Prendre les images',
        'function_class' => "Class fonctions d'éditions",
        'agg_class' => "Class d'agrégation",
        "name_from" => "Nom à utiliser si 'name' n'existe pas",
        'inde_class_list' => [
            'label' => 'Class indépendante à lier',
            'prompt' => 'Entrez les classes indépendantes',
            'name' => 'Nom de la class ( sera utilisé notamment dans Word)',
            'class' => 'Nom de la class indépendante',
            'ids' => 'list des ID, si vide le premier sera pris',
        ],

    ],
    'job_list' => [
        'name' => "Nom de la tache",
        'started_at' => "Commencé à",
        'created_at' => "Crée à",
        'end_at' => "Terminé à",
        'state' => "Etat",
        'date_diff' => "Durée en S",
        "user_name" => "Utilisateur",
        "scopes" => [
            "not_end" => "Ne pas afficher les taches terminées",
            "only_user" => "Seulement vos taches",

        ],
    ],
    'scopes' => [
        "libelle" => "Intitulé de la restriction",
        "is_scope" => "Restriction ? ",
        "self" => "Fonction de restriction lié à ce modèle",
        "target" => "Nom de la relation portant la restriction",
        "field" => "Nom du champ",
        "field_com" => "Nom de la colonne qui portera la valeur",
        "target_com" => "Écrire le nom de la relation. les relations parentes ne sont pas disponibles",
        "scope_field" => "Nom du champ",
        "scope_value" => "Valeur unique du champ",
        "scope_values" => "Lister les valeurs",
        "scope_values_com" => "Saisissez une valeur et cliquez",
        "scope_bool" => "Vrai Faux",
        "type" => "Type de restriction",
        "scope_relation" => "Choisir la relation",
        "scope_relation_com" => "Si vous recherchez chez un parent vous devez indiquer la relation avec le parent",
        "userRoles" => "Rôle des utilisateurs",
        'users' => 'Utilisateurs',
    ],
    'settings' => [
        "activate_dashboard" => "Activer le bouton du Dashboard",
        "activate_user_btn" => "Activer le bouton des utilisateurs",
        "activate_cms" => "Activer le bouton du CMS",
        "activate_builder" => "Activer le bouton du Builder",
        "activate_task_btn" => "Activer le bouton dynamique de taches",
        "activate_media_btn" => "Activer le bouton des média",
        "label" => "Utilitaires",
        "description" => "Cachez des éléments",

    ],
    'side_bar_info' => [
        'state_history' => "Historique des transitions d'états",
        'update' => "Mettre à jours",
    ],
    'workflow' => [
        'no_state_change' => "Aucune transitions",
        'btn_workflow' => 'Sauver et ...',
        "state_change_forbidden" => "Modifications et transitions interdites",
        "state" => "Etat",
        "must_trans" => "Vous devez obligatoiremment utiliser une transition",
        "change_state" => "Changer d'état",
        "error_place" => "erreur d'état",
        'popup' => [
            'confirm_title' => "Confirmation",
            'transition' => "Transition : ",
            'next_place_label' => "Prochain état : ",
            'save_no_transition' => "Votre modèle sera sauvé sans appliquer de transition.",
        ],
    ],
    'prod' => [
        'production' => "Production",
        'produce' => "Produire",
        'send' => 'Envoyer',
        'tools' => 'Outils',
    ],
    'duplicateBehavior' => [
        'title' => "Dupliquer",

    ],
    'page' => [
        'access_denied' => [
            'label' => 'Accès impossible',
        ],
    ],

];
